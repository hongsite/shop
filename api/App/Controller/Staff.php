<?php
/*
* @author 牛头
* @function 会员管理
* @date 2018-04-09
* @version v1.0.0
*/
namespace hst\Staff;
use hst\Common;
class Staff extends Common{   
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
		$this->tb = 'Member';
	}
	
    public function _before_index(){
		$where = '`A1`.`deleted`=0 ';

		$q = I('q',0);
		$code = I('code',0);
		if($q!=''){
			if(strlen($q)==4 && is_numeric($q)){
				$where .= ' AND RIGHT(`A1`.`mobile`,4) = \''.$q.'\'';
			}else if(checkMobile($q)){
				$where .= ' AND `A1`.`mobile` = '.$q;
			}else{
				$where .= ' AND `A1`.`truename` like \'%'.$q.'%\'';
			}
		}
		switch ($code){
			case 'yuangong':
				$where .= ' AND `A1`.`roleid`=1';
				break;
			case 'manager':
				$where .= ' AND `A1`.`roleid`=5';
				break;
			case 'admin':
				$where .= ' AND `A1`.`roleid`=9';
				break;
			case 'myremark':
				$where .= ' AND `A1`.`remark`!=\'\'';
				break;
		}
		
		$this->detail = array('MemberDetail','Department','Job');
		$this->field = '`A1`.`id`,`A1`.`username`,`A1`.`roleid`,`A1`.`truename`';
		$this->field .= ',`A2`.`nickname`,`A2`.`wxface`,`A2`.`sex`,`A2`.`addtime`,`A2`.`lasttime`,`A2`.`ip`,`A2`.`lastip`';
		$this->field .= ',`A3`.`title` AS `department`,`A4`.`title` AS `job`';		
		$this->tbon = 	array('`A1`.`id`=`A2`.`id`','`A1`.`departmentid`=`A3`.`id`','`A1`.`jobid`=`A4`.`id`');
		$this->order = '`A1`.`id` DESC';
		$this->where = $where;
    }
	function _after_index_list($list=null){
		$Ip = D('Ipaddress',1);
		if($list){
			foreach($list as $k=>$v){
				$ip = $v['ip'] ?? 0;
				$lastip = $v['lastip'] ?? 0;
				$ip = long2ip($ip);
				$lastip = long2ip($lastip);
				$list[$k]['ip'] = $ip;
				$list[$k]['lastip'] = $lastip;
				$list[$k]['address'] = $Ip->getaddress($ip);
				$list[$k]['lastaddress'] = $Ip->getaddress($lastip);
			}
		}		
		return $list;		
	}	
	function _after_edit_json($id=0,$js=null){
		$js['department_list'] = M('Department')->field('id,title')->where('deleted=0')->order('sort ASC')->select();
		$js['job_list'] = M('Job')->field('id,title')->where('deleted=0')->order('sort ASC')->select(); 
		return $js;
	}
	public function _before_edit(){	
		$this->detail = array('Department','Authorized');
		$this->field = '`A2`.`title` AS `department`,`A3`.`nickname` AS `wxnickname`,IFNULL(`A3`.`face`,\'\') AS `face`';		
		$this->tbon = 	array('`A1`.`departmentid`=`A2`.`id`','`A1`.`wxid`=`A3`.`id`');
    }
	public function _after_update_vo(){	
		$this->detail = array('Department','Job','Authorized');
		$this->field = '`A2`.`title` AS `department`,`A3`.`title` AS `job`,`A4`.`nickname` AS `wxnickname`,IFNULL(`A4`.`face`,\'\') AS `face`';		
		$this->tbon = array('`A1`.`departmentid`=`A2`.`id`','`A1`.`jobid`=`A3`.`id`','`A1`.`wxid`=`A4`.`id`');
    }
	function _after_edit($rt=null){
		$Ip = D('Ipaddress',1);
		if($rt){
			$ip = $rt['ip'] ?? 0;
			$rt['address'] = $ip>0 ? $Ip->getaddress(long2ip($ip)):'';
		}		
		return $rt;		
	}
	public function _before_update(){

		$password = isset($_POST['password']) ? $_POST['password']:'';
		if(strlen($password)<4){
		    unset($_POST['password']);
			unset($_POST['rndstr']);
			unset($_POST['encmd5']);
		}else{
		    $rndstr = genrndstr(10);			
			$password = md5($password);
			$_POST['rndstr'] = $rndstr;
			$_POST['password'] = $password;
			$_POST['encmd5'] = md5($password.$rndstr);			
		}
		$username = I('username',1,'post');
		$truename = I('truename',0,'post');
		if(!checkMobile($username)){
			return '请输入正确的登录手机号';
		}
		if(strlen($truename)<3){
			return '请输入真实姓名';
		}
		$nowt = time();
		$ip = getipint();
		$id = I('id',1,'post');
		$rt = null;
		if($id>0){
			$rt = M($this->tb)->where('username=\''.$username.'\' AND id<>'.$id)->find();
		}else{
			$_POST['lasttime'] = $nowt;
			$_POST['lastip'] = $ip;
			$_POST['ip'] = $ip;
			$rt = M($this->tb)->where('username=\''.$username.'\'')->find();
		}
		if($rt){
			return '登录账号已存在';
		}
		return true;
	}
	//列表部门人员
	function wxlist_json(){
		$js = null;
		$js['status'] = 0;
		$js['msg'] = '';
		$q = I('q',0);
		$chooseid = I('chooseid',1);

		$where = '1';
		if($q!=''){
			$where .= ' AND (`A1`.`nickname` like \'%'.$q.'%\')';
		}
		$field = '`A1`.`id`,`A1`.`nickname`,`A1`.`face`,'.$chooseid.' AS `chooseid`';
		$arr['name'] = array('Authorized');	
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
}