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
        echo "<pre>准备压缩数据:\n";
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

                //记录队列状态
                $statArr = msg_stat_queue($seg);
                _status($statArr['msg_qnum'], APM_HOST . '(监控消耗)', "队列", $key, var_export($statArr, true), APM_HOSTNAME);

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
            if (!empty($redis_tns['query'])) $redis->auth($redis_tns['query']);

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
                    $queue_data[] = json_decode($row, 1);
                }
            }
        }

        $monitor = array();
        foreach ($queue_data as $msg_array) {
            $time = date('Y-m-d H', strtotime($msg_array['time']));
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
            $monitor[$time][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']] = $oldArr;
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
                                $add_array = array('total_diff_time' => $v['total_diff_time']);
                                _status($v['count'], $type, $host, $act, $key, $hostip, abs($v['diff_time']), $v['uptype'], strtotime($time . ":00:00"), $add_array);
                            }
                        }
                    }
                }
            }
        }
        $ic = count($queue_data);
        echo "队列：从{$ic}个压缩到{$cs}<br />\n";
        _status((($ic - $cs) / $ic) * 100, APM_HOST . '(监控消耗)', '队列', '压缩比例', APM_QUEUE_NAMES, APM_HOSTNAME, 0, 'replace');
        unset($monitor);
    }
}

?>