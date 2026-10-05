<?php
/**
 * 支付宝支付（支持服务商模式）
 */
namespace Models\AlipayPay;

class AlipayPay{
    public $privateKey = '';
    public $charset = 'utf-8';
    private $fileCharset = "UTF-8";
    public $postCharset = "UTF-8";
    private $sign_type = 'RSA2';
    private $SIGN = 'sign';
    public $alipayrsaPublicKey;
    public $rsaPrivateKey;
    private $serverUrl;
    private $timeout = 10;	

    /**
     * 付款码支付（支持服务商模式）
     */
    public function fkm($arr, $paykeys){
        $appid = empty($arr['appid']) ? '':$arr['appid'];		
        $out_trade_no = empty($arr['out_trade_no']) ? '':$arr['out_trade_no'];
        $total_fee = empty($arr['total_fee']) ? 0:$arr['total_fee'];
        $body = empty($arr['body']) ? '':$arr['body'];
        $auth_code = empty($arr['auth_code']) ? '':$arr['auth_code'];
        // 新增：服务商代商户发起时所需的商户授权令牌
        $app_auth_token = empty($arr['app_auth_token']) ? '':$arr['app_auth_token'];

        $js['status'] = 0;
        $js['msg'] = '';
        $js['code'] = '';
        $this->privateKey = $paykeys;		

        if($total_fee < 1){
            $js['msg'] = '参数金额错误';
            return $js;
        }
        $total_fee = formatp($total_fee); // 假设外部函数，格式化金额

        // 业务请求参数
        $requestConfigs = array(
            'out_trade_no' => $out_trade_no,			
            'total_amount' => $total_fee,
            'subject'      => $body,
            'auth_code'    => $auth_code,
            'scene'        => 'bar_code',
            'product_code' => 'FACE_TO_FACE_PAYMENT',
        );		

        // 公共请求参数
        $datas = array(
            'app_id'      => $appid,
            'method'      => 'alipay.trade.pay',
            'sign_type'   => $this->sign_type,			
            'version'     => '1.0',
            'timestamp'   => date('Y-m-d H:i:s'),
            'biz_content' => json_encode($requestConfigs),
            'charset'     => $this->charset
        );

        // 服务商模式：如果传入了 app_auth_token，则添加到公共参数中
        if (!empty($app_auth_token)) {
            $datas['app_auth_token'] = $app_auth_token;
        }

		//print_r($datas);

        // 要提交的数据（带签名）
        $data_sign = $this->buildGetUrl($datas);
        $post_data = $data_sign;

        // 初始化 curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.alipay.com/gateway.do?charset='.$this->charset);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);		
        curl_setopt($ch, CURLOPT_POST, 1);		
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);	

        $rt = json_decode($data, true);		
        $rt = $rt['alipay_trade_pay_response'] ?? array();
        if($rt['code'] == 10000){
            $js['status'] = 1;	
            $js['data'] = $rt;
            $js['msg'] = $rt['msg'] ?? '';
            return $js;
        }		
        $js['msg'] = $rt['msg'] ?? '';		
        return $js;
    }

    /**
     * 统一下单（支持多种支付方式，服务商模式）
     */
    public function orderdo($arr, $paykeys, $method='alipay.trade.precreate'){		
        $appid = empty($arr['appid']) ? '':$arr['appid'];		
        $out_trade_no = empty($arr['out_trade_no']) ? '':$arr['out_trade_no'];
        $total_fee = empty($arr['total_fee']) ? 0:$arr['total_fee'];
        $body = empty($arr['body']) ? '':$arr['body'];
        $notify_url = empty($arr['notify_url']) ? '':$arr['notify_url'];
        // 新增：服务商代商户发起时所需的商户授权令牌
        $app_auth_token = empty($arr['app_auth_token']) ? '':$arr['app_auth_token'];
        // 新增：网页支付的回调地址 return_url
        $return_url = empty($arr['return_url']) ? '':$arr['return_url'];

        $js['status'] = 0;
        $js['msg'] = '';
        $js['code'] = '';
        $this->privateKey = $paykeys;		

        if($total_fee < 1){
            $js['msg'] = '参数金额错误';
            return $js;
        }
        $total_fee = formatp($total_fee); // 假设外部函数，格式化金额

        $notify_alipay_app = array('notify_alipay_app' => true);

        // 根据支付类型设置不同的 product_code
        if($method == 'alipay.trade.app.pay'){
            $product_code = 'QUICK_MSECURITY_PAY';
        } elseif($method == 'alipay.trade.page.pay') {
            $product_code = 'FAST_INSTANT_TRADE_PAY';
        } else {
            $product_code = 'FACE_TO_FACE_PAYMENT';			
        }

        // 业务请求参数
        $requestConfigs = array(
            'out_trade_no'   => $out_trade_no,
            'product_code'   => $product_code,
            'total_amount'   => $total_fee,
            'subject'        => $body,
            'business_params'=> $notify_alipay_app
        );		

        // 公共请求参数
        $datas = array(
            "app_id"      => $appid,
            "method"      => $method,
            "sign_type"   => $this->sign_type,
            "version"     => '1.0',
            "timestamp"   => date('Y-m-d H:i:s'),
            "biz_content" => json_encode($requestConfigs),
            "notify_url"  => $notify_url,
            "charset"     => $this->charset
        );

        // 服务商模式：如果传入了 app_auth_token，则添加到公共参数中
        if (!empty($app_auth_token)) {
            $datas['app_auth_token'] = $app_auth_token;
        }

        // 网页支付需要添加 return_url
        if($method == 'alipay.trade.page.pay' && !empty($return_url)){
            $datas['return_url'] = $return_url;
        }

        // 构建签名字符串
        $data_sign = $this->buildGetUrl($datas);

        // APP支付和网页支付直接返回签名字符串（用于客户端或表单提交）
        if($method == 'alipay.trade.app.pay' || $method == 'alipay.trade.page.pay'){
            $js['status'] = 1;
            $js['data'] = $data_sign;
            $js['msg'] = '生成成功';
            return $js;
        }

        // 其他情况（如预创建）需要发送 POST 请求获取二维码链接
        $post_data = $data_sign;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.alipay.com/gateway.do?charset='.$this->charset);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);		
        curl_setopt($ch, CURLOPT_POST, 1);		
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $rt = json_decode($data, true);
        $rt = $rt['alipay_trade_precreate_response'] ?? array();
        if($rt['code'] == 10000){
            $js['status'] = 1;
            $js['code_url'] = $rt['qr_code'];
            $js['msg'] = $rt['msg'] ?? '';
            return $js;
        }		
        $js['msg'] = $rt['sub_msg'] ?? $rt['msg'] ?? '未知错误';		
        return $js;
    }

    // ------------------ 以下为签名、验签等辅助方法，无需修改 ------------------
    public function buildGetUrl($query=array()){
        if(!is_array($query)){
            //exit;
        }
        //排序参数，
        $data = $this->buildQuery($query);

        // 私钥密码
        $passphrase = '';
        $key_width = 64;

        //私钥
        $privateKey = $this->privateKey;
        $p_key = array();
        //如果私钥是 1行
        if( ! stripos( $privateKey, "\n" )  ){
            $i = 0;
            while( $key_str = substr( $privateKey , $i * $key_width , $key_width) ){
                $p_key[] = $key_str;
                $i ++ ;
            }
        }else{
            //echo '一行？';
        }
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . implode("\n", $p_key) ;
        $privateKey = $privateKey ."\n-----END RSA PRIVATE KEY-----";

        //私钥
        $private_id = openssl_pkey_get_private( $privateKey , $passphrase);

        // 签名
        $signature = '';

        if("RSA2"==$this->sign_type){
            openssl_sign($data, $signature, $private_id, OPENSSL_ALGO_SHA256 );
        }else{
            openssl_sign($data, $signature, $private_id, OPENSSL_ALGO_SHA1 );
        }

        openssl_free_key( $private_id );

        //加密后的内容通常含有特殊字符，需要编码转换下
        $signature = base64_encode($signature);
        $signature = urlencode( $signature );

        $out = $data .'&'. $this->SIGN .'='. $signature;
        return $out ;
    }

    public function buildQuery( $query ){
        if ( !$query ) {
            return null;
        }
        ksort($query);
        $params = array();
        foreach($query as $key => $value){
            $params[] = $key .'='. $value ;
        }
        $data = implode('&', $params);
        return $data;
    }

    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $v = $this->characet($v, $this->postCharset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value) {
        if (!isset($value)) return true;
        if ($value === null) return true;
        if (trim($value) === "") return true;
        return false;
    }

    public function rsaCheckV1($params,$signType='RSA2',$pubKey=''){
        $this->alipayrsaPublicKey = $pubKey;
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        return $this->verify($this->getSignContent($params),$sign,$signType);
    }

    public function rsaCheckV2($params,$signType='RSA2',$pubKey=''){
        $this->alipayrsaPublicKey = $pubKey;
        $sign = $params['sign'];
        $params['sign'] = null;
        return $this->verify($this->getSignContent($params),$sign,$signType);
    }

    function verify($data,$sign,$signType = 'RSA') {
        $pubKey = $this->alipayrsaPublicKey;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        return $result;
    }
}