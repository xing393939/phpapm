<?php

/**
 * @desc   修改v1配置
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_v1_do extends project_config
{
    function _initialize()
    {
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(serialize($this->admin_user))) {
            exit();
        }

        $conn_db = _ocilogon($this->db);
        //删除v1
        if ($_POST['delete_v1']) {
            $this->_report_monitor_delete($conn_db);
        } else {
            $sql = "select * from {$this->report_monitor_v1} t where v1=:v1 ";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $_GET['v1']);
            $oci_error = _ociexecute($stmt);
            $_row = array();
            ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

            $sql = "update {$this->report_monitor_v1} set as_name=:as_name,count_type=:count_type,char_type=:char_type,
        group_name=:group_name,group_name_1=:group_name_1,group_name_2=:group_name_2,start_clock=:start_clock,show_template=:show_template,show_all=1,
        percent_count_type=:percent_count_type,day_count_type=:day_count_type,hour_count_type=:hour_count_type,
        duibi_name=:duibi_name,is_duty=:is_duty,pinfen_rule_name=:pinfen_rule_name
        where v1=:v1 ";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $_GET['v1']);
            _ocibindbyname($stmt, ':as_name', $_POST['as_name']);
            _ocibindbyname($stmt, ':count_type', $_POST['count_type']);
            _ocibindbyname($stmt, ':char_type', $_POST['char_type']);
            _ocibindbyname($stmt, ':group_name', $_POST['group_name']);
            _ocibindbyname($stmt, ':group_name_1', $_POST['group_name_1']);
            _ocibindbyname($stmt, ':group_name_2', $_POST['group_name_2']);
            _ocibindbyname($stmt, ':start_clock', $_POST['start_clock']);
            _ocibindbyname($stmt, ':show_template', $_POST['show_template']);
            _ocibindbyname($stmt, ':percent_count_type', $_POST['percent_count_type']);
            _ocibindbyname($stmt, ':day_count_type', $_POST['day_count_type']);
            _ocibindbyname($stmt, ':hour_count_type', $_POST['hour_count_type']);
            _ocibindbyname($stmt, ':duibi_name', $_POST['duibi_name']);
            _ocibindbyname($stmt, ':is_duty', intval($_POST['is_duty']));
            _ocibindbyname($stmt, ':pinfen_rule_name', $_POST['pinfen_rule_name']);
            $oci_error = _ociexecute($stmt);
            print_r($oci_error);
            //排版统一
            if ($_POST['show_template_checkbox'] == 1) {
                $sql = "update {$this->report_monitor_v1} set show_template=:show_template  where group_name=:group_name ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':show_template', $_POST['show_template']);
                _ocibindbyname($stmt, ':group_name', $_POST['group_name']);
                $oci_error = _ociexecute($stmt);
            }
            foreach (array(
                         'percent_count_type',
                         'day_count_type',
                         'hour_count_type',
                     ) as $k => $v) {
                //统一同类型配置
                if ($_POST[$v] != 'NULL') {
                    $sql = "update {$this->report_monitor_config} set {$v}=:{$v}  where v1=:v1 ";
                    $stmt = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt, ':v1', $_GET['v1']);
                    _ocibindbyname($stmt, ":{$v}", $_POST[$v]);
                    $oci_error = _ociexecute($stmt);
                    print_r($oci_error);
                }
            }
            //直接联动修改分组名称
            if ($_POST['show_group'] && $_POST['group_name_1'] <> $_row['GROUP_NAME_1']) {
                $sql = "update {$this->report_monitor_v1} t set group_name_1=:group_name_1
            where group_name=:group_name_old and  group_name_1=:group_name_1_old ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':group_name', $_POST['group_name']);
                _ocibindbyname($stmt, ':group_name_old', $_row['GROUP_NAME']);
                _ocibindbyname($stmt, ':group_name_1', $_POST['group_name_1']);
                _ocibindbyname($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
            }

            //直接联动修改分组名称
            if ($_POST['show_group_2'] && $_POST['group_name_2'] <> $_row['GROUP_NAME_2']) {
                $sql = "update {$this->report_monitor_v1} t set  group_name_2=:group_name_2
            where group_name=:group_name_old and group_name_1=:group_name_1_old  and group_name_2=:group_name_2_old ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':group_name', $_POST['group_name']);
                _ocibindbyname($stmt, ':group_name_old', $_row['GROUP_NAME']);
                _ocibindbyname($stmt, ':group_name_1', $_POST['group_name_1']);
                _ocibindbyname($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                _ocibindbyname($stmt, ':group_name_2', $_POST['group_name_2']);
                _ocibindbyname($stmt, ':group_name_2_old', $_row['GROUP_NAME_2']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
            }

            //直接联动修改分组名称
            if ($_POST['show_group_3'] && $_POST['group_name'] <> $_row['GROUP_NAME']) {
                $sql = "update {$this->report_monitor_v1} t set group_name=:group_name
            where group_name=:group_name_old and group_name_1=:group_name_1_old  and group_name_2=:group_name_2_old ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':group_name', $_POST['group_name']);
                _ocibindbyname($stmt, ':group_name_old', $_row['GROUP_NAME']);
                _ocibindbyname($stmt, ':group_name_1', $_POST['group_name_1']);
                _ocibindbyname($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                _ocibindbyname($stmt, ':group_name_2', $_POST['group_name_2']);
                _ocibindbyname($stmt, ':group_name_2_old', $_row['GROUP_NAME_2']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
            }
        }
        header("location: {$_SERVER['HTTP_REFERER']}");
    }

    /**
     * @desc   删除v1
     * @author xing39393939@gmail.com
     * @since  2012-11-12 14:55:11
     * @throws 注意:无DB异常处理
     */
    function _report_monitor_delete($conn_db)
    {
        if ($_REQUEST['v2']) {
            $where = 'and v2=:v2';
        }
        $sql = "delete from {$this->report_monitor} where v1=:v1 {$where} and  cal_date>sysdate-10/24 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);
        $sql = "delete from {$this->report_monitor_config} where v1=:v1 {$where} ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "delete from {$this->report_monitor_date} where v1=:v1 {$where} and cal_date>sysdate-10 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "delete from {$this->report_monitor_hour} where v1=:v1 {$where}  and cal_date>sysdate-10";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "select * from {$this->report_monitor_config} where v1=:v1 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        if (!$_row) {
            $sql = "delete from {$this->report_monitor_v1} where v1=:v1   ";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
            _ociexecute($stmt);
        }
    }
}

?>