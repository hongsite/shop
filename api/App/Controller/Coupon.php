<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 余额
* @date 2022/1/12
* @version hst-v1.0.0
*/
namespace Hst\Coupon;
use Hst\Common;
class Coupon extends Common {
	function _init(){
		$this->allowindex = true;		
		parent::_init();		
	}
	function _before_index(){
		$code = I('code',0);
		$q = I('q',0);
		$cid = I('cid',1);		
		$where = '`A1`.`uid`='.$this->uid;		
		$this->field = '`A1`.`id`,`A1`.`title`,`A1`.`stat`,`A1`.`price`,`A1`.`stat`,`A1`.`start_time`,`A1`.`end_time`,`A1`.`man`';
		$this->where = $where;
		$this->order = '`A1`.`id` DESC';
		$this->perpage = 15;
	}	
}
