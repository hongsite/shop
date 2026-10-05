<?php
/*
* @author 牛头
* @function 首页
* @date 2022/03/14
* @version v1.0.0
*/
namespace Hst\Jifen;
use Hst\Common;
class Jifen extends Common{	
    public function _init() {
        parent::_init();		
		$this->allowindex = true;
    }
	function _before_index(){
		$uid = getuid();
		$sort = I('sort',0);
		$q = I('q',0);
		$cid = I('cid',1);
		if($sort=='') $sort = '`A1`.`id` DESC';
		switch ($sort){
			case '':
				$sort = '`A1`.`id` DESC';
				break;
			case 'sale-desc':
				$sort = '`A1`.`saled` DESC';
				break;
			case 'price-asc':
				$sort = '`A1`.`price` ASC';
				break;
			case 'price-desc':
				$sort = '`A1`.`price` DESC';
				break;
		}
		$where = '`A1`.`uid`='.$uid;
		if($q!='') $where .= ' AND `A1`.`title` like \'%'.$q.'%\'';
		if($cid>0) $where .= ' AND `A1`.`cid` in('.$cid.')';
		$this->field = '`A1`.`id`,`A1`.`addtime`,`A1`.`jifen`,`A1`.`remark`';		
		$this->where = $where;
		$this->order = $sort;
		$this->perpage = 15;
	}
	
}