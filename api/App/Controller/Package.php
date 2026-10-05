<?php
/*
* @author 牛头
* @function 新闻
* @date 2022/11/18
* @version v1.0.0
*/
namespace hst\Package;
use hst\Common;
class Package extends Common{	
	function _init(){
		parent::_init();	
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
		$searinfo[] = array('field'=>'username');
		$this->seararr=$searinfo;
	}

	
	function _before_update(){
		$_POST['price'] *= 100;		
		return true;		
	}

	function _after_update($id=0){		
		$where = 'stat=1';
		$arr['tb'] = 'Package';
		$arr['where'] = $where;
		$arr['order'] = 'sort ASC';
		$arr['filename'] = ROOT.'static/cache/packagelist.php';
		$arr['timeout'] = 3600;
		$arr['limit'] = 10;
		$arr['force'] = true;
		createcachelist($arr);
	}
	function _after_batch(){		
		$where = 'stat=1';		
		$arr['tb'] = 'Package';
		$arr['where'] = $where;
		$arr['order'] = 'sort ASC';
		$arr['filename'] = ROOT.'static/cache/packagelist.php';
		$arr['timeout'] = 3600;
		$arr['limit'] = 10;
		$arr['force'] = true;
		createcachelist($arr);

		$payremark = I('payremark',0,'post');		
		$data['payremark'] = $payremark;
		M('Website')->where('id=1')->save($data);
		loadcache(2,true);	
	}
	
	
	function _before_index(){

		$web = M('Website')->where('id=1')->find();

		$code = I('code',0,'');
		$title = I('title',0,'');
		$stat = I('stat',0,'');
		$where = '1 ';
		switch ($code){
			case 'nofb':
				$where .= ' AND `stat`=0';
				break;
			case 'fbed':
				$where .= ' AND `stat`=1';
				break;
		}
		if($title!=''){
			$where .= ' AND `title` like \'%'.$title.'%\'';
		}		

		$this->order = '`sort` ASC';
		$this->field = '';
		$this->noload = 1;
		$this->where = $where;
	}
}