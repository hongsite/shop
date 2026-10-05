<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 收货地址
* @date 2020/10/25
* @version hst-v1.0.0
*/
namespace Hst\Address;
use Hst\Common;
class Address extends Common {
	private $citypath;
    function _init(){
		parent::_init();
		$this->subtabnums = C('DB_SUB_NUMS_ADDRESS');
		$this->citypath =ROOT.'static/other/city/';
		creatdir($this->citypath);
	}	

	//获得省份信息
	public function province_json(){
		header('content-type:application/json');
		$filename = $this->citypath.'province.txt';
		if(file_exists($filename)){			
			echo file_get_contents($filename);
			exit;
		}
		$m = M('Area');
		$arr = $m->field('id,title')->where('`pid`=0')->order('sort ASC')->select();
		if($arr){
			file_put_contents($filename,json_encode($arr));
		}
		echo file_get_contents($filename);	
	}
	//取得二级信息
	public function city_json(){
		header('content-type:application/json');			
		$pid = I('pid',1,'');

		$filename = $this->citypath.'city_'.$pid.'.txt';
		if(file_exists($filename)){
			echo file_get_contents($filename);
			exit;
		}
		$m = M('Area');
		$arr = $m->field('id,title')->where('`pid`='.$pid)->order('sort ASC')->select();
		if($arr){
			file_put_contents($filename,json_encode($arr));
		}
		echo file_get_contents($filename);		
	}
	//取得地区相关信息
	public function county_json(){
		header('content-type:application/json');
		$cid = I('pid',1,'');		
		$filename = $this->citypath.'county_'.$cid.'.txt';
		if(file_exists($filename)){			
			echo file_get_contents($filename);
			exit;
		}
		$m = M('Area');
		$arr = $m->field('id,title')->where('`pid`='.$cid)->order('sort ASC')->select();
		if($arr){
			file_put_contents($filename,json_encode($arr));
		}
		echo file_get_contents($filename);	
	}
	//取得aid的相关信息如postcode
	public function getpostcode(){
		$countyid = I('countyid',1,'');
		$map['countyid'] = $countyid;
		$m = M('Area');
		$arr = $m->where($map)->cache(true,600)->find();
		echo json_encode($arr);
	}	
	//编辑地址html
	public function edit_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$id = I('id',1,'');
		$uid = getuid();
		$rt = null;
		if($id>0){
			$rt = D('Address')->getlist($uid,$id);			
		}
		if($rt){
		    $js['status'] = 1;
			$js['data'] = $rt;
		}

		$filename = $this->citypath.'province.txt';
		if(file_exists($filename)){			
			$provincelist = file_get_contents($filename);
			$js['provincelist'] = json_decode($provincelist);
			$this->json($js);
		}
		$m = M('Area');
		$arr = $m->field('id,title')->where('`pid`=0')->order('sort ASC')->select();
		if($arr){
			file_put_contents($filename,json_encode($arr));
		}
		$js['provincelist'] = $arr;		
		$this->json($js);
    }
	//编辑地址html
	public function json_list(){		
		$js['status'] = 0;
		$js['msg'] = '';		
		$uid = getuid();		
		$address = D('Address')->getlist($uid,0);
		if($address){
		    $js['status'] = 1;
			$js['data'] = $address;			
		}else{
		}
		$this->json($js);

	}
	
	//删除地址
	public function detault_json(){
		$js['status'] = 0;
		$js['msg'] = '';		
		$id = I('id',1,'');
		$uid = getuid();
		$tb = getcarttb($uid,'Address');
		$m = M($tb);		
		$map['id'] = $id;
		$map['uid'] = $uid;
		$rt = $m->where($map)->find();
		if(!$rt){
			$js['msg'] = '地址不存在';	
			$this->json($js);
		}
		$rtcmd = D('Address')->setDefault($uid,$id);
		if($rt!==false){
			$js['status'] = 1;
			$js['msg'] = '设置成功';
		}else{
			$js['msg'] = '设置失败';
		}
		$this->json($js);
    }
	//清除以前的
	private function clearaddress($uid){
		$name = 'Address';
		$map['uid'] = $uid;
		$tbnums = ceil($uid/$this->subtabnums);
		$m = M($name.C('DB_SUB_AFT').$tbnums);
		$m->where($map)->setField('isdefault',0);
	}
	//编辑和添加地址ajax
	public function delete_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$uid = getuid();

		$tb = 'Address';
			
		$id = I('id',1,'post');		
		$map['id'] = $id;
		$map['uid'] = $uid;
		$tb = getcarttb($uid,'Address');
		$m = M($tb);
		$rt = $m->where($map)->find();
		if(!$rt){
			$js['msg'] = '地址不存在';	
			$this->json($js);
		}		
		$rtcmd = $m->where($map)->delete();
		if($rtcmd!==false){
			$js['status'] = 1;
			$js['msg'] = '删除成功';
		}else{
			$js['msg'] = '删除失败';
		}
		D('Address')->updateSetDefault($uid);
		$this->json($js);
	}	
	//编辑和添加地址ajax
	public function update_json(){		
		$js['status'] = 0;
		$js['msg'] = '';
					
		$id = I('id',1,'post');
		$uid = getuid();

		$name = 'Address';

		$address = I('address',0,'post');
		
		$truename = I('truename',0,'post');
		if(strlen($truename)<3){
		    $js['msg'] = '请填写收货人';	
			$this->json($js);
		}

		$mobile = I('mobile',0,'post');
		$tel = I('tel',0,'post');
		$telbef = I('telbef',0,'post');
		$telaft = I('telaft',0,'post');
		if($mobile==''){
		    $js['msg'] = '手机号错误';	
			$this->json($js);
		}
		if(checkMobile($mobile)!==true){
			$js['msg'] = '手机号错误';	
			$this->json($js);
		}
		

		$postcode = I('postcode',0,'post');

		$isdefault = I('isdefault',1,'post');

		$isdefault>1 && $isdefault = 0;
		
		$countyid = I('countyid',1,'post');		

		if($countyid==0){
		    $js['msg'] = '请选择县或区';	
			$this->json($js);
		}

		if(strlen($address)<3){
		    $js['msg'] = '请填写收货地址';	
			$this->json($js);
		}		

		if($id>0){
			$areart = D('Address')->getlist($uid,$id);
			if(!$areart){
				$js['msg'] = '地址不存在';	
				$this->json($js);
			}
		}
		
		$data['countyid'] = $countyid;

		$tb = getcarttb($uid,'Address');
		$m = M($tb);	


		$data['address'] = $address;
		$data['truename'] = $truename;
		$data['mobile'] = $mobile;
		
		if($id==0){			
			$data['uid'] = $uid;
			

			
			$rt = $m->add($data);



			//设置默认收货地址
			if($isdefault==1){			
				D('Address')->setDefault($uid,$rt);
			}else{
				D('Address')->updateSetDefault($uid);
			}			
			
			if($rt!==false){
				$arrs = D('Address')->getlist($uid,$rt);
				$js['status'] = 1;
				$js['msg'] = '新增地址成功';
				$js['data'] = $arrs;
				$js['tid'] = 1;
			}else{
				$js['msg'] = '新增地址失败';
			}			
			$this->json($js);

		}
		

		$map['id'] = $id;
		$map['uid'] = $uid;			
		$rt = $m->where($map)->save($data);
		if($rt!==false){
			$arr = D('Address')->getlist($uid,$id);
			$js['status'] = 1;
			$js['data'] = $arr;
			$js['msg'] = '更新地址成功';
			$js['tid'] = 2;
		}else{
			$js['msg'] = '更新地址失败';	
		}

		//设置默认收货地址
		if($isdefault==1){			
			D('Address')->setDefault($uid,$id);
		}else{
			D('Address')->updateSetDefault($uid);
		}

		$this->json($js);
    }

	function ipdw(){
		echo '{"code":0,"data":{"country":"\u4e2d\u56fd","country_id":"CN","area":"\u534e\u5357","area_id":"800000","region":"\u6d77\u5357\u7701","region_id":"460000","city":"\u6d77\u53e3\u5e02","city_id":"460100","county":"","county_id":"-1","isp":"\u79fb\u52a8","isp_id":"100025","ip":"183.254.226.162"}}';
		exit;
		$ip =  get_client_ip();
		$caiji = D('Caiji');
		$url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
		$content = $caiji->gethtml($url);
		echo $content;
	}
	function getcityjson(){
	    $cityid = I('cityid',0,'post');
		$cityid=='' && $cityid = '110100';
		$arr = M('Area')->where('pid='.$cityid)->field('cid,city')->group('cid')->select();
		$data['status'] = 1;
		$data['msg'] = '';
		$data['data'] = $arr;
		$this->ajaxReturn($data,'JSON');
	}
	function getareajson(){
	    $countyid = I('countyid',0,'post');
		$countyid=='' && $countyid = '110100';
		$arr = M('Area')->where('cid='.$countyid)->field('pid,province,cid,city,aid,county')->group('aid')->select();
		$data['status'] = 1;
		$data['msg'] = '';
		$data['data'] = $arr;
		$this->ajaxReturn($data,'JSON');
	}
	function test2(){
	    $m = M('Area');
		$arr = $m->field('pid AS `id`,county AS `name`,0 AS `root`,1 AS `djd`,72 AS `c`,`province`')->group('pid')->select();		
		$str = '';
		$newstr = '';
		foreach($arr as $key=>$v){
			//$str .= '"'.$v['province'].'"'.':{ id: "'.$v['id'].'", root: 0, djd: 1,c:72 },';
			$str .= '"'.$v['id'].'":[';
			$newarr = $m->field('aid AS `id`,county AS `name`')->where('pid='.$v['id'])->select();
			foreach($newarr as $kkk=>$vv){
				$newstr .= '{"id":'.$vv['id'].',"name":"'.$vv['name'].'"},';
			}
			$newstr = substr($newstr,0,-1);
			$str .= $newstr.'],';
		}
		echo '{'.substr($str,0,-1).'}';
	}
	function test1(){
	    $m = M('Area');
		$arr = $m->field('pid AS `id`,county AS `name`,0 AS `root`,1 AS `djd`,72 AS `c`,`province`')->group('pid')->select();		
		$str = '';
		$newstr = '';
		foreach($arr as $key=>$v){
			$str .= '<li data-id="'.$v['id'].'"><a href="javascript:void(0);">'.$v['province'].'</a></li>';
		}
		$str = str_replace('省','',$str);
		$str = str_replace('市','',$str);
		echo $str;
	}

	

}
?>