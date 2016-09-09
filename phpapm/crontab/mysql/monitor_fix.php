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
        $IPCS = explode('|', APM_IPC_NAMES);
        foreach ($IPCS as $ipcs) {
            $ic = $cs = 0;
            $seg = msg_get_queue($ipcs, 0600);
            $msgtype = 1;
            $msg_array = array();
            $monitor = array();
            //读取队列数据
            while (msg_receive($seg, $msgtype, $msgtype, 1024 * 1024 * 5, $msg_array, true, MSG_IPC_NOWAIT)) {
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['uptype'] = $msg_array['uptype'];
                if ($msg_array['uptype'] == 'replace')
                    $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] = $msg_array['num'];
                else
                    $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] += $msg_array['num'];
                //最大耗时
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'], $msg_array['diff_time']);
                if ($ic++ > 15 * 10000)
                    break;
            }

            //压缩回去
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
            echo "队列：$ipcs 从{$ic}个压缩到{$cs}<br />\n";
            _status((($ic - $cs) / $ic) * 100, APM_HOST . '(PHPAPM)', '队列', '压缩比例', $ipcs, APM_HOSTNAME, 0, 'replace');
            unset($monitor);
        }
    }
}

?>