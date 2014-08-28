<?php

/**
 * @desc   更新一下v2的排序
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_sort extends project_config
{
    function _initialize()
    {
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(serialize($this->admin_user))) {
            exit();
        }

        $conn_db = _ocilogon($this->db);
        $sql = "select * from {$this->report_monitor_config} t where v1=:v1 order by v2_group,decode(as_name,null,v2,as_name) asc ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        $i = 0;
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $i++;
            $sql2 = "update {$this->report_monitor_config} set orderby=:orderby where id=:id ";
            $stmt2 = _ociparse($conn_db, $sql2);
            _ocibindbyname($stmt2, ':id', $_row['ID']);
            _ocibindbyname($stmt2, ':orderby', $i);
            $oci_error_2 = _ociexecute($stmt2);
            $_row2 = array();
        }
        header("location: {$_SERVER['HTTP_REFERER']}");
    }
}

?>