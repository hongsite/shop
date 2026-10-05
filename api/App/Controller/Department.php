<?php
/*
* @author 牛头
* @function 会员管理
* @date 2018-04-09
* @version v1.0.0
*/
namespace hst\Department;
use hst\Common;
class Department extends Common{   
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
	}
	
   public function _before_index(){
		$where = '`A1`.`deleted`=0 ';

		$q = I('q',0);
		$code = I('code',0);
		if($q!=''){
			$where .= ' AND `A1`.`title` like \'%'.$q.'%\'';
		}		
		
		$this->detail = array('Member');
		$this->field = '`A2`.`username`';		
		$this->tbon = 	array('`A1`.`headid`=`A2`.`id`');
		$this->order = '`A1`.`sort` ASC';
		$this->where = $where;
    }
	function _after_index_list($list=null){
		if($list){
			$m = M('Member');
			foreach($list as $k=>$v){
				$nums = $m->where('departmentid='.$v['id'].' AND deleted=0')->count();				
				$list[$k]['nums'] = $nums;
			}
		}		
		return $list;		
	}
	public function _before_edit(){		
		$this->detail = array('Member');
		$this->field = '`A2`.`username`';		
		$this->tbon = 	array('`A1`.`headid`=`A2`.`id`');
    }	
	function headlist_json(){
		$js = null;
		$js['status'] = 0;
		$js['msg'] = '';
		$q = I('q',0);

		$where = '1';
		if($q!=''){
			if(strlen($q)==4 && is_numeric($q)){
				$where .= ' AND RIGHT(`A1`.`mobile`,4) = \''.$q.'\'';
			}else if(checkMobile($q)){
				$where .= ' AND `A1`.`mobile` = '.$q;
			}else{
				$where .= ' AND (`A1`.`truename` like \'%'.$q.'%\')';
			}
		}
		$field = '`A1`.`id`,`A1`.`username`,`A1`.`truename`';
		$arr['name'] = array('Member');	
		$arr['field'] = $field;
		$arr['where'] = $where;
		$arr['limit'] = 20;			
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);
		if($list){
			$js['status'] = 1;
			$js['data'] = $list;
		}
		$this->json($js);
	}
	public function _after_update_vo(){	
		$this->detail = array('Member');
		$this->field = '`A2`.`truename` AS `header`';		
		$this->tbon = 	array('`A1`.`headid`=`A2`.`id`');
    }
}