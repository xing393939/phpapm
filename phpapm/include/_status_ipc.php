<?php
//ipcs方案start
/*
echo 16384000 > /proc/sys/kernel/msgmnb
echo 81920 > /proc/sys/kernel/msgmax
echo 30 > /proc/sys/kernel/msgmni
*/
if (!function_exists('msg_get_queue')) {
    function msg_get_queue() {}
}
//ipcs方案end

/**
 * @desc   WHAT? $uptype=replace/utf-8
 * @author
 * @since  2012-06-22 20:14:54
 * @throws 注意:无DB异常处理
 */
function _status($num, $v1, $v2, $v3 = APM_HOSTNAME, $v4 = null, $v5 = APM_HOSTNAME, $diff_time = 0, $up_type = null, $time = null, $add_array = array())
{
    if (strpos(PHP_OS, 'WIN') !== false) {
        return false;
    }

    if (!$time)
        $START_TIME_DATE = date('Y-m-d H:i:s', APM_START_TIME);
    else
        $START_TIME_DATE = date('Y-m-d H:i:s',$time);

    $IPCS = explode('|', APM_IPC_NAMES);
    $ipcs_key = $IPCS[rand(0, count($IPCS) - 1)];
    $seg = msg_get_queue($ipcs_key, 0600);
    if ($seg) {
        if ($v3 == NULL)
            $v3 = APM_HOSTNAME;

        list($_up_type) = explode('/', $up_type);
        settype($add_array, 'array');
        $array = array(
                'vhost' => APM_HOST,
                'num' => $num,
                #计算值
                'v1' => $v1,
                #大分类
                'v2' => $v2,
                #小分类
                'v3' => $v3,
                #主要统计类型
                'v4' => $v4,
                #具体的弹窗描述
                'v5' => $v5,
                #连接地址
                'diff_time' => $diff_time,
                'time' => $START_TIME_DATE,
                'uptype' => $_up_type
            ) + $add_array;
        $bool = msg_send($seg, 1, $array, true, false);
        if (!$bool) {
            error_log("队列错误:" . str_pad(dechex($ipcs_key), 8, '0', STR_PAD_LEFT));
        }
    }
}