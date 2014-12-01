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
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(APM_ADMIN_USER)) {
            exit();
        }

        $conn_db = _ocilogon(APM_DB_ALIAS);
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

                if ($_POST['v2_compare'] <> 'NULL') {
                    $where[] = " v2_compare=:v2_compare";
                }
                if (!empty($where)) {
                    $where = join(',', $where);
                    $sql = "update ".APM_DB_PREFIX."monitor_config set {$where} where v1=:v1 and v2=:v2 ";
                    $stmt = _ociparse($conn_db, $sql);
                    if ($_POST['percent_count_type_1'] <> 'NULL')
                        _ocibindbyname($stmt, ':percent_count_type', $_POST['percent_count_type_1']);
                    if ($_POST['day_count_type_1'] <> 'NULL')
                        _ocibindbyname($stmt, ':day_count_type', $_POST['day_count_type_1']);
                    if ($_POST['hour_count_type_1'] <> 'NULL')
                        _ocibindbyname($stmt, ':hour_count_type', $_POST['hour_count_type_1']);

                    if ($_POST['v2_compare'] <> 'NULL')
                        _ocibindbyname($stmt, ':v2_compare', $_POST['v2_compare']);

                    _ocibindbyname($stmt, ':v1', $v1);
                    _ocibindbyname($stmt, ':v2', $v2);
                    $oci_error = _ociexecute($stmt);
                    print_r($oci_error);
                }
                //联动同名不同v1下面的v2
                if ($_POST['group_all']) {
                    $sql = "select * from  ".APM_DB_PREFIX."monitor_config  where v1=:v1 and v2=:v2 ";
                    $stmt = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt, ':v1', $v1);
                    _ocibindbyname($stmt, ':v2', $v2);
                    $oci_error = _ociexecute($stmt);
                    $_row = array();
                    ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

                    $sql = "update ".APM_DB_PREFIX."monitor_config t set percent_count_type=:percent_count_type,day_count_type=:day_count_type,
                    hour_count_type=:hour_count_type,orderby=:orderby,as_name=:as_name
                    where  v2=:v2";
                    $stmt = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt, ':percent_count_type', $_row['PERCENT_COUNT_TYPE']);
                    _ocibindbyname($stmt, ':day_count_type', $_row['DAY_COUNT_TYPE']);
                    _ocibindbyname($stmt, ':hour_count_type', $_row['HOUR_COUNT_TYPE']);
                    _ocibindbyname($stmt, ':as_name', $_row['AS_NAME']);
                    _ocibindbyname($stmt, ':orderby', $_row['ORDERBY']);
                    _ocibindbyname($stmt, ':v2', $v2);
                    $oci_error = _ociexecute($stmt);
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
        $sql = "delete from ".APM_DB_PREFIX."monitor where v1=:v1 {$where} and  cal_date>sysdate-10/24 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);
        $sql = "delete from ".APM_DB_PREFIX."monitor_config where v1=:v1 {$where} ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "delete from ".APM_DB_PREFIX."monitor_date where v1=:v1 {$where} and cal_date>sysdate-10 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "delete from ".APM_DB_PREFIX."monitor_hour where v1=:v1 {$where}  and cal_date>sysdate-10";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        if ($_REQUEST['v2']) {
            _ocibindbyname($stmt, ':v2', $_REQUEST['v2']);
        }
        $oci_error = _ociexecute($stmt);

        $sql = "select * from ".APM_DB_PREFIX."monitor_config where v1=:v1 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        if (!$_row) {
            $sql = "delete from ".APM_DB_PREFIX."monitor_v1 where v1=:v1 ";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
            _ociexecute($stmt);
        }
    }
}

?>