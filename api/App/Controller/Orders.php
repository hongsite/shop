<?php
/*
* @author 牛头
* @function 帮助文档
* @date 2024/7/29
* @version v1.0.0
*/
namespace hst\Orders;
use hst\Common;
class Orders extends Common{
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
		$this->add_update_log = true;
		$this->perpage = 12;
	}

	function _before_index(){
		$where = '`A1`.`deleted`=0';
		$nowt = time();

		$q = I('q',0);
		$hid = I('hid',1);
		$code = I('code',0);
		switch ($code){
			case 'waitPay':
				$where .= ' AND `A1`.`stat`=0';
				break;
			case 'payed':
				$where .= ' AND `A1`.`stat`=1';
				break;
			case 'waitSh':
				$where .= ' AND `A1`.`stat`=2';
				break;
			case 'close':
				$where .= ' AND `A1`.`stat`=9';
				break;
			case 'cancel':
				$where .= ' AND `A1`.`stat`=4';
				break;
			case 'success':
				$where .= ' AND `A1`.`stat`=3';
				break;
			case 'refund':
				$where .= ' AND `A1`.`stat`=8';
				break;
			default:
				//
		}
		if($q!=''){
			if(strlen($q)==4 && is_numeric($q)){
				$where .= ' AND RIGHT(`A1`.`lxdh`,4) = \''.$q.'\'';
			}else if(checkMobile($q)){
				$where .= ' AND `A1`.`lxdh` = '.$q;
			}else{
				$where .= ' AND `A1`.`company` like \'%'.$q.'%\'';
			}
		}
		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`allprice`,`A1`.`payprice`,`A1`.`shipping`,`A1`.`discount`,`A1`.`addtime`,`A1`.`ordid`,`A1`.`stat`';
		$this->where = $where;		
	}
	function _after_index_list($list=null){
		if($list){
			$m = M('OrdersDetails');
			foreach($list as $k=>$v){
				$id = $v['id'] ?? 0;
				$detail = $m->field('id,title,quantity,price,xjprice,sku,picurl')->where('ordid='.$id.' AND deleted=0')->limit('100')->select();
				$list[$k]['detail'] = $detail;
			}
		}		
		return $list;		
	}
	public function _after_edit($rt=null){
		$id = $rt['id'] ?? 0;
		$address = D('Address')->getordersaddress($id);
		$rt['truename'] = $address['truename'] ?? '';
		$rt['mobile'] = $address['mobile'] ?? '';
		$rt['address'] = $address['address'] ?? '';
		$rt['county'] = $address['county'] ?? '';
		$rt['countyid'] = $address['countyid'] ?? '';
		$rt['city'] = $address['city'] ?? '';
		$rt['cityid'] = $address['cityid'] ?? '';
		$rt['province'] = $address['province'] ?? '';
		$rt['provinceid'] = $address['provinceid'] ?? '';
		return $rt;
	}
	
	public function _after_edit_json($id=0,$js){
		$mm = M('OrdersDetails');
		$detail = $mm->field('id,title,quantity,price,xjprice,sku,picurl')->where('ordid='.$id.' AND deleted=0')->limit(100)->select();
		$js['detail'] = $detail;
		return $js;
	}
}