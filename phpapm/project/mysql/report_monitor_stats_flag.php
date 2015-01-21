<?php

/**
 * @desc   修改v2统计数据标示
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_stats_flag
{
    function _initialize()
    {
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(APM_ADMIN_USER)) {
            exit();
        }

        $conn_db = apm_db_logon(APM_DB_ALIAS);

        $sql = "select * from ".APM_DB_PREFIX."monitor_config where id=:id";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':id', $_POST['id']);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        $_row = apm_db_fetch_assoc($stmt);

        $v2_config_other = unserialize($_row['V2_CONFIG_OTHER']);

        $v2_config_other['stats_flag'] = $_POST['stats_flag'];
        if (empty($_POST['stats_flag'])) {
            unset($v2_config_other['stats_flag']);
        }
        $v2_config_other = serialize($v2_config_other);
        $sql = "update ".APM_DB_PREFIX."monitor_config set v2_config_other=:v2_config_other where v2=:v2";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v2_config_other', $v2_config_other);
        apm_db_bind_by_name($stmt, ':v2', $_row['V2']);
        $oci_error = apm_db_execute($stmt);
    }
}

?>