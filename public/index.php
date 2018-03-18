<?php
date_default_timezone_set('Asia/Shanghai');
header("Content-Type:text/html;charset=utf-8");
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');
// init
if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
    exit;
}
error_reporting(E_ALL ^ E_NOTICE);

// run
define('APP_PATH', realpath(dirname(__FILE__) . '/../'));
include APP_PATH . '/application/vendor/autoload.php';

// 定义开始时间
define('BEGIN_TIME', microtime(TRUE));

$app = new Yaf_Application(APP_PATH . '/conf/application.ini');

$app->bootstrap()->run();