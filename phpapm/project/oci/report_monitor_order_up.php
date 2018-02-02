<?php

/**
 * @desc   v2排序操作：上升一位
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_order_up
{
    function _initialize()
    {
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(APM_ADMIN_USER)) {
            exit();
        }

        $conn_db = apm_db_logon(APM_DB_ALIAS);
        if (!$_REQUEST['orderby'])
            $this->report_monitor_order();
        //上面的减下来
        $sql = "update  ".APM_DB_PREFIX."monitor_config set orderby=:orderby where v1=:v1 and   orderby=:orderby-1 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        apm_db_bind_by_name($stmt, ':orderby', $_REQUEST['orderby']);
        $oci_error = apm_db_execute($stmt);
        //本身上升
        $sql = "update  ".APM_DB_PREFIX."monitor_config set orderby=:orderby-1 where  v1=:v1 and v2=:v2 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        apm_db_bind_by_name($stmt, ':v2', $_REQUEST['v2']);
        apm_db_bind_by_name($stmt, ':orderby', $_REQUEST['orderby']);
        $oci_error = apm_db_execute($stmt);
        header("location: {$_SERVER['HTTP_REFERER']}");
    }
}

?>