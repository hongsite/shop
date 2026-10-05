<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 微信二维码生成
*@date 2018-06-23
*@version hst-v1.0.0
*/
namespace Models\Logistics;
class Logistics{
	function detectExpressCompany($trackingNumber) {
		$trackingNumber = trim($trackingNumber);
		$ztoPatterns = [
			'/^[0-9]{10,12}$/',
			'/^7[0-9]{9}$/',
			'/^4[0-9]{11}$/',
			'/^[0-9]{15}$/', 
			'/^ZTO[0-9]{10,12}$/i',
		];
		
		// 顺丰快递单号规则
		$sfPatterns = [
			'/^SF[0-9]{13}$/i',
			'/^[0-9]{12}$/',
			'/^[0-9]{15}$/',
			'/^118[0-9]{8}$/',
			'/^77[0-9]{9}$/',
		];
		
		// 检查中通
		foreach ($ztoPatterns as $pattern) {
			if (preg_match($pattern, $trackingNumber)) {
				return '中通快递';
			}
		}
		
		// 检查顺丰
		foreach ($sfPatterns as $pattern) {
			if (preg_match($pattern, $trackingNumber)) {
				return '顺丰快递';
			}
		}
		
		return '未知快递';
	}
	//crontab
	function crontab_json(){
		$mytime = time();
		$m = M('Logistics');
		$where = 'logistics_status!=\'SIGN\' AND logistics_status!=\'AGENT_SIGN\' AND logistics_status!=\'FAILED\' AND logistics_uptime<'.($mytime-3600*6);		
		$list = $m->field('id,lsn,mobile,logistics_status')->where('tid=1 '.$where)->limit(5)->select();
		if(!$list){
			echo '已全部更新';
			exit;
		}
		if($list){
			foreach($list as $k=>$v){
				$this->upwlord($v['lsn'],$v['mobile'],$v['id'],$m);
			}
			//echo '更新list数量：'.count($list);
			//echo '<br />';
		}		
		echo '更新完成';
		
		//echo M('Book')->getlastsql();
	}
	function lsninfo($lsn='',$mobile=0){		
		$js['status'] = false;
		$js['msg'] = '';
		$js['iscache'] = 0;
		$nowt = time();		
		if($lsn==''){
			$js['msg'] = '数据不存在';
			return $js;
		}
		$rt = M('Logistics')->where('lsn=\''.$lsn.'\'')->find();
		if(!$rt){
			$js['msg'] = '数据不存在，请先更新数据';
			return $js;
		}		
		$id = $rt['id'];
		if(strlen($mobile)==11){
			$mobile = substr($mobile,-4);
		}
		
		$js['status'] = true;
		$js['ordinfo'] = $rt;
		$js['mobile'] = $mobile;

		if(!preg_match('/^[A-Za-z0-9]{6,30}$/',$lsn)){
			$js['msg'] = '无效快递单号';
			return $js;
		}

		$logistics = $rt['logistics'] ?? '';
		$logistics_status = $rt['logistics_status'] ?? '';
		$logistics_uptime = $rt['logistics_uptime'] ?? 0;
		if($logistics_status=='SIGN'||$logistics_status=='AGENT_SIGN'){
			$js['iscache'] = 1;
			$js['data'] = json_decode($logistics,true);
			return $js;
		}

		$kdcom = $this->detectExpressCompany($lsn);
		if(($kdcom=='顺丰快递' || $kdcom=='中通快递') && $mobile==0){
			$js['msg'] = '请输入手机尾号';
			return $js;
		}		
		$info = $this->wuliuapi($lsn,$mobile);
		$logistics = json_encode($info,JSON_UNESCAPED_UNICODE);
		$logistics_data = $info['data'] ?? [];
		$logistics_status = $logistics_data['logisticsStatus'] ?? '';
		$lsnname = $logistics_data['logisticsCompanyName'] ?? '';
		$theLastMessage = $logistics_data['theLastMessage'] ?? '';
		$data = null;
		$data['logistics'] = $logistics;
		$data['logistics_uptime'] = $nowt;
		$data['logistics_status'] = $logistics_status;
		$data['lsnname'] = $lsnname;
		$data['theLastMessage'] = $theLastMessage;
		M('Logistics')->where('id='.$id)->save($data);
		$js['data'] = $info;
		return $js;	
	}
	public function upwlord($lsn,$mobile,$id=0){
		$info = $this->wuliuapi($lsn,$mobile);
		$logistics = json_encode($info,JSON_UNESCAPED_UNICODE);
		$logistics_data = $info['data'] ?? [];
		$logistics_status = $logistics_data['logisticsStatus'] ?? '';
		$lsnname = $logistics_data['logisticsCompanyName'] ?? '';
		$theLastMessage = $logistics_data['theLastMessage'] ?? '';
		$nowt = time();
		$data = null;
		$logistics_status=='' && $logistics_status = 'FAILED';
		$data['logistics'] = $logistics;
		$data['logistics_uptime'] = $nowt;
		$data['logistics_status'] = $logistics_status;
		$data['lsnname'] = $lsnname;
		$data['theLastMessage'] = $theLastMessage;
		M('Logistics')->where('id='.$id)->save($data);
	}
	private function getordinfo($id=0){

		$where = '`A1`.`id`='.$id.' AND sid='.$this->sid;
		$field = '`A2`.`title` AS `county`,`A3`.`id` AS `cityid`,`A3`.`title` AS `city`,`A4`.`id` AS `provinceid`,`A4`.`title` AS `province`';
		$arr['name'] = array('Book','Area','Area','Area');			
		$arr['on'] = array('`A1`.`countyid`=`A2`.`id`','`A2`.`pid`=`A3`.`id`','`A3`.`pid`=`A4`.`id`');
		$arr['field'] = $field;
		$arr['where'] = $where;
		$arr['limit'] = 1;			
		$obj = D('SubTab',1);
		$rt = $obj->mysql_list($arr);
		return $rt;
	}


	function wuliuapi(string $num = '', string $mobile = ''): array{
		$result = [
			'status' => false,
			'code' => 0,
			'msg' => '',
			'data' => null
		];
		
		// 参数验证
		if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $num)) {
			$result['msg'] = '无效快递单号';
			return $result;
		}		
		
		// 配置信息
		$appcode = '5ace5a0489e740f99b795b6f2eddcc46';
		$baseUrl = 'https://kzexpress.market.alicloudapi.com/api-mall/api/express/query';
		
		// 构建请求URL
		$queryParams = ['expressNo' => $num];
		if ($mobile!='') {
			$queryParams['mobile'] = $mobile;
		}
		
		$url = $baseUrl . '?' . http_build_query($queryParams);
		
		// 请求头
		$headers = [
			"Authorization: APPCODE " . $appcode,
			"Content-Type: application/json; charset=utf-8"
		];
		
		// cURL配置
		$curlOptions = [
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_USERAGENT => 'Mozilla/5.0 ( compatible )'
		];

		
		$ch = curl_init();
		curl_setopt_array($ch, $curlOptions);
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		$curlErrno = curl_errno($ch);
		
		curl_close($ch);
		
		// 错误处理
		if ($curlErrno !== 0) {
			$result['code'] = $curlErrno;
			$result['msg'] = "cURL请求失败: {$curlError}";
			return $result;
		}
		
		// HTTP状态码处理
		if ($httpCode >= 400) {
			$result['code'] = $httpCode;
			$result['msg'] = "API请求失败，HTTP状态码: {$httpCode}";
			return $result;
		}
		
		// 成功处理
		$result['status'] = true;
		$result['code'] = $httpCode;
		
		$response = str_replace('\'','\'\'',$response);

		$result = json_decode($response,true);		
		return $result;
	}
}