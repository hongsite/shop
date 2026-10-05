<?php
/*
*@author 牛头
*@company 鸿思特科技
*@funtion 运行实例
*@date 2026/9/28
*@version v1.0.0
*/
ini_set('date.timezone', 'Asia/Shanghai');
// 开发模式开启，生产环境请关闭
ini_set("display_errors", "On");
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");

defined('LIB_PATH') or define('LIB_PATH', './Controller/');

require ROOT . 'Core/Function.php';
require ROOT . 'Conf/function.php';

// 先加载配置，再启动 session

if(!file_exists(ROOT . APP_NAME . '/Conf/config.php')) {
	$js = array('status'=>0,'msg'=>'请先配置数据库文件，请将api/Config/config.bak.php修改为config.php，并配置数据库链接参数');
	echo json_encode($js);
	exit;
}
C(include ROOT . APP_NAME . '/Conf/config.php');
session_start();

$btime = microtime(true);
require ROOT . 'Core/Route.php';

require ROOT . 'Core/Controller.php';
require ROOT . 'Core/Model.php';
require ROOT . 'Core/Db.php';
require ROOT . 'Core/DbMysqli.php';

if (is_file(APP_PATH . 'Controller/Common.php')) {
    require APP_PATH . 'Controller/Common.php';
}

$controllerName = MODULE_NAME;
$d = 'Hst\\' . $controllerName . '\\' . $controllerName;
$a = ACTION_NAME;
$sfile = APP_PATH . 'Controller/' . $controllerName . '.php';
if (!is_file($sfile)) {
    echo '控制文件不存在';
    exit;
}
require $sfile;
$a == '' && $a = 'index';
$c = new $d();
if (!method_exists($c, $a)) {
    echo 'action不存在';
    exit;
}
$c->$a();