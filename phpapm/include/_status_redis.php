<?php
/**
 * @desc   WHAT? $uptype=replace/utf-8
 * @author
 * @since  2012-06-22 20:14:54
 * @throws 注意:无DB异常处理
 */
function _status($num, $v1, $v2, $v3 = APM_HOSTNAME, $v4 = null, $v5 = APM_HOSTNAME, $diff_time = 0, $up_type = null, $time = null, $add_array = array())
{
    if (!$time)
        $START_TIME_DATE = date('Y-m-d H:i:s', APM_START_TIME);
    else
        $START_TIME_DATE = date('Y-m-d H:i:s', $time);

    $names = explode('|', APM_QUEUE_NAMES);
    $key = $names[array_rand($names)];

    static $redis = null;
    if (empty($redis)) {
        $redis = new Redis();
        $redis_tns = parse_url(APM_QUEUE_TNS);
        $redis->connect($redis_tns['host'], $redis_tns['port'], 2);
        if (!empty($redis_tns['query'])) $redis->auth($redis_tns['query']);
    }
    if ($v3 == NULL)
        $v3 = APM_HOSTNAME;

    list($_up_type) = explode('/', $up_type);
    settype($add_array, 'array');
    if (empty($add_array['total_diff_time'])) {
        $add_array['total_diff_time'] = $diff_time;
    }
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
    try {
        $redis->lpush("phpapm:{$key}", json_encode($array));
    } catch (RedisException $e) {
        error_log("队列错误:{$key}", 8, '0', STR_PAD_LEFT);
    }
}