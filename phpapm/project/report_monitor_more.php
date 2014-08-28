<?php

/**
 * @desc   弹出窗v4的详细信息
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_more extends project_config
{
    function _initialize()
    {
        $conn_db = _ocilogon($this->db);
        $this->pageObj = new page(10000, 100);
        if ($_REQUEST['fun_host'] == '汇总') {
            $sql = "select FUN_COUNT,v3 v4  from {$this->report_monitor_hour} t where v1=:v1 and v2=:v2  and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc  ";
            $stmt = _ociparse($conn_db, "{$this->pageObj->num_1} {$sql} {$this->pageObj->num_3}");
            _ocibindbyname($stmt, ':v1', $_REQUEST['fun_type']);
            _ocibindbyname($stmt, ':v2', $_REQUEST['fun_act']);
            _ocibindbyname($stmt, ':num_1', intval($this->pageObj->limit_1));
            _ocibindbyname($stmt, ':num_3', intval($this->pageObj->limit_3));
            _ocibindbyname($stmt, ':cal_date', $_REQUEST['cal_date']);
            $oci_error = _ociexecute($stmt);
            $_row = array();
            $monitor_more = array();
            while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
                $monitor_more[] = $_row;
            }
        } else {
            if ($_REQUEST['fun_act'])
                $sql = "select * from {$this->report_monitor} where v1=:v1 and v2=:v2 and  v3=:v3 and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc ";
            else
                $sql = "select * from {$this->report_monitor} where v1=:v1 and v2=:v2 and v3 is null and cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss') order by FUN_COUNT desc ";
            $stmt = _ociparse($conn_db, "{$this->pageObj->num_1} {$sql} {$this->pageObj->num_3}");
            _ocibindbyname($stmt, ':v1', $_REQUEST['fun_type']);
            _ocibindbyname($stmt, ':v2', $_REQUEST['fun_host']);
            _ocibindbyname($stmt, ':num_1', intval($this->pageObj->limit_1));
            _ocibindbyname($stmt, ':num_3', intval($this->pageObj->limit_3));
            if ($_REQUEST['fun_act'])
                _ocibindbyname($stmt, ':v3', $_REQUEST['fun_act']);
            _ocibindbyname($stmt, ':cal_date', $_REQUEST['cal_date']);
            $oci_error = _ociexecute($stmt);
            $_row = array();
            $monitor_more = array();
            while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
                $monitor_more[] = $_row;
            }
        }
        include PHPAPM_PATH . "./project_tpl/report_monitor_more.html";
    }
}

?>