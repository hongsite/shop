<?php
/*
* @author 牛头
* @function 首页
* @date 2022/03/14
* @version v1.0.0
*/
namespace Hst\Upload;
use Hst\Common;
class Upload extends Common{
	public function _init() {
        parent::_init();		
    }
	public function upload_json(){
		$ischeck = I('ischeck',1,'post');
		$timestamp = I('timestamp',1,'post');
		$sig = I('sig',0,'post');
		$subpath = I('path',0,'post');
		$iszip = I('iszip',1,'post');
		$issfz = I('issfz',1,'post');
		$side = I('side',0,'post');
		$isbank = I('isbank',1,'post');
		$zipwidth = I('zipwidth',1,'post');
		$calfun = I('calfun',0,'post');
		$forceThumb = I('forceThumb',1,'post');
		$thumb_width = I('thumb_width',0,'post');
		$addtime = time();
		$js['status'] = 0;
		$js['msg'] = '';

		$allow_ext = array('jpeg', 'jpg', 'png', 'gif', 'bmp', 'doc', 'docx', 'pdf', 'xlsx', 'xls', 'ppt', 'pptx', 'txt','webp');
		$AUTHENTICATE = '6Q714mzm01dTDC1yDtVf4Qkcd';//跨站上传认证	

		$maxsize = 5*1024*1024;


		if(!in_array($subpath,array('pic1','des','anti'))){
			$js['msg'] = '不允许的上传目录';
			$this->json($js);
		}

		$path = '../static/upload/'.$subpath.'/';
		creatdir($path);

		
		$upload = D('UploadFile');// 实例化上传类
		$upload->maxSize  = $maxsize ;// 设置附件上传大小
		$upload->allowExts  = $allow_ext;
		$upload->savePath =  $path;

		$upload->forceThumb = $forceThumb;


		$upload->autoSub = true;
		$upload->subType = 'date';
		$upload->dateFormat = 'Ym';

		$thumb_height = $thumb_width;

		if($thumb_width!=''){
			$thumb_width_arr = explode(',',$thumb_width);
			$thumb_height_arr = explode(',',$thumb_width);
			foreach($thumb_width_arr as $key=>$v){
				$thumbSuffixarr[$key] = '_'.$v.'.jpg';
			}
			$thumbSuffix = implode(',',$thumbSuffixarr);
			$upload->thumb = true;
			$upload->thumbMaxWidth = $thumb_width; 
			$upload->thumbMaxHeight = $thumb_height;
			$upload->thumbPrefix    = '';   
			$upload->thumbSuffix    = $thumbSuffix;
		}

		if(!$upload->upload()) {// 上传错误提示错误信息
			$js['msg'] = $upload->getErrorMsg();
			$this->json($js);
		}

		//上传成功
		$info = $upload->getUploadFileInfo();
		$arr = $info[0] ?? array();

		$uid = I('uid',1,'post');
		$aid = I('aid',1,'post');
		$hid = I('hid',1,'post');
		$module_name = I('module_name',0,'post');

		$filename = $arr['savename'] ?? '';
		$size = $arr['size'] ?? 0;
		$oldfilename = $arr['name'] ?? '';
		$extension = $arr['extension'] ?? '';
		$ext = strtolower($extension);
		$savepath = $arr['savepath'] ?? '';
		$savepath =  str_replace('../','/',$savepath);

		$js['md5'] = MD5(MD5($AUTHENTICATE).$filename);

		$exts = '';
		$width = 0;
		$height = 0;


		$ispic = 0;
		if(in_array($ext,array('jpg','jpeg','png','bmp','gif','webp'))){
			$ispic = 1;
			list($width, $height, $exts) = getimagesize('../'.$savepath.$filename);
		}

		$js['filename'] = $filename;
		$js['addtime'] = $addtime;
		$js['oldfilename'] = $oldfilename;
		$js['size'] = $size;
		$js['width'] = $width;
		$js['height'] = $height;
		$js['ext'] = $ext;
		$js['exts'] = $exts;
		$js['ispic'] = $ispic;		

		$ip = getipint();

		$data = null;
		$data['filename'] = $filename;
		$data['ext'] = $ext;
		$data['size'] = $size;
		$data['oldfilename'] = $oldfilename;
		$data['module_name'] = $module_name;
		$data['aid'] = $aid;
		$data['addtime'] = $addtime;
		$data['ispic'] = $ispic;
		$data['ip'] = $ip;
		$data['uid'] = $uid;
		$data['hid'] = $hid;
		
		$rtcmd = M('Files')->add($data);

		if($rtcmd!==false){
			if($iszip==1 && $zipwidth>0 && in_array($ext,array('png','jpg','jpeg'))){
				zipimg($savepath.$$filename,$savepath.$$filename,$zipwidth);
			}

			$js['status'] = 1;
			$js['msg'] = '上传成功';
		}else{
			$js['msg'] = '上传失败';
		}

		$js['id'] = $rtcmd;
		
		$this->json($js);
	}
}