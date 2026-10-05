<?php
/*
*@author 牛头
*@company 鸿思特科技
*@function 支付类
*@date 2025/11/11
*@version hst-v1.0.0
*/
namespace Models\Orders;
use Model\Model;
class Orders extends Model{	
	public function orderhandle($data,$detailtbid=1){
		$js = null;
		$js['status'] = false;
		$js['msg'] = '';
		$ordid= $data['out_trade_no'] ?? '';
		$paytype= $data['paytype'] ?? 0;
		$transaction_id = $data['transaction_id'] ?? '';
		$m = M('Orders');
		$nowt = time();		
		$rt = $m->field('id,title,stat,ordid,trantype,uid,packageid,payprice')->where('ordid=\''.$ordid.'\'')->find();
		if(!$rt){
			$js['msg'] = '数据不存在';
			return $js;
		}
		$title = $rt['title'] ?? '';
		$stat = $rt['stat'] ?? 0;
		$id = $rt['id'] ?? 0;
		$packageid = $rt['packageid'] ?? 0;
		$trantype = $rt['trantype'] ?? 0;
		$payprice = $rt['payprice'] ?? 0;
		$uid = $rt['uid'] ?? 0;
		if($stat!=0){
			$js['msg'] = '可能已支付'.$stat;
			return $js;
		}
		$data['stat'] = 1;
		$data['paytype'] = $paytype;
		$rtcmd = $m->where('id='.$id)->save($data);
		if($rtcmd===false){
			$js['msg'] = '保存失败';
			return $js;
		}
		$agent = null;
		switch ($trantype){			
			case 0:
				$agent = $this->addAgentOrders($id,$uid,$payprice);
				break;			
			case 1:
				break;		
			
		}		
		$mp = M('OrdersPay');
		$datap['id'] = $id;
		$datap['out_trade_no'] = $ordid;
		$datap['transaction_id'] = $transaction_id;
		$datap['paytime'] = $nowt;
		$mp->add($datap);
		$js['datap'] = $datap;
		$js['agent'] = $agent;
		return $js;	

	}
	private function addAgentOrders($id=0,$uid=0,$payprice=0){
		$m = M('Member');
		$rt = $m->field('`id`,`levelid`')->where('`id`='.$uid)->find();
		if(!$rt) return false;
		$levelid = $rt['levelid'] ?? '';
		if($levelid=='') return false;
		$arr = explode(',',$levelid);
		if(!$arr) return false;
		$nowt = time();
		$pricearr = $this->caclAgentPrice($payprice,$levelid);
		$nums = $pricearr;
		$data = null;
		foreach($arr as $k=>$v){
			$rate = isset($pricearr[$k]) ? floatval($pricearr[$k]) : 0;
			$data[$k]['ordid'] = $id;
			$data[$k]['price'] = $payprice*$rate/100;
			$data[$k]['uid'] = $v;
			$data[$k]['addtime'] = $nowt;
			$data[$k]['rate'] = $rate;
		}
		if($data){
			//print_r($data);
			M('OrdersAgent')->addAll($data);
		}
		return $data;
	}

	public function caclAgentPrice($allprice=0,$levelid=''){

		$allprice = $allprice;

		if($levelid=='') return array(0,0,0);
		$arr = array_values(array_filter(array_map('trim', explode(',', $levelid)), function ($v) {
			return $v!=='';
		}));		
		$count = count($arr);		
		$baseRates = [5,3,2];
		if ($count === 1) {
			return array($baseRates[0]+$baseRates[1]+$baseRates[2]);
		}elseif ($count === 3) {
			return array($baseRates[0],$baseRates[1],$baseRates[2]);
		}elseif ($count === 2){			
			$firstRate = $baseRates[0];
			$addToSecond = $baseRates[1]/$baseRates[0];
			$addToThird  = $firstRate-$addToSecond;
			$rates = [
				$baseRates[1] + $addToSecond,
				$baseRates[2] + $addToThird
			];
			return $rates;
		}else {
			return array(0,0,0);
		}		
	}


	private function addviptime($sid=0,$uid=0,$str='',$price=0){
		$nowt = time();
		$m = M('Shop');
		$rt = $m->field('id,vip_etime')->where('id='.$sid)->find();
		$yvip_etime = $rt['vip_etime'] ?? 0;
		if(!$rt) return false;
		$datavip = null;
		if($yvip_etime<$nowt){
			$datavip['vip_btime'] = $nowt;
			$vip_etime = strtotime($str,$nowt);
			
		}else{
			$datavip['vip_btime'] = $yvip_etime;
			$vip_etime = strtotime($str,$yvip_etime);
		}
		$datavip['vip_etime'] = $vip_etime;
		$datavip['addtime'] = $nowt;
		$datavip['price'] = $price;
		$datavip['sid'] = $sid;
		$datavip['uid'] = $sid;
		$datavip['str'] = $str;
		$data = null;
		$data['vip_etime'] = $vip_etime;
		$rtcmd = $m->where('id='.$uid)->save($data);
		if($rtcmd!==false){
			
			
			M('ShopBuyLog')->add($datavip);
		}
	}
}
?>