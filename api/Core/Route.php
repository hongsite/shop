<?php
/*
*@author 牛头
*@company 鸿思特科技
*@funtion 路由转发
*@date 2026/9/28
*@version v1.0.0
*/
$controllerName = 'Index';
$actionName = 'index';
const CURL_PROXY_HOST = "0.0.0.0";
const CURL_PROXY_PORT = 0;
$url = $_SERVER['REQUEST_URI'];
$url = strtolower($url);
$url = substr($url,1,strlen($url));
$url = rtrim($url,'/');
$info   =  parse_url($url);
$path = isset($info['path']) ? $info['path']:'';
if(substr($path,0,4)=='api/') $path = substr($path,4,strlen($path));
$controllerName = 'Index';
$actionName = 'index';
if(isset($_GET['s']) && isset($_GET['a'])){
	$controllerName = $_GET['s'];
	$actionName = $_GET['a'];
}else if(strpos($path,'index.php')!==false){
	$controllerName = isset($_GET['s']) ? $_GET['s']:$controllerName;
	$actionName = isset($_GET['a']) ? $_GET['a']:$actionName;
}else{
	$depr = '/';
	$params = null;
	if(!empty($path)){
		$params = explode($depr,trim($path,$depr));
	}
	if(is_dir(ROOT.$path.'/')){
		$controllerName = 'Index';
		$actionName = 'index';
	}else{
		$controllerName = !empty($params)?array_shift($params):'Index';
		$actionName =!empty($params)?array_shift($params):'index';
		$controllerName = ucwords($controllerName);
		if(!empty($params)){
			if(count($params)>1){				
				if($params[0]=='api'){
					foreach($params as $k=>$v){
						if(($k+2) % 2==0 && $k>0){
							$_GET[$params[$k-2]]=$v;
						}
					}
					if(!empty($params)){
						if (count($params) > 1) {
							if ($params[0] == 'api') {
								foreach ($params as $k => $v) {
									if (($k + 2) % 2 == 0 && $k > 0 && isset($params[$k - 2])) {
										$_GET[$params[$k - 2]] = $v;
									}
								}
							} else {
								foreach ($params as $k => $v) {
									if (($k + 1) % 2 == 0 && isset($params[$k - 1])) {
										$_GET[$params[$k - 1]] = $v;
									}
								}
							}
						}
					}
				}else{
					foreach($params as $k=>$v){
						if(($k+1) % 2==0){
							$_GET[$params[$k-1]]=$v;
						}
					}
				}
			}
		}
	}
	if(substr($actionName,-5)=='.html'){
		$actionName = substr($actionName,0,strlen($actionName)-5);
	}
}
$controllerName = ucwords($controllerName);
$adfunname = ROOT.APP_NAME.'/Config/common.php';	
if(is_file($adfunname)){		
    require $adfunname;
}
$adconname = ROOT.APP_NAME.'/Config/config.php';	
if(is_file($adconname)){		
	C(include $adconname);
}
if(!defined('MODULE_NAME')) define('MODULE_NAME',toCamelCase($controllerName));
if(!defined('ACTION_NAME')) define('ACTION_NAME',$actionName);