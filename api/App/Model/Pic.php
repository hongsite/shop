<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 图片处理
* @date 2020/7/15
* @version hst-v1.0.0
*/
namespace Models\Pic;
use Model\Model;
class Pic extends Model {
	function __construct(){
	}
	public function getewmzj($url=''){
		$qrfile = ROOT.'App/Model/phpqrcode.php';

		if(!is_file($qrfile)){
			echo '文件不存在';
			return ;
		}
		require $qrfile;

		$month = date('Ym');
		$fname = uniqid().'.png';
		$fullfname = ROOT.'static/qrcode/'.$fname;		
		$folder = dirname($fullfname);
		substr($folder,-1)!='/' && $folder .= '/';
		creatdir($folder);
		\QRcode::png($url,$fullfname,'L',400,6);
		return $fullfname;
	}
	public function getewm($arr){
		$js['status'] = 0;
		$js['msg'] = '';
		$url = isset($arr['url']) ? $arr['url']:'';
		$title = isset($arr['title']) ? $arr['title']:'';
		$fontsize = isset($arr['fontsize']) ? $arr['fontsize']:12;
		$posY = isset($arr['y']) ? $arr['y']:20;
		if($url==''){
			$js['msg'] = '地址错误';
			return $js;
		}

		$qrfile = ROOT.'App/Model/phpqrcode.php';
		if(!is_file($qrfile)){
			echo '文件不存在';
			return ;
		}
		include_once $qrfile;

		$month = date('Ym');
		$fname = uniqid().'.png';
		$fullfname = ROOT.'static/qrcode/'.$fname;		
		$folder = dirname($fullfname);
		substr($folder,-1)!='/' && $folder .= '/';
		creatdir($folder);
		\QRcode::png($url,$fullfname,'L',400,6);
		$this->resize($fullfname,400);
		$this->getQRcode($fullfname,$fontsize,$posY,$title);
		if(is_file($fullfname)){
		$js['data'] = $fullfname;
		$js['status'] = 1;
		$js['msg'] = '生成成功';
		}else{
			$js['msg'] = '生成失败';
		}
		return $js;
		//echo '<img src="/static/qrcode/'.$fname.'" style="width:400px;" />';
	}



	public function getQRcode($fname,$fontsize=12,$posY=40,$value=''){        
        // 先读取存好的二维码图片,再追加文字
        $image = imagecreatefrompng($fname);
        // 字体文件
        $font = ROOT.'static/font/wryh.ttf';

		$QR_width = imagesx($image); 

		$b = ImageTTFBBox($fontsize,0,$font,$value);
		$w = abs($b[2] - $b[0]);
		$h = abs($b[5] - $b[3]);

		$posX = ($QR_width-$w)/2;

        // 文字颜色
        $color = imagecolorallocate($image,0,0,0); 
        // 创建二维码图片下文字
        imagettftext($image, $fontsize, 0, $posX, $posY, $color, $font, $value);        
        // 保存新生成的二维码图片
        imagepng($image,$fname);
		imagedestroy($image);
    }
	
	public function addfont($arr=null){
		$fontsize = empty($arr['fontsize']) ? 12:$arr['fontsize'];
		$posX = empty($arr['x']) ? 20:$arr['x'];
		$poxY = empty($arr['y']) ? 20:$arr['y'];
		$source = empty($arr['source']) ? '':$arr['source'];
		
		$tosource = empty($arr['tosource']) ? $source:$arr['tosource'];		
		$angle = empty($arr['angle']) ? 0:$arr['angle'];
		$maxw = empty($arr['maxw']) ? 0:$arr['maxw'];
		$title = empty($arr['title']) ? '无':$arr['title'];
		$iscenter = empty($arr['iscenter']) ? 0:1;
		$isdel = empty($arr['isdel']) ? 0:1;
		$colorstr = empty($arr['color']) ? '255,255,255':$arr['color'];

		if(!is_file($source)){
			echo '图片不存在';
		}
		
		$QR_string = imagecreatefromstring(file_get_contents($source));    
		$QR_width = imagesx($QR_string);  
		$QR_height = imagesy($QR_string);

		$QR_string = $this->addfont_one($QR_string,$QR_width,$QR_height,$arr);	


		$this->savefile($QR_string,$tosource);
		
		//imagedestroy($QR_string);		

	}
	private function savefile($qrstring,$tosource=''){
		$ext = getext($tosource,'.');
		$ext = strtolower($ext);
		switch($ext){
			case 'gif' :
				imagegif($qrstring,$tosource);
				break;
			case 'jpg':
				imagejpeg($qrstring,$tosource);
				break;
			case 'jpeg':
				imagejpeg($qrstring,$tosource);
				break;
			case 'png':
				imagepng($qrstring,$tosource);
				break;
		}
	}
	public function addfont_one($QR_string,$QR_width,$QR_height,$arr){	
		$fontsize = empty($arr['fontsize']) ? 12:$arr['fontsize'];
		$posX = empty($arr['x']) ? 20:$arr['x'];
		$poxY = empty($arr['y']) ? 20:$arr['y'];	
		
		$angle = empty($arr['angle']) ? 0:$arr['angle'];
		$maxw = empty($arr['maxw']) ? 0:$arr['maxw'];
		$title = empty($arr['title']) ? '无':$arr['title'];
		$iscenter = empty($arr['iscenter']) ? 0:1;
		$isdel = empty($arr['isdel']) ? 0:1;
		$colorstr = empty($arr['color']) ? '255,255,255':$arr['color'];	
		
		

		$fonttype = empty($arr['fonttype']) ? ROOT.'static/font/wryh.ttf':ROOT.'static/font/'.$arr['fonttype'];	


		if(!file_exists($fonttype)){
			echo '字体不存在';
			return false;
		}
		$b = ImageTTFBBox($fontsize,0,$fonttype,$title);
		$w = abs($b[2] - $b[0]);
		$h = abs($b[5] - $b[3]);

		if($maxw>0){
			$cont = $this->autowrap($fontsize,$angle,$fonttype,$title,$maxw);
		}else{
			$cont = $title;
		}	

		if($iscenter==1){
			if($maxw>0){
				if($w>$maxw){
					$posX = ($QR_width-$maxw)/2;
				}else{
					$posX = ($QR_width-$w)/2;
				}
			}else{
				$posX = ($QR_width-$w)/2;
			}
		}

		$colorinfo = explode(',',$colorstr);		

		imagesavealpha($QR_string,true);//至关重要：保留原图alpha通道，否则png透明部分会被添黑
		
		$color = imagecolorallocate($QR_string,$colorinfo[0],$colorinfo[1],$colorinfo[2]);// 为一幅图像分配颜色 255,0,0表示红色
		$arrs = imagettftext($QR_string, $fontsize, $angle, $posX, $poxY, $color, $fonttype, $cont);

		if($isdel==1) imageline($QR_string,$arrs[0],$arrs[1]-($fontsize/2),$arrs[2],$arrs[3]-($fontsize/2),$color);
		
		//加下划线
		//$im = imagecreate($w,$h);
		//$white = imagecolorallocate($im,0xFF,0xFF,0xFF);
		//imagecolortransparent($im,$white);
		return $QR_string;
	}
	// 自动转换字符集 支持数组转换
    private function autoCharset($fContents, $from='gbk', $to='utf-8') {
        $from   = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to     = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }
	function autowrap($fontsize, $angle, $fontface, $string, $width) {
		// 参数分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
		$content = "";
		// 将字符串拆分成一个个单字 保存到数组 letter 中
		preg_match_all("/./u", $string, $arr);
		$letter = $arr[0];
		foreach($letter as $l) {
			$teststr = $content.$l;
			$testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
			if (($testbox[2] > $width) && ($content !== "")) {
				$content .= PHP_EOL;
			}
			$content .= $l;
		}
		return $content;
	}
	//根据文件类型 创建一个新图象
	private function imgCreateFrom($img_src){
		$ext = getextension($img_src,'.');
		$ext = strtolower($ext);		
		switch($ext){
			case 'gif':
				$img = imagecreatefromgif($img_src);
				break;
			case 'jpg':
				$img = imagecreatefromjpeg($img_src);
				break;
			case 'jpeg':
				$img = imagecreatefromjpeg($img_src);
				break;
			case 'png':
				$img = imagecreatefrompng($img_src);
				break;
		}
		return $img;
	}
	function resize($imgsrc, $width, $height = 0) {  //参数定义为：源图片，目标宽度，目标高度 
		$data = file_get_contents($imgsrc);
		$img_s = imagecreatefromstring($data);  // 获得源图片资源
		$width_s = imagesx($img_s);  // 获得源图片宽度
		$height_s = imagesy($img_s);  // 获得源图片高度

		$width_t = $width;  // 获得目标图片宽度
		$height_t = ($height == 0) ? $width : $height;  // 获得目标图片高度

		$image_t = imagecreatetruecolor($width_t, $height_t); //创建一个彩色的底图 
		$white = imagecolorallocate($image_t, 255, 255, 255);
		imagefill($image_t, 0, 0, $white); // 初始化背景为白色

		if (($width_s / $height_s) < ($width_t / $height_t)) {
			$_final['width'] = $width_s * $height_t / $height_s;
			$_final['height'] = $height_t;
			$dst['x'] = ($width_t - $_final['width']) / 2;
			$dst['y'] = 0;
		} else {
			$_final['width'] = $width_t;
			$_final['height'] = $width_t * $height_s / $width_s;
			$dst['x'] = 0;
			$dst['y'] = ($height_t - $_final['height']) / 2;
		}

		ImageCopyResized($image_t, $img_s, $dst['x'], $dst['y'], 0, 0, $_final['width'], $_final['height'], $width_s, $height_s);
		$rel = imagepng($image_t, $imgsrc);
		imagedestroy($img_s);
		imagedestroy($image_t);
		return $rel ? $imgsrc : false;
	}
}
?>