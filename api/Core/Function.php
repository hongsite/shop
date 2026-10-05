<?php
/*
*@author 牛头
*@company 鸿思特科技
*@funtion 通用主函数
*@date 2026/9/28
*@version v1.0.0
*/
function A($name,$layer='') {
	static $_action  =   array();
	$layer='Controller';
	$fname=APP_PATH.$layer.'/'.$name;
    if(isset($_action[$fname])) return $_action[$name];
	import($fname);
	$class =   'hst\\'.$name.'\\'.$name;
	$model =   new $class();   
    $_action[$name]  =  $model;
    return $model;
}
function C($name=null,$value=null){
	static $_config = array();	
	if(is_string($name)) {
		if(!strpos($name,'.')){
		    $name = strtolower($name); if (is_null($value)) return isset($_config[$name]) ? $_config[$name] : null; $_config[$name] = $value; return;
		}
		$name = explode('.', $name); $name[0] = strtolower($name[0]);
		if (is_null($value)) return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null; $_config[$name[0]][$name[1]] = $value; return;
	}
	if(is_array($name)){
		$_config = array_merge($_config, array_change_key_case($name));		
		return;
	}
	return null;
}
function D($name='',$isadmin=0) {
    static $_model  =   array();    
	$layer='Model';
	if($isadmin==0){
		$fname=APP_PATH.$layer.'/'.$name;	
	}else{
		$fname=ROOT.'App/'.$layer.'/'.$name;
	}
    if(isset($_model[$fname])) return $_model[$name];
	import($fname);
	$class =   'Models\\'.$name.'\\'.$name;
	$model     =   new $class();   
    $_model[$name]  =  $model;
    return $model;
}
// 修复 I() 函数
function I($text = '', $isnum = 0, $method = '', $isnodyh = false, $isnohtml = false, $isthyh = true)
{
    $str = "";
    switch ($method) {
        case "":
            $str = isset($_GET[$text]) ? ($isnum == 0 ? $_GET[$text] : floatval($_GET[$text])) : ($isnum == 0 ? "" : 0);
            break;
        case "post":
            $str = isset($_POST[$text]) ? ($isnum == 0 ? $_POST[$text] : floatval($_POST[$text])) : ($isnum == 0 ? '' : 0);
            break;
        case "request":
            $str = isset($_REQUEST[$text]) ? ($isnum == 0 ? $_REQUEST[$text] : floatval($_REQUEST[$text])) : ($isnum == 0 ? '' : 0);
            break;
        case "per":
            $str = empty($text) ? ($isnum == 0 ? '' : 0) : $text;
            break;
        default:
            break;
    }

    if (!is_array($str)) {
        if ($isnodyh === false) {
            $str = str_replace('\'', '', $str);
        }
        if ($isthyh === false) {
            $str = str_replace('"', '', $str);
        }
        if (empty($str) || $str == '') {
            return $str;
        }
        // PHP 7.4+ 已移除 magic_quotes_gpc，直接 addslashes
        if ($isnohtml === false) {
            $str = addslashes($str);
        }
    }
    return $str;
}

// 修复 file_exists_case
function file_exists_case($filename)
{
    return file_exists($filename);
}

// 修复 import，避免重复 require
function import($class, $ext = '.php')
{
    static $_file = array();
    if (isset($_file[$class])) {
        return true;
    }
    $_file[$class] = true;

    $classfile = $class . $ext;
    if (!class_exists($class, false)) {
        if (file_exists($classfile)) {
            require $classfile;
        }
    }
    return true;
}

// 修复 session
function session($name = '', $value = null, $isdestroy = false)
{
    $prefix = C('SESSION_PREFIX');
    if ($value === null) {
        if ($name == '' && $isdestroy === true) {
            session_unset();
            session_destroy();
        } else if ($isdestroy === true) {
            unset($_SESSION[$prefix . $name]);
        } else {
            return isset($_SESSION[$prefix . $name]) ? $_SESSION[$prefix . $name] : '';
        }
    } else {
        $_SESSION[$prefix . $name] = $value;
    }
}

// 修复 cookie
function cookie($name = '', $value = null, $expire = 3600, $path = '/')
{
    $prefix = C('COOKIE_PREFIX');
    if ($name == '') {
        if ($expire < 0) {
            foreach ($_COOKIE as $key => $val) {
                setcookie($key, '', time() - 3600);
                unset($_COOKIE[$key]);
            }
        }
        return '';
    }
    if ($value === null && $expire < 0) {
        foreach ($_COOKIE as $key => $val) {
            if ($key == $prefix . $name) {
                setcookie($key, '', time() + $expire, $path);
                unset($_COOKIE[$key]);
                break;
            }
        }
        return '';
    } else if ($value === null) {
        return isset($_COOKIE[$prefix . $name]) ? $_COOKIE[$prefix . $name] : '';
    }
    setcookie($prefix . $name, $value, time() + $expire, $path);
}
function isUsername($username=''){
    return preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z_]+$/u', $username) === 1;
}
function M($name='', $tablePrefix='',$connection='') {
    static $_model  = array();
    if(strpos($name,':')) {
        list($class,$name)    =  explode(':',$name);
    }else{
        $class      =   'Model\\Model';
    }
    $guid=$tablePrefix.$name.'_'.$class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class($name,$tablePrefix,$connection);
    return $_model[$guid];
}
function N($key, $step=0,$save=false) {
    static $_num    = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save)? S('N_'.$key) :  0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int) $step;
    if(false !== $save){ // 保存结果
        S('N_'.$key,$_num[$key],$save);
    }
}
function U($url='',$vars=null){
	$depr = '/';
	$url = trim($_SERVER['REQUEST_URI']);	
	$info = parse_url($url);
	$path = $info['path'];
	$query = null;
	isset($info['query']) && parse_str($info['query'],$query);	
	$querykey = !empty($query) ?array_keys($query):array();
	if(substr($path,0,1)=='/') $path = substr($path,1,strlen($path));
	if(!isset($vars['p'])) $vars['p'] = 1;	
	if(strpos($path,'index.php')===false){				
		if(!$query) $query['p'] = $vars['p'];
		foreach($query as $k=>$v){
			$query['p'] = $vars['p'];
		}		
		$url = '/'.$path.'?'.http_build_query($query);
		return $url;
	}else{
		$url = '/'.$path;
		if(!empty($query)){
			$query['p'] = $vars['p'];
			$url .= '?'.http_build_query($vars);
		}		
	}	
	return $url;	
}
function getext($filename,$fh='.'){
    return strtolower(substr(strrchr($filename,$fh),1));
}
function formatp($price=0,$ws=2,$isqz=100){
	if(!is_numeric($price)) return 0;
	if($isqz>0) $price = $price/$isqz;
	$arr = explode('.',$price);
	$aft = '';
	$aft = count($arr)<2 ? str_pad('',$ws,'0'):str_pad(substr($arr[1],0,$ws),$ws,'0');
	return $arr[0].'.'.$aft;
}
function formatpno($price=0,$ws=2){
	if(!is_numeric($price)) return 0;
	$arr = explode('.',$price);
	$aft = '';
	$aft = count($arr)<2 ? str_pad('',$ws,'0'):str_pad(substr($arr[1],0,$ws),$ws,'0');
	return $arr[0].'.'.$aft;
}
function require_cache($filename) {
    static $_importFiles = array();	
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {			
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}
function require_array($array,$return=false){
	foreach ($array as $file){
        if (require_cache($file) && $return) return true;
    }
    if($return) return false;
}
function get_instance_of($name, $method='', $args=array()) {
    static $_instance = array();
    $identify = empty($args) ? $name . $method : $name . $method . to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if (class_exists($name)) {
            $o = new $name();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
                $_instance[$identify] = $o;
        }
        else
            die('_CLASS_NOT_EXIST_');
    }
    return $_instance[$identify];
}
function to_guid_string($mix) {
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}
function throw_exception($msg, $type='ThinkException', $code=0) {
    if (class_exists($type, false))
        throw new $type($msg, $code);
    else
        die($msg);        // 异常类型不存在则输出错误信息字串
}
function parse_name($name, $type=0) {
    if ($type) {
        if(version_compare(PHP_VERSION,'7.0.0','<')){
            return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
        } else {
            return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($r) {
                return strtoupper($r[1]);
            }, $name));
        }
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}
function random($min = 0, $max = 1){
    return $min + mt_rand()/mt_getrandmax()*($max-$min);
}
function getip(){
     if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
         $ip = $_SERVER['HTTP_CLIENT_IP'];
     } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
         $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
     } else if (!empty($_SERVER['REMOTE_ADDR'])) {
         $ip = $_SERVER['REMOTE_ADDR'];
     } else {
         $ip = $_SERVER['REMOTE_ADDR'];
     }
     return $ip;
}
function getipint(){
	return ip2long(getip());
}
//自动创建文件夹
function creatdir($path=''){
    if(!is_dir($path)) {
        if(creatdir(dirname($path))){
            mkdir($path,0777);
            return $path;
        }
    }else{
        return $path;
    }
}
//获取域名
function getdomain($url){
	$arr=explode('/',$url);
	return $arr[0].'//'.$arr[2].'/';
}
//转换表名成下划线
function cc_table($name){
	$temp_array = array();
	for($i=0;$i<strlen($name);$i++){
	    $ascii_code = ord($name[$i]);
	    if($ascii_code >= 65 && $ascii_code <= 90){
			if($i == 0){
				$temp_array[] = chr($ascii_code + 32);
			}else{
				$temp_array[] = '_'.chr($ascii_code + 32);
			}
	    }else{
			$temp_array[] = $name[$i];
	    }
	}
    return implode('',$temp_array);
}
//检查表是否存在
function check_table_isext($table,$db_server=0){		
	$table = cc_table($table);
	$prefix = C('DB_PREFIX');
	if(strpos($table,$prefix)===false){
		$table = $prefix.$table;
	}
	$sql = "SHOW TABLES LIKE '".$table."'";	
	$m = M('');	
	$arr = $m->query($sql);
	if(count($arr)==1){
		return true;
	}else{
		return false;
	}
}
//创建表
function create_table($table,$nums,$db_server=0,$fenbiao_type=''){		
    $table = cc_table($table);
    $prefix = C('DB_PREFIX');
    if(strpos($table,$prefix)===false){
		$table = $prefix.$table;
	}
	$subname = C('DB_SUB_AFT');
	$firsttb = cc_table($table.$subname.'1');
	if($fenbiao_type==''){
	    //按id,uid分表
		$tihuantb = $table.$subname.$nums;
	}else{
		//按年月分表
		$tihuantb = $table.$subname.$fenbiao_type;
	}
	$sql = 'show create table `'.$firsttb.'`';
	$m = M();
	$arr = $m->query($sql);
	$sqlcmd = $arr[0]['Create Table'];
	$sqlcmd = str_replace('`'.$firsttb.'`','`'.cc_table($tihuantb).'`',$sqlcmd);
	
	$m->execute($sqlcmd);
	if(check_table_isext($tihuantb,$db_server)===true){
		return true;
	}else{
		return false;
	}
}

//自动创建表功能
function auto_table_key($table,$db_server=0){    
	if(check_table_isext($table.'Key',$db_server)!==true){			
		$prefix = C('DB_PREFIX');
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$prefix.cc_table($table).'_key`(`id` bigint(20) NOT NULL auto_increment,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';		
		$m = M();
		$m->execute($sql);		
		if(check_table_isext($table.'Key',$db_server)!==true){
			return false;
		}
    }
	return true;
}

//自动创建表功能
function auto_table($table,$nums,$db_server=0,$merge='',$fenbiao=0,$fenbiao_type=''){
    $subname = C('DB_SUB_AFT');

	$table_name = $table.$subname.$nums;	

	if(check_table_isext($table_name,$db_server)!==true){
		
		
		
		$create_tb_success = false;		
		if(check_table_isext($table.$subname.'1',$db_server)!==true){
			return false;			
		}
		
		$create_tb_success = create_table($table,$nums,$db_server,$fenbiao_type);
		
		if($create_tb_success===false){
			return false;
		}else{
			if($merge!=''&&$nums!=''){
				//更新引擎表
				$prefix = C('DB_PREFIX');				
				$m = M('');

				/*------开始更新引擎表------*/
				//主表
				$maintable = $prefix.cc_table($table);			
				$sqlcmdarr = $m->query('show create table `'.$maintable.'`');
				$str = $sqlcmdarr[0]['Create Table'];
				$str = getmiddle($str,'UNION=(',')');
				$newtable = $prefix.cc_table($table.$subname).$nums;

				if(strpos($str,$newtable)===false){
					$sql = 'ALTER TABLE `'.$prefix.cc_table($table).'`  UNION = ('.$str.',`'.$newtable.'`);';
					$m->execute($sql);
				}				
			}
		}		
    }
	return true;
}
//获取表名和数据   当前ID,分表数量,指定表ID,分表分隔标识
function get_table_nums($nums,$sub_nums=1,$tid=0,$pre=''){
	$nums==0 && $nums = 1;
	if(!is_numeric($sub_nums)) return 1;
	$sub_nums = ceil($nums/intval($sub_nums));
	$sub_nums == 0 && $sub_nums = 1;
	if($tid>0){
		$sub_nums = C('DB_SUB_AFT').$sub_nums;
	}
	if($pre!=''){
		$sub_nums = $pre.$sub_nums;
	}
	return $sub_nums;
}
function ispc(){
	//如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
	return false;
	} 
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset($_SERVER['HTTP_VIA'])){
	// 找不到为flase,否则为true
	return stristr($_SERVER['HTTP_VIA'], "wap") ? false : true;
	} 
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
	$clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger'); 
	// 从HTTP_USER_AGENT中查找手机浏览器的关键字
	if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
	return false;
	} 
	} 
	// 协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT'])) { 
	// 如果只支持wml并且不支持html那一定是移动设备
	// 如果支持wml和html但是wml在html之前则是移动设备
	if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
	return false;
	} 
	} 
	return true;
}
function isWeixin() { 
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) { 
	return true; 
	}else{
	return false; 
	}
}
function base64url_encode($str) {
	$data = base64_encode($str);
	$data = str_replace(array('+','/','='),array('-','_',''),$data);
	return $data;
}
function base64url_decode($str) {
	$data = str_replace(array('-','_'),array('+','/'),$str);
    $mod4 = strlen($data) % 4;
	if($mod4){
		$data .= substr('====', $mod4);
	}
   return base64_decode($data); 
}
//替换图片地址
function load_content($rt,$tihuan=null){
    if(empty($tihuan)) return $rt;
    foreach($tihuan as $k=>$v){
		$name = $v['field'];
		$servername = $v['server'];
		$cont = $rt[$name];
		//$cont = stripslashes($cont);
		$cont = str_replace('[pic_path]',$servername,$cont);		
		$rt[$name] = $cont;
    }
    return $rt;
}
function tihuan_content($arr){    
	if(!$arr) return ;
    foreach($arr as $k=>$v){
		if(isset($_POST[$v['field']])){
			$content = $_POST[$v['field']];			
			$content = str_replace($v['server'],'[pic_path]',$content);			
			$_POST[$v['field']] = $content;
		}
    }
}
function formatsku($sku='',$fh=false,$nohtml=false){
	if($sku==''||$sku=='0') return '';
	$str = '';
	$ti = '</span>';
	if($fh!==false){
		$ti .= $fh;
	}
	$ti .= '<em class="skutit">'; 
	$str = str_replace('@@@',':</em><span class="skucon">',$sku);
	$str = str_replace('|||',$ti,$str);
	$str = '<em class="skutit">'.$str.'</span>';
	if($nohtml===true) $str = strip_tags($str);
	return $str;
}
function genrndstr($length=6,$chars='abcdefghijklmnopqrstuvwxyz') {
    $str = '';
    for ( $i = 0; $i < $length; $i++ ){
		// 这里提供两种字符获取方式
		// 第一种是使用 substr 截取$chars中的任意一位字符；
		// 第二种是取字符数组 $chars 的任意元素
		// $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}
function getmiddle($str='', $start='', $end=''){ 
	if($start == ''||$end == ''){ 
		return; 
	}
	$str = explode($start,$str);
	if(count($str)<2) return '';
	$str = explode($end,$str[1]); 
	return $str[0]; 
}
function utf_cutstr($str,$len) {    
    $i = 0;    
    $tlen = 0;    
    $tstr = '';    
    while ($tlen < $len) {    
        $chr = mb_substr($str, $i, 1, 'utf8');    
        $chrLen = ord($chr) > 127 ? 2 : 1;    
        if ($tlen + $chrLen > $len) break;    
        $tstr .= $chr;    
        $tlen += $chrLen;    
        $i ++;    
     }    
    if ($tstr != $str) {    
        $tstr .= '';    
     }    
    return $tstr;    
}
function getref($arr=null){	
	$jianmi = empty($arr['jianmi']) ? false:true;
	$jiami = empty($arr['jiami']) ? false:true;
	$formtype = empty($arr['formtype']) ? '':$arr['formtype'];
	$ref = I('ref',0,$formtype);	
	if($jianmi===true){
		if($formtype!='') return base64url_decode($ref);
	}
	$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']:'';	
	if($jianmi===true) return base64url_decode($ref);
	if($jiami===true) return base64url_encode($ref);				
	return $ref;
}
//检测手机号
function checkMobile($mobile=0) {   
    if(!is_numeric($mobile)){
		return false;
	}
	if(!preg_match('/^1([0-9]{10})$/',$mobile)){
		return false;
	}
	return true;
}
function http($url='', $params=null, $method = 'GET', $header = array(), $multi = false){
	$opts = array(
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		    //CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_1_3 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Mobile/10B329 MicroMessenger/5.0.1',
			CURLOPT_HTTPHEADER     => $header
	);

	/* 根据请求类型设置特定参数 */
	switch(strtoupper($method)){
		case 'GET':
			if($params){
				$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
			}else{
				$opts[CURLOPT_URL] = $url;
			}
			break;
		case 'POST':
			//判断是否传输文件
			//$params = $multi ? $params : http_build_query($params);
			$opts[CURLOPT_URL] = $url;
			$opts[CURLOPT_POST] = 1;
			$opts[CURLOPT_POSTFIELDS] = $params;
			break;
		default:
			throw new \Exception('不支持的请求方式！');
	}

	/* 初始化并执行curl请求 */
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	//curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:26.0) Gecko/20100101 Firefox/26.0');
	$data  = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	
	if($error){
		$a = $_SERVER["QUERY_STRING"];
		if(strpos($error,'SSL CA cert')!==false){
			$error = '您的PHP环境为安全模式，不支持此请求，请更换成兼容模式';
		}	
	}
	return $data;
}
function utf8_strlen($str){
	$count = 0;
	for($i = 0; $i < strlen($str); $i++){
		$value = ord($str[$i]);
		if($value > 127) {
			$count++;
			if($value >= 192 && $value <= 223) $i++;
			elseif($value >= 224 && $value <= 239) $i = $i + 2;
			elseif($value >= 240 && $value <= 247) $i = $i + 3;
			else('');
		}
		$count++;
	}
	return $count;
}
function gettbfield($Type=''){	

	$field = getmiddle('[abccc]'.$Type,'[abccc]','(');
	$result = 0;
	
	if(in_array($field,array('bigint','int','tinyint','smallint','mediumint','integer'))){
		$result = 1;
	}else if(in_array($field,array('float','double'))){
		$result = 2;
	}
	
	return $result;
}
function snakeToCamel($str, $capitalized=false){
    $result = str_replace('_', '', ucwords($str, '_'));
    if(!$capitalized){
        $result = lcfirst($result);
    }
    return $result;

}

//驼峰命名转下划线命名
function toUnderScore($str){
	$dstr = preg_replace_callback('/([A-Z]+)/',function($matchs){
		return '_'.strtolower($matchs[0]);
	},$str);
		return trim(preg_replace('/_{2,}/','_',$dstr),'_');
}
//下划线命名到驼峰命名
function toCamelCase($str){
	$array = explode('_', $str);
	$result = $array[0];
	$len=count($array);
	if($len>1){
		for($i=1;$i<$len;$i++){
			$result.= ucfirst($array[$i]);
		}
	}
	return $result;
}
function parseDatePlaceholder($where = array()) {
    // 处理字符串或数组输入
    if (is_string($where)) {
        $inputIsString = true;
        $template = $where;
    } elseif (is_array($where)) {
        $inputIsString = false;
        $template = $where['_string'] ?? '';
    } else {
        return $where;
    }

    if ($template === '') {
        return $inputIsString ? '' : $where;
    }

    // 仅匹配 {today} 格式（无美元符号）
    preg_match_all('/\{([a-z]+)\}/', $template, $matches);
    if (empty($matches[1])) {
        return $inputIsString ? $template : $where;
    }

    // 替换占位符
    if (!empty($matches[1][0])) {
		$tid = $matches[1][0];
		$between = getBetweenClause($tid);
		if ($between !== '') {
			$template = str_replace('{' . $tid . '}',$between,$template);
		}
	}

    if ($inputIsString) {
        return $template;
    } else {
        $where['_string'] = $template;
        return $where;
    }
}

/**
 * 根据时间类型生成 BETWEEN 子句（不含字段名）
 * @param string $tid 时间类型：today, yesterday, month, lastmonth, year, lastyear, week, lastweek, season, lastseason
 * @return string 例如 " BETWEEN 1717920000 AND 1718006399 "
 */
function getBetweenClause($tid) {
    $now = time();
    $between = '';

    switch ($tid) {
        case 'today':
            $btime = strtotime(date('Y-m-d 00:00:00', $now));
            $etime = strtotime(date('Y-m-d 23:59:59', $now));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'yestoday':
            $btime = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day', $now)));
            $etime = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day', $now)));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'month':
            $btime = strtotime(date('Y-m-01 00:00:00', $now));
            $etime = strtotime(date('Y-m-t 23:59:59', $now));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'lastmonth':
            $lastMonthFirst = strtotime('first day of last month', $now);
            $lastMonthLast  = strtotime('last day of last month', $now);
            $btime = strtotime(date('Y-m-d 00:00:00', $lastMonthFirst));
            $etime = strtotime(date('Y-m-d 23:59:59', $lastMonthLast));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'year':
            $btime = strtotime(date('Y-01-01 00:00:00', $now));
            $etime = strtotime(date('Y-12-31 23:59:59', $now));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'lastyear':
            $lastYear = $now - 365 * 86400;
            $btime = strtotime(date('Y-01-01 00:00:00', $lastYear));
            $etime = strtotime(date('Y-12-31 23:59:59', $lastYear));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'week':
            $dayOfWeek = date('N', $now);
            $btime = strtotime('-' . ($dayOfWeek - 1) . ' days', $now);
            $btime = strtotime(date('Y-m-d 00:00:00', $btime));
            $etime = strtotime('+' . (7 - $dayOfWeek) . ' days', $now);
            $etime = strtotime(date('Y-m-d 23:59:59', $etime));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'lastweek':
            $lastWeekStart = strtotime('last week monday', $now);
            $lastWeekEnd   = strtotime('last week sunday', $now);
            $btime = strtotime(date('Y-m-d 00:00:00', $lastWeekStart));
            $etime = strtotime(date('Y-m-d 23:59:59', $lastWeekEnd));
            $between = " BETWEEN {$btime} AND {$etime} ";
            break;

        case 'season':
            $quarter = ceil(date('n', $now) / 3);
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth   = $quarter * 3;
            $btime = strtotime(date("Y-{$startMonth}-01 00:00:00", $now));
            $etime = strtotime(date("Y-{$endMonth}-t 23:59:59", $now));
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        case 'lastseason':
            $quarter = ceil(date('n', $now) / 3);
            if ($quarter == 1) {
                $year = date('Y', $now) - 1;
                $quarter = 4;
            } else {
                $year = date('Y', $now);
                $quarter--;
            }
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth   = $quarter * 3;
            $btime = strtotime("{$year}-{$startMonth}-01 00:00:00");
            $etime = strtotime("{$year}-{$endMonth}-t 23:59:59");
            $between = "BETWEEN {$btime} AND {$etime} ";
            break;

        default:
            $between = '';
    }

    return $between;
}
function xcx_payinfo($appid='',$keys='',$prepay_id=''){
	$arr = null;
	$arr['appId'] = $appid;
	$arr['timeStamp'] = time().'';
	$arr['nonceStr'] = get_unique_value();
	$arr['package'] = 'prepay_id='.trim($prepay_id);
	$arr['signType'] = 'MD5';
	ksort($arr);
	$str = ToUrlParams($arr);
	$str .= '&key='.$keys;
	$arr['paySign']= MD5($str);
	return $arr;
}
function get_unique_value(){  
	$str=uniqid(mt_rand(),1);  
	$str=sha1($str);  
	return md5($str);  
}
function ToUrlParams($data){
	$buff = "";
	foreach ($data as $k => $v)
	{
		if($k != "sign" && $v != "" && !is_array($v)){
			$buff .= $k . "=" . $v . "&";
		}
	}	
	$buff = trim($buff, "&");
	return $buff;
}

function arraytoxml($arr){  
	$xml="<xml>";  
	foreach($arr as $k=>$v){  
		$xml.="<".$k.">".$v."</".$k.">";  
	}  
	$xml.="</xml>";  
	return $xml;
}	

function xmlToArray($xml){
	$xml = new \SimpleXMLElement($xml);
	$xml || exit;
	foreach ($xml as $key => $value) {
		$data[$key] = strval($value);		}
	return $data;
}
function makeSign($data=null,$keys=''){ 
	$data=array_filter($data);  
	//签名步骤一：按字典序排序参数  
	ksort($data);  
	$string_a=http_build_query($data);  
	$string_a=urldecode($string_a);  
	//签名步骤二：在string后加入KEY  
	//$config=$this->config;  
	$string_sign_temp=$string_a."&key=".$keys;  
	//签名步骤三：MD5加密  
	$sign = md5($string_sign_temp);  
	// 签名步骤四：所有字符转为大写  
	$result=strtoupper($sign);  
	return $result;  
}
function postXmlCurl($xml, $url, $useCert = false, $second = 30){		
$ch = curl_init();

	//设置超时
	curl_setopt($ch, CURLOPT_TIMEOUT, $second);

	//如果有配置代理这里就设置代理
	if(CURL_PROXY_HOST != "0.0.0.0"	&& CURL_PROXY_PORT != 0){
	curl_setopt($ch,CURLOPT_PROXY, CURL_PROXY_HOST);
	curl_setopt($ch,CURLOPT_PROXYPORT,CURL_PROXY_PORT);
	}
	curl_setopt($ch,CURLOPT_URL, $url);


	//curl_setopt($ch, CURLOPT_VERBOSE, '1');//启用时会汇报所有的信息,存放在STDERR或指定的 CURLOPT_STDERR 中。 
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '2');//2 检查公用名是否存在,并且是否与提供的主机名匹配。 
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, '1');//cURL从服务端进行验证



	if(stripos($url,"https://")!==false){
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	}else{
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
	}


	//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
	//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验

	//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);// 只信任CA颁布的证书   
	//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);// 检查证书中是否设置域名，并且是否与提供的主机名匹配 
	//设置header
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	//要求结果为字符串且输出到屏幕上
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	if($useCert == true){
	//设置证书
	//使用证书：cert 与 key 分别属于两个.pem文件

	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');			
	curl_setopt($ch,CURLOPT_SSLCERT, ROOT.'cert/apiclient_cert.pem');

	curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
	curl_setopt($ch,CURLOPT_SSLKEY, ROOT.'cert/apiclient_key.pem');

	curl_setopt($ch,CURLOPT_CAINFO,'PEM');
	curl_setopt($ch,CURLOPT_CAINFO,ROOT.'cert/rootca.pem');

	//echo WxPayConfig::SSLCERT_PATH;
	//echo '<hr />';
	//echo WxPayConfig::SSLKEY_PATH;
	}
	//post提交方式
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	//运行curl
	$data = curl_exec($ch);
	//返回结果
	if($data){
	curl_close($ch);
	return $data;
	} else { 
	$error = curl_errno($ch);
	curl_close($ch);
	//echo 'curl出错，错误码:'.$error;
	//throw new ("curl出错，错误码:$error");
	}
}
function readFiles($filename=''){
	if(!is_file($filename)) return '';
	return file_get_contents($filename);
}
/**
 * 校验日期格式是否正确
 *
 * @param string $date 日期
 * @param string $formats 需要检验的格式数组
 * @return boolean
 */
function is_date($date, $formats = array("Y-m-d", "Y/m/d")) {
    $unixTime = strtotime($date);
    if (!$unixTime) { //strtotime转换不对，日期格式显然不对。
        return false;
    }
    //校验日期的有效性，只要满足其中一个格式就OK
    foreach ($formats as $format) {
        if (date($format, $unixTime) == $date) {
            return true;
        }
    }

    return false;
}
function expexcel_mall($id,$list){
		$tabarr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

		
		$arr = M('Exp')->where('id='.$id)->find();
		if(!$arr){
			echo '导出数据表不存在，请先添加<a href="javascript:void(0);" onclick="history.go(-1);">返回</a>';
			exit;
		}
		

		include ROOT.'App/Model/PHPExcel.php';
		require_once ROOT.'App/Model/PHPExcel/IOFactory.php';
		require_once ROOT.'App/Model/PHPExcel/Reader/Excel5.php';	


		//新建一个PHPExcel对象
		$objPHPExcel = new \PHPExcel();

		$title = $arr['title'];
		$tab = $arr['tab'];
		$exptitle = $arr['exptitle'];
		$field = $arr['field'];
		$tid = $arr['tid'];
		$width = $arr['width'];
		$exptitle_arr = explode(',',$exptitle);
		$field_arr = explode(',',$field);
		$tid_arr = explode(',',$tid);
		$width_arr = explode(',',$width);
		$height = $arr['height'];
		$background_one = $arr['background_one'];
		$background = $arr['background'];

		foreach($exptitle_arr as $kk=>$vv){
			$tabs = $tabarr[$kk].'1';
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($tabs, $vv);
			if($background_one!=''){
				$objPHPExcel->getActiveSheet()->getStyle($tabs)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($background_one);
			}
			$objPHPExcel->getActiveSheet()->getStyle($tabs)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			//$objPHPExcel->getActiveSheet()->getStyle($tabs)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
			//$objPHPExcel->getActiveSheet()->getStyle($tabs)->getBorders()->getRight()->getColor()->setARGB('000000');
		}
		if($height>0){
			$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight($height);
		}

		

		

		//echo '<table>';
		foreach($list as $key=>$vo){
			$num=$key+2;
			//echo '<tr>';
			foreach($field_arr as $keys=>$vos){
			    $tabs = $tabarr[$keys].$num;
				$objPHPExcel->getActiveSheet()->setCellValue($tabs,$vo[$vos]);
				$tidstr = intval($tid_arr[$keys]);
				switch ($tidstr){
					case 1:
						$objPHPExcel->getActiveSheet()->getStyle($tabs)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
					    break;
					case 2:
						$objPHPExcel->getActiveSheet()->getStyle($tabs)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
						break;
					case 3:
						$objPHPExcel->getActiveSheet()->getStyle($tabs)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
						break;
					default:
						$objPHPExcel->getActiveSheet()->getStyle($tabs)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
						$objPHPExcel->getActiveSheet()->getStyle($tabs)->getAlignment()->setWrapText(true);
						break;
				}
				$objPHPExcel->getActiveSheet()->getStyle($tabs)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
				if($background!=''){
					$objPHPExcel->getActiveSheet()->getStyle($tabs)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($background);
				}
				$objPHPExcel->getActiveSheet()->getStyle($tabs)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

			}
			if($height>0){
			$objPHPExcel->getActiveSheet()->getRowDimension($num)->setRowHeight($height);
			}
			
			//echo '<tr>';
		}
		//echo '</table>';


		foreach($width_arr as $kk=>$vv){
		    if($vv>0){
			    $objPHPExcel->getActiveSheet()->getColumnDimension($tabarr[$kk])->setWidth($vv);
			}
		}
		//exit;

// Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle($tab);


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel5)


		$filename = $title.'_'.date('Ymd_His');		

		header('Content-Type: application/vnd.ms-excel');

		$ua = $_SERVER["HTTP_USER_AGENT"];
		//兼容IE11
		if(preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)){
			header('Content-Disposition: attachment;filename="'.urlencode($filename).'.xls"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment;filename*="'.$filename.'.xls"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
		}
		header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
}
function formatsize($bytes,$prec=2){
    $rank=0;
    $size=$bytes;
    $unit="B";
    while($size>1024){
        $size=$size/1024;
        $rank++;
    }
    $size=round($size,$prec);
    switch ($rank){
        case "1":
            $unit="KB";
            break;
        case "2":
            $unit="MB";
            break;
        case "3":
            $unit="GB";
            break;
        case "4":
            $unit="TB";
            break;
        default :
            
    }
    return $size." ".$unit;
}
function jsencode($arr) {
	$str = str_replace("\\/","/",json_encode($arr));
	$search = "#\\\u([0-9a-f]+)#i";	
	if(strpos(strtoupper(PHP_OS),'WIN')===false){
		$replace = "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))";//LINUX
	} else {
		$replace = "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))";//WINDOWS
	}	
	return preg_replace($search,$replace,$str);
}
function hidePhone($phone) {
    if (empty($phone) || strlen($phone) < 7) {
        return $phone;
    }
    return substr_replace($phone, '****', 3, 4);
}