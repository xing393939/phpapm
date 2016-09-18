<?php

/**
 * @desc   压缩队列，单个队列的队列数不能超过15W
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class monitor_fix
{
    function _initialize()
    {
        echo "<pre> 准备压缩数据:\n";
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
            $redis->connect($redis_tns['host'], $redis_tns['port']);

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

        $monitor = array();
        foreach ($queue_data as $msg_array) {
            //日志数据,不会被删除
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))] = array();
            }
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']] = array();
            }
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']] = array();
            }
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']] = array();
            }
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']] = array();
            }
            if (empty($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']])) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']] = array(
                    'uptype' => '', 'count' => 0, 'diff_time' => 0, 'total_diff_time' => 0, 'memory_max' => 0, 'memory_total' => 0, 'cpu_user_time_max' => 0, 'cpu_user_time_total' => 0, 'cpu_sys_time_max' => 0, 'cpu_sys_time_total' => 0,
                );
            }
            $msg_array['memory'] = isset($msg_array['memory']) ? $msg_array['memory'] : 0;
            $msg_array['user_cpu'] = isset($msg_array['user_cpu']) ? $msg_array['user_cpu'] : 0;
            $msg_array['sys_cpu'] = isset($msg_array['sys_cpu']) ? $msg_array['sys_cpu'] : 0;
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['uptype'] = $msg_array['uptype'];
            if ($msg_array['uptype'] == 'replace')
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] = $msg_array['num'];
            else
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] += $msg_array['num'];
            //最大耗时
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'], abs($msg_array['diff_time']));
            //总耗时
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['total_diff_time'] += abs($msg_array['diff_time']);

            //内存单次最大消耗
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_max'], abs($msg_array['memory']));
            //内存消耗.总
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_total'] += abs($msg_array['memory']);

            // 用户消耗CPU,单次最大
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_max'], abs($msg_array['user_cpu']));
            //用户消耗CPU,总
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_total'] += abs($msg_array['user_cpu']);

            //系统消耗CPU,单次最大
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_max'], abs($msg_array['sys_cpu']));
            //系统消耗CPU,总
            $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_total'] += abs($msg_array['sys_cpu']);

        }

        //压缩回去
        $cs = 0;
        foreach ($monitor as $time => $vtype) {
            foreach ($vtype as $type => $vhost) {
                foreach ($vhost as $host => $vact) {
                    foreach ($vact as $act => $vkey) {
                        foreach ($vkey as $key => $vhostip) {
                            foreach ($vhostip as $hostip => $v) {
                                $cs++;
                                _status($v['count'], $type, $host, $act, $key, $hostip, abs($v['diff_time']), $v['uptype'], strtotime($time . ":00:00"));
                            }
                        }
                    }
                }
            }
        }
        $ic = count($queue_data);
        echo "队列：从{$ic}个压缩到{$cs}<br />\n";
        _status((($ic - $cs) / $ic) * 100, APM_HOST . '(PHPAPM)', '队列', '压缩比例', APM_QUEUE_NAMES, APM_HOSTNAME, 0, 'replace');
        unset($monitor);
    }
}

?>