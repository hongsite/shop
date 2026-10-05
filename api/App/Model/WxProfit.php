<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 收货地址类
* @date 2020/11/9
* @version hst-v1.0.0
*/
namespace Models\WxProfit;
use Model\Model;
class WxProfit extends Model{	
	 public function profitdo($arr,$paykeys,$receivers=''){
		// 请求参数
		// 随机字符串 

		$mch_id = $arr['mchid'] ?? '';		

		
		$appid = $arr['appid'] ?? '';		

		$out_order_no = $arr['out_order_no'] ?? '';
		$transaction_id = $arr['transaction_id'] ?? '';		
		
		$data['mch_id'] = $mch_id;
		$data['appid'] = $appid;
		$data['nonce_str']= get_unique_value();
		$data['sign_type'] = 'HMAC-SHA256';	
		$data['transaction_id']=$transaction_id;
		$data['out_order_no']=$out_order_no;
		$data['receivers']=$receivers;		
		
		ksort($data);
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;

		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));		
		$url="https://api.mch.weixin.qq.com/secapi/pay/profitsharing";	  
		
		$xml= arraytoxml($data);

		//进行请求操作  
		$res= postXmlCurl($xml,$url,true,6); 


		//将请求结果由xml转换成数组  
		$info= xmlToArray($res);
		return $info;
	}
	//完结分账
	public function finish($arr,$paykeys){
		// 请求参数
		// 随机字符串


		$mch_id = $arr['mchid'] ?? '';
		$appid = $arr['appid'] ?? '';
		$description = $arr['description'] ?? '';	

		$out_order_no = $arr['out_order_no'] ?? '';
		$transaction_id = $arr['transaction_id'] ?? '';		
		
		$data['appid'] = $appid;
		$data['mch_id'] = $mch_id;		
		$data['nonce_str']= get_unique_value();   
		$data['transaction_id']=$transaction_id;
        $data['out_order_no']=$out_order_no; 
		$data['sign_type'] = 'HMAC-SHA256';
		$data['description'] = $description;	
		
		ksort($data);
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;	
		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));			
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish";		
	  
		//将请求数据由数组转换成xml  
		$xml= arraytoxml($data);

		//进行请求操作  
		$res= postXmlCurl($xml,$url,true,6); 


		//将请求结果由xml转换成数组  
		$info= xmlToArray($res);
		return $info;
	}
	//解冻全部金额
	public function thaw_remaining($arr,$paykeys){
		// 请求参数
		// 随机字符串
		$mch_id = empty($arr['mch_id']) ? '':$arr['mch_id'];
		$sub_mch_id = empty($arr['sub_mch_id']) ? '':$arr['sub_mch_id'];
		$sub_appid = empty($arr['sub_appid']) ? '':$arr['sub_appid'];
		$description = empty($arr['description']) ? '':$arr['description'];

		
		$appid = empty($arr['appid']) ? '':$arr['appid'];		

		$out_order_no = empty($arr['out_order_no']) ? '':$arr['out_order_no'];
		$transaction_id = empty($arr['transaction_id']) ? '':$arr['transaction_id'];		
		
		$data['appid'] = $appid;
		$data['mch_id'] = $mch_id;		
		$data['nonce_str']= get_unique_value();   
		$data['transaction_id']= $transaction_id;
        $data['out_order_no']=$out_order_no; 
		$data['sign_type'] = 'HMAC-SHA256';	
		
		ksort($data);
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;	
		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));			
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/v3/profitsharing/orders/unfreeze";		
	  
		//将请求数据由数组转换成xml  
		$xml=arraytoxml($data);

		//进行请求操作  
		$res=postXmlCurl($xml,$url,true,6); 


		//将请求结果由xml转换成数组  
		//$info=xmlToArray($res);
		//return $info;
	}

	public function sharing($arr,$paykeys,$receivers=''){
		// 请求参数
		// 随机字符串 
		$mch_id = empty($arr['mch_id']) ? '':$arr['mch_id'];
		$sub_mch_id = empty($arr['sub_mch_id']) ? '':$arr['sub_mch_id'];
		$sub_appid = empty($arr['sub_appid']) ? '':$arr['sub_appid'];
		$brand_mch_id = empty($arr['brand_mch_id']) ? '':$arr['brand_mch_id'];

		
		$appid = empty($arr['appid']) ? '':$arr['appid'];		

		$out_order_no = empty($arr['out_order_no']) ? '':$arr['out_order_no'];
		$transaction_id = empty($arr['transaction_id']) ? '':$arr['transaction_id'];		
		
		$data['appid'] = $appid;
		$data['mch_id'] = $mch_id;
		//$data['sub_mch_id'] = $mch_id;
		$data['nonce_str']=get_unique_value();   
		$data['transaction_id']=$transaction_id;
        $data['out_order_no']=$out_order_no; 
		$data['receivers']=$receivers;
		$data['sign_type'] = 'HMAC-SHA256';	
		
		ksort($data);
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;	
		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));

		//echo '<hr />';
		//dump($data);
		//exit;
			
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/secapi/pay/profitsharing";


		//dump($data);
		//exit;
	  
		//将请求数据由数组转换成xml  
		$xml=arraytoxml($data); 


		//进行请求操作  
		$res=postXmlCurl($xml,$url,true,6); 


		//将请求结果由xml转换成数组  
		$info=xmlToArray($res);
		return $info;
	}

	public function addre($arr,$paykeys,$profitSharingAccounts){
		$mch_id = $arr['mch_id'] ?? '';
		$sub_mch_id = $arr['sub_mch_id'] ?? '';
		$sub_appid = $arr['sub_appid'] ?? '';
		$brand_mch_id = $arr['brand_mch_id'] ?? '';		
		$appid = $arr['appid'] ?? '';		
        $receiver = json_encode($profitSharingAccounts,JSON_UNESCAPED_UNICODE);
		
		
		$data['mch_id'] = $mch_id;
		$data['sub_mch_id'] = $mch_id;
		$data['appid'] = $appid;
		$data['nonce_str'] = get_unique_value(); 
		$data['receiver']=$receiver;
		$data['sign_type'] = 'HMAC-SHA256';
		//$data['sub_appid'] = $appid;
		
		ksort($data);
		
		
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;	
		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));	  
		 
		$url="https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver";
		
	  
		//将请求数据由数组转换成xml  
		$xml = arraytoxml($data); 


		//进行请求操作  
		$res = postXmlCurl($xml,$url,true,6); 

		//print_r($res);


		//将请求结果由xml转换成数组  
		$info = xmlToArray($res);
		return $info;
	}

	public function delre($arr,$paykeys,$profitSharingAccounts){
		$mch_id = empty($arr['mch_id']) ? '':$arr['mch_id'];
		$sub_mch_id = empty($arr['sub_mch_id']) ? '':$arr['sub_mch_id'];
		$sub_appid = empty($arr['sub_appid']) ? '':$arr['sub_appid'];
		$brand_mch_id = empty($arr['brand_mch_id']) ? '':$arr['brand_mch_id'];

		
		$appid = empty($arr['appid']) ? '':$arr['appid'];		
		
        $receiver = json_encode($profitSharingAccounts,JSON_UNESCAPED_UNICODE);	
		
		
		$data['mch_id'] = $mch_id;
		//$data['sub_mch_id'] = $mch_id;
		$data['appid'] = $appid;
		$data['nonce_str']=get_unique_value(); 
		$data['receiver']=$receiver;
		$data['sign_type'] = 'HMAC-SHA256';		
		ksort($data);	
		
		$str = ToUrlParams($data);		
		$str .= '&key='.$paykeys;	
		$data['sign']= strtoupper(hash_hmac("sha256",$str,$paykeys));
	  
		//发起支付  
		$url="https://api.mch.weixin.qq.com/pay/profitsharingremovereceiver";
	  
		//将请求数据由数组转换成xml  
		$xml=arraytoxml($data); 


		//进行请求操作  
		$res=postXmlCurl($xml,$url,true,6); 


		//将请求结果由xml转换成数组  
		$info=xmlToArray($res);
		return $info;
	}	
}