<?php

/**
 * @desc   处理页面访问队列
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class monitor
{
    function _initialize()
    {
        echo "<pre>准备压缩数据:\n";
        $tt1 = microtime(true);
        $queue_data = array();
        if (APM_QUEUE_TYPE == 'db') {
            $conn_db = apm_db_logon(APM_DB_ALIAS);
            $sql = "select * from ".APM_DB_PREFIX."monitor_queue order by id desc LIMIT 0, 5000";
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_execute($stmt);
            while ($_row = apm_db_fetch_assoc($stmt)) {
                $msg_array = unserialize($_row['QUEUE']);
                $queue_data[] = $msg_array;
            }
            //clear queue start
            $sql_d = "delete from ".APM_DB_PREFIX."monitor_queue";
            $stmt_d = apm_db_parse($conn_db, $sql_d);
            apm_db_execute($stmt_d);
            //clear queue end
        } elseif (APM_QUEUE_TYPE == 'ipc') {
            $names = explode('|', APM_QUEUE_NAMES);
            $ic = 0;
            foreach ($names as $key) {
                $seg = msg_get_queue($key, 0600);
                $msgType = 1;
                $msg_array = array();
                //读取队列数据
                while (msg_receive($seg, $msgType, $msgType, 1024 * 1024 * 5, $msg_array, true, MSG_IPC_NOWAIT)) {
                    $queue_data[] = $msg_array;
                    if ($ic++ > 10 * 10000)
                        break;
                }
            }
        } elseif (APM_QUEUE_TYPE == 'redis') {
            $redis = new Redis();
            $redis_tns = parse_url(APM_QUEUE_TNS);
            $redis->connect($redis_tns['host'], $redis_tns['port'], 2);

            $length = 10 * 10000;
            $names = explode('|', APM_QUEUE_NAMES);
            $redis->multi(Redis::PIPELINE);
            foreach ($names as $key) {
                $redis->lrange("phpapm:{$key}", 0, $length - 1);
                $redis->ltrim("phpapm:{$key}", $length, -1);
            }
            $arList = $redis->exec();

            foreach ($arList as $arData) {
                if (!is_array($arData))
                    continue;
                foreach ($arData as $row) {
                    $queue_data[] = unserialize($row);
                }
            }
        }

        //入库
        $xxi = 0;
        $monitor_count = $monitor = array();
        $config_data = array();
        foreach ($queue_data as $msg_array) {
            $time = date('Y-m-d H', strtotime($msg_array['time']));

            //查看命中了哪些监控
            if (empty($config_data[$msg_array['v1']])) $config_data[$msg_array['v1']] = array();
            if (empty($config_data[$msg_array['v1']][$msg_array['v2']])) $config_data[$msg_array['v1']][$msg_array['v2']] = 0;
            $config_data[$msg_array['v1']][$msg_array['v2']]++;
            $monitor_count[md5($time . $msg_array['v1'] . $msg_array['v2'] . $msg_array['v3'] . $msg_array['v4'] . $msg_array['v5'])] = 1;

            if (empty($monitor[$time])) {
                $monitor[$time] = array();
            }
            if (empty($monitor[$time][$msg_array['v1']])) {
                $monitor[$time][$msg_array['v1']] = array();
            }
            if (empty($monitor[$time][$msg_array['v1']][$msg_array['v2']])) {
                $monitor[$time][$msg_array['v1']][$msg_array['v2']] = array();
            }
            if (empty($monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']])) {
                $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']] = array();
            }
            if (empty($monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']])) {
                $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']] = array();
            }
            if (empty($monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']])) {
                $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']] = array(
                    'uptype' => '',
                    'count' => 0,
                    'diff_time' => 0,
                    'total_diff_time' => 0,
                    'memory_max' => 0,
                    'memory_total' => 0,
                    'cpu_user_time_max' => 0,
                    'cpu_user_time_total' => 0,
                    'cpu_sys_time_max' => 0,
                    'cpu_sys_time_total' => 0,
                );
            }
            $oldArr = $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']];
            $oldArr['uptype'] = $msg_array['uptype'];
            if ($msg_array['uptype'] == 'replace') {
                $oldArr['count'] = $msg_array['num'];
            } else {
                $oldArr['count'] += $msg_array['num'];
            }
            $oldArr['diff_time'] = max($oldArr['diff_time'], abs($msg_array['diff_time']));
            $oldArr['total_diff_time'] += abs($msg_array['total_diff_time']);
            if (!empty($msg_array['cpu_user_time_total'])) {
                $oldArr['cpu_user_time_total'] += abs($msg_array['cpu_user_time_total']);
                $oldArr['cpu_user_time_max'] = max($msg_array['cpu_user_time_max'], abs($oldArr['cpu_user_time_max']));
                $oldArr['cpu_sys_time_total'] += abs($msg_array['cpu_sys_time_total']);
                $oldArr['cpu_sys_time_max'] = max($msg_array['cpu_sys_time_max'], abs($oldArr['cpu_sys_time_max']));
                $oldArr['memory_total'] += abs($msg_array['memory_total']);
                $oldArr['memory_max'] = max($msg_array['memory_max'], abs($oldArr['memory_max']));
            }
            $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']] = $oldArr;
        }

        $diff_time = sprintf('%.5f', microtime(true) - $tt1);
        echo "\n从" . count($queue_data) . "个压缩到" . count($monitor_count) . "(耗时:{$diff_time})\n";
        echo "命中的类型:\n";
        print_r($config_data);
        echo "\n\n";
        $conn_db = apm_db_logon(APM_DB_ALIAS);

        foreach ($monitor as $time => $vtype) {
            foreach ($vtype as $type => $vhost) {
                foreach ($vhost as $host => $vact) {
                    foreach ($vact as $act => $vkey) {
                        foreach ($vkey as $key => $vhostip) {
                            foreach ($vhostip as $hostip => $v) {
                                if (!$host)
                                    $host = 'null';
                                //截取4000字节
                                if (strlen($key) > 4000)
                                    $key = substr($key, 0, 4000);
                                if (strlen($hostip) > 200)
                                    $hostip = substr($hostip, 0, 200);
                                if (strlen($act) > 200)
                                    $act = substr($act, 0, 200);
                                //去掉回车
                                $act = strtr($act, array(
                                    "\n" => null,
                                    "\r" => null
                                ));
                                if ($v['uptype'] == 'replace')
                                    //memory_max=,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total
                                    $sql = "update ".APM_DB_PREFIX."monitor set
                                    fun_count=:fun_count,
                                    oci_unique=".mt_rand(1, 2147483647).",
                                    v6=:v6,
                                    total_diff_time=:total_diff_time,
									memory_max=:memory_max,
									memory_total=:memory_total,
									cpu_user_time_max=:cpu_user_time_max,
									cpu_user_time_total=:cpu_user_time_total,
									cpu_sys_time_max=:cpu_sys_time_max,
									cpu_sys_time_total=:cpu_sys_time_total
									where md5=:md5";
                                else
                                    $sql = "update ".APM_DB_PREFIX."monitor set
                                    fun_count=fun_count+:fun_count,
                                    oci_unique=".mt_rand(1, 2147483647).",
                                    v6=GREATEST(ifnull(v6,0),:v6),
                                    total_diff_time=total_diff_time+:total_diff_time,
								    memory_max=GREATEST(ifnull(memory_max,0),:memory_max),
								    memory_total=memory_total+:memory_total,
								    cpu_user_time_max=GREATEST(ifnull(cpu_user_time_max,0),:cpu_user_time_max),
								    cpu_user_time_total=cpu_user_time_total+:cpu_user_time_total,
								    cpu_sys_time_max=GREATEST(ifnull(cpu_sys_time_max,0),:cpu_sys_time_max),
								    cpu_sys_time_total=cpu_sys_time_total+:cpu_sys_time_total
								    where md5=:md5";
                                $stmt = apm_db_parse($conn_db, $sql);
                                apm_db_bind_by_name($stmt, ':md5', md5($time . $type . $host . $act . $key . $hostip));
                                apm_db_bind_by_name($stmt, ':fun_count', $v['count']);
                                apm_db_bind_by_name($stmt, ':v6', abs($v['diff_time']));
                                apm_db_bind_by_name($stmt, ':total_diff_time', $v['total_diff_time']);
                                apm_db_bind_by_name($stmt, ':memory_max', $v['memory_max']);
                                apm_db_bind_by_name($stmt, ':memory_total', $v['memory_total']);
                                apm_db_bind_by_name($stmt, ':cpu_user_time_max', $v['cpu_user_time_max']);
                                apm_db_bind_by_name($stmt, ':cpu_user_time_total', $v['cpu_user_time_total']);
                                apm_db_bind_by_name($stmt, ':cpu_sys_time_max', $v['cpu_sys_time_max']);
                                apm_db_bind_by_name($stmt, ':cpu_sys_time_total', $v['cpu_sys_time_total']);
                                $oci_error = apm_db_execute($stmt);
                                print_r($oci_error);
                                if ($oci_error)
                                    _status(1, APM_HOST . "(基本统计)", 'SQL错误', APM_URI, var_export(array(
                                            'cal_date' => $time,
                                            'v1' => $type,
                                            'v2' => $host,
                                            'v3' => $act,
                                            'v4' => $key,
                                            'v5' => $hostip,
                                            'fun_count' => $v['count'],
                                            'v6' => abs($v['diff_time']),
                                            'total_diff_time' => $v['total_diff_time'],
                                            'memory_max' => $v['memory_max'],
                                            'memory_total' => $v['memory_total'],
                                            'cpu_user_time_max' => $v['cpu_user_time_max'],
                                            'cpu_user_time_total' => $v['cpu_user_time_total'],
                                            'cpu_sys_time_max' => $v['cpu_sys_time_max'],
                                            'cpu_sys_time_total' => $v['cpu_sys_time_total']
                                        ), true) . "|" . var_export($oci_error, true), APM_HOSTNAME);
                                else
                                    _status(1, APM_HOST . "(监控消耗)", "统计消耗", $type, 'monitor(update)', APM_HOSTNAME);
                                $_row_count = apm_db_row_count($stmt);
                                if (!$_row_count) {
                                    $xxi++;
                                    echo "{$xxi}:[$time . $type . $host . $act . $key . $hostip]\n";
                                    $sql = "insert into ".APM_DB_PREFIX."monitor (id,v1,v2,v3,v4,v5,fun_count,cal_date,v6,total_diff_time,memory_max,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total,md5)
                                    values(NULL,:v1,:v2,:v3,:v4,:v5,:fun_count,:cal_date,:v6,:total_diff_time,:memory_max,:memory_total, :cpu_user_time_max,:cpu_user_time_total,:cpu_sys_time_max,:cpu_sys_time_total,:md5)";
                                    $stmt = apm_db_parse($conn_db, $sql);
                                    apm_db_bind_by_name($stmt, ':md5', md5($time . $type . $host . $act . $key . $hostip));
                                    apm_db_bind_by_name($stmt, ':cal_date', $time);
                                    apm_db_bind_by_name($stmt, ':v1', $type);
                                    apm_db_bind_by_name($stmt, ':v2', $host);
                                    apm_db_bind_by_name($stmt, ':v3', $act);
                                    apm_db_bind_by_name($stmt, ':v4', $key);
                                    apm_db_bind_by_name($stmt, ':v5', $hostip);
                                    apm_db_bind_by_name($stmt, ':fun_count', $v['count']);
                                    apm_db_bind_by_name($stmt, ':v6', abs($v['diff_time']));
                                    apm_db_bind_by_name($stmt, ':total_diff_time', $v['total_diff_time']);
                                    apm_db_bind_by_name($stmt, ':memory_max', $v['memory_max']);
                                    apm_db_bind_by_name($stmt, ':memory_total', $v['memory_total']);
                                    apm_db_bind_by_name($stmt, ':cpu_user_time_max', $v['cpu_user_time_max']);
                                    apm_db_bind_by_name($stmt, ':cpu_user_time_total', $v['cpu_user_time_total']);
                                    apm_db_bind_by_name($stmt, ':cpu_sys_time_max', $v['cpu_sys_time_max']);
                                    apm_db_bind_by_name($stmt, ':cpu_sys_time_total', $v['cpu_sys_time_total']);
                                    $oci_error = apm_db_execute($stmt);
                                    print_r($oci_error);
                                    if ($oci_error)
                                        _status(1, APM_HOST . "(基本统计)", 'SQL错误', APM_URI, var_export(array(
                                                'cal_date' => $time,
                                                'time' => date('Y-m-d H:i:s'),
                                                'md5' => md5($time . $type . $host . $act . $key . $hostip),
                                                'v1' => $type,
                                                'v2' => $host,
                                                'v3' => $act,
                                                'v4' => $key,
                                                'v5' => $hostip,
                                                'fun_count' => $v['count'],
                                                'v6' => abs($v['diff_time']),
                                                'memory_max' => $v['memory_max'],
                                                'memory_total' => $v['memory_total'],
                                                'cpu_user_time_max' => $v['cpu_user_time_max'],
                                                'cpu_user_time_total' => $v['cpu_user_time_total'],
                                                'cpu_sys_time_max' => $v['cpu_sys_time_max'],
                                                'cpu_sys_time_total' => $v['cpu_sys_time_total']
                                            ), true) . "|" . var_export($oci_error, true), APM_HOSTNAME);
                                    else
                                        _status(1, APM_HOST . "(监控消耗)", "统计消耗", $type, 'monitor', APM_HOSTNAME);
                                }
                            }
                        }
                    }
                }
            }
        }
        apm_db_logoff($conn_db);

        die("\n" . date("Y-m-d H:i:s") . ',file:' . __FILE__ . ',line:' . __LINE__ . "\n");
    }
}

?>