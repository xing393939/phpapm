<?php
define('APM_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
$u_name_arr = explode(' ', php_uname());
define('APM_VIP', strpos(PHP_OS, 'WIN') === false ? $u_name_arr[1] : $u_name_arr[2]);

//select db func
if (APM_DB_TYPE == 'mysql') {
    include APM_PATH . "./include/db_mysql.func.php";
    include APM_PATH . "./include/oci2mysql.func.php";
} elseif (APM_DB_TYPE == 'oci') {
    include APM_PATH . "./include/db_oci.func.php";
} else {
    exit('config error');
}

//select status type
if (defined('APM_IPCS')) {
    include APM_PATH . "./include/_status_ipcs.php";
    if (isset($_GET['act']) && $_GET['act'] == 'monitor' &&
        strpos($_SERVER['PHP_SELF'], 'crontab.php') !== false) {
        $_GET['act'] = 'monitor_ipcs';
    }
} else {
    include APM_PATH . "./include/_status.php";
}
include APM_PATH . "./include/header.func.php";

class apm_db_config
{
    var $dbconfig = array(
        APM_DB_ALIAS => array(
            'db_type' => APM_DB_TYPE,
            'user_name' => APM_DB_USERNAME,
            'password' => APM_DB_PASSWORD,
            'TNS' => APM_DB_TNS,
            'db' => APM_DB_NAME
        ),
    );
}