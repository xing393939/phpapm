<?php

/**
 * @desc   修改v1配置
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_v1_do
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        //删除v1
        if ($_POST['delete_v1']) {
            $this->_report_monitor_delete($conn_db);
        //删除v1、v2、v3的pv=1的
        } elseif (!empty($_GET['v1']) && !empty($_GET['v2']) && !empty($_GET['start_date'])) {
            $sql = "delete from ".APM_DB_PREFIX."monitor_hour where v1=:v1 and v2=:v2 and cal_date>=:cal_date1 and cal_date<:cal_date2 and fun_count<2";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt, ':v1', $_GET['v1']);
            apm_db_bind_by_name($stmt, ':v2', trim($_GET['v2']));
            apm_db_bind_by_name($stmt, ':cal_date1', $_GET['start_date']);
            apm_db_bind_by_name($stmt, ':cal_date2', date('Y-m-d', strtotime($_GET['start_date']) + 86400));
            $oci_error = apm_db_execute($stmt);

        } else {
            $sql = "select * from ".APM_DB_PREFIX."monitor_v1 t where v1=:v1 ";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt, ':v1', $_GET['v1']);
            $oci_error = apm_db_execute($stmt);
            $_row = apm_db_fetch_assoc($stmt);

            $sql = "update ".APM_DB_PREFIX."monitor_v1 set as_name=:as_name,count_type=:count_type,char_type=:char_type,
        group_name=:group_name,group_name_1=:group_name_1,group_name_2=:group_name_2,start_clock=:start_clock,show_template=:show_template,show_all=1,
        percent_count_type=:percent_count_type,day_count_type=:day_count_type,hour_count_type=:hour_count_type,
        duibi_name=:duibi_name,is_duty=:is_duty,pinfen_rule_name=:pinfen_rule_name
        where v1=:v1 ";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt, ':v1', $_GET['v1']);
            apm_db_bind_by_name($stmt, ':as_name', $_POST['as_name']);
            apm_db_bind_by_name($stmt, ':count_type', $_POST['count_type']);
            apm_db_bind_by_name($stmt, ':char_type', $_POST['char_type']);
            apm_db_bind_by_name($stmt, ':group_name', $_POST['group_name']);
            apm_db_bind_by_name($stmt, ':group_name_1', $_POST['group_name_1']);
            apm_db_bind_by_name($stmt, ':group_name_2', $_POST['group_name_2']);
            apm_db_bind_by_name($stmt, ':start_clock', $_POST['start_clock']);
            apm_db_bind_by_name($stmt, ':show_template', $_POST['show_template']);
            apm_db_bind_by_name($stmt, ':percent_count_type', $_POST['percent_count_type']);
            apm_db_bind_by_name($stmt, ':day_count_type', $_POST['day_count_type']);
            apm_db_bind_by_name($stmt, ':hour_count_type', $_POST['hour_count_type']);
            apm_db_bind_by_name($stmt, ':duibi_name', $_POST['duibi_name']);
            apm_db_bind_by_name($stmt, ':is_duty', intval($_POST['is_duty']));
            apm_db_bind_by_name($stmt, ':pinfen_rule_name', $_POST['pinfen_rule_name']);
            $oci_error = apm_db_execute($stmt);
            print_r($oci_error);
            //排版统一
            if ($_POST['show_template_checkbox'] == 1) {
                $sql = "update ".APM_DB_PREFIX."monitor_v1 set show_template=:show_template  where group_name=:group_name ";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':show_template', $_POST['show_template']);
                apm_db_bind_by_name($stmt, ':group_name', $_POST['group_name']);
                $oci_error = apm_db_execute($stmt);
            }
            foreach (array(
                         'percent_count_type',
                         'day_count_type',
                         'hour_count_type',
                     ) as $k => $v) {
                //统一同类型配置
                if ($_POST[$v] != 'NULL') {
                    $sql = "update ".APM_DB_PREFIX."monitor_config set {$v}=:{$v}  where v1=:v1 ";
                    $stmt = apm_db_parse($conn_db, $sql);
                    apm_db_bind_by_name($stmt, ':v1', $_GET['v1']);
                    apm_db_bind_by_name($stmt, ":{$v}", $_POST[$v]);
                    $oci_error = apm_db_execute($stmt);
                    print_r($oci_error);
                }
            }
            //直接联动修改分组名称
            if ($_POST['show_group'] && $_POST['group_name_1'] <> $_row['GROUP_NAME_1']) {
                $sql = "update ".APM_DB_PREFIX."monitor_v1 t set group_name_1=:group_name_1
            where group_name=:group_name_old and  group_name_1=:group_name_1_old ";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':group_name', $_POST['group_name']);
                apm_db_bind_by_name($stmt, ':group_name_old', $_row['GROUP_NAME']);
                apm_db_bind_by_name($stmt, ':group_name_1', $_POST['group_name_1']);
                apm_db_bind_by_name($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                $oci_error = apm_db_execute($stmt);
                print_r($oci_error);
            }

            //直接联动修改分组名称
            if ($_POST['show_group_2'] && $_POST['group_name_2'] <> $_row['GROUP_NAME_2']) {
                $sql = "update ".APM_DB_PREFIX."monitor_v1 t set  group_name_2=:group_name_2
            where group_name=:group_name_old and group_name_1=:group_name_1_old  and group_name_2=:group_name_2_old ";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':group_name', $_POST['group_name']);
                apm_db_bind_by_name($stmt, ':group_name_old', $_row['GROUP_NAME']);
                apm_db_bind_by_name($stmt, ':group_name_1', $_POST['group_name_1']);
                apm_db_bind_by_name($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                apm_db_bind_by_name($stmt, ':group_name_2', $_POST['group_name_2']);
                apm_db_bind_by_name($stmt, ':group_name_2_old', $_row['GROUP_NAME_2']);
                $oci_error = apm_db_execute($stmt);
                print_r($oci_error);
            }

            //直接联动修改分组名称
            if ($_POST['show_group_3'] && $_POST['group_name'] <> $_row['GROUP_NAME']) {
                $sql = "update ".APM_DB_PREFIX."monitor_v1 t set group_name=:group_name
            where group_name=:group_name_old and group_name_1=:group_name_1_old  and group_name_2=:group_name_2_old ";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':group_name', $_POST['group_name']);
                apm_db_bind_by_name($stmt, ':group_name_old', $_row['GROUP_NAME']);
                apm_db_bind_by_name($stmt, ':group_name_1', $_POST['group_name_1']);
                apm_db_bind_by_name($stmt, ':group_name_1_old', $_row['GROUP_NAME_1']);
                apm_db_bind_by_name($stmt, ':group_name_2', $_POST['group_name_2']);
                apm_db_bind_by_name($stmt, ':group_name_2_old', $_row['GROUP_NAME_2']);
                $oci_error = apm_db_execute($stmt);
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
        $sql = "delete from ".APM_DB_PREFIX."monitor where v1=:v1 {$where} and cal_date>:cal_date_10h";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        apm_db_bind_by_name($stmt, ':cal_date_10h', date('Y-m-d H:i:s', time() - 36000));
        if ($_REQUEST['v2']) {
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = apm_db_execute($stmt);
        $sql = "delete from ".APM_DB_PREFIX."monitor_config where v1=:v1 {$where}";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = apm_db_execute($stmt);

        $sql = "delete from ".APM_DB_PREFIX."monitor_date where v1=:v1 {$where} and cal_date>:cal_date_10d";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        apm_db_bind_by_name($stmt, ':cal_date_10d', date('Y-m-d H:i:s', time() - 864000));
        if ($_REQUEST['v2']) {
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = apm_db_execute($stmt);

        $sql = "delete from ".APM_DB_PREFIX."monitor_hour where v1=:v1 {$where} and cal_date>:cal_date_10d";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        apm_db_bind_by_name($stmt, ':cal_date_10d', date('Y-m-d H:i:s', time() - 864000));
        if ($_REQUEST['v2']) {
            apm_db_bind_by_name($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = apm_db_execute($stmt);

        $sql = "select * from ".APM_DB_PREFIX."monitor_config where v1=:v1";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        $_row = apm_db_fetch_assoc($stmt);
        if (!$_row) {
            $sql = "delete from ".APM_DB_PREFIX."monitor_v1 where v1=:v1";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
            apm_db_execute($stmt);
        }
    }
}

?>