<?php
define('PHPAPM_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
$u_name_arr = explode(' ', php_uname());
define('VIP', strpos(PHP_OS, 'WIN') === false ? $u_name_arr[1] : $u_name_arr[2]);
define('VHOST', 'www.dxslaw.com');
define('IPCS', '0x00000222');
include PHPAPM_PATH . "./common/oci2mysql.php";
include PHPAPM_PATH . "./common/_status.php";
include PHPAPM_PATH . "./common/header_function.php";

class oracleDB_config
{
    var $dbconfig = array(
        'PHPAPM' => array(
            'db_type' => 'mysql',
            'user_name' => 'root',
            'password' => 'root',
            'TNS' => 'localhost',
            'db' => 'test'
        ),
    );
}

class memcache_config
{
    var $config = array(
        '19' => array(
            array(
                'host' => '10.77.6.19',
                'port' => 11211
            ),
            array(
                'host' => '10.77.6.20',
                'port' => 11311
            ),
        )
    );
}

class project_config
{
    var $db = 'PHPAPM';
    var $admin_user = array('admin', 'admin');
    var $log_path = "/usr/local/apache/logs/www.example.com";
    var $ipcs = IPCS;
    var $report_monitor_queue = 'phpapm_monitor_queue';
    var $report_monitor = 'phpapm_monitor';
    var $report_monitor_config = 'phpapm_monitor_config';
    var $report_monitor_v1 = 'phpapm_monitor_v1';
    var $report_monitor_date = 'phpapm_monitor_date';
    var $report_monitor_hour = 'phpapm_monitor_hour';
}