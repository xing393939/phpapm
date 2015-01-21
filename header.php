<?php
$u_name_arr = explode(' ', php_uname());
$u_name = strpos(PHP_OS, 'WIN') === false ? $u_name_arr[1] : $u_name_arr[2];
$u_env = 'product';
if (in_array($u_name, array('ITA-1405-3043', 'DOCTOR-PC'))) {
    $u_env = 'local';
}

define('APM_URI', $_SERVER['PHP_SELF'] . (isset($_GET['act']) ? '?act=' . $_GET['act'] : ''));
define('APM_HOST', 'www.dxslaw.com');
define('APM_IPCS', '0x00000222');

define('APM_DB_ALIAS', 'APM');
define('APM_DB_TYPE', 'mysql');
define('APM_DB_USERNAME', 'root');
define('APM_DB_PASSWORD', $u_env == 'local' ? 'root' : '86bc1ec713');
define('APM_DB_TNS', 'localhost');
define('APM_DB_NAME', $u_env == 'local' ? 'test' : 'dxslaw');
define('APM_DB_PREFIX', 'phpapm_');
define('APM_ADMIN_USER', 'xing393939,159159');
define('APM_LOG_PATH', "/usr/local/apache/logs/www.example.com");

include 'phpapm/header.inc.php';
?>