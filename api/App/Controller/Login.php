<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 关于我们
* @date 2022/11/02
* @version hst-v1.0.0
*/
namespace Hst\Login;
use Hst\Common;
class Login extends Common{
	function _init(){
		parent::_init();
	}
	private function before_reurl(){
		$ref = getref();
	}
	public function index(){
		exit;
	}		
	//是否登录检测
	public function islogin(){
		$js['status'] = 0;
		$js['msg'] = '';
		$uid = getuid();
		$nickname = I('member_nickname',0,'cookies');
		$face = I('member_face',0,'cookies');
		$js['nickname'] = $nickname;
		$js['face'] = $face;
		$js['status'] = $uid>0 ? 1:0;	
		$js['msg'] =  $uid>0 ? '已登录成功':'您还没有登录';
		$js['ref'] = '/index.php?s=Login&ref='.getref();		
		$this->json($js);
	}
	//ajax登录post提交检测
	public function login_json(){
		$mytime = time();		
		$js['status'] = 0;
		$js['msg'] = '';
		$js['code'] = '';

		
		$username = I('username',0,'post');
		$ref = getref(true);		
		$password = I('password',0,'post');
		$sku = I('sku',0,'post');
		$protocol = I('protocol',0,'post');
		$isverify = I('isverify',1,'');
		$ischeck = I('ischeck',1);
		$mobilecode = I('mobileCode',1,'post');
		$isreg = I('isreg',1);	
				
		
		if(!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
			$js['msg'] = '无效的用户名';
			$this->json($js);
		}


		/*if(checkMobile($username)==false){			
			$js['msg'] = '请输入手机号';			
			$this->json($js);
		}*/
		

		if($isverify==1){
			if($this->checkverifyajax()!==true){
				$js['msg'] = '验证错误，请先点击完成验证';
				$this->json($js);
			}
		}

		$m = M('Member');
		$map['username'] = $username;
		$map['deleted'] = 0;
		$encmd5 = '';		
		$rt = $m->where($map)->find();
		if(!$rt) {
			$js['msg'] = '账号未注册';
			$js['code'] = '101';
			$this->json($js);
		}

		if($password=='') {
			$js['msg'] = '请输入密码';
			$this->json($js);
		}

		$password = md5($password);
		$encmd5 = md5($password.$rt['rndstr']);
		
		if($rt['encmd5']!=$encmd5){
			$js['msg'] = '账号或密码错误';
			$this->json($js);
		}
		if($rt['stat']==1){
			$js['msg'] = '账号状态异常';
			$this->json($js);
		}
		if($rt['stat']==2){
			$js['msg'] = '账号状态异常';
			$this->json($js);
		}
		if($rt['stat']==3) {
			$js['msg'] = '账号状态异常';
			$this->json($js);
		}

		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功';
			$this->json($js);
		}

		$ip = getipint();		

		//登录成功后的处理		
		$data['lastip'] = $ip;
		$data['lastlogintime'] = $mytime;		


		$condition['id'] = $rt['id'];
		$mm = M('MemberDetail');
		$rt1= $mm->where($condition)->data($data)->save();
		$mm->where($condition)->setInc('times',1);

		$roleid = $rt['roleid'] ?? 0;

		setLogin($rt,'Member');		
		$js['username'] = $username;
		$js['encmd5'] = $rt['encmd5'];
		$js['cookiepre'] = C('COOKIE_PREFIX');
		$js['roleid'] = $roleid;
			
		$js['status'] = 1;
		$js['msg'] = '登录成功';
		$this->json($js);
	}
	//注册新账号
	public function reg_json(){
		
		$mytime = time();		
		$js['status'] = 0;
		$js['msg'] = '';
		$js['code'] = '';
		
		$username = I('username',0,'post');
		$ref = getref(true);		
		$password = I('password',0,'post');
		$sku = I('sku',0,'post');
		$protocol = I('protocol',0,'post');
		$isverify = I('isverify',1,'');
		$ischeck = I('ischeck',1);
		$mobilecode = I('mobileCode',1,'post');
		$salerid = I('salerid',1,'post');
		$isreg = I('isreg',1);
		$ip = getip();		
		$ipint = ip2long($ip);

		if(checkMobile($username)==false){			
			$js['msg'] = '请输入手机号'.$username;			
			$this->json($js);
		}

		$js['msg'] = '注册功能已关闭';			
		$this->json($js);
		
		

		if($isverify==1){
			if($this->checkverifyajax()!==true){
				$js['msg'] = '验证错误，请先点击完成验证';
				$this->json($js);
			}
		}

		
		
		$m = M('Member');
		$map['username'] = $username;			
		$rt = $m->where($map)->find();

		if($rt){
			$js['msg'] = '账号已存在，请直接登录';	
			$js['code'] = '102';
			$this->json($js);
		}

		if($salerid>0){
			$rts = M('Saler')->where('id='.$salerid)->find();
			if(!$rts){
				$js['msg'] = '销售人员ID号错误，请检查或留空';	
				$this->json($js);
			}
		}

		/*
		if(strlen($mobilecode)!=4){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}
		if(!is_numeric($mobilecode)){
			$js['msg'] = '验证码错误';			
			$this->json($js);
		}

		
		
		$mc = M('MobileCode');
		$rtcode = $mc->order('id DESC')->where('mobile='.$username.' AND tid=1')->find();

		if(!$rtcode){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}

		if(time()-$rtcode['addtime']>600){
			$js['msg'] = '验证码超时';
			$this->json($js);
		}

		$code = $rtcode['code'];
		if($mobilecode!=$code){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}*/

		if(strlen($password)<6){
			$js['msg'] = '请设置一个最少六位数的密码';
			$this->json($js);
		}

		if(strlen($password)>20){
			$js['msg'] = '密码超过20位';
			$this->json($js);
		}

		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功';
			$this->json($js);
		}

		

		$sourceid = $this->ism==1 ? 3:1;				
				
		$data = null;
		$data['username'] = $username;
		$data['groupid'] = 11;				
		$data['password'] = $password;
		$data['addip'] = $ipint;
		$data['ip'] = $ipint;
		$data['sourceid'] = $sourceid;
		$data['salerid'] = $salerid;

		$rtcmd = D('Member',1)->addMember($data);
		if($rtcmd!==false){					
			$js['msg'] = '恭喜您注册成功！';
			$rt = $m->where('id='.$rtcmd)->find();
			$datas = null;
			setLogin($rt,'Member');
					
			$js['status'] = 1;
			$js['username'] = $username;
			$js['encmd5'] = $rt['encmd5'];
			$js['cookiepre'] = C('COOKIE_PREFIX');
			$js['data'] = $datas;
		}else{
			$js['msg'] = '账号注册失败';
			
		}
		$this->json($js);
	}
	
	//发送快捷注册验证码
	public function sendcode_reg_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$js['code'] = '0';
		$isverify = I('isverify',1);

		$username = I('username',1,'post');		
		$m = M('Member');
		if(checkMobile($username)!==true){
			$js['msg'] = '请输入手机号';
			$this->json($js);
		}
		
		if($isverify==1){
			if($this->checkverifyajax()!==true){
				$js['msg'] = '验证错误，请先点击完成验证';
				$this->json($js);
			}
		}
		
		$rt = $m->where('username=\''.$username.'\'')->find();
		if($rt){
			$js['msg'] = '账号已注册，请直接登录';
			$js['code'] = '102';
			$this->json($js);
		}
		

		$mobile_code = rand(1000,9999);		
		$smsarr = get_smstmp(1);
		if(!$smsarr){
			$js['msg'] = '模板错误';
			$this->json($js);
		}		
		$content = $smsarr['content'];
		$templateId = $smsarr['smsid'];

		$content = str_replace('${code}',$mobile_code,$content);

		$arr['content'] = $content;
		$arr['smsid'] = $smsarr['smsid'];
		$arr['tmpid'] = 'SMS_154500530';
		$arr['sign'] = '海口鸿思特网络科技';
		$arr['mobile'] = $username;
		$arr['code'] = $mobile_code;
		$arr['tid'] = 1;		

		$info = send_code($arr);
		$stat = $info['stat'];
		$js['msg'] = $info['msg'];
		if($stat===true){
			$js['status'] = 1;			
		}else{
			$js['msg'] = $info['msg'];
		}
		$this->json($js);
	}
	//发送快捷注册验证码
	public function sendcode_forget_json(){
		$js['status'] = 0;
		$js['msg'] = '';

		$isverify = I('isverify',1);


		$username = trim(I('username',0,'post'));
		$password = trim(I('password',0,'post'));
		$isxcx = I('isxcx',1);
		
		$m = M('Member');		
		if(strlen($username)!=11){
			$js['msg'] = '请输入手机号';
			$this->json($js);
		}		


		if(!checkMobile($username)){
			$js['msg'] = '请输入手机号';
			$this->json($js);
		}

		if($isverify==1 && $this->checkverifyajax()!==true){
			$js['msg'] = '验证错误，请先点击完成验证';
			$this->json($js);
		}

		

		$map['username'] = $username;
		$rtcmd = $m->where($map)->find();

		if(!$rtcmd){
			$js['msg'] = '账号不存在';
			$this->json($js);
		}		

		$mobile_code = rand(1000,9999);		
		$smsarr = get_smstmp(2);		
		if(!$smsarr){
			$js['msg'] = '模板不存在';
			$this->json($js);
		}
		$content = $smsarr['content'];
		$templateId = $smsarr['smsid'];

		$content = str_replace('${code}',$mobile_code,$content);

		$arr['content'] = $content;
		$arr['smsid'] = $smsarr['smsid'];
		$arr['tmpid'] = 'SMS_154500533';
		$arr['sign'] = '海口鸿思特网络科技';
		$arr['mobile'] = $username;
		$arr['code'] = $mobile_code;
		$arr['tid'] = 2;

		$info = send_code($arr);
		$stat = $info['stat'];
		$js['msg'] = $info['msg'];
		if($stat===true){
			$js['status'] = 1;			
		}else{
			$js['msg'] = $info['msg'];
		}
		$this->json($js);
	}

	//找回密码提交
	public function forget_json(){
		//用户名检测
		$js['status'] = 0;
		$js['msg'] = '';

		$isverify = I('isverify',1);
		

		//手机检测
		$username = trim(I('username',0,'post'));
		$mobilecode = trim(I('mobileCode',0,'post'));
		$isxcx = I('isxcx',1);
		if(!checkMobile($username)){
			$js['msg'] = '请输入正确的手机号码';
			$this->json($js);
		}

		if($isverify==1 && $this->checkverifyajax()!==true){
			$js['msg'] = '验证错误，请先完成验证';
			$this->json($js);
		}

		$m = M('Member');

		$rt = $m->where('username=\''.$username.'\'')->find();
		if(!$rt){
			$js['msg'] = '账号还未注册';
			$this->json($js);
		}
		
		if(strlen($mobilecode)!=4){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}
		if(!is_numeric($mobilecode)){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}		
		
		$mc = M('MobileCode');
		$rtcode = $mc->order('id DESC')->where('mobile='.$username.' AND tid=2')->find();

		if(!$rtcode){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}

		if(time()-$rtcode['addtime']>600){
			$js['msg'] = '验证码超时';
			$this->json($js);
		}

		$code = $rtcode['code'];
		if($mobilecode!=$code){
			$js['msg'] = '验证码错误';
			$this->json($js);
		}		

		//密码判断
		$password = trim(I('password',0,'post'));		
		if(strlen($password)<4){			
			$js['msg'] = '密码太短了呦';
			$this->json($js);
		}
		if(strlen($password)>50){
			$js['msg'] = '密码太长了呦';
			$this->json($js);
		}				

		$mytime = time();
		M('MemberLog')->add(array('addtime'=>$mytime,'uid'=>$rt['id'],'password'=>$password));



		$rndstr = genrndstr(10);
		$password = md5($password);
		$encmd5 = md5($password.$rndstr);
		$mytime = time();
		$regip = ip2long(getip());
		$data['rndstr'] = $rndstr;
		$data['encmd5'] = $encmd5;
		$data['password'] = $password;

		$sku = '';

		$result = $m->where('id='.$rt['id'])->save($data);
		if($result!==false){			
			$rt = $m->where('id='.$rt['id'])->find();
			$rt['nickname'] = M('MemberDetail')->where('id='.$rt['id'])->getField('nickname');
			setLogin($rt,'Member');


			$js['username'] = $username;
			$js['encmd5'] = $encmd5;
			$js['cookiepre'] = C('COOKIE_PREFIX');


			
			$js['status'] = 1;
			$js['msg'] = '找回密码成功';
			$js['sku'] = $sku;
			$js['ref'] = '/';
			$js['data'] = $data;

			$this->json($js);

		}else{
			$js['msg'] = '找回密码失败';
			$this->json($js);
		}
	}	
	Public function verify_json(){		
		$randval = rand(1000,9999);
		session('verify',md5($randval));
		$js['status'] = 1;
		$js['msg'] = '';
		$js['data'] = $randval;
		$this->json($js);

	}
	private function checkverifyajax(){
		$verify = trim(I('verify',0,'post'));
		if($this->check_verify($verify)!==false){
			return true;
		}else{
			return false;
		}
	}
	private function check_verify($code){
		if(!is_numeric($code)){
			return false;
		}
		if(strlen($code)<4){
			return false;
		}
		if(session('verify')==md5($code)){
			return true;
		}else{
			return false;
		}
	}

	public function logout_json(){
		$js['status'] = 1;
		$js['msg'] = '您已安全退出';
		$js['code'] = '';
		session_destroy();
		cookie('member_uname','',-3600);
		cookie('member_upwd','',-3600);
		cookie('member_utruename','',-3600);
		cookie('member_sysday','',-3600);
		cookie('member_viptime','',-3600);
		$this->json($js);
	}
}