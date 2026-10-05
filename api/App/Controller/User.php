<?php
namespace Hst\User;
use Hst\Common;
class User extends Common{
	function _init(){
		parent::_init();
	}
	private function loaduserinfo($uid=0){
		$vo = D('User')->getValue($uid);
	}	

	public function getorder_json(){		
		$uid = getuid();	
		$userinfo = D('Member')->getValue($uid);
		
		$js['status'] = 1;
		$js['msg'] = '';		
		
		$viplist = M('Package')->where('stat=1')->order('sort ASC')->select();
		$chargelist = M('Package')->where('stat=1')->order('sort ASC')->select();

		$js['viplist'] = $viplist;
		$js['chargelist'] = $chargelist;

		$isagent = I('isagent',1);
		if($isagent==1){
			$agentinfo = null;
			$m = M('OrdersAgent');
			$totalPrice = $m->where('uid='.$uid)->sum('price');
			$settledPrice = $m->where('uid='.$uid.' AND settled=1')->sum('price');
			$unsettledPrice = $totalPrice-$settledPrice;
			$agentinfo['totalPrice'] = $totalPrice;
			$agentinfo['settledPrice'] = $settledPrice;
			$agentinfo['unsettledPrice'] = $unsettledPrice;
			$js['agentinfo'] = $agentinfo;

			$incomeList = $this->getnewagentorders($uid);
			$js['incomeList'] = $incomeList;
		}
		
		$js['data'] = $userinfo;		
		$js['msg'] = '登录成功';
		$this->json($js);
	}
	private function getnewagentorders($uid=0){
		$arr = null;
		$where = '`A1`.`uid`='.$uid;
		$arr['field'] = '`A1`.`id`,`A1`.`price`,`A1`.`addtime`,`A3`.`username`';
		$arr['name'] = array('OrdersAgent','Orders','Member');
		$arr['on'] = array('`A1`.`ordid`=`A2`.`id`','`A2`.`uid`=`A3`.`id`');
		$arr['where'] = $where;
		$arr['order'] = '`A1`.`id` DESC';
		$arr['limit'] = 10;
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);
		if($list){
			foreach($list as $k=>$v){
				$username = $v['username'] ?? '';
				$username = hidePhone($username);
				$list[$k]['title'] = '用户：'.$username.'的订单奖励收入';
				$list[$k]['username'] = $username;
			}
		}
		return $list;
	}
	
	public function getmodify_json(){
		$js['status'] = 0;
		$js['msg'] = '';	
		$uid = $this->uid;

		$rt = D('Member')->getValue($uid);

		if($rt){
			$Ip = D('Ipaddress',1);
			$ip = long2ip($rt['ip'] ?? 0);				
			$rt['ipaddress'] = $Ip->getaddress($ip);
			$js['status'] = 1;
			$js['data'] = $rt;
		}

		$this->json($js);
	}

	//微信实名认证
	public function bindweixin_json(){
		
		$js['status'] = 0;
		$js['msg'] = '';	
		
		$iswx = I('iswx',1,'');

		$uid = $this->uid;						

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功';
			$this->json($js);
		}
		
		$rtcmd = false;
		
		
		if($rtcmd!==false) {
			$js['status'] = 1;
			$js['msg'] = '资料修改成功';
			$this->json($js);
		}else {
			$js['msg'] = '资料修改失败';
			$this->json($js);
		}
	}

	//修改资料保存json方式
	public function modify_json(){
		
		$js['status'] = 0;
		$js['msg'] = '';	
		
		$iswx = I('iswx',1,'');

		$uid = $this->uid;	
			
		$status = 0;
		$msg = '';	

		$ischeck = I('ischeck',1);

		$tb = 'Member';

		$data = null;

		$m = M($tb);
		$truename = I('truename',0,'post');
		$nickname = I('nickname',0,'post');
		$sex = I('sex',1,'post');
				
		$sex>3 && $sex = 1;	
		if(isset($_POST['truename'])) $data['truename'] = $truename;
		if(isset($_POST['nickname'])) $data['nickname'] = $nickname;
		if(isset($_POST['sex'])) $data['sex'] = $sex;

		if(strlen($truename)<4){
			$js['msg'] = '请填写真实姓名';
			$this->json($js);
		}

		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功';
			//$this->json($js);
		}
		
		$rtcmd = $m->where('id='.$uid)->save($data);
		
		
		if($rtcmd!==false) {
			$js['status'] = 1;
			$js['msg'] = '资料修改成功';
			$this->json($js);
		}else {
			$js['msg'] = '资料修改失败';
			$this->json($js);
		}
	}

	

	//修改资料保存json方式
	public function modifypwd_json(){
		$js['status'] = 0;
		$js['msg'] = '';
		$uid = $this->uid;

		$ischeck = I('ischeck',1);

		$status = '';
		$msg = '';
		$oldpassword = I('oldpassword',0,'post');
		$password = I('password',0,'post');
		$password1 = I('password1',0,'post');
		if(strlen($oldpassword)<4){
			$js['msg'] = '请输入旧密码';
			$this->json($js);
		}
		if(strlen($password)<4){
			$js['msg'] = '请输入新密码';
			$this->json($js);
		}
		if(strlen($password1)<4){
			$js['msg'] = '请输入新密码';
			$this->json($js);
		}
		/*做一个密码长度控制*/
		if(strlen($password)<6||strlen($password)>20){
			$js['msg'] = '新密码长度只能在6-20位字符之间';
			$this->json($js);
		}		
		if($password!=$password1){
			$js['msg'] = '您的密码不一致';
			$this->json($js);
		}
		$m = M('Member');		
		$map['id'] = $uid;		
		$user = $m->where($map)->find();
		if(!$user){
			$js['msg'] = '您的用户不存在';
			$this->json($js);
		}

		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功';
			$this->json($js);
		}

		$rndstr = $user['rndstr'];

		//新密码
		$password = md5($password);
		$encmd5 = md5($password.$rndstr);

		$data['password'] = $password;
		$data['encmd5'] = $encmd5;
		$rt = $m->where($map)->save($data);
		if($rt!==false) {
			
			$vo = D('Member')->getValue($uid);		


			$redata['id'] = $vo['id'];
			$redata['mobile'] = $vo['mobile'];
			$redata['encmd5'] = $vo['encmd5'];
			$redata['cookiepre'] = C('COOKIE_PREFIX');

			$js['status'] = 1;
			$js['data'] = $redata;

			$js['status'] = 1;
			$js['msg'] = '修改成功';
			$this->json($js);
		}else {
			$js['msg'] = '修改失败';
			$this->json($js);
		}
	}	
	private function add_log($tb='MemberFitLog',$id=0,$info=array(),$stat=0){
		$infojson = json_encode($info,JSON_UNESCAPED_UNICODE);
		$data['aid'] = $id;
		$data['addtime'] = time();
		$data['json'] = $infojson;
		$data['stat'] = $stat;
		M($tb)->add($data);
	}
	//绑定微信账号
	public function realname_json(){		
		$js['status'] = 0;
		$js['msg'] = '';
		$uid = getuid();

		$openid = I('openid',0);
		$truename = I('truename',0,'post');

		if(strlen($openid)<5){
			$js['msg'] = '您还未登录微信';
			$this->json($js);
		}

		$mm = M('Authorized');
		$rta = $mm->field('id,openid,nickname,face')->where('openid=\''.$openid.'\'')->find();
		if(!$rta){
			$js['msg'] = '获取数据错误';
			$this->json($js);
		}
		$wxid = $rta['id'] ?? 0;

		/*$rts = $m->field('id')->where('wxid='.$wxid.' AND id<>'.$uid)->find();
		if($rts){
			$js['msg'] = '当前微信已绑定过账号了，不能再绑定';
			$this->json($js);
		}*/

		$m = M('Member');
		$rt = $m->field('id,username,verifield')->where('id='.$uid)->find();
		$verifield = $rt['verifield'] ?? 0;
		if($verifield!=0){
			$js['msg'] = '您已完成实名认证，无须重复提交';
			$this->json($js);
		}

		$rta = $m->field('id,username')->where('openid=\''.$openid.'\' AND id<>'.$uid)->find();
		if($rta){
			$js['msg'] = '些微信账号已被其它用户绑定';
			$this->json($js);
		}

		if(strlen($truename)<3){
			$js['msg'] = '请填写正确的真实姓名';
			$this->json($js);
		}

		$idcard = I('idcard',0,'post');
		if(strlen($idcard)!=18){
			$js['msg'] = '请填写正确的身份证号';
			$this->json($js);
		}

		$nickname = I('nickname',0,'post');
		if(strlen($nickname)<3){
			$js['msg'] = '您选择微信昵称';
			$this->json($js);
		}
		$face = I('avatarUrl',0,'post');
		if(strlen($face)<100){
			$js['msg'] = '您选择微信头像';
			$this->json($js);
		}

		$ischeck = I('ischeck',1);
		if($ischeck==0){
			$js['status'] = 1;
			$js['msg'] = '检测成功!';
			$this->json($js);
		}

		$wxface = '';
		$url = 'http://typic.hongsite.com/weixinface.php';
		$jsonarr['base64'] = $face;
		$text = http($url,$jsonarr,'post');
		if(strpos($text,'status')!==false){
			$remojs = json_decode($text,true);
			if($remojs['status']==1){
				$wxface = $remojs['picurl'] ?? '';
			}
		}

		//echo $facefilename;

		
		$myuser = array('type'=>'PERSONAL_OPENID','account'=>$openid,'name'=>$truename,'relation_type'=>'DISTRIBUTOR');//分销商
		$arr['mch_id'] = $this->wx_mchid;
		$arr['sub_mch_id'] = $this->wx_mchid;
		$arr['appid'] = $this->wx_appid;		
		
		$obj = D('WxProfit');
		
		$info = $obj->addre($arr,$this->wx_paykeys,$myuser);
		//print_r($info);
		if($info['return_code']!='SUCCESS'||$info['result_code']!='SUCCESS'){
			$this->add_log('MemberFitLog',$uid,$info,0);
			$js['msg'] = $info['err_code_des'];
			$this->json($js);
		}
		$this->add_log('MemberFitLog',$uid,$info,1);

		$nickname = preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$nickname);
		$data_mem = null;
		$data_mem['wxid'] = $wxid;		
		$data_mem['openid'] = $openid;
		$data_mem['verifield'] = 1;
		
		$rtcmd = $m->where('id='.$uid)->save($data_mem);

		$data_mem_detail = null;
		$data_mem_detail['truename'] = $truename;
		$data_mem_detail['nickname'] = $nickname;
		$data_mem_detail['idcard'] = $idcard;
		$data_mem_detail['wxface'] = $wxface;
		$data_mem_detail['relation_type'] = 'DISTRIBUTOR';

		$md = M('MemberDetail');
		$rtd = $md->where('id='.$uid)->find();
		$rtcmd1 = false;
		if($rtd!==false){
			$rtcmd1 = $md->where('id='.$uid)->save($data_mem_detail);
		}else{
			$data_mem_detail['id'] = $uid;
			$rtcmd1 = $md->add($data_mem_detail);
		}		
		
		if($rtcmd!==false && $rtcmd1!==false){
			$js['status'] = 1;
			$js['msg'] = '实名认证成功';
			$this->json($js);
		}else {
			$js['msg'] = '实名认证失败，可能是系统繁忙，请重试';
			$this->json($js);
		}
	}
}