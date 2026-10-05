<?php
/*
* @author 牛头
* @function 帮助文档
* @date 2024/7/29
* @version v1.0.0
*/
namespace hst\Authorized;
use hst\Common;
class Authorized extends Common{
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
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
	public function _before_index(){
		$where = '1 ';

		$q = I('q',0);
		$code = I('code',0);
		if($q!=''){
			$where .= ' AND `nickname` like \'%'.$q.'%\'';
		}		
		$this->where = $where;
    }
	public function _after_edit($rt){
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