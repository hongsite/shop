<?php
/*
* @author 牛头
* @function 会员管理
* @date 2018-04-09
* @version v1.0.0
*/
namespace hst\Job;
use hst\Common;
class Job extends Common{   
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
	}
	
    public function _before_index(){
		$where = 'deleted=0 ';
		$q = I('q',0);
		$code = I('code',0);
		if($q!=''){
			$where .= ' AND `title` like \'%'.$q.'%\'';
		}
		$this->order = 'sort ASC';
		$this->where = $where;
			
    }
	public function _before_update(){	

		$privilegeid = I('privilegeid',0,'post');
		if($privilegeid){
			$_POST['privilegeid'] = implode(',',$privilegeid);
		}else{
			$_POST['privilegeid'] = '';
		}
		return true;
	}
	function _after_index_list($list=null){
		if($list){
			$m = M('Member');
			foreach($list as $k=>$v){
				$nums = $m->where('jobid='.$v['id'].' AND deleted=0')->count();				
				$list[$k]['nums'] = $nums;
			}
		}		
		return $list;		
	}
}