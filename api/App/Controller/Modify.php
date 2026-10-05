<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 网站首页
* @date 2018/7/2
* @version hst-v1.0.0
*/
namespace hst\Modify;
use hst\Common;
class Modify extends Common{
    public function _init() {
        parent::_init();
		$this->allowedit = true;
    }	
	public function index(){
		exit;
	}
	public function edit(){
		$uid = getuid();
		$js['status'] = 1;
		$js['msg'] = '';

		$rt = D('Member')->getValue($uid);

		$Ip = D('Ipaddress',1);
		$ip = $rt['ip'] ?? 0;
		$ip = long2ip($ip);

		$lastip = $rt['lastip'] ?? 0;
		$lastip = long2ip($lastip);
		$rt['ip'] = $ip;
		$rt['lastip'] = $lastip;
		$rt['address'] = $Ip->getaddress($ip);
		$rt['lastaddress'] = $Ip->getaddress($lastip);
			
		$js['data'] = $rt;

		$this->json($js);
    }
	//修改资料保存json方式
	public function update_json(){
		$uid = getuid();		
		$js['status'] = 0;
		$js['msg'] = '';		
		$vo = D('Member')->getValue($uid);
		if(!$vo){
			$js['msg'] = '用户不存在';
			$this->json($js);
		}		

		$truename = I('truename',0,'post');
		$nickname = I('nickname',0,'post');
		$email = I('email',0,'post');	
		$birthday = I('birthday',0,'post');
		$bio = I('bio',0,'post');	
		$sex = I('sex',1,'post');
		$sex>3 && $sex = 1;
		$data = null;
		isset($_POST['truename']) && $data['truename'] = $truename;
		isset($_POST['nickname']) && $data['nickname'] = $nickname;
		isset($_POST['sex']) && $data['sex'] = $sex;
		isset($_POST['email']) && $data['email'] = $email;
		isset($_POST['birthday']) && $data['birthday'] = $birthday;
		isset($_POST['bio']) && $data['bio'] = $bio;
		
		if(!$data){
			$js['msg'] = '数据未更改';
			$this->json($js);
		}

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}

		$m = M('Member');
		$rtcmd = $m->where('id='.$uid)->save($data);
		$rts = M('MemberDetail')->where('id='.$uid)->find();
		if(!$rts){
			$data['id'] = $uid;
			M('MemberDetail')->add($data);
		}else{
			M('MemberDetail')->where('id='.$uid)->save($data);
		}
		
		
		if($rtcmd!==false) {
			$js['status'] = 1;
			$js['msg'] = '个人资料修改成功';
		}else {
			$js['msg'] = '您的资料可能还未修改';
			
		}
		$this->json($js);
	}
}