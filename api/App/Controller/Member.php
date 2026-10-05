<?php
/*
* @author 牛头
* @function 会员管理
* @date 2018-04-09
* @version v1.0.0
*/
namespace hst\Member;
use hst\Common;
class Member extends Common{   
	function _init(){
		parent::_init();
		$this->allowindex = true;
		$this->allowdelete = true;
		$this->allowedit = true;
		$this->allowupdate = true;
	}
	public function test(){
		$arr = null;
		$arr['username'] = 13876509208;
		$arr['parentid'] = 17;
		D('Member')->addMember($arr);
	}

	function paytest(){
		$uid = getuid();
		$arr = null;
		$arr['out_trade_no'] = '2026091254985748';
		$arr['paytype'] = 1;
		$info = D('Orders')->orderhandle($arr);
		/*$arr = null;
		if($info){
			foreach($info as $k=>$v){
				$arr[] = 9988*$v/100;
			}
		}
		dump($info);
		dump($arr);

		$info1 = D('Orders')->addAgentOrders(17,17,921000);*/
		print_r($info);
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
			case 'topagent':
				$where .= ' AND `A1`.`topagent`=1';
				break;
			case 'level2':
				$where .= ' AND `A1`.`level`=2';
				break;
			case 'level3':
				$where .= ' AND `A1`.`level`=3';
				break;
			case 'level0':
				$where .= ' AND `A1`.`level`=0';
				break;
		}
		
		$this->detail = array('MemberDetail');
		$this->field = '`A1`.`id`,`A1`.`username`,`A1`.`truename`,`A1`.`roleid`,`A1`.`level`,`A1`.`openid`,`A1`.`parentid`,`A1`.`topagent`,`A1`.`verifield`';	
		$this->field .= ',`A2`.`addtime`,`A2`.`lasttime`,`A2`.`ip`,`A2`.`lastip`,`A2`.`nickname`,`A2`.`wxface`,`A2`.`sex`,`A2`.`idcard`';
		$this->tbon = 	array('`A1`.`id`=`A2`.`id`');
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
	public function _before_edit(){	
		$this->detail = array('MemberDetail');
		$this->field = '`A2`.`addtime`,`A2`.`lasttime`,`A2`.`ip`,`A2`.`lastip`,`A2`.`nickname`,`A2`.`wxface`,`A2`.`sex`,`A2`.`idcard`';		
		$this->tbon = 	array('`A1`.`id`=`A2`.`id`');
    }
	public function _after_update_vo(){	
		$this->detail = array('MemberDetail');
		$this->field = '`A2`.`addtime`,`A2`.`lasttime`,`A2`.`ip`,`A2`.`lastip`,`A2`.`nickname`,`A2`.`wxface`,`A2`.`sex`,`A2`.`idcard`';		
		$this->tbon = 	array('`A1`.`id`=`A2`.`id`');
    }
	public function _after_update($id=0){
		$m = M(MODULE_NAME);
		$rt = $m->field('id,levelid,level,topagent')->where('id='.$id)->find();
		$levelid = $rt['levelid'] ?? '';
		$levely = $rt['level'] ?? 0;
		$topagent = $rt['topagent'] ?? 0;
		$level = 0;
		if($levelid!=''){
			$levelidarr = explode(',',$levelid);
			$nums = count($levelidarr);
			if(strpos(','.$levelid.',',','.$id.',')!==false){
				$level = $nums;
			}
		}
		$data = null;
		$data['level'] = $level;
		if($topagent==1 && $levelid==''){
			$data['levelid'] = $id;
		}
		if($topagent==0 && $levelid==$id){
			$data['levelid'] = '';
		}
		$m->where('id='.$id)->save($data);
    }
	function _after_edit($rt=null){
		$Ip = D('Ipaddress',1);
		if($rt){
			$ip = $rt['ip'] ?? 0;
			$lastip = $rt['lastip'] ?? 0;
			$rt['address'] = $ip>0 ? $Ip->getaddress(long2ip($ip)):'';
			$rt['lastaddress'] = $lastip>0 ? $Ip->getaddress(long2ip($lastip)):'';
		}		
		return $rt;		
	}
	public function _before_update(){

		$this->detail = array('MemberDetail');

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
		if(strlen($truename)<6){
			return '请输入真实姓名';
		}

		$topagent = I('topagent',1,'post');
		$_POST['topagent'] = $topagent;

		
		
		

		$nowt = time();
		$ip = getipint();
		$id = I('id',1,'post');
		$rt = null;
		if($id>0){
			$m = M('Member');			
			if($topagent==1){
				$rt = $m->field('id,levelid')->where('id='.$id)->find();
				$levelid = $rt['levelid'] ?? '';
				if($levelid!='' && $levelid!==$id){
					return '已是分销商，无法设置为顶级代理';
				}
				if($levelid==''){
					$_POST['levelid'] = $id;
				}
			}
			
			$rt = M(MODULE_NAME)->where('username=\''.$username.'\' AND id<>'.$id)->find();
		}else{			
			$_POST['lasttime'] = $nowt;
			$_POST['lastip'] = $ip;
			$_POST['ip'] = $ip;
			$rt = M(MODULE_NAME)->where('username=\''.$username.'\'')->find();
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