<?php
header('Content-Type: text/html;charset=utf-8');
include "../header.php";
include APM_PATH . "./include/page.class.php";

ini_set('display_errors', 'On');
error_reporting(E_ALL);

$_GET['act'] = isset($_GET['act']) ? $_GET['act'] : "index";
$_GET['act_method'] = isset($_GET['act_method']) ? $_GET['act_method'] : "_initialize";
$file = APM_PATH . './project/' . APM_DB_TYPE . '/' . $_GET['act'] . '.php';

//检查登录
if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(APM_ADMIN_USER)) {
    if (!in_array($_GET['act'], array('index', 'login'))) {
        exit('404');
    }
}

if (file_exists($file)) {
    include $file;
    if (class_exists($_GET['act'])) {
        $a = new $_GET['act']();
        if (method_exists($a, $_GET['act_method'])) {
            $a->$_GET['act_method']();
        } else {
            exit("{$_GET['act_method']} method not found");
        }
    } else {
        exit("{$_GET['act']} class not found");
    }
} else {
    exit("{$_GET['act']} file not found");
}
