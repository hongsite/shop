<?php
/**
微信小程序支付
*/
namespace Models\WeixinPay;
class WeixinPay{	
	 public function orderdo($arr,$appsecret){
		// 请求参数
		// 随机字符串  
		
		$appid = $arr['appid'] ?? '';		
		$mch_id = $arr['mchid'] ?? '';
		$openid = $arr['openid'] ?? '';
		$body = $arr['body'] ?? '';
		$out_trade_no = $arr['out_trade_no'] ?? '';
		$total_fee = $arr['total_fee'] ?? 0;
		$notify_url = $arr['notify_url'] ?? '';
		$isprofit = $arr['isprofit'] ?? 0;	
		
		$json['err_code_des'] = '';
		
		if($total_fee<1){
			$json['err_code_des'] = '参数金额错误';
			return $json;
		}
		if($openid==''){
			$json['err_code_des'] = '参数openid错误';
			return $json;
		}	
		 
		$data['appid']=  $appid;
		$data['openid']=  $openid;
		$data['mch_id']= $mch_id;//商户号，输入你的商户号					
		$data['nonce_str']= get_unique_value();//随机字符串
		$data['body']= $body;
		$data['out_trade_no'] = $out_trade_no;

		$data['total_fee'] = $total_fee;
		$data['notify_url'] = $notify_url;
		$data['trade_type'] = 'JSAPI';
		$data['spbill_create_ip']= getIP();	
		if($isprofit==1) $data['profit_sharing'] = 'Y';		 
		  
		// 生成签名  
		//对数据数组进行处理  
		//API密钥,输入自己的K 微信商户号里面的K  
		//$data=array_filter($data);  
		ksort($data);

		$str = ToUrlParams($data);		
		  
		$str .= '&key='.$appsecret;

		
		$data['sign']= MD5($str);
		
		file_put_contents(ROOT.'static/cache/pay.txt',var_export($data,true));
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/pay/unifiedorder";  
	  
		//将请求数据由数组转换成xml
		$xml= arraytoxml($data);
		//进行请求操作  
		$res= postXmlCurl($xml,$url,false,6);
		//将请求结果由xml转换成数组  
		$info= xmlToArray($res);
		return $info;
	}
	//退款申请
	public function refund($arr,$keys){		
		$appid = empty($arr['appid']) ? '':$arr['appid'];		
		$mchid = empty($arr['mchid']) ? '':$arr['mchid'];		
		$out_refund_no = empty($arr['out_refund_no']) ? '':$arr['out_refund_no'];
		$transaction_id = empty($arr['transaction_id']) ? '':$arr['transaction_id'];
		$total_fee = empty($arr['total_fee']) ? 0:$arr['total_fee'];
		$refund_fee = empty($arr['refund_fee']) ? 0:$arr['refund_fee'];
		$refund_desc = empty($arr['refund_desc']) ? '':$arr['refund_desc'];		
		$json['err_code_des'] = '';		
		if($total_fee<1){
			$json['err_code_des'] = '订单金额错误';
			return $json;
		}
		if($refund_fee<1){
			$json['err_code_des'] = '退款金额错误';
			return $json;
		}		 
		$data['appid']=  $appid;
		$data['mch_id']= $mchid;
		$data['nonce_str']= get_unique_value();
		$data['transaction_id'] = $transaction_id;
		$data['out_refund_no'] = $out_refund_no;
		$data['total_fee'] = $total_fee;
		$data['refund_fee'] = $refund_fee;
		$data['refund_desc'] = $refund_desc;	
		ksort($data);
		$str = ToUrlParams($data);		  
		$str .= '&key='.$keys;		
		$data['sign']= MD5($str);	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/secapi/pay/refund";	  
		//将请求数据由数组转换成xml
		$xml= arraytoxml($data);
		//进行请求操作  
		$res= postXmlCurl($xml,$url,true,6);
		//将请求结果由xml转换成数组  
		$info= xmlToArray($res);
		return $info;
	}

	public function native($arr,$keys){
		
		$appid = empty($arr['appid']) ? '':$arr['appid'];		
		$mch_id = empty($arr['mch_id']) ? '':$arr['mch_id'];		
		$body = empty($arr['body']) ? '':$arr['body'];
		$out_trade_no = empty($arr['out_trade_no']) ? '':$arr['out_trade_no'];
		$total_fee = empty($arr['total_fee']) ? 0:$arr['total_fee'];
		$notify_url = empty($arr['notify_url']) ? '':$arr['notify_url'];		
		
		$json['err_code_des'] = '';
		
		if($total_fee<1){
			$json['err_code_des'] = '参数金额错误';
			return $json;
		}		
		 
		$data['appid']=  $appid;		
		$data['mch_id']= $mch_id;//商户号，输入你的商户号					
		$data['nonce_str']= get_unique_value();//随机字符串
		$data['body']= $body;
		$data['out_trade_no'] = $out_trade_no;

		$data['total_fee'] = $total_fee;
		$data['notify_url'] = $notify_url;
		$data['trade_type'] = 'NATIVE';
		$data['spbill_create_ip']= getIP();			 
		  
		// 生成签名  
		//对数据数组进行处理  
		//API密钥,输入自己的K 微信商户号里面的K  
		//$data=array_filter($data);  
		ksort($data);

		$str = ToUrlParams($data);		
		  
		$str .= '&key='.$keys;

		
		$data['sign']= MD5($str);		
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/pay/unifiedorder";  
	  
		//将请求数据由数组转换成xml
		$xml= arraytoxml($data);
		//进行请求操作  
		$res= postXmlCurl($xml,$url,true,6);
		//将请求结果由xml转换成数组  
		$info= xmlToArray($res);
		return $info;
	}
}