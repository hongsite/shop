<?php
//管理员
namespace Models\Admin;
use Model\Model;
class Admin extends Model {
	public function getUserinfo($uid){		
		return $this->getValue($uid,1);
	}
	public function getValue($uid=0,$limit=1){
		$arr['name'] = array('Admin');
		
		//$arr['db_server'] = 15;
		$arr['field'] = '';
		$arr['where'] = '`A1`.`id`='.$uid;
		$arr['fenbiao'] = 0;
		$arr['limit'] = $limit;
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);
		return $list;
	}
	//设置角色
	public function setValue($uid,$arr){
		$mm = M('Admin');
		$rt = $mm->where('id='.$uid)->find();
		if(!$rt){
			return false;
		}
		$m = M('RoleUser');
		if(!$arr){
			$rtcmd = $m->where('user_id='.$uid)->delete();
			return false;
		}
		
		$str = implode(',',$arr);
		$rtcmd = $m->where('user_id='.$uid.' AND (role_id not in('.$str.'))')->delete();
		foreach($arr as $key=>$v){
			$rtcomm = $m->where('user_id='.$uid.' AND role_id='.$v)->find();			
			if(!$rtcomm){
				$data = null;
				$data['user_id'] = $uid;
				$data['role_id'] = $v;
				$m->add($data);
			}
		}
	}
}
?>