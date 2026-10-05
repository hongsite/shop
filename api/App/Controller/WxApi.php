<?php
/*
* @author 牛头
* @function 首页
* @date 2022/03/14
* @version v1.0.0
*/
namespace Hst\WxApi;
use Hst\Common;
class WxApi extends Common{
	private $sessionKey = '';
	private $grant_type = 'authorization_code';
    public function _init() {
        parent::_init();		
    }
	public function licheng_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$arr = null;
		$arr[] = array("value1"=>100,"value2"=>300,"value3"=>200,"value4"=>500,"value5"=>290,"value6"=>390);
		$js['data'] = $arr;
		$this->json($js);
	}
	public function encry_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		//获取小程序参数
		$js_code = I('code',0,'post');
		$iv = I('iv',0,'post');		
		$encryptedData=I('encryptedData',0,'post');
		$parentid = I('parentid',1,'post');

		if($this->wx_appid==''||$this->wx_appsecret=='') $this->json($js);
		
		//请求官方API
		$url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$this->wx_appid.'&secret='.$this->wx_appsecret.'&js_code='.$js_code.'&grant_type='.$this->grant_type;
		$html=$this->api_notice_increment_wx($url);

		$arr = json_decode($html,true);
		if(isset($arr['errcode']) && $arr['errcode']!='0'){
			$js['msg'] = $arr['errmsg'];
			$this->json($js);
		}

		if(!$arr['session_key']){
			$js['msg'] = 'session_key验证错误';
			$this->json($js);
		}
		$this->sessionKey = $arr['session_key'];
		$openid = $arr['openid'];
		$info = $this->decryptData($encryptedData,$iv);
		if($info['status']!==true){
			$js['msg'] = $info['msg'];
			$this->json($js);
		}
		$data = $info['data'];

		$m = M('Member');

		

		$js['status'] = 1;			
		$mobile = trim($data['phoneNumber']);
		if(checkMobile($mobile)===true){
			$where = 'username=\''.$mobile.'\'';
			$rt = $m->field('id,encmd5,username')->where($where)->find();
			if(!$rt){
				$ip = getipint();
				$arr['username'] = $mobile;
				$arr['mobile'] = $mobile;
				$arr['password'] = genrndstr(16);
				$arr['ip'] = $ip;				
				$arr['parentid'] = $parentid;
				$arr['sourceid'] = 5;
				$rtcmd = D('Member')->addMember($arr);
				if($rtcmd!==false) $rt = D('Member')->getValue($rtcmd);
			}
			$data['mobile'] = $mobile;
			$data['encmd5'] = $rt['encmd5'] ?? '';
			$data['cookiepre'] = C('COOKIE_PREFIX');
		}
		$js['msg'] = '登录成功';
		$js['data'] = $data;	
		$this->json($js);
		
	}
	public function decryptData($encryptedData='',$iv=''){
		$json['status'] = false;
		$json['msg'] = '';
		if(strlen($this->sessionKey)!=24) {
			$json['msg'] = 'sessionKey错误';
			return $json;
		}
		$aesKey=base64_decode($this->sessionKey);

        
		if (strlen($iv)!=24) {
			$json['msg'] = '用户取消了授权操作';
			return $json;
		}
		$aesIV=base64_decode($iv);

		$aesCipher=base64_decode($encryptedData);

		$result=openssl_decrypt($aesCipher,"AES-128-CBC",$aesKey,1,$aesIV);
		//file_put_contents(ROOT.'result.txt',$result,true);

		$dataObj=json_decode($result,true);
		if(!$dataObj){
			$json['msg'] = '可能是系统繁忙，请重试';
			return $json;
		}		
		$json['status'] = true;
		$json['data'] =$dataObj; 
		return $json;
	}
	public function getsessionkey_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		

		$data = $this->getOpenidAction();

		$redata = null;

		$openid = $data['openid'] ?? '';

		if(strlen($openid)<10){
			$js['msg'] = '登录失败';
			$this->json($js);
		}			
		$ip = getipint();
		$m = M('Authorized');
		$rt = $m->field('id,openid,rndstr')->where('openid=\''.$openid.'\'')->find();
		if(!$rt){			
			$rndstr = genrndstr(10);
			$data['openid'] = $openid;
			$data['rndstr'] = $rndstr;				
			$data['xcx'] = 1;
			$data['ip'] = $ip;
			$data['addtime'] = $this->nowtime;
			$data['uptime'] = $this->nowtime;
			$rtcmd = $m->add($data);
			$id = $rtcmd;
		}else{
			$id = $rt['id'] ?? 0;
			$data['id'] = $id;
			$rndstr = $rt['rndstr'] ?? '';
			$dataup = null;
			$dataup['uptime'] = $this->nowtime;
			$dataup['ip'] = $ip;
			if(strlen($rndstr)<5){
				$rndstr = genrndstr(10);
				$dataup['rndstr'] = $rndstr;	
			}
			$m->where('id='.$id)->save($dataup);
		}
		//$js['sql'] = $m->getlastsql();
		$redata['uptime'] = $this->nowtime;
		$redata['rndstr'] = $rndstr;
		$redata['openid'] = $openid;
		$redata['token'] = md5(md5($openid).$rndstr);
		$redata['session_key'] = $data['session_key'] ?? '';

		$js['status'] = 1;
		$js['data'] = $redata;
		$js['msg'] = '登录成功';
		$this->json($js);
	}
	public function authlogin_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$openid = I('openid',0,'post');

		$map['openid']=trim($openid);
		$m = M('Authorized');
		$rt = $m->where($map)->find();
		if(!$rt) {
			$js['msg'] = '授权失败！';
			$this->json($js);
		}		
		$nickname = I('NickName',0,'post');
		$data['nickname'] = base64_encode(I('NickName',0,'post'));
		$data['sex'] = I('gender',0,'post');
		$data['addtime'] = time();		

		$rt = $m->where('id='.$rt['id'])->find();
		if(!$rt) {
			$js['msg'] = '授权失败！';
			$this->json($js);
		}
		$rt['nickname'] = base64_decode($rt['nickname']);
		
		$js['data'] = $rt;
		$js['msg'] = '登录成功';
		$this->json($js);
	}
	
	public function getOpenidAction(){
        $js_code = I('code',0,'post');
        $curl = curl_init();
        //使用curl_setopt() 设置要获得url地址
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$this->wx_appid.'&secret='.$this->wx_appsecret.'&js_code='.$js_code.'&grant_type='.$this->grant_type;
        curl_setopt($curl, CURLOPT_URL, $url);

        //设置是否输出header
        curl_setopt($curl, CURLOPT_HEADER, false);

        //设置是否输出结果
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        //设置是否检查服务器端的证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        //使用curl_exec()将curl返回的结果转换成正常数据并保存到一个变量中
        $data = curl_exec($curl);
        //关闭会话
        curl_close($curl);

		//file_put_contents(ROOT.'getOpenid.txt',var_export($data,true));

		$arr = json_decode($data,true);
		//print_r($arr);
		//if(

		if(!isset($arr['openid'])){
			$arr['openid'] = '';
			$arr['redata'] = '';
		}


        return $arr;
    }
	function api_notice_increment_wx($url){
		$ch = curl_init();
		//$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		if (curl_errno($ch)) {
		curl_close( $ch );
		return $ch;
		}else{
		curl_close( $ch );
		return $tmpInfo;
		}
	}
}