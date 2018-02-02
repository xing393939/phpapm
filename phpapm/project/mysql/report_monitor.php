<?php

/**
 * @desc   显示统计的主页面
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        $s1 = isset($_GET['s1']) ? $_GET['s1'] : date('Y-m-d', strtotime("-1 month"));
        $s2 = isset($_GET['s2']) && strtotime($_GET['s2']) < time() ? $_GET['s2'] : date('Y-m-d');
        $start_date = isset($_GET["start_date"]) ? $_GET["start_date"] : date("Y-m-d");
        //时间乱传,不在范围之内
        if (strtotime($start_date) > strtotime($s2) || strtotime($start_date) < strtotime($s1))
            $start_date = '';
        $start_date1 = $start_date;

        $group_name_2 = '默认';
        $group_name = '默认';
        if ($_COOKIE[md5($_SERVER['SCRIPT_FILENAME']) . '_v1_group_name'])
            $group_name = $_COOKIE[md5($_SERVER['SCRIPT_FILENAME']) . '_v1_group_name'];
        //别名替换
        $sql = "select t.*, ifnull(as_name, v1) as_name1
                from ".APM_DB_PREFIX."monitor_v1 t where id>0
                order by as_name1";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $v1_config_group = $this->v1_config = $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            if ($_GET['type'] == $_row['V1']) {
                $group_name = $_row['GROUP_NAME'];
                $group_name_1 = $_row['GROUP_NAME_1'];
                $group_name_2 = $_row['GROUP_NAME_2'];
            }
            $v1_config_group[$_row['GROUP_NAME_1']][$_row['GROUP_NAME_2']][$_row['GROUP_NAME']][$_row['V1']] = $_row;
        }
        $this->v1_config = $v1_config_group[$group_name_1][$group_name_2][$group_name];
        //偏差时差
        if ($this->v1_config[$_GET['type']]['START_CLOCK'])
            $start_date1 = date('Y-m-d H:i:s', strtotime($start_date . " +{$this->v1_config[$_GET['type']]['START_CLOCK']} hour"));
        //所有类型
        $sql = "select v1 from ".APM_DB_PREFIX."monitor_config where id>0  group by v1 order by v1 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $this->type = $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $this->type[] = $_row['V1'];
            if (!$_GET['type'] && $this->v1_config[$_row['V1']]) {
                $_GET['type'] = $_row['V1'];
            }
        }

        //当前类型下面的所有模块
        $sql = "select t.*, ifnull(as_name, v2) as_name1
                from ".APM_DB_PREFIX."monitor_config t where v1=:v1 and v2<>'汇总'
                order by orderby,as_name1";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_GET['type']);
        apm_db_execute($stmt);
        $this->host = $_row = array();
        $this->host[] = array(
            'V1' => $_GET['type'],
            'V2' => '汇总',
            'AS_NAME1' => '汇总'
        );
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            $this->host[$_row['V2']] = $_row;
            if ($_GET['host'] == $_row['V2'])
                $this->v_config = $_row;
        }

        //全部下级日统计数据
        $sql = "select t.*, cal_date CAL_DATE_F from
            ".APM_DB_PREFIX."monitor_date t where
            cal_date>=:s1 and cal_date<=:s2 and v1=:v1 and v2<>'汇总'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_GET['type']);
        apm_db_bind_by_name($stmt, ':s1', $s1);
        apm_db_bind_by_name($stmt, ':s2', $s2);
        $oci_error = apm_db_execute($stmt);
        $this->all_start_date_all = $this->all_start_date_count = $this->all_start_date = $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            if (!$this->v1_config[$_GET['type']]['START_CLOCK']) {
                $this->all_start_date_count[$_row['V2']]['total'] += $_row['FUN_COUNT'];
                $this->all_start_date_count[$_row['V2']]['total_i']++;
                $this->all_start_date_count[$_row['V2']]['total_avg'] = round($this->all_start_date_count[$_row['V2']]['total'] / $this->all_start_date_count[$_row['V2']]['total_i'], 2);
                $this->all_start_date_all[$_row['CAL_DATE_F']] += $_row['FUN_COUNT'];
                $this->all_start_date[$_row['CAL_DATE_F']][$_row['V2']] += $_row['FUN_COUNT'];

                //统计汇总
                if (!$this->host[$_row['V2']]['V2_CONFIG_OTHER']['NO_COUNT']) {
                    //查看当前数据是够需要统计
                    $this->all_start_date_count['汇总']['total'] += $_row['FUN_COUNT'];
                    $this->all_start_date_count['汇总']['total_i']++;
                    $this->all_start_date_count['汇总']['total_avg'] = round($this->all_start_date_count['汇总']['total'] / $this->all_start_date_count['汇总']['total_i'], 2);
                    $this->all_start_date[$_row['CAL_DATE_F']]['汇总'] += $_row['FUN_COUNT'];
                }
            }
        }

        //获取v2分组
        $sql = "select t.*, ifnull(as_name, v2) as_name1
                from ".APM_DB_PREFIX."monitor_config t where v1=:v1 and v2<>'汇总'
                order by orderby,as_name1";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_GET['type']);
        apm_db_execute($stmt);
        $this->group = array();
        $this->group['汇总'][] = array(
            'V1' => $_GET['type'],
            'V2' => '汇总',
            'AS_NAME1' => '汇总'
        );
        $cospan = 1;
        while ($_row = apm_db_fetch_assoc($stmt)) {
            if ($_row['V2_GROUP'] == '') {
                $_row['V2_GROUP'] = '其它';
            } else {
                $is_group = true;
            }
            $cospan++;
            if ($_row['V2_CONFIG_OTHER']) {
                $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            }
            $this->group[$_row['V2_GROUP']][] = $_row;

        }
        //获取分组总数
        if ($is_group) {
            foreach ($this->group as $k => $v) {
                foreach ($v as $v2) {
                    foreach ($this->all_start_date_count as $c => $i) {
                        if ($c == $v2['V2']) {
                            $this->group_count[$k]['count'] += $i['total'];
                        }
                    }
                }
            }
        }
        //时区有偏差,改成从小时表读取数据
        if ($this->v1_config[$_GET['type']]['START_CLOCK']) {
            $sql = "select v1,v2,sum(fun_count) fun_count, t.cal_date as cal_date_f
                    	from  ".APM_DB_PREFIX."monitor_hour t
                        where cal_date>=:s1 and cal_date<=:s2
                        and v1=:v1
                        group by v1,v2,t.cal_date";
            $stmt = apm_db_parse($conn_db, $sql);
            $diffHour = $this->v1_config[$_GET['type']]['START_CLOCK'];
            apm_db_bind_by_name($stmt, ':s1', date('Y-m-d', strtotime($s1) + $diffHour * 3600));
            apm_db_bind_by_name($stmt, ':s2', date('Y-m-d', strtotime($s2) + $diffHour * 3600));
            apm_db_bind_by_name($stmt, ':v1', $_GET['type']);
            $oci_error = apm_db_execute($stmt);
            $_row = array();
            while ($_row = apm_db_fetch_assoc($stmt)) {
                if (date('H', strtotime($_row['CAL_DATE_F'])) < $this->v1_config[$_GET['type']]['START_CLOCK'])
                    $_row['CAL_DATE_F'] = date('Y-m-d', strtotime($_row['CAL_DATE_F'] . " -1 day"));
                else
                    $_row['CAL_DATE_F'] = date('Y-m-d', strtotime($_row['CAL_DATE_F']));
                //汇总计算
                if (!$this->host[$_row['V2']]['V2_CONFIG_OTHER']['NO_COUNT']) {
                    //判断项目是否需要汇总
                    $this->all_start_date_count['汇总'] += $_row['FUN_COUNT'];
                    $this->all_start_date[$_row['CAL_DATE_F']]['汇总'] += $_row['FUN_COUNT'];
                }

                $this->all_start_date_count[$_row['V2']] += $_row['FUN_COUNT'];
                $this->all_start_date[$_row['CAL_DATE_F']][$_row['V2']] += $_row['FUN_COUNT'];
                $this->all_start_date_all[$_row['CAL_DATE_F']] += $_row['FUN_COUNT'];
            }
        }

        if ($start_date && $_GET['host'] == '汇总') {
            //当日数据
            $sql = "  select v2 as v3,sum(fun_count) fun_count,round(avg(fun_count),2) fun_count_avg,DATE_FORMAT(t.cal_date, '%d %H') as cal_date_f,
                max(t.diff_time) diff_time, sum(t.total_diff_time) total_diff_time
                from  ".APM_DB_PREFIX."monitor_hour t
                where cal_date>=:cal_date1 and cal_date<:cal_date2
                and v1=:v1  and v2<>'汇总'
                group by v1,v2,cal_date_f";
            $stmt2 = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt2, ':cal_date1', date('Y-m-d H:i:s', strtotime($start_date1) - 86400));
            apm_db_bind_by_name($stmt2, ':cal_date2', date('Y-m-d H:i:s', strtotime($start_date1) + 86400));
            apm_db_bind_by_name($stmt2, ':v1', $_GET['type']);
            $oci_error = apm_db_execute($stmt2);
            print_r($oci_error);
            $this->fun_count = $this->fun_count2 = $_row = array();
            while ($_row = apm_db_fetch_assoc($stmt2)) {
                $this->fun_count[$_row['V3']]['AS_NAME1'] = $_row['V3'];
                foreach ($this->host as $k => $v)
                    if ($_row['V3'] == $v['V2']) {
                        $this->fun_count[$_row['V3']]['AS_NAME1'] = $v['AS_NAME1'];
                        break;
                    }
                $this->fun_count[$_row['V3']][$_row['CAL_DATE_F']] = $_row;
                $this->fun_count[$_row['V3']]['DIFF_TIME'] = max($this->fun_count[$_row['V3']]['DIFF_TIME'], abs($_row['DIFF_TIME']));
                $this->fun_count[$_row['V3']]['TOTAL_DIFF_TIME'] += abs($_row['TOTAL_DIFF_TIME']);
                if ($_row['CAL_DATE_F'] >= date("d H", strtotime($start_date))) {
                    $this->fun_count[$_row['V3']]['FUN_COUNT'] += $_row['FUN_COUNT'];
                }
                $this->fun_count[$_row['V3']]['FUN_COUNT_I'] += 1;
                $this->fun_count[$_row['V3']]['FUN_COUNT_AVG'] = round($this->fun_count[$_row['V3']]['FUN_COUNT'] / $this->fun_count[$_row['V3']]['FUN_COUNT_I'], 2);
                $this->fun_count2[$_row['CAL_DATE_F']] += $_row['FUN_COUNT'];
            }
            uasort($this->fun_count, create_function('$a,$b', 'if ($a["FUN_COUNT"] == $b["FUN_COUNT"]) return 0; return ($a["FUN_COUNT"]<$b["FUN_COUNT"]);'));
        } elseif ($start_date) {
            $this->pageObj = new page(10000, 300);
            //文档数据
            $sql_v2 = "select * from ".APM_DB_PREFIX."monitor_config where v2=:v2";
            $stmt_v2 = apm_db_parse($conn_db, $sql_v2);
            apm_db_bind_by_name($stmt_v2, ':v2', $_GET['host']);
            apm_db_execute($stmt_v2);
            $row_v2 = apm_db_fetch_assoc($stmt_v2);
            if ($row_v2['V2_CONFIG_OTHER']) {
                $row_v2['V2_CONFIG_OTHER'] = unserialize($row_v2['V2_CONFIG_OTHER']);
            }

            //当日数据
            $sql = "{$this->pageObj->num_1} select v3,sum(fun_count) fun_count
                    from  ".APM_DB_PREFIX."monitor_hour
                    where cal_date>=:cal_date1 and cal_date<:cal_date2
                    and v1=:v1 and v2=:v2
                    group by v1,v2,v3  {$this->pageObj->num_3} ";
            $stmt2 = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt2, ':cal_date1', $start_date1);
            apm_db_bind_by_name($stmt2, ':cal_date2', date('Y-m-d H:i:s', strtotime($start_date1) + 86400));
            apm_db_bind_by_name($stmt2, ':v1', $_GET['type']);
            apm_db_bind_by_name($stmt2, ':v2', $_GET['host']);
            apm_db_bind_by_name($stmt2, ':num_1', intval($this->pageObj->limit_1));
            apm_db_bind_by_name($stmt2, ':num_3', intval($this->pageObj->limit_3));
            $oci_error = apm_db_execute($stmt2);
            $this->fun_count = $this->fun_count2 = $_row2 = array();

            while ($_row2 = apm_db_fetch_assoc($stmt2)) {
                $sql = "select t.*,DATE_FORMAT(t.cal_date, '%d %H') as cal_date_f
                       from ".APM_DB_PREFIX."monitor_hour t
                       where cal_date>=:cal_date1 and cal_date<:cal_date2
                       and v1=:v1 and v2=:v2 and v3=:v3   order by fun_count desc";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':cal_date1', date('Y-m-d H:i:s', strtotime($start_date1) - 86400));
                apm_db_bind_by_name($stmt, ':cal_date2', date('Y-m-d H:i:s', strtotime($start_date1) + 86400));
                apm_db_bind_by_name($stmt, ':v1', $_GET['type']);
                apm_db_bind_by_name($stmt, ':v2', $_GET['host']);
                apm_db_bind_by_name($stmt, ':v3', $_row2['V3']);
                $oci_error = apm_db_execute($stmt);
                $_row = array();
                while ($_row = apm_db_fetch_assoc($stmt)) {
                    $this->fun_count[$_row2['V3']]['AS_NAME1'] = $_row2['V3'];
                    $this->fun_count[$_row2['V3']][$_row['CAL_DATE_F']] = $_row;
                    if ($_row['CAL_DATE'] >= $start_date . ' 00:00:00') {
                        $this->fun_count[$_row2['V3']]['FUN_COUNT'] += $_row['FUN_COUNT'];
                        $this->fun_count[$_row2['V3']]['FUN_COUNT_I'] += 1;
                        $this->fun_count[$_row2['V3']]['FUN_COUNT_AVG'] = round($this->fun_count[$_row['V3']]['FUN_COUNT'] / $this->fun_count[$_row['V3']]['FUN_COUNT_I'], 2);
                        $this->fun_count[$_row2['V3']]['DIFF_TIME'] = max($this->fun_count[$_row['V3']]['DIFF_TIME'], abs($_row['DIFF_TIME']));
                        $this->fun_count[$_row2['V3']]['TOTAL_DIFF_TIME'] += abs($_row['TOTAL_DIFF_TIME']);
                        $this->fun_count2[$_row['CAL_DATE_F']] += $_row['FUN_COUNT'];
                        $this->fun_count3['FUN_COUNT_I']++;
                        $this->fun_count3['FUN_COUNT'] += $_row['FUN_COUNT'];
                        $this->fun_count3['FUN_COUNT_AVG'] = round($this->fun_count3['FUN_COUNT'] / $this->fun_count3['FUN_COUNT_I'], 2);

                    }

                }
            }
            uasort($this->fun_count, create_function('$a,$b', 'if ($a["FUN_COUNT"] == $b["FUN_COUNT"]) return 0; return ($a["FUN_COUNT"]<$b["FUN_COUNT"]);'));
        }
        //end today start yestoday

        //加载文件
        if (!isset($_COOKIE['cmp_base'])) {
            $_COOKIE['cmp_base'] = 10000;
            setcookie('cmp_base', 10000, time() + 3600 * 24 * 7);
        }
        if (!isset($_COOKIE['red_factor'])) {
            $_COOKIE['red_factor'] = 2;
            setcookie('red_factor', 2, time() + 3600 * 24 * 7);
        }
        include APM_PATH . "./project_tpl/report_monitor.html";
    }
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-06-16 12:11:22
 * @throws 注意:无DB异常处理
 */
function _p($pageID, $is_page = true, $pageFirst = null)
{
    static $page_tp, $page_first;
    if ($is_page) {
        if ($pageID < 2) {
            return $page_first;
        } else
            return str_replace('{p}', $pageID, $page_tp);
    } else {
        $page_tp = $pageID;
        $page_first = $pageFirst;
    }
}