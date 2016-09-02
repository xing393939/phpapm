<?php
define('APM_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('APM_HOSTNAME', php_uname('n'));

//select db func
include APM_PATH . "./include/db_" . APM_DB_TYPE . ".func.php";

//select status type
include APM_PATH . "./include/_status_" . APM_QUEUE_TYPE . ".php";

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