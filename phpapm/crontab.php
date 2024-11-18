<?php
//DOS方式下的运行
if (!empty($_SERVER['argv']) && empty($_SERVER['HTTP_HOST'])) {
    foreach ($_SERVER['argv'] as $k1 => $v1) {
        $str_array = array();
        parse_str($v1, $str_array);
        if (count($str_array) > 1) {
            $key = key($str_array);
            $current = current($str_array);
            array_shift($str_array);
            $str_array[$key] = $current . "&" . http_build_query($str_array);
            $_GET = $str_array + $_GET;
            unset($str_array);
        } else
            $_GET = $str_array + $_GET;
    }
}

include dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "header.php";

header("Content-type: text/html; charset=utf-8");
ini_set('display_errors', 'On');
error_reporting(E_ALL);

$_GET['act'] = isset($_GET['act']) ? $_GET['act'] : "index";
$_GET['act_method'] = isset($_GET['act_method']) ? $_GET['act_method'] : "_initialize";
$file = APM_PATH . './crontab/' . APM_DB_TYPE . '/' . $_GET['act'] . '.php';

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
