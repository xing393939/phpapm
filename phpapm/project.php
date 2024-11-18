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
            $a->{$_GET['act_method']}();
        } else {
            exit("{$_GET['act_method']} method not found");
        }
    } else {
        exit("{$_GET['act']} class not found");
    }
} else {
    exit("{$_GET['act']} file not found");
}

function format_unit($num, $unitType = 'number')
{
    $str = '<span style="color:#AAA">0</span>';
    if ($num == 0) {
        return $str;
    }
    if ($unitType == 'capacity') {
        if ($num > 1024 * 1024) {
            $str = round($num / 1024 / 1024, 1) . 'T';
        } elseif ($num > 1024) {
            $str = round($num / 1024, 1) . 'G';
        } else {
            $str = round($num, 1) . 'M';
        }
    } elseif ($unitType == 'memory') {
        if ($num < 1024) {
            $str = sprintf('%.4fM', $num);
        } elseif ($num < 1024 * 1024) {
            $str = sprintf('%.4fG', $num / 1024);
        } else {
            $str = sprintf('%.4fT', $num / (1024 * 1024));
        }
    } elseif ($unitType == 'time') {
        if ($num < 60) {
            $str = sprintf('%.4f秒', $num);
        } elseif ($num < 3600) {
            $str = sprintf('%.4f分', $num / 60);
        } else {
            $str = sprintf('%.4f小时', $num / 3600);
        }
    } else {
        if ($num > 10000 * 10000) {
            $str = round($num / 10000 / 10000, 1) . '亿';
        } elseif ($num > 10000) {
            $str = round($num / 10000, 1) . '万';
        } else {
            $str = $num;
        }
    }
    return $str;
}