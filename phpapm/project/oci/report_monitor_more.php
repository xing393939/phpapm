<?php

/**
 * @desc   弹出窗v4的详细信息
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_more
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        $this->pageObj = new page(10000, 100);
        if ($_REQUEST['fun_host'] == '汇总') {
            $sql = "select FUN_COUNT,v3 v4  from ".APM_DB_PREFIX."monitor_hour t where v1=:v1 and v2=:v2  and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc  ";
            $stmt = apm_db_parse($conn_db, "{$this->pageObj->num_1} {$sql} {$this->pageObj->num_3}");
            apm_db_bind_by_name($stmt, ':v1', $_REQUEST['fun_type']);
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['fun_act']);
            apm_db_bind_by_name($stmt, ':num_1', intval($this->pageObj->limit_1));
            apm_db_bind_by_name($stmt, ':num_3', intval($this->pageObj->limit_3));
            apm_db_bind_by_name($stmt, ':cal_date', $_REQUEST['cal_date']);
            $oci_error = apm_db_execute($stmt);
            $_row = array();
            $monitor_more = array();
            while ($_row = apm_db_fetch_assoc($stmt)) {
                $monitor_more[] = $_row;
            }
        } else {
            if ($_REQUEST['fun_act'])
                $sql = "select * from ".APM_DB_PREFIX."monitor where v1=:v1 and v2=:v2 and  v3=:v3 and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc ";
            else
                $sql = "select * from ".APM_DB_PREFIX."monitor where v1=:v1 and v2=:v2 and v3 is null and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc ";
            $stmt = apm_db_parse($conn_db, "{$this->pageObj->num_1} {$sql} {$this->pageObj->num_3}");
            apm_db_bind_by_name($stmt, ':v1', $_REQUEST['fun_type']);
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['fun_host']);
            apm_db_bind_by_name($stmt, ':num_1', intval($this->pageObj->limit_1));
            apm_db_bind_by_name($stmt, ':num_3', intval($this->pageObj->limit_3));
            if ($_REQUEST['fun_act'])
                apm_db_bind_by_name($stmt, ':v3', $_REQUEST['fun_act']);
            apm_db_bind_by_name($stmt, ':cal_date', $_REQUEST['cal_date']);
            $oci_error = apm_db_execute($stmt);
            $_row = array();
            $monitor_more = array();
            while ($_row = apm_db_fetch_assoc($stmt)) {
                $monitor_more[] = $_row;
            }
        }
        include APM_PATH . "./project_tpl/report_monitor_more.html";
    }
}

?>