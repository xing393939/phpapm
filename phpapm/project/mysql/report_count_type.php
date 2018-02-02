<?php

/**
 * @desc   批量设置v2
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_count_type
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        foreach ($_POST['uncount'] as $k => $v) {
            list($v1, $v2) = (explode('#@', $v));
            $_REQUEST['v1'] = $v1;
            $_REQUEST['v2'] = $v2;
            if ($_POST['all_delete']) {
                $this->_report_monitor_delete($conn_db);
            } else {
                $min_count_type_1 = 0;
                $where = array();
                if ($_POST['percent_count_type_1'] <> 'NULL')
                    $where[] = " percent_count_type=:percent_count_type ";
                if ($_POST['day_count_type_1'] <> 'NULL') {
                    $where[] = " day_count_type=:day_count_type ";
                }
                if ($_POST['hour_count_type_1'] <> 'NULL') {
                    $where[] = " hour_count_type=:hour_count_type ";
                }
                if (!empty($where)) {
                    $where = join(',', $where);
                    $sql = "update ".APM_DB_PREFIX."monitor_config set {$where} where v1=:v1 and v2=:v2 ";
                    $stmt = apm_db_parse($conn_db, $sql);
                    if ($_POST['percent_count_type_1'] <> 'NULL')
                        apm_db_bind_by_name($stmt, ':percent_count_type', $_POST['percent_count_type_1']);
                    if ($_POST['day_count_type_1'] <> 'NULL')
                        apm_db_bind_by_name($stmt, ':day_count_type', $_POST['day_count_type_1']);
                    if ($_POST['hour_count_type_1'] <> 'NULL')
                        apm_db_bind_by_name($stmt, ':hour_count_type', $_POST['hour_count_type_1']);

                    apm_db_bind_by_name($stmt, ':v1', $v1);
                    apm_db_bind_by_name($stmt, ':v2', $v2);
                    $oci_error = apm_db_execute($stmt);
                    print_r($oci_error);
                }
                //联动同名不同v1下面的v2
                if ($_POST['group_all']) {
                    $sql = "select * from  ".APM_DB_PREFIX."monitor_config  where v1=:v1 and v2=:v2 ";
                    $stmt = apm_db_parse($conn_db, $sql);
                    apm_db_bind_by_name($stmt, ':v1', $v1);
                    apm_db_bind_by_name($stmt, ':v2', $v2);
                    $oci_error = apm_db_execute($stmt);
                    $_row = array();
                    $_row = apm_db_fetch_assoc($stmt);

                    $sql = "update ".APM_DB_PREFIX."monitor_config t set percent_count_type=:percent_count_type,day_count_type=:day_count_type,
                    hour_count_type=:hour_count_type,orderby=:orderby,as_name=:as_name
                    where  v2=:v2";
                    $stmt = apm_db_parse($conn_db, $sql);
                    apm_db_bind_by_name($stmt, ':percent_count_type', $_row['PERCENT_COUNT_TYPE']);
                    apm_db_bind_by_name($stmt, ':day_count_type', $_row['DAY_COUNT_TYPE']);
                    apm_db_bind_by_name($stmt, ':hour_count_type', $_row['HOUR_COUNT_TYPE']);
                    apm_db_bind_by_name($stmt, ':as_name', $_row['AS_NAME']);
                    apm_db_bind_by_name($stmt, ':orderby', $_row['ORDERBY']);
                    apm_db_bind_by_name($stmt, ':v2', $v2);
                    $oci_error = apm_db_execute($stmt);
                    print_r($oci_error);
                }
            }
        }
        header("location: {$_SERVER['HTTP_REFERER']}");
    }

    //删除v2
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
        $sql = "delete from ".APM_DB_PREFIX."monitor_config where v1=:v1 {$where} ";
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

        $sql = "select * from ".APM_DB_PREFIX."monitor_config where v1=:v1 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        $_row = apm_db_fetch_assoc($stmt);
        if (!$_row) {
            $sql = "delete from ".APM_DB_PREFIX."monitor_v1 where v1=:v1 ";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
            apm_db_execute($stmt);
        }
    }
}

?>