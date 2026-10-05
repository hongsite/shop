<?php
/*
* @author 牛头
* @function 帮助文档
* @date 2024/7/29
* @version v1.0.0
*/
namespace hst\OrdersAgent;
use hst\Common;
class OrdersAgent extends Common{
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
		$this->add_update_log = true;
		$this->perpage = 16;
	}

	function _before_index(){
		$uid = getuid();
		$where = '1';
		if($this->ismanager!==true) $where .= ' AND `A1`.`uid`='.$uid;
		$nowt = time();
		$q = I('q',0);
		$hid = I('hid',1);
		$code = I('code',0);
		switch ($code){
			case 'settled':
				$where .= ' AND settled=1';
				break;
			case 'unsettled':
				$where .= ' AND settled=0';
				break;
		}
		$this->field = '`A1`.`id`,`A1`.`price`,`A1`.`addtime`,`A1`.`settled`,`A2`.`title`,`A2`.`ordid`,`A3`.`username`,`A4`.`username` AS `syr`,`A4`.`truename` AS `syrtruename`,`A2`.`stat`';
		$this->detail = array('Orders','Member','Member');
		$this->tbon = array('`A1`.`ordid`=`A2`.`id`','`A2`.`uid`=`A3`.`id`','`A1`.`uid`=`A4`.`id`');
		$this->where = $where;
	}
	function _after_index_list($list=null){
		if($list){
			foreach($list as $k=>$v){
				$username = $v['username'] ?? '';
				$username = hidePhone($username);
				$list[$k]['title'] = '用户：'.$username.'的订单奖励收入';
				$list[$k]['username'] = $username;
			}
		}		
		return $list;		
	}

}