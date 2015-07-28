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

//右侧菜单栏 start
$conn_db = apm_db_logon(APM_DB_ALIAS);
$sql = "select t.*,as_name as_name1 from " . APM_DB_PREFIX . "monitor_v1 t
        order by GROUP_NAME_1,GROUP_NAME_2,group_name,as_name1";
$stmt = apm_db_parse($conn_db, $sql);
$oci_error = apm_db_execute($stmt);
$v1_config_group = array();
while ($_row = apm_db_fetch_assoc($stmt)) {
    if (!$_row['AS_NAME1']) {
        $_row['AS_NAME1'] = $_row['V1'];
    }
    if (!$v1_config_group[$_row['GROUP_NAME_1']][$_row['GROUP_NAME_2']][$_row['GROUP_NAME']])
        $v1_config_group[$_row['GROUP_NAME_1']][$_row['GROUP_NAME_2']][$_row['GROUP_NAME']] = $_row['V1'];
}
//右侧菜单栏 end

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
