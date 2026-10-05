<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 余额
* @date 2022/1/12
* @version hst-v1.0.0
*/
namespace Hst\Fav;
use Hst\Common;
class Fav extends Common {
	function _init(){
		$this->allowindex = true;		
		parent::_init();		
	}
	function _before_index(){
		$uid = getuid();
		$code = I('code',0);
		$q = I('q',0);
		$cid = I('cid',1);		
		$where = '`A1`.`uid`='.$uid.' AND `A2`.`stat`=0';		
		$this->field = '`A1`.`id`,`A2`.`stat`,`A2`.`id` AS `proid`,`A2`.`title`,`A2`.`price`,`A3`.`picurl_0` AS `picurl`,`A3`.`w_0` AS `width`,`A3`.`h_0` AS `height`';
		$this->detail = array('Pro','ProPicurl');
		$this->tbon = array('`A1`.`proid`=`A2`.`id`','`A2`.`id`=`A3`.`id`');
		$this->where = $where;
		$this->order = '`A1`.`id` DESC';
		$this->perpage = 15;
	}	
}
