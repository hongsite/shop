<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 分离
*@date 2023/02/01
*@version hst-v1.0.0
*/
namespace Models\Location;
use Model\Model;
class Location extends Model{
    private $appId;
    private $appSecret;
	private $ticketapipath='';
	private $fname = '';
    public function getSignPackage($appid='',$appsecret=''){
		$this->appId = $appid;
		$this->appSecret = $appsecret;

		$path = ROOT.'static/cache/';
		creatdir($path);		

		$this->fname = $path.'jsapi_ticket.php';

        $jsapiTicket = $this->getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );		
        return $signPackage;
    }
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    private function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode($this->get_php_file($this->fname),true);
        if ($data['expire_time']<time()){
            $accessToken = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url),true);
			$ticket = $res['ticket'];
            if ($ticket) {
                $data['expire_time'] = time() + 7000;
                $data['access_token'] = $ticket;
                $this->set_php_file($this->fname,json_encode($data));
            }
        } else {
            $ticket = $data['access_token'];
        }
        return $ticket;
    }
    private function getAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
		$data = null;
		if(!file_exists($this->fname)){			
			$text = $this->httpGet($url);
			$res = json_decode($text,true);
            $access_token = $res['access_token'];
            if ($access_token) {
                $data['expire_time'] = time() + 7000;
                $data['access_token'] = $access_token;
                $this->set_php_file($this->fname, json_encode($data));
            }
			return $access_token;
		}
        $data = json_decode($this->get_php_file($this->fname),true);
        if ($data['expire_time'] < time()) {
            $text = $this->httpGet($url);
            $res = json_decode($text,true);
            $access_token = $res['access_token'];
            if ($access_token) {
                $data['expire_time'] = time() + 7000;
                $data['access_token'] = $access_token;
                $this->set_php_file($this->fname, json_encode($data));
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
    private function get_php_file($filename){
		if(!is_file($filename)){
			$arr = null;
			$arr['expire_time'] = 0;
			$arr['access_token'] = '';
			return json_encode($arr);
		}
		return file_get_contents($filename);
    }
    private function set_php_file($filename, $content) {
        $fp = fopen($filename, "w");
        fwrite($fp,$content);
        fclose($fp);
    }
}