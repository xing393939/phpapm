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

    //累计_status
    static $_status_sql = '';

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
    $_status_sql .= "('" . addslashes(serialize($array)) . "'),";

    //入队列
    if ($v1 == APM_HOST . "(BUG错误)" && in_array($v2, array('脚本', '内网', '外网'))) {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        $_status_sql = rtrim($_status_sql, ',');
        $stmt = apm_db_parse($conn_db, "insert into ".APM_DB_PREFIX."monitor_queue (`queue`) values {$_status_sql}");
        apm_db_execute($stmt);
    }
}
?>