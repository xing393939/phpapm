<?php
define('APM_URI', $_SERVER['PHP_SELF'] . (isset($_GET['act']) ? '?act=' . $_GET['act'] : ''));
define('APM_HOST', 'www.dxslaw.com');
define('APM_IPCS', '0x00000222');

define('APM_DB_ALIAS', 'APM');
define('APM_DB_TYPE', 'mysql');
define('APM_DB_USERNAME', 'root');
define('APM_DB_PASSWORD', '86bc1ec713');
define('APM_DB_TNS', 'localhost');
define('APM_DB_NAME', 'dxslaw');
define('APM_DB_PREFIX', 'phpapm_');
define('APM_ADMIN_USER', 'xing393939,159159');
define('APM_LOG_PATH', "/usr/local/apache/logs/www.example.com");

class apm_project_config
{
    var $report_monitor_queue = 'phpapm_monitor_queue';
    var $report_monitor = 'phpapm_monitor';
    var $report_monitor_config = 'phpapm_monitor_config';
    var $report_monitor_v1 = 'phpapm_monitor_v1';
    var $report_monitor_date = 'phpapm_monitor_date';
    var $report_monitor_hour = 'phpapm_monitor_hour';
}
include 'phpapm/header.inc.php';
?>