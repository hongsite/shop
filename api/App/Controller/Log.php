<?php
/*
* @author 牛头
* @function 会员管理
* @date 2018-04-09
* @version v1.0.0
*/
namespace hst\Log;
use hst\Common;
class Log extends Common{   
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
	}
	
    public function _before_index(){
		$where = '1 ';
		$code = I('code',0);
		switch ($code){
			case 'bianji':
				$where .= ' AND `A1`.`tid`=0';
				break;
			case 'shanchu':
				$where .= ' AND `A1`.`tid`=1';
				break;			
		}
		$this->detail = array('Member');
		$this->field = '`A1`.`id`,`A1`.`addtime`,`A1`.`ip`,`A1`.`uid`,`A1`.`aid`,`A1`.`tid`,`A1`.`module_name`,`A2`.`truename`';		
		$this->tbon = 	array('`A1`.`uid`=`A2`.`id`');
		$this->where = $where;
    }
	function _after_index_list($list=null){
		$Ip = D('Ipaddress',1);
		if($list){
			foreach($list as $k=>$v){
				$ip = $v['ip'] ?? 0;
				$ip = long2ip($ip);
				$list[$k]['ip'] = $ip;
				$list[$k]['address'] = $Ip->getaddress($ip);
			}
		}		
		return $list;		
	}
	public function _before_edit(){	
		$this->detail = array('Member');
		$this->field = '`A2`.`truename`';		
		$this->tbon = 	array('`A1`.`uid`=`A2`.`id`');
    }
	
	function _after_edit($rt=null){
		$Ip = D('Ipaddress',1);
		if($rt){
			$ip = $rt['ip'] ?? 0;
			$ip = long2ip($ip);
			$rt['ip'] = $ip;
			$rt['address'] = $Ip->getaddress($ip);
		}		
		return $rt;		
	}
}