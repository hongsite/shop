<?php
//数据库通用配置
return array(
    'DB_TYPE' => 'mysqli',
    'DB_HOST' => '127.0.0.1',//数据库地址
    'DB_NAME' => 'shop',//数据库名
    'DB_USER' => 'root',//用户名
    'DB_PWD'  => 'root',//用户密码
    'DB_PORT' => 3306,
    'DB_PREFIX' => 'hst_',
    'VERSION' => '1.0.0',
    'THEME' => true,
    'DEFAULT_THEME' => 'Pc',
    'DB_CHARSET' => 'utf8',
    'DOMAINPIC' => 'hongsite.com',
    'DB_SUB_AFT' => 'A',
    'DB_SUB_NUMS' => 2000000,
    'DB_SUB_NUMS_ORDERS' => 5000000,
    'DB_SUB_NUMS_ADDRESS' => 50000,
    'DB_SUB_NUMS_CART' => 50000,
    'DB_SUB_NUMS_FAV' => 50000,
    'COOKIE_PREFIX' => 'hst_',
    'SESSION_PREFIX' => 'hst_',
    'HTMLSPECIAL' => true
);