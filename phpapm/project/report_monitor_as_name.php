<?php

/**
 * @desc   修改v2的别名
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_as_name extends project_config
{
    function _initialize()
    {
        $conn_db = _ocilogon($this->db);

        $sql = "select * from {$this->report_monitor_config} where id=:id ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':id', $_POST['id']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

        $sql = "update {$this->report_monitor_config} set as_name=:as_name where v2=:v2  ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':as_name', $_POST['as_name']);
        _ocibindbyname($stmt, ':v2', $_row['V2']);
        $oci_error = _ociexecute($stmt);
    }
}

?>