<?php

/**
 * @desc   修改v2统计数据标示
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_stats_flag extends project_config
{
    function _initialize()
    {
        $conn_db = _ocilogon($this->db);

        $sql = "select * from {$this->report_monitor_config} where id=:id";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':id', $_POST['id']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

        $v2_config_other = unserialize($_row['V2_CONFIG_OTHER']);

        $v2_config_other['stats_flag'] = $_POST['stats_flag'];
        if (empty($_POST['stats_flag'])) {
            unset($v2_config_other['stats_flag']);
        }
        $v2_config_other = serialize($v2_config_other);
        $sql = "update {$this->report_monitor_config} set v2_config_other=:v2_config_other where v2=:v2";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v2_config_other', $v2_config_other);
        _ocibindbyname($stmt, ':v2', $_row['V2']);
        $oci_error = _ociexecute($stmt);
    }
}

?>