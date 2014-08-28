<?php
header('Content-Type: text/html;charset=utf-8');
include "header.php";
include PHPAPM_PATH . "./lib/page.class.php";

ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$_GET['act'] = isset($_GET['act']) ? $_GET['act'] : "index";
$_GET['act_method'] = isset($_GET['act_method']) ? $_GET['act_method'] : "_initialize";
$file = PHPAPM_PATH . './project/' . $_GET['act'] . '.php';

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
