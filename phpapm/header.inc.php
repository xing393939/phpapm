<?php
define('APM_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
$u_name_arr = explode(' ', php_uname());
define('APM_VIP', strpos(PHP_OS, 'WIN') === false ? $u_name_arr[1] : $u_name_arr[2]);
if (in_array(APM_VIP, array('ITA-1405-3043', 'DOCTOR-PC'))) {
    define('APM_LOCAL_ENV', true);
}
include APM_PATH . "./include/oci2mysql.func.php";
include APM_PATH . "./include/_status" . (strpos(PHP_OS, 'WIN') === false ? '_ipcs.php' : '.php');
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