<?php

/**
 * @desc   主机：整合运算
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class monitor_config
{
    function _initialize()
    {
        set_time_limit(0);
        ini_set("display_errors", true);
        echo "<pre>";
        $conn_db = _ocilogon(APM_DB_ALIAS);
        if (!$conn_db)
            return;

        //每小时汇总[上小时+当前小时]
        $hourtime = strtotime(date('Y-m-d H:0:0') . " -1 hour");
        $endtime = time();
        if ($_GET['hour']) {
            $hourtime = strtotime($_GET['hour']);
            $endtime = strtotime("{$_GET['hour']} +1 day");
        }
        //所有配置信息 包含虚列
        $sql = "select * from  ".APM_DB_PREFIX."monitor_config t  where id>0";
        $stmt = _ociparse($conn_db, $sql);
        $oci_error = _ociexecute($stmt);
        $this->all_config = $_row = array();
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS))
            $this->all_config[$_row['V1'] . $_row['V2']] = $_row;

        $addwhere = null;
        if ($_GET['v1'])
            $addwhere .= " and v1=:v1 ";
        if ($_GET['v2'])
            $addwhere .= " and v2=:v2 ";
        for ($it = $hourtime; $it <= $endtime; $it += 3600) {
            $hour = date('Y-m-d H:00:00', $it);
            echo "hour:{$hour}\n";
            //每小时数据汇总memory_max,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total
            $sql = "select to_char(t.cal_date, 'yyyy-mm-dd hh24') cal_date, t.v1, decode(t.v2,null,'null',v2) v2,
                    decode(t.v3,null,'null',v3) v3, sum(fun_count) fun_count,avg(fun_count) fun_count_avg,max(abs(nvl(v6,0))) DIFF_TIME, sum(abs(t.total_diff_time)) total_diff_time,
                    max(memory_max) memory_max, sum(memory_total) memory_total, max(cpu_user_time_max) cpu_user_time_max,sum(cpu_user_time_total) cpu_user_time_total, max(cpu_sys_time_max) cpu_sys_time_max, sum(cpu_sys_time_total) cpu_sys_time_total
                    from ".APM_DB_PREFIX."monitor t
                    where cal_date >= to_date(:hour,'yyyy-mm-dd hh24:mi:ss') and cal_date <to_date(:hour,'yyyy-mm-dd hh24:mi:ss')+1/24
                    {$addwhere}
                    group by t.v1, t.v2,t.v3, to_char(t.cal_date, 'yyyy-mm-dd hh24')  ";
            $stmt_list = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt_list, ':hour', $hour);
            if ($_GET['v1'])
                _ocibindbyname($stmt_list, ':v1', $_GET['v1']);
            if ($_GET['v2'])
                _ocibindbyname($stmt_list, ':v2', $_GET['v2']);
            $oci_error = _ociexecute($stmt_list);
            print_r($oci_error);
            $_row = array();

            while (ocifetchinto($stmt_list, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
                $_row2 = $this->all_config[$_row['V1'] . $_row['V2']];
                //正常情况下从原始表读取数据.如果是按照最后一分钟计算.走min表
                //虚列数据不进行计算
                if ($_row2['VIRTUAL_COLUMNS'] == 0) {
                    if ($_row2['HOUR_COUNT_TYPE'] == 4) {
                        $_row['FUN_COUNT'] = $_row['FUN_COUNT_AVG'];
                    }
                    $sql = "update ".APM_DB_PREFIX."monitor_hour set fun_count=:fun_count,diff_time=:diff_time, total_diff_time=:total_diff_time,
                memory_max=:memory_max, memory_total=:memory_total, cpu_user_time_max=:cpu_user_time_max, cpu_user_time_total=:cpu_user_time_total, cpu_sys_time_max=:cpu_sys_time_max, cpu_sys_time_total=:cpu_sys_time_total
                where v1=:v1 and v2=:v2 and v3=:v3  and  cal_date=to_date(:cal_date,'yyyy-mm-dd hh24') ";
                    $stmt = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt, ':v1', $_row['V1']);
                    _ocibindbyname($stmt, ':v2', $_row['V2']);
                    _ocibindbyname($stmt, ':v3', $_row['V3']);
                    _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                    _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                    _ocibindbyname($stmt, ':diff_time', abs($_row['DIFF_TIME']));
                    _ocibindbyname($stmt, ':total_diff_time', abs($_row['TOTAL_DIFF_TIME']));
                    _ocibindbyname($stmt, ':memory_max', $_row['MEMORY_MAX']);
                    _ocibindbyname($stmt, ':memory_total', $_row['MEMORY_TOTAL']);
                    _ocibindbyname($stmt, ':cpu_user_time_max', $_row['CPU_USER_TIME_MAX']);
                    _ocibindbyname($stmt, ':cpu_user_time_total', $_row['CPU_USER_TIME_TOTAL']);
                    _ocibindbyname($stmt, ':cpu_sys_time_max', $_row['CPU_SYS_TIME_MAX']);
                    _ocibindbyname($stmt, ':cpu_sys_time_total', $_row['CPU_SYS_TIME_TOTAL']);
                    $oci_error = _ociexecute($stmt);
                    print_r($oci_error);
                    _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'monitor_hour(update)', APM_VIP);
                    $ocirowcount = ocirowcount($stmt);
                    if ($ocirowcount < 1) {
                        $sql = "insert into ".APM_DB_PREFIX."monitor_hour (cal_date,v1,v2,v3,fun_count,diff_time, total_diff_time,memory_max,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total)
                    values (to_date(:cal_date,'yyyy-mm-dd hh24'),:v1,:v2,:v3,:fun_count,:diff_time, :total_diff_time, :memory_max,:memory_total, :cpu_user_time_max,:cpu_user_time_total,:cpu_sys_time_max,:cpu_sys_time_total) ";
                        $stmt = _ociparse($conn_db, $sql);
                        _ocibindbyname($stmt, ':v1', $_row['V1']);
                        _ocibindbyname($stmt, ':v2', $_row['V2']);
                        _ocibindbyname($stmt, ':v3', $_row['V3']);
                        _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                        _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                        _ocibindbyname($stmt, ':diff_time', abs($_row['DIFF_TIME']));
                        _ocibindbyname($stmt, ':total_diff_time', abs($_row['TOTAL_DIFF_TIME']));
                        _ocibindbyname($stmt, ':memory_max', $_row['MEMORY_MAX']);
                        _ocibindbyname($stmt, ':memory_total', $_row['MEMORY_TOTAL']);
                        _ocibindbyname($stmt, ':cpu_user_time_max', $_row['CPU_USER_TIME_MAX']);
                        _ocibindbyname($stmt, ':cpu_user_time_total', $_row['CPU_USER_TIME_TOTAL']);
                        _ocibindbyname($stmt, ':cpu_sys_time_max', $_row['CPU_SYS_TIME_MAX']);
                        _ocibindbyname($stmt, ':cpu_sys_time_total', $_row['CPU_SYS_TIME_TOTAL']);
                        $oci_error = _ociexecute($stmt);
                        print_r($oci_error);
                        if ($oci_error) {
                            $get_included_files = basename(array_shift(get_included_files()));
                            _status(1, APM_HOST . "(BUG错误)", 'SQL错误', $get_included_files . '/' . $_GET['act'], var_export($oci_error, true) . "|" . var_export($_row, true));
                        } else {
                            _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'hour', APM_VIP);
                        }
                    }

                    //虚数列数据
                    $compare_group = array_filter(explode('|', '|' . $_row2['COMPARE_GROUP']));
                    if (count($compare_group) > 0) {
                        foreach ($compare_group as $v) {
                            $sql = "update ".APM_DB_PREFIX."monitor_hour set fun_count=:fun_count,diff_time=:diff_time,total_diff_time=:total_diff_time,
                                        memory_max=:memory_max, memory_total=:memory_total, cpu_user_time_max=:cpu_user_time_max, cpu_user_time_total=:cpu_user_time_total, cpu_sys_time_max=:cpu_sys_time_max, cpu_sys_time_total=:cpu_sys_time_total
                                        where v1=:v1 and v2=:v2 and v3=:v3  and  cal_date=to_date(:cal_date,'yyyy-mm-dd hh24') ";
                            $stmt = _ociparse($conn_db, $sql);
                            _ocibindbyname($stmt, ':v1', $v);
                            _ocibindbyname($stmt, ':v2', $_row['V1'] . '_' . $_row['V2']);
                            _ocibindbyname($stmt, ':v3', $_row['V3']);
                            _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                            _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                            _ocibindbyname($stmt, ':diff_time', abs($_row['DIFF_TIME']));
                            _ocibindbyname($stmt, ':total_diff_time', abs($_row['TOTAL_DIFF_TIME']));
                            _ocibindbyname($stmt, ':memory_max', $_row['MEMORY_MAX']);
                            _ocibindbyname($stmt, ':memory_total', $_row['MEMORY_TOTAL']);
                            _ocibindbyname($stmt, ':cpu_user_time_max', $_row['CPU_USER_TIME_MAX']);
                            _ocibindbyname($stmt, ':cpu_user_time_total', $_row['CPU_USER_TIME_TOTAL']);
                            _ocibindbyname($stmt, ':cpu_sys_time_max', $_row['CPU_SYS_TIME_MAX']);
                            _ocibindbyname($stmt, ':cpu_sys_time_total', $_row['CPU_SYS_TIME_TOTAL']);
                            $oci_error = _ociexecute($stmt);
                            print_r($oci_error);
                            _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'monitor_hour(update)', APM_VIP);
                            $ocirowcount = ocirowcount($stmt);
                            if ($ocirowcount < 1) {
                                $sql = "insert into ".APM_DB_PREFIX."monitor_hour (cal_date,v1,v2,v3,fun_count,diff_time,total_diff_time,memory_max,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total)
                                            values (to_date(:cal_date,'yyyy-mm-dd hh24'),:v1,:v2,:v3,:fun_count,:diff_time,:total_diff_time, :memory_max,:memory_total, :cpu_user_time_max,:cpu_user_time_total,:cpu_sys_time_max,:cpu_sys_time_total) ";
                                $stmt = _ociparse($conn_db, $sql);
                                _ocibindbyname($stmt, ':v1', $v);
                                _ocibindbyname($stmt, ':v2', $_row['V1'] . '_' . $_row['V2']);
                                _ocibindbyname($stmt, ':v3', $_row['V3']);
                                _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                                _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                                _ocibindbyname($stmt, ':diff_time', abs($_row['DIFF_TIME']));
                                _ocibindbyname($stmt, ':total_diff_time', abs($_row['TOTAL_DIFF_TIME']));
                                _ocibindbyname($stmt, ':memory_max', $_row['MEMORY_MAX']);
                                _ocibindbyname($stmt, ':memory_total', $_row['MEMORY_TOTAL']);
                                _ocibindbyname($stmt, ':cpu_user_time_max', $_row['CPU_USER_TIME_MAX']);
                                _ocibindbyname($stmt, ':cpu_user_time_total', $_row['CPU_USER_TIME_TOTAL']);
                                _ocibindbyname($stmt, ':cpu_sys_time_max', $_row['CPU_SYS_TIME_MAX']);
                                _ocibindbyname($stmt, ':cpu_sys_time_total', $_row['CPU_SYS_TIME_TOTAL']);
                                $oci_error = _ociexecute($stmt);
                                print_r($oci_error);
                                if ($oci_error) {
                                    $get_included_files = basename(array_shift(get_included_files()));
                                    _status(1, APM_HOST . "(BUG错误)", 'SQL错误', $get_included_files . '/' . $_GET['act'], var_export($oci_error, true) . "|" . var_export($_row, true));
                                } else {
                                    _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'hour', APM_VIP);
                                }
                            }
                        }
                    }
                }
            }
        }
        //刷新一天的数据
        $sql = "select to_char(t.cal_date, 'yyyy-mm-dd') cal_date, t.v1, decode(t.v2,null,'null',v2) v2,
                  sum(fun_count) fun_count,avg(fun_count) fun_count_avg from ".APM_DB_PREFIX."monitor_hour t
                  where cal_date >= to_date(:m_date,'yyyy-mm-dd') and cal_date<to_date(:m_date,'yyyy-mm-dd')+1 {$addwhere}
                  group by t.v1, t.v2, to_char(t.cal_date, 'yyyy-mm-dd')";
        $stmt_list = _ociparse($conn_db, $sql);
        echo htmlspecialchars($sql);
        var_dump(date("Y-m-d", $hourtime));
        //print_r($_GET);
        _ocibindbyname($stmt_list, ':m_date', date("Y-m-d", $hourtime));
        if ($_GET['v1'])
            _ocibindbyname($stmt_list, ':v1', $_GET['v1']);
        if ($_GET['v2'])
            _ocibindbyname($stmt_list, ':v2', $_GET['v2']);
        $oci_error = _ociexecute($stmt_list);
        print_r($oci_error);
        $_row = array();
        while (ocifetchinto($stmt_list, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            //补全v1的信息
            $sql = "select * from ".APM_DB_PREFIX."monitor_v1 where v1=:v1  ";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $_row['V1']);
            $oci_error = _ociexecute($stmt);
            print_r($oci_error);
            $_row_config = array();
            ocifetchinto($stmt, $_row_config, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
            if (!$_row_config) {
                $sql = "insert into ".APM_DB_PREFIX."monitor_v1 (v1,id) values (:v1,seq_".APM_DB_PREFIX."monitor.nextval) ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'v1_config', APM_VIP);
            }

            $_row_config = $this->all_config[$_row['V1'] . $_row['V2']];

            //如果是不累计的,重置总量为上个小时的总量
            if ($_row_config['DAY_COUNT_TYPE'] == 1 || $_row_config['DAY_COUNT_TYPE'] == 2 || $_row_config['DAY_COUNT_TYPE'] == 5 || $_row_config['DAY_COUNT_TYPE'] == 7) {
                //echo "只计算最后一小时\n";
                $sql2 = "select to_char(max(cal_date),'yyyy-mm-dd hh24:mi:ss') cal_date from
                ".APM_DB_PREFIX."monitor_hour where cal_date>=to_date(:cal_date,'yyyy-mm-dd')
                and  cal_date<to_date(:cal_date,'yyyy-mm-dd')+1 and v1=:v1 and v2=:v2 ";
                $stmt2 = _ociparse($conn_db, $sql2);
                _ocibindbyname($stmt2, ':v1', $_row['V1']);
                _ocibindbyname($stmt2, ':v2', $_row['V2']);
                _ocibindbyname($stmt2, ':cal_date', $_row['CAL_DATE']);
                $oci_error2 = _ociexecute($stmt2);
                print_r($oci_error2);
                $_row2 = array();
                ocifetchinto($stmt2, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
                //print_r($_row2);
                $sql = "select  t.v1, t.v2,  sum(fun_count) fun_count,avg(fun_count) fun_count_avg
 			from  ".APM_DB_PREFIX."monitor_hour t where cal_date=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')
                    and v1=:v1 and v2=:v2  group by t.v1, t.v2";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                _ocibindbyname($stmt, ':v2', $_row['V2']);
                _ocibindbyname($stmt, ':cal_date', $_row2['CAL_DATE']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                $_row2 = array();
                ocifetchinto($stmt, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
                $_row['FUN_COUNT'] = $_row2['FUN_COUNT'];
                //v3个数
                if ($_row_config['DAY_COUNT_TYPE'] == 7) {
                    //echo "计算V3个数\n";
                    $sql = "select  count(distinct(t.v3)) num
 			from  ".APM_DB_PREFIX."monitor_hour t where cal_date>=to_date(:cal_date,'yyyy-mm-dd')
                    and v1=:v1 and v2=:v2";
                    $stmt = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt, ':v1', $_row['V1']);
                    _ocibindbyname($stmt, ':v2', $_row['V2']);
                    _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                    $oci_error = _ociexecute($stmt);
                    print_r($oci_error);
                    ocifetchinto($stmt, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
                    $_row['FUN_COUNT'] = $_row2['NUM'];
                    //echo " num:{$_row['FUN_COUNT']} \n";
                }
                //最后一小时的平均值
                if ($_row_config['DAY_COUNT_TYPE'] == 5)
                    $_row['FUN_COUNT'] = $_row2['FUN_COUNT_AVG'];
            }
            //当天的平均数
            if ($_row_config['DAY_COUNT_TYPE'] == 6)
                $_row['FUN_COUNT'] = $_row['FUN_COUNT_AVG'];
            //print_r($_row);
            //echo " num:{$_row['FUN_COUNT']} \n";
            $sql = "update ".APM_DB_PREFIX."monitor_date set fun_count=:fun_count
              where v1=:v1 and v2=:v2 and cal_date=to_date(:cal_date,'yyyy-mm-dd') ";
            $stmt2 = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt2, ':v1', $_row['V1']);
            _ocibindbyname($stmt2, ':v2', $_row['V2']);
            _ocibindbyname($stmt2, ':cal_date', $_row['CAL_DATE']);
            _ocibindbyname($stmt2, ':fun_count', $_row['FUN_COUNT']);
            $oci_error = _ociexecute($stmt2);
            print_r($oci_error);
            _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'monitor_date(update)', APM_VIP);
            $_row_count = ocirowcount($stmt2);
            if (!$_row_count) {
                $sql = "insert into ".APM_DB_PREFIX."monitor_date (cal_date,v1,v2,fun_count) values
                    (to_date(:cal_date,'yyyy-mm-dd'),:v1,:v2,:fun_count) ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                _ocibindbyname($stmt, ':v2', $_row['V2']);
                _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'date', APM_VIP);
            }
            $compare_group = array_filter(explode('|', '|' . $_row_config['COMPARE_GROUP']));
            if (count($compare_group) > 0) {
                foreach ($compare_group as $v) {
                    $sql = "update ".APM_DB_PREFIX."monitor_date set fun_count=:fun_count
                                  where v1=:v1 and v2=:v2 and cal_date=to_date(:cal_date,'yyyy-mm-dd') ";
                    $stmt2 = _ociparse($conn_db, $sql);
                    _ocibindbyname($stmt2, ':v1', $v);
                    _ocibindbyname($stmt2, ':v2', $_row['V1'] . '_' . $_row['V2']);
                    _ocibindbyname($stmt2, ':cal_date', $_row['CAL_DATE']);
                    _ocibindbyname($stmt2, ':fun_count', $_row['FUN_COUNT']);
                    $oci_error = _ociexecute($stmt2);
                    print_r($oci_error);
                    _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'monitor_date(update)', APM_VIP);
                    $_row_count = ocirowcount($stmt2);
                    if (!$_row_count) {
                        $sql = "insert into ".APM_DB_PREFIX."monitor_date (cal_date,v1,v2,fun_count) values
                    (to_date(:cal_date,'yyyy-mm-dd'),:v1,:v2,:fun_count) ";
                        $stmt = _ociparse($conn_db, $sql);
                        _ocibindbyname($stmt, ':v1', $v);
                        _ocibindbyname($stmt, ':v2', $_row['V1'] . '_' . $_row['V2']);
                        _ocibindbyname($stmt, ':cal_date', $_row['CAL_DATE']);
                        _ocibindbyname($stmt, ':fun_count', $_row['FUN_COUNT']);
                        $oci_error = _ociexecute($stmt);
                        print_r($oci_error);
                        _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'date', APM_VIP);
                    }
                }
            }

            if (!$_row_config) {
                $sql = "select count(*) c from ".APM_DB_PREFIX."monitor_config where v1=:v1 ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                $_row2 = array();
                ocifetchinto($stmt, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
                $sql = "select * from ".APM_DB_PREFIX."monitor_v1 where  v1=:v1 ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                $_row3 = array();
                ocifetchinto($stmt, $_row3, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

                $sql = "insert into  ".APM_DB_PREFIX."monitor_config (v1,v2,orderby,id,day_count_type,hour_count_type,percent_count_type)
                values (:v1,:v2,:orderby,seq_".APM_DB_PREFIX."monitor.nextval,:day_count_type,:hour_count_type,:percent_count_type) ";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $_row['V1']);
                _ocibindbyname($stmt, ':v2', $_row['V2']);
                _ocibindbyname($stmt, ':day_count_type', intval($_row3['DAY_COUNT_TYPE']));
                _ocibindbyname($stmt, ':hour_count_type', intval($_row3['HOUR_COUNT_TYPE']));
                _ocibindbyname($stmt, ':percent_count_type', intval($_row3['PERCENT_COUNT_TYPE']));

                if ($_row['V2'] == '汇总')
                    _ocibindbyname($stmt, ':orderby', intval(0));
                else
                    _ocibindbyname($stmt, ':orderby', max(1, $_row2['C'] + 1));
                $oci_error = _ociexecute($stmt);
                print_r($oci_error);
                _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $_row['V1'], 'config', APM_VIP);
            }
        }

        //清除过期数据
        if ($_GET['del'] && rand(1, 10) == 1) {
            $sql = "delete from  ".APM_DB_PREFIX."monitor where cal_date<=sysdate-10 ";
            $stmt_list = _ociparse($conn_db, $sql);
            $oci_error = _ociexecute($stmt_list);
            print_r($oci_error);
        }
    }
}

?>