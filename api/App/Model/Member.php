<?php
/*
* @author 牛头
* @company 来客宝特科技
* @function 用户模块
* @date 2020/7/28
* @version hst-v1.0.0
*/
namespace Models\Member;
use Model\Model;
class Member extends Model {	
	public function getValue($uid=0,$field=''){
		if($field==''){			
			$fieldstr = '`A1`.`id`,`A1`.`username`,`A1`.`truename`,`A1`.`encmd5`,`A1`.`roleid`,`A1`.`departmentid`';
			$fieldstr .= ',`A1`.`balance`,`A1`.`jifen`,`A1`.`coupon`,`A1`.`vip_etime`,`A1`.`verifield`';			
			$fieldstr .= ',`A1`.`parentid`,`A1`.`level`';
			$fieldstr .= ',`A2`.`idcard`,`A2`.`sex`,`A2`.`wxface`,`A2`.`nickname`,`A2`.`addtime`,`A2`.`ip`,`A2`.`lastip`,`A2`.`lasttime`';
			$fieldstr .= ',`A2`.`relation_type`,`A2`.`nickname`,`A2`.`email`,`A2`.`birthday`,`A2`.`bio`';			
			$fieldstr .= ',`A3`.`privilegeid`,`A3`.`title` AS `jobname`,`A4`.`headid`,`A4`.`title` AS `department`';
		}else{
			$fieldstr = $field;
		}	
		
		$arr['name'] = array('Member','MemberDetail','Job','Department');
		$arr['on'] = array('`A1`.`id`=`A2`.`id`','`A1`.`jobid`=`A3`.`id`','`A1`.`departmentid`=`A4`.`id`');		
		$arr['limit'] = 1;
		$arr['where'] = '`A1`.`id`='.$uid;
		$arr['field'] = $fieldstr;
		$arr['showsql'] = true;
		$obj = D('SubTab');
		$rt = $obj->mysql_list($arr);
		if($rt){
			$level = $rt['level'] ?? 0;			
			$allowShare = 0;
			if($level==1) $allowShare = 1;
			$rt['allowShare'] = $allowShare;
		}
		return $rt;
	}	
    /**
     * 生成用户名
     * @return string
     */
    public function makeUserName()
    {
        $sn =  'U' .date('md') . str_pad(mt_rand(1, 999999), 4, '0', STR_PAD_LEFT);
        if ($order_sn=M('MemberDetail')->where('nickname ='.$sn)->field('id')->find()) {
            return $this->makeUserName();
        } else {
            return $sn;
        }
    }
	public function addMember($user){		
		$username =$user['username'] ?? '';

		if(!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
			return false;
		}		

		$password = $user['password'] ?? '123987';
		$ism = $user['ism'] ?? 0;
		$parentid = $user['parentid'] ?? 0;
		$sourceid = $user['sourceid'] ?? 0;
		$salerid = $user['salerid'] ?? 0;
		$roleid = $user['roleid'] ?? 0;
		$truename = $user['truename'] ?? '';
		
		$m = M('Member');
		$rt = $m->where('username=\''.$username.'\'')->find();
		if($rt) return $rt;
		//$rt1 = $m->field('id,username,mobile')->where(array('username'=>$username))->find();
		//if($rt) return $rt;

		

		$rndstr = genrndstr(10);
		$password = md5($password);
		$encmd5 = md5($password.$rndstr);
		$nowt = time();
		$ip = getipint();

		$data=array(			
		'rndstr'=>$rndstr,
		'encmd5'=>$encmd5,	
		'parentid'=>$parentid,				
		'username'=>$username,	
		'roleid'=>$roleid,
		'ism'=>$ism,
		'groupid'=>11,			
		'sourceid'=>$sourceid,
		);		

		$rtcmd = M('Member')->add($data);
		$datadetail = array(
		'id'=>$rtcmd,
		'truename'=>$truename,	
		'addtime'=>$nowt,			
		'ip'=>$ip,					
		'lasttime'=>$nowt,
		'lastip'=>$ip,
		'wxface'=>'userface.png',
		'nickname'=>''
		);

		$rtcmd1 = M('MemberDetail')->add($datadetail);
		if($rtcmd!==false){
			$this->setagent($rtcmd,$parentid);
			$data['id'] = $rtcmd;
		}else{
			return false;
		}		
		return $rtcmd;
	}

	private function setagent($id=0,$parentid=0){
		$m = M('Member');
		$level = 0;
		$data = null;
		if($parentid>0){
			$rts = $m->field('id,levelid')->where('id='.$parentid)->find();
			$levelid = $rts['levelid'] ?? '';
			$uid = $rts['id'] ?? 0;
			if($levelid!=''){				
				$levelidarr = explode(',',$levelid);
				$nums = count($levelidarr);
				if($nums==1){
					$levelid = $id.','.$levelid;
					$level = 2;
				}else if($nums==2){
					$levelid = $id.','.$levelid;
					$level = 3;
				}else if($nums==3){
					$level = 0;
				}
				$data['levelid'] = $levelid;				
			}
		}
		$data['level'] = $level;
		$m->where('id='.$id)->save($data);
		//echo $m->getlastsql();
	}

	public function addviptime($uid=0,$vipstr='',$price=0,$tid=0){
		$nowt = time();
		$m = M('Member');
		$user = $m->where('id='.$uid)->find();
		if($user['vip_etime']<$nowt){
			$vip_etime = strtotime($vipstr,$nowt);
		}else{
			$vip_etime = strtotime($vipstr,$user['vip_etime']);
		}
		$m->where('id='.$uid)->setField('vip_etime',$vip_etime);
		$datavip['addtime'] = $nowt;
		$datavip['price'] = $price;
		$datavip['uid'] = $uid;
		$datavip['vipstr'] = $vipstr;
		$datavip['tid'] = $tid;
		M('MemberVipLog')->add($datavip);
	}	
}
?>