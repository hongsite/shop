<?php
/*
* @author 牛头
* @function 角色
* @date 2018-04-11
* @version v1.0.0
*/
namespace Models\Role;
use Model\Model;
class Role extends Model {    
	public function getMenuList($pid=0,$aid=0){
		$cachepath = ROOT.'static/cache/admin/menu/user'.$aid;
		creatdir($cachepath);
		$fname = $cachepath.'/menu_'.$pid.'.php';
		
		

		if($aid!=1 && $pid>0){
			
			$field = '`A1`.*';

			$where = '`A1`.`pid`='.$pid.' AND `A3`.`user_id`='.$aid;
			$ord = '`A1`.`sort` ASC';


			$tb[] = 'MenuClass';
			$tb[] = 'RoleMenu_map';
			$tb[] = 'RoleUser';

			$tbon[] = '`A1`.`id`=`A2`.`menu_id`';
			$tbon[] = '`A2`.`role_id`=`A3`.`role_id`';

			$arr['name'] = $tb;
			$arr['field'] = $field;
			$arr['db_server'] = 0;
			$arr['on'] = $tbon;
			$arr['where'] = $where;		
			$arr['limit'] = 500;
			$arr['order'] = $order;
			$obj = D('SubTab',1);
			$list = $obj->mysql_list($arr);
			return $list;
			
		}else{
			$list = M('MenuClass')->where('pid='.$pid)->order('sort asc')->select();
		}
		return $list;
	}
	public function getroleuser($role_id){		
		$tb[] = 'admin';
		$tb[] = 'role_user';
		$tb[] = 'role';
		
		$tbon[] = '`A1`.`id`=`A2`.`user_id`';
		$tbon[] = '`A2`.`role_id`=`A3`.`id`';

		$where = '`A2`.`role_id`='.$role_id;
		$field = '`A1`.`id`,`A1`.`truename` AS `title`';
		$arr['name'] = $tb;			
		$arr['on'] = $tbon;
		$arr['field'] = $field;
		$arr['where'] = $where;
		$arr['limit'] = 1000;
		$arr['sub_table_nums'] = 0;
		$arr['db_server'] = 0;
		$obj = D('SubTab');
		$list = $obj->mysql_list($arr);		
		return $list;
	}
	//获取指定用户组的
	public function getroleuid($role_id=''){
		$where = '`A3`.`id` in('.$role_id.')';
		$field = '`A1`.`id`,`A1`.`truename` AS `title`';
		$arr['name'] = array('Admin','RoleUser','Role');			
		$arr['on'] = array('`A1`.`id`=`A2`.`user_id`','`A2`.`role_id`=`A3`.`id`');
		$arr['field'] = $field;
		$arr['where'] = $where;		
		$arr['limit'] = 100;
		$arr['sub_table_nums'] = 0;
		$arr['db_server'] = 0;
		$obj = D('SubTab');
		$list = $obj->mysql_list($arr);

		if(!$list) return false;

		foreach($list as $k=>$v){
			$lists[] = $v['id'];
		}

		return $lists;
	}

}