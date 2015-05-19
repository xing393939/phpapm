<?php

/**
 * @desc   更新一下v2的排序
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_sort
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        $sql = "select * from ".APM_DB_PREFIX."monitor_config t
                where v1=:v1 order by v2_group, ifnull(as_name, v2) asc";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        $i = 0;
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $i++;
            $sql2 = "update ".APM_DB_PREFIX."monitor_config set orderby=:orderby where id=:id ";
            $stmt2 = apm_db_parse($conn_db, $sql2);
            apm_db_bind_by_name($stmt2, ':id', $_row['ID']);
            apm_db_bind_by_name($stmt2, ':orderby', $i);
            $oci_error_2 = apm_db_execute($stmt2);
        }
        header("location: {$_SERVER['HTTP_REFERER']}");
    }
}

?>