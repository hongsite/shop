<?php
//通用函数
function getuid($tid = 'member_uid',$field='Member'){
	checkislogin(0,$field,'username');
	return intval(session($tid));
}
function getaid($tid = 'admin_uid',$field='Admin'){
	checkislogin(0,$field,'username');
	return intval(session($tid));
}
function getsid($tid = 'merchant_uid',$field='Merchant'){
	checkislogin(0,$field);
	return intval(session($tid));
}
function gethid($tid = 'huiyuan_uid',$field='Huiyuan'){
	checkislogin(0,$field,'mobile');
	return intval(session($tid));
}
function build_order_no(){
    return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}
function checkislogin($tid=0,$field='Member'){
    //通用检测是否登录
    //$tid 0:普通检测 1:json
	$fieldl = strtolower($field);
    
    $uid = 0;
	$uname = '';
	$upwd = '';        
	$uid = intval(session($fieldl.'_uid'));	
	$uname = cookie($fieldl.'_uname');
	$upwd = cookie($fieldl.'_upwd');
	$data['status'] = 0;
	$data['msg'] = '';

	//未登录
	if($uid>0){
		$data['status'] = 1;
	    $data['msg'] = '登录成功';
		$data['username'] = $uname;
		$data['groupid'] = intval(session($fieldl.'_groupid'));
		return 0;
	}
	
	
	//用户名不为空,cookies有用户名
	if($uname==''||$upwd==''){
		$data['msg'] = '用户名或密码不能为空';
		return 0;
	}

	$where = 'username=\''.$uname.'\'';
	$sid = I('sid',1);	
	$cxfield = 'id,username,stat,encmd5,roleid';	
	if($field=='Huiyuan'){		
		$where .= ' AND sid='.$sid;
	}	
    $mm = M($field);	
	$rt = $mm->field($cxfield)->where($where)->find();
	if(!$rt){
		$data['msg'] = '用户名不存在';
		return 0;
	}
	$encmd5 = trim($rt["encmd5"]);
	if($upwd!=$encmd5){
		$data['msg'] = '密码错误';
		return 0;
	}
	$id = $rt['id'] ?? 0;
	$dataup = null;
	$dataup['lasttime'] = time();
	$dataup['lastip'] = getipint();
	M($field.'Detail')->where('id='.$id)->save($dataup);
	$data = setlogin($rt,$field);
	return $rt['id'];
}
//设置登录后的cookies等
function setLogin($rt=null,$field=''){
	$nowt = time();
	$field1 = strtolower($field);
	$sysday = 7;	
	$cooketimes = 31536000;
	$username = $rt['username'] ?? '';
	$encmd5 = $rt['encmd5'] ?? '';
	$roleid = $rt['roleid'] ?? 0;
	$uid = $rt['id'] ?? 0;
	cookie($field1.'_uid',$uid,$cooketimes);
	cookie($field1.'_uname',$username,$cooketimes);
	cookie($field1.'_upwd',$encmd5,$cooketimes);
	cookie($field1.'_roleid',$roleid,$cooketimes);
	session($field1.'_uid',$uid);
	$data = null;
	$data['status'] = 1;
	$data['msg'] = '登录成功';
	$data['username'] = '';
	return $data;
}
function prinlogin($tid=0,$istrue=false,$arr=null){
	switch ($tid){
		case 0:
			return $istrue;
		    break;
		case 1:
			echo json_encode($arr);
		    break;
		case 2:
			echo json_encode($arr);
		    break;
	}
}
//载入站点设置
function loadcache($tid=2,$force=false){
		$field = 'id,website,beian,url,wx_appid,wx_appsecret,wx_mchid,wx_paykeys,agentstat';
		switch ($tid){
			case 1:
				$fname = 'payconfig.php';
				$tb = 'WebsitePay';
				break;
			case 2:
				$fname = 'website.php';
				$tb = 'Website';
				break;
			default:
				$fname = 'payconfig.php';
				$tb = 'WebsitePay';
		}
		$filename = ROOT.'static/cache/'.$fname;
		$arr = null;
		$arr['tb'] = $tb;
		$arr['field'] = $field;
		$arr['timeout'] = 0;
		$arr['filename'] = $filename;
		return createcache($arr);
		
}
function createcache($arr){
	$tb = empty($arr['tb']) ? '':$arr['tb'];
	$filename = empty($arr['filename']) ? '':$arr['filename'];
	$force = empty($arr['force']) ? false:true;
	$where = empty($arr['where']) ? '':$arr['where'];
	$timeout = empty($arr['timeout']) ? 60:$arr['timeout'];
	$order = empty($arr['order']) ? '':$arr['order'];
	$field = empty($arr['field']) ? '':$arr['field'];
	$limit = empty($arr['limit']) ? 500:intval($arr['limit']);
	$folder = dirname($filename);
	if(substr($folder,-1)!='/') $folder .= '/';
	creatdir($folder);
	if(file_exists($filename)){
		if($timeout==0 && $force===false){
			$arrs = require $filename;
			return $arrs;
		}
		$a = filemtime($filename);
		if((time()-$a)<$timeout && $force===false){		
			$arrs = require $filename;
			return $arrs;
		}
	}

	$str = "<?php".chr(13).chr(10);

	$fieldarr = null;

	if($field==''){
		$sql ='SHOW FULL COLUMNS FROM '.C('DB_PREFIX').cc_table($tb);	
		$fieldlist = M('')->query($sql);
		foreach($fieldlist as $kk=>$vv){
			$fieldarr[] = $vv['Field'];
		}
	}else{
		$fieldlist = explode(',',$field);
		foreach($fieldlist as $kk=>$vv){
			$fieldarr[] = $vv;
		}
	}
	$folder = dirname($filename).'/';
	creatdir($folder);
	$m = M($tb);
	$tmp_pay = $field=='' ? $m->where($where)->find():$m->field($field)->where($where)->find();
	
	$str .= '/*'.$tb.'缓存数据*/'; 
	$str .= chr(13).chr(10);
	foreach($fieldarr as $key=>$v){
		$value = $tmp_pay[$v];
		$value = str_replace("'","",$value);
		$str .= "\$arr_tmp['".$v."']='".$value."';".chr(13).chr(10);
	}	
	$str .= 'return $arr_tmp;';
	$str .= chr(13).chr(10);
	$str .= "?>";
	file_put_contents($filename,$str);	
	return $tmp_pay;
}
function send_code($arr){		
	$mobile = $arr['mobile'];	
	$tid = $arr['tid'];

	$m = M('MobileCode');
	$rt = $m->order('id DESC')->where('mobile='.$mobile.' AND tid='.$tid)->find();
	if(!$rt){
		$rtcmd = send_code_do($arr);
		return $rtcmd;
	}

	$codetime = $rt['addtime'];
	if((time()-$codetime)<60){
		return '请过60秒后再发送';
	}
	$rtcmd = send_code_do($arr);
	return $rtcmd;
}
function send_code_do($arr){	
	$code = $arr['code'];	
	$paramsinfo = array(
	'code' => $code
	);
	$website = loadcache(2);
	$arr['paramsinfo'] = $paramsinfo;
	$arr['msgappid'] = $website['msgappid'] ?? '';
	$arr['msgkey'] = $website['msgkey'] ?? '';

	$obj = D('Sms');
	$info = $obj->sendSms($arr);	
	$stat = $info['Code'];
	$msg = $info['Message'];
	if($stat=='OK'){			
		$data['mobile'] = $arr['mobile'];
		$data['addtime'] = time();
		$data['code'] = $code;
		$data['tid'] = $arr['tid'];
		$m = M('MobileCode');
		$rtcmd = $m->add($data);		
		if($rtcmd!==false){
			return array('stat'=>true,'msg'=>'验证码发送成功');
		}else{
			return array('stat'=>false,'msg'=>'系统繁忙请稍后重新');
		}			
	}else{
		return array('stat'=>false,'msg'=>$msg);
	}
}
function createcachelist($arr){
		$tb = empty($arr['tb']) ? '':$arr['tb'];
		$filename = empty($arr['filename']) ? '':$arr['filename'];
		$force = empty($arr['force']) ? false:true;
		$where = empty($arr['where']) ? '':$arr['where'];
		$order = empty($arr['order']) ? '':$arr['order'];
		$limit = empty($arr['limit']) ? 500:intval($arr['limit']);
		$timeout = empty($arr['timeout']) ? 60:$arr['timeout'];
		
		if(is_file($filename)){
			if($timeout==0 && $force===false){
				$arrs = require $filename;
				return $arrs;
			}
			$a = filemtime($filename);
			if((time()-$a)<$timeout && $force===false){		
				$arrs = require $filename;
				return $arrs;
			}
		}
		$folder = dirname($filename).'/';
		creatdir($folder);
		$m = M($tb);
		$list = $m->where($where)->order($order)->limit($limit)->select();
		$str = '<?php'.chr(13).chr(10).'$data='.var_export($list,true).';'.chr(13).chr(10).'return $data;?>';
		file_put_contents($filename,$str);
		return $list;
	}
function websitecache($force=false){
    //if($domain=='') return false;
    $path = ROOT.'static/cache/website/';
    creatdir($path);
    $filename = $path.'1.php';	
    if(file_exists($filename) && $force===false){
    $company_info = require($filename);
    return $company_info;
    }
    $tb = 'Website';
    $m = M($tb);	
    $company_info = $m->find();
    if(!$company_info) return false;
    $sql ='SHOW FULL COLUMNS FROM '.C('DB_PREFIX').cc_table($tb);	
    $fieldlist = M('')->query($sql);
    foreach($fieldlist as $kk=>$vv){
    $fieldarr[] = $vv['Field'];
    }	
    $str = "<?php".chr(13).chr(10);
    $str .= '/*站点配置文件*/'; 
    $str .= chr(13).chr(10);
    foreach($fieldarr as $key=>$v){
    $value = $company_info[$v];
    $value = str_replace("'","",$value);
    $str .= "\$company_info['".$v."']='".$value."';".chr(13).chr(10);
    }
    $str .= 'return $company_info;';
    $str .= chr(13).chr(10);
    $str .= "?>";
    file_put_contents($filename,$str);
    return $company_info;
}
function get_smstmp($id=0,$force=false){
	if($id==0) return false;	
	$arr = createcache(array('tb'=>'Smstmp','filename'=>ROOT.'static/cache/smstmp_'.$id.'.php','where'=>'id='.$id,'force'=>$force));
	return $arr;
}
function getcarttb($uid = 0, $tb = 'Cart'){
    $nums = 50000;
    switch ($tb) {
        case 'Cart':
            $nums = C('DB_SUB_NUMS_CART');
            break;
        case 'Address':
            $nums = C('DB_SUB_NUMS_ADDRESS');
            break;
        case 'History':
            $nums = C('DB_SUB_NUMS_CART');
            break;
        case 'Fav':
            $nums = C('DB_SUB_NUMS_FAV');
            break;
    }
    // 注意：get_table_nums 第一个参数是当前ID，第二个是分表数量
    $nums = get_table_nums($uid, $nums, 1);
    $tbs = $tb . C('DB_SUB_AFT') . $nums;
    if (check_table_isext($tb . C('DB_SUB_AFT') . $nums) !== true) {
        auto_table($tb, $nums);
    }
    return $tbs;
}