<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 收货地址类
* @date 2020/11/9
* @version hst-v1.0.0
*/
namespace Models\Address;
use Model\Model;
class Address extends Model{
	public function getlist($uid=0,$id=0){		
		if($uid==0) return false;
		
		$tb = 'Address';

		$ord = '`A1`.`isdefault` DESC,`A1`.`id` DESC';

		$limit = 1;

		$where = '`A1`.`uid`='.$uid;
		
		if($id==0){
			$limit = 20;			
		}else if($id==-1){				
			$where .= ' AND `A1`.`isdefault`=1';
		}else{				
			$where .= ' AND `A1`.`id`='.$id;
		}		

		$name = getcarttb($uid,'Address');

		$field = '`A1`.`id`,`A1`.`isdefault`,`A1`.`truename`,`A1`.`mobile`,`A1`.`address`';
		$field .= ',`A2`.`title` AS `county`,`A3`.`title` AS `city`,`A4`.`title` AS `province`';
		$field .= ',`A2`.`id` AS `countyid`,`A3`.`id` AS `cityid`,`A4`.`id` AS `provinceid`';

		$arr['name'] = array($name,'Area','Area','Area');
		$arr['field'] = $field;
		$arr['on'] = array('`A1`.`countyid`=`A2`.`id`','`A2`.`pid`=`A3`.`id`','`A3`.`pid`=`A4`.`id`');
		$arr['nosub'] = 1;
		$arr['order'] = $ord;
		
		$arr['limit'] = $limit;
		$arr['where'] = $where;
		$arr['sub_table_nums'] = 0;
		$arr['db_server'] = 0;
		$obj = D('SubTab');
		$list = $obj->mysql_list($arr);
		return $list;
	}

	public function updateSetDefault($uid=0){
		$name = 'Address';
		$tb = getcarttb($uid,'Address');
		$m = M($tb);
		$nums = $this->getDefaultNums($uid);
		if($nums==1) return false;			
		$where = 'uid='.$uid;
		$rt = $m->where($where)->order('id DESC')->field('id,uid')->find();
		if(!$rt) return false;
		$this->setDefault($uid,$rt['id']);
	}	

	public function setDefault($uid=0,$id=0,$isdel=true){
		$tb = getcarttb($uid,'Address');
		$m = M($tb);
		$nums = $this->getDefaultNums($uid);
		if($isdel===true && $nums>0){
			$where = 'uid='.$uid;
			$m->where($where)->setField('isdefault',0);
		}
		$rtcmd = $m->where('id='.$id)->setField('isdefault',1);
		return $rtcmd;
	}
	public function getDefaultNums($uid=0){
		$where = 'uid='.$uid;
		$name = 'Address';
		$tb = getcarttb($uid,'Address');
		$m = M($tb);
		$nums = $m->where($where.' AND isdefault=1')->count();
		return $nums;
	}

	public function getordersaddress($id=0){		
		if($id==0) return false;
		$where = '`A1`.`id`='.$id;			
		$field = '`A1`.`id`,`A1`.`truename`,`A1`.`mobile`,`A1`.`address`';
		$field .= ',`A2`.`title` AS `county`,`A2`.`id` AS `countyid`,`A3`.`title` AS `city`,`A3`.`id` AS `cityid`,`A4`.`title` AS `province`';
		$field .= ',`A4`.`id` AS `provinceid`';

		$arr['name'] = array('OrdersAddress','Area','Area','Area');
		$arr['field'] = $field;
		$arr['on'] = array('`A1`.`countyid`=`A2`.`id`','`A2`.`pid`=`A3`.`id`','`A3`.`pid`=`A4`.`id`');
		
		
		$arr['limit'] = 1;
		$arr['where'] = $where;
		$obj = D('SubTab',1);
		$rt = $obj->mysql_list($arr);
		return $rt;
	}

	

}
?>