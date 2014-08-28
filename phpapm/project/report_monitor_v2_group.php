<?php

/**
 * @desc   修改v2分组的别名
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_v2_group extends project_config
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

        $sql = "update {$this->report_monitor_config} set v2_group=:v2_group where v2=:v2";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v2_group', $_POST['v2_group']);
        _ocibindbyname($stmt, ':v2', $_row['V2']);
        $oci_error = _ociexecute($stmt);
    }
}

?>