<?php
$u_name_arr = explode(' ', php_uname());
$u_name = strpos(PHP_OS, 'WIN') === false ? $u_name_arr[1] : $u_name_arr[2];
$u_env = 'product';
if (in_array($u_name, array('ITA-1405-3043', 'DOCTOR-PC'))) {
    $u_env = 'local';
}

$apm_uri = '';
if (isset($_GET['act'])) {
    $apm_uri = $_SERVER['PHP_SELF'] . '?act=' . $_GET['act'];
}
if (isset($_GET['s'])) {
    preg_match("/(\/[^\/]*)(\/?[^\/]*)(\/?[^\/]*)/", $_GET['s'], $apm_match);
    $apm_uri = "{$_SERVER['PHP_SELF']}?s={$apm_match[1]}{$apm_match[2]}{$apm_match[3]}";
}
define('APM_URI', $apm_uri);
define('APM_HOST', 'www.dxslaw.com');
define('APM_QUEUE_TYPE', 'db');
define('APM_QUEUE_TNS', 'tcp://10.200.25.201:19000');
define('APM_QUEUE_NAMES', '0x00000001|0x00000002');

define('APM_DB_ALIAS', 'APM');
define('APM_DB_TYPE', 'mysql');
define('APM_DB_USERNAME', 'root');
define('APM_DB_PASSWORD', $u_env == 'local' ? 'root' : '123456');
define('APM_DB_TNS', 'localhost');
define('APM_DB_NAME', $u_env == 'local' ? 'test' : 'dxslaw');
define('APM_DB_PREFIX', 'phpapm_');
define('APM_ADMIN_USER', 'xing393939,123456');
define('APM_LOG_PATH', "/usr/local/apache/logs/www.example.com");

include 'phpapm/header.inc.php';
?>