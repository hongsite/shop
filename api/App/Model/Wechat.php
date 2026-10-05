<?php
namespace Models\Wechat;
class Wechat{
	/**
	 * 微信推送过来的数据或响应数据
	 * @var array
	 */
	private $data = array();
	
	/**
	 * 主动发送的数据
	 * @var array
	 */
	private $send = array();
	private $appid;	
	private $appsecret;	
		
	/**
	 * 获取微信推送的数据
	 * @return array 转换为数组后的数据
	 */
	function __construct(){
		$this->appid = 'wxcb9384eb6c8e8f0d';
		$this->appsecret = 'a5639b2a33abc8ec3fe3ffdac203923f';
	}
	public function reminder($truename='',$title='',$openid=''){
		$openid = 'oukp_t1BRWCiqs4AZaJ81k3X2tU8';//老谭
		$tmpid = 'KeovstALlEQwWQpna4K1SbWTQ-hXRuQofHExcaqLzDs';
		$data = [
			'touser' => $openid,
			'template_id' => $tmpid,
			'url' => 'http://http://sm.hongsite.com/m/book/',
			'data' => [
				'first' => [
					'value' => '订单疑难件提醒',
					'color' => '#173177'
				],
				
				'thing3' => [
					'value' => $truename,
					'color' => '#173177'
				],								
				'time12' => [
					'value' => date('Y年m月d日'),
					'color' => '#173177'
				],	
				'thing14' => [
				'value' => $title,
				'color' => '#173177'
				],
					
				'remark' => [
					'value' => '订单疑难件提醒，请注意查看',
					'color' => '#173177'
				]
			]
		];	
		$rtcmd = $this->sendTemp($data);
		return $rtcmd;
	}
	public function request(){
		$this->auth() || exit;
		
		if(isset($_GET['echostr'])){
			exit($_GET['echostr']);
		} else {
			$xml = file_get_contents("php://input");
			$xml = new \SimpleXMLElement($xml);
			$xml || exit;
		
			foreach ($xml as $key => $value) {
				$this->data[$key] = strval($value);
			}
		}
		//file_put_contents(ROOT.'auth1.txt',var_export($this->data,true));
       	return $this->data;
	}

	/**
	 * * 被动响应微信发送的信息（自动回复）
	 * @param  string $to      接收用户名
	 * @param  string $from    发送者用户名
	 * @param  array  $content 回复信息，文本信息为string类型
	 * @param  string $type    消息类型
	 * @param  string $flag    是否新标刚接受到的信息
	 * @return string          XML字符串
	 */
	public function response($content, $type = 'text', $flag = 0){
		/* 基础数据 */
		$this->data = array(
			'ToUserName'   => $this->data['FromUserName'],
			'FromUserName' => $this->data['ToUserName'],
			'CreateTime'   => NOW_TIME,
			'MsgType'      => $type,
		);

		/* 添加类型数据 */
		$this->$type($content);

		/* 添加状态 */
		$this->data['FuncFlag'] = $flag;

		/* 转换数据为XML */
		$xml = new \SimpleXMLElement('<xml></xml>');
		$this->data2xml($xml, $this->data);
		exit($xml->asXML());
	}
	public function sendMsg($content, $openid = '', $type = 'text') {
		/* 基础数据 */
		$this->send['touser'] = $openid;
		$this->send['msgtype'] = $type;

		
		/* 添加类型数据 */
		$sendtype = 'send' . $type;
		$this->$sendtype($content);		
			
		/* 发送 */
		$sendjson = jsencode($this->send);
		//$sendjson = $this->send;

		//dump($sendjson);
		
		$restr = $this->send($sendjson);
		return $restr;
	}
	public function sendtemp($data){
		$access_token = $this->accessToken();
		if($access_token=='') return 'token获取错误';
		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;		 
		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
		 
		$response = curl_exec($ch);
		curl_close($ch);
		 
		return $response;
		
	}
	
	/**
	 * 发送文本消息
	 * 
	 * @param string $content
	 *        	要发送的信息
	 */
	private function sendtext($content) {
		$this->send ['text'] = array (
				'content' => $content 
		);
	}
	
	/**
	 * 发送图片消息
	 * 
	 * @param string $content
	 *        	要发送的信息
	 */
	private function sendimage($content) {
		$this->send ['image'] = array (
				'media_id' => $content 
		);
	}

	/**
	 * 发送视频消息
	 * @param  string $content 要发送的信息
	 */
	private function sendvideo($video){
		list (
			$video ['media_id'],
			$video ['title'],
			$video ['description']
		) = $video;
		
		$this->send ['video'] = $video;
	}
	
	/**
	 * 发送语音消息
	 * 
	 * @param string $content
	 *        	要发送的信息
	 */
	private function sendvoice($content) {
		$this->send ['voice'] = array (
				'media_id' => $content 
		);
	}
	
	/**
	 * 发送音乐消息
	 * 
	 * @param string $content
	 *        	要发送的信息
	 */
	private function sendmusic($music) {
		list ( 
			$music ['title'], 
			$music ['description'], 
			$music ['musicurl'], 
			$music ['hqmusicurl'], 
			$music ['thumb_media_id']
		) = $music;
		
		$this->send ['music'] = $music;
	}
	
	/**
	 * 发送图文消息
	 * @param  string $news 要回复的图文内容
	 */
	private function sendnews($news){
		
		//$articles = array();
		
		foreach ($news as $key => $v) {
			$articles[$key]['title'] = $v['Title'];
			$articles[$key]['description'] = $v['Description'];
			$articles[$key]['url'] = $v['Url'];
			$articles[$key]['picurl'] = $v['PicUrl'];
			if($key >= 9) { break; } //最多只允许10调新闻
		}	
		
		/*
		foreach ($news as $key => $v) {
			$articles[$key]['title'] = $v['title'];
			$articles[$key]['description'] = $v['description'];
			$articles[$key]['url'] = $v['url'];
			$articles[$key]['picurl'] = $v['picurl'];
			
			if($key >= 9) { break; } //最多只允许10调新闻
		}
		
		foreach ($news as $key => $v) {
			$articles[$key]['Title'] = $v['Title'];
			$articles[$key]['Description'] = $v['Description'];
			$articles[$key]['Url'] = $v['Url'];
			$articles[$key]['PicUrl'] = $v['PicUrl'];
			
			if($key >= 9) { break; } //最多只允许10调新闻
		}*/
		//$this->send['ArticleCount'] = count($articles);
		//dump($articles);
		//exit;
		$this->send['news']['articles'] = $articles;
	}
	
  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

	

	function dw(){
		$signPackage = $this->GetSignPackage();
		return $signPackage;
	}
	private function get_cache_file($filename) {
		return file_get_contents($filename);
	}
	private function set_cache_file($filename,$content) {
		$fp = fopen($filename,'w');
		fwrite($fp,$content);
		fclose($fp);
	}
	
	
	/**
	 * * 获取微信用户的基本资料
	 * 
	 * @param string $openid   	发送者用户名
	 * @return array 用户资料
	 */	
	public function user($openid = ''){
		if ($openid) {
			$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->accessToken().'&openid='.$openid.'&lang=zh_CN';

			//$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);		 
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($ch, CURLOPT_POST, true);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
			 
			$response = curl_exec($ch);
			curl_close($ch);

			//file_put_contents(ROOT.'user.txt',$response);
			 
			return json_decode($response,true);
			

		} else {
			return false;
		}
	}

	public function batchuser($userlist=null){
		if ($openid) {
			$url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token='.$this->accessToken();			
			$httpstr = http($url,$userlist);
			$harr = json_decode($httpstr,true);
			return $harr;
		} else {
			return false;
		}
	}
	
	/**
	 * 生成菜单
	 * @param  string $data 菜单的str
	 * @return string  返回的结果；
	 */
	public function setMenu($data = NULL){
		$access_token = $this->accessToken();
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
		//print_r($data);
		//exit;
		$menustr = http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"), true);
		//dump($menustr);
		return $menustr;
	}		
	function api_notice_increment($url){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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

	/**
	 * 回复文本信息
	 * @param  string $content 要回复的信息
	 */
	private function text($content){
		$this->data['Content'] = $content;
	}

	/**
	 * 回复音乐信息
	 * @param  string $content 要回复的音乐
	 */
	private function music($music){
		list(
			$music['Title'], 
			$music['Description'], 
			$music['MusicUrl'], 
			$music['HQMusicUrl']
		) = $music;
		$this->data['Music'] = $music;
	}

	/**
	 * 回复图文信息
	 * @param  string $news 要回复的图文内容
	 */
	private function news($news){
		/*
		$articles = array();
		
		foreach ($news as $key => $value) {
			list(
				$articles[$key]['Title'],
				$articles[$key]['Description'],
				$articles[$key]['PicUrl'],
				$articles[$key]['Url']
			) = $value;
			if($key >= 9) { break; } //最多只允许10调新闻
		}
		*/
		foreach ($news as $key => $v) {
			$articles[$key]['Title'] = $v['Title'];
			$articles[$key]['Description'] = $v['Description'];
			$articles[$key]['Url'] = $v['Url'];
			$articles[$key]['PicUrl'] = $v['PicUrl'];
			
			if($key >= 9) { break; } //最多只允许10调新闻
		}
		$this->data['ArticleCount'] = count($articles);
		$this->data['Articles'] = $articles;
	}
		
	/**
	 * 主动发送的信息
	 * @param  string $data    json数据
	 * @return string          微信返回信息
	 */
	private function send($data = NULL) {

		//dump($data);
		//exit;
		$access_token = $this->accessToken();
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
		$restr = http($url,$data,'POST', array("Content-type: text/html; charset=utf-8"),true);
		return $restr;
	}
	private function sendmb($data = NULL) {
		$access_token = $this->accessToken();
		//$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/subscribe?access_token={$access_token}";
		$restr = http ( $url, $data, 'POST', array ( "Content-type: text/html; charset=utf-8" ), true );
		return $restr;
	}

	/**
     * 数据XML编码
     * @param  object $xml  XML对象
     * @param  mixed  $data 数据
     * @param  string $item 数字索引时的节点名称
     * @return string
     */
    private function data2xml($xml, $data, $item = 'item') {
        foreach ($data as $key => $value) {
            /* 指定默认的数字key */
            is_numeric($key) && $key = $item;

            /* 添加子元素 */
            if(is_array($value) || is_object($value)){
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
            	if(is_numeric($value)){
            		$child = $xml->addChild($key, $value);
            	} else {
            		$child = $xml->addChild($key);
	                $node  = dom_import_simplexml($child);
				    $node->appendChild($node->ownerDocument->createCDATASection($value));
            	}
            }
        }
    }

    /**
	 * 对数据进行签名认证，确保是微信发送的数据
	 * @param  string $token 微信开放平台设置的TOKEN
	 * @return boolean       true-签名正确，false-签名错误
	 */
	private function auth(){	
		
		$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp']:'';
		$nonce = isset($_GET['nonce']) ? $_GET['nonce']:'';
		$signature1 = isset($_GET['signature']) ? $_GET['signature']:'';
		
		/* 获取数据 */
		$data = array($timestamp, $nonce,$this->token);
		$sign = $signature1;
		
		/* 对数据进行字典排序 */
		sort($data,SORT_STRING);

		/* 生成签名 */
		$signature = sha1(implode($data));

        if(isset($_GET['debug'])){
			return true;
		}

		//file_put_contents(ROOT.'auths.txt',var_export($_GET,true));

		return $signature === $sign;
	}
		
	/**
	 * 获取保存的 accesstoken（带文件锁和错误处理）
	 */
	public function accessToken(){
		$path = ROOT.'static/cache/wxapi/';
		if(!is_dir($path)){
			mkdir($path,0777,true);
		}
		$filename = $path.'token.txt';
		if(file_exists($filename)){
			$modTime = filemtime($filename);
			if(time()-$modTime<3600){
				$token = file_get_contents($filename);
				//echo $token;
				return $token;
			}
		}
		$token = $this->getAcessToken();
		if($token!=''){
			file_put_contents($filename,$token);
		}
		return $token;
	}	
	private function getAcessToken() {
		$url = 'https://api.weixin.qq.com/cgi-bin/token';
		$params = [
			'grant_type' => 'client_credential',
			'appid' => $this->appid,
			'secret' => $this->appsecret
		];
		//print_r($params);
		$query = http_build_query($params);
		$response = $this->https_request($url . '?' . $query);
		$arr = json_decode($response, true);

		$access_token = $arr['access_token'] ?? '';

		return $access_token;
	}

	/**
	 * HTTPS 请求（支持 GET/POST）
	 */
	private function https_request($url, $data = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

}