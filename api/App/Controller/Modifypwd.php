<?php
/*
* @author 牛头
* @function 修改密码
* @date 2018-06-25
* @version v1.0.0
*/
namespace hst\ModifyPwd;
use hst\Common;
class ModifyPwd extends Common {
	function _init(){
		parent::_init();
	}	
	//修改资料保存json方式
	public function update_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$uid = getuid();

		$status = '';
		$msg = '';
		$oldpassword = $_POST['oldpassword'] ?? '';
		$password = $_POST['password'] ?? '';
		$password1 = $_POST['password1'] ?? '';
		if(strlen($oldpassword)<4){
			$js['msg'] = '请输入旧密码';
			$this->json($js);
		}		
		if(strlen($password)<6||strlen($password)>20){
			$js['msg'] = '新密码长度只能在6-20位字符之间';
			$this->json($js);
		}		
		if($password!=$password1){
			$js['msg'] = '您两次输入的密码不一致';
			$this->json($js);
		}
		if($password==$oldpassword){
			$js['msg'] = '您的新密码和旧密码输入一样，无需更改';
			$this->json($js);
		}
		$m = M('Member');
		$rt = $m->where('id='.$uid)->find();
		if(!$rt){
			$js['msg'] = '用户不存在';
			$this->json($js);
		}
		$username = $rt['username'] ?? '';
		$encmd5 = $rt['encmd5'] ?? '';
		$oldpassword = md5($oldpassword);
		$rndstr = $rt['rndstr'] ?? '';
		$oldencmd5 = md5($oldpassword.$rndstr);
		if($oldencmd5!=$encmd5){
			$js['msg'] = '您的旧密码错误';
			$this->json($js);
		}

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}

		//新密码
		$password = md5($password);
		$encmd5 = md5($password.$rndstr);
		
		$data = null;

		$data['password'] = $password;
		$data['encmd5'] = $encmd5;
		$rtcmd = $m->where('id='.$uid)->save($data);
		if($rtcmd!==false) {
			session_destroy();
			cookie('member_uid','',-3600);
			cookie('member_uname','',-3600);
			cookie('member_upwd','',-3600);
			
			$rts = null;
			$rts['id'] = $uid;
			$rts['username'] = $username;
			$rts['encmd5'] = $encmd5;

			setLogin($rts,'Member');

			$js['status'] = 1;
			$js['username'] = $username;
			$js['encmd5'] = $encmd5;			
			$js['cookiepre'] = C('COOKIE_PREFIX');

			$js['msg'] = '登录密码修改成功';
			$this->json($js);
		}else {
			$js['msg'] = '登录密码修改失败';
			$this->json($js);
		}
	}
}