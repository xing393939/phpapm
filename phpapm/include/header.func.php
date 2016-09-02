<?php
date_default_timezone_set("PRC");
define('APM_START_TIME', microtime(true));

//脚本、内网、其他功能
$apm_request_type = 'CLI';
if (!empty($_SERVER['REMOTE_ADDR'])) {
    $apm_client_ip = $_SERVER['REMOTE_ADDR'];
    if (strpos($apm_client_ip, '10.') === 0 && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $apm_client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (strpos($apm_client_ip, '192.168.') === 0 || strpos($apm_client_ip, '10.') === 0) {
        $apm_request_type = "PRIVATE({$apm_client_ip})";
    } else {
        $apm_request_type = 'PUBLIC';
    }
}
define('APM_REQUEST_TYPE', $apm_request_type);

register_shutdown_function('apm_shutdown_function');

/**
 * @desc   计算脚本执行时间，仍队列
 * @author
 * @since  2012-03-23 14:50:13
 * @throws 无DB异常处理
 */
function apm_shutdown_function()
{
    if (connection_aborted())
        _status(1, APM_HOST . "(WEB日志分析)", '被断开', APM_URI, APM_REQUEST_TYPE);
    $diff_time = sprintf('%.5f', microtime(true) - APM_START_TIME);

    //定时任务不记录执行效率
    if (APM_REQUEST_TYPE != 'CLI') {
        if ($diff_time < 1) {
            _status(1, APM_HOST . '(BUG错误)', '一秒内', _debugtime($diff_time), APM_URI, APM_REQUEST_TYPE . ":" . APM_HOSTNAME, $diff_time);
        } else {
            _status(1, APM_HOST . '(BUG错误)', '超时', _debugtime($diff_time), APM_URI, APM_REQUEST_TYPE . ":" . APM_HOSTNAME, $diff_time);
        }
    }

    //获取最后一个php错误
    $e = error_get_last();
    if (strpos($e['message'], 'Call to undefined') !== false)
        return _status(1, APM_HOST . "(BUG错误)", 'PHP错误', "未定义函数", APM_URI, var_export($e, true) . "|" . var_export($_REQUEST, true) . "|" . APM_HOSTNAME, $diff_time);
    else if ($e['type'] == E_ERROR)
        return _status(1, APM_HOST . "(BUG错误)", 'PHP错误', APM_URI, var_export($e, true), APM_HOSTNAME, $diff_time);

    //内存消耗统计
    $add_array = array();
    if (function_exists('getrusage')) {
        $data = getrusage();
        $add_array['user_cpu'] = $data['ru_utime.tv_sec'] + $data['ru_utime.tv_usec'] / 1000000;
        $add_array['sys_cpu'] = $data['ru_stime.tv_sec'] + $data['ru_stime.tv_usec'] / 1000000;
        if (function_exists('memory_get_peak_usage'))
            $add_array['memory'] = memory_get_peak_usage() / 1024 / 1024 / 1024;
    }

    //功能执行统计
    if (APM_REQUEST_TYPE == 'CLI') {
        $array_str = preg_replace('/[^\x00-\x7f]+/', '', var_export($_SERVER, true));
        _status(1, APM_HOST . "(BUG错误)", "脚本", APM_URI, APM_REQUEST_TYPE . "|" . $array_str, APM_HOSTNAME, $diff_time, NULL, NULL, $add_array);
    } else if (APM_REQUEST_TYPE == 'PUBLIC') {
        _status(1, APM_HOST . "(BUG错误)", "外网", APM_URI, APM_REQUEST_TYPE, APM_HOSTNAME, $diff_time, NULL, NULL, $add_array);
    } else {
        _status(1, APM_HOST . "(BUG错误)", "内网", APM_URI, APM_REQUEST_TYPE, APM_HOSTNAME, $diff_time, NULL, NULL, $add_array);
    }
}

set_exception_handler('apm_exception_handler');

function apm_exception_handler($e) {
    _status(1, APM_HOST . '(BUG错误)', "PHP错误", APM_URI, "(file:{$e->file} | line:{$e->line}){$e->message}\n");
}

set_error_handler("apm_error_handler");

/**
 * @desc   接管PHP的异常处理信息,仍到队列后台处理
 * @author
 * @since  2012-04-02 09:50:31
 * @throws 无DB异常处理
 */
function apm_error_handler($no, $msg, $file, $line)
{
    switch ($no) {
        case E_NOTICE:
        case E_USER_ERROR:
        case E_USER_NOTICE:
        case E_STRICT:
            return;
    }
    if ($msg == 'Division by zero')
        return;
    if ($msg == 'Invalid argument supplied for foreach()')
        return;
    if (strpos($msg, 'current()') === 0)
        return;
    if (strpos($msg, 'next()') === 0)
        return;
    if (strpos($msg, 'ftp_mkdir()') === 0)
        return;
    if (isset($_GET['act']) && strpos($_GET['act'], 'monitor') === 0 && strpos($msg, 'msg_send') !== false)
        return;
    if (strpos($msg, 'UTF-8 sequence') !== false)
        return;
    if (strpos($msg, 'fopen(') !== false)
        $msg = preg_replace("#fopen\((.*)\)#", "[file]", $msg);

    $debug_backtrace_str = var_export(debug_backtrace(), true);
    if (strpos($msg, 'oci') === 0 || strpos($msg, 'mysql_') === 0) {
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", APM_URI, "(file:{$file} | line:{$line}){$msg}");
    } elseif (strpos($msg, 'Memcache') === 0) {
        _status(1, APM_HOST . '(BUG错误)', "Memcache错误", APM_URI, "(file:{$file} | line:{$line}){$msg}\n{$debug_backtrace_str}");
    } elseif (strpos($msg, 'msg_send') !== false) {
        _status(1, APM_HOST . '(BUG错误)', "PHP错误", APM_URI, "(file:{$file} | line:{$line}){$msg}\n");
    } else {
        $array_str = preg_replace('/[^\x00-\x7f]+/', '', var_export($_SERVER, true));
         _status(1, APM_HOST . '(BUG错误)', "PHP错误", APM_URI, "(file:{$file} | line:{$line}){$msg}\n|" . $array_str . "\n{$debug_backtrace_str}");
    }
}

/**
 * @desc   what?
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function _debugtime($diff_time = 0)
{
    if ($diff_time < 0.01)
        $diff_time_str = "0.00s到0.01s";
    elseif ($diff_time < 0.02)
        $diff_time_str = "0.01s到0.02s";
    elseif ($diff_time < 0.03)
        $diff_time_str = "0.02s到0.03s";
    elseif ($diff_time < 0.04)
        $diff_time_str = "0.03s到0.04s";
    elseif ($diff_time < 0.05)
        $diff_time_str = "0.04s到0.05s";
    elseif ($diff_time < 0.1)
        $diff_time_str = "0.05s到0.1s";
    elseif ($diff_time < 0.5)
        $diff_time_str = "0.1s到0.5s";
    elseif ($diff_time < 1)
        $diff_time_str = "0.5s到1s";
    elseif ($diff_time < 5)
        $diff_time_str = "1s到5s";
    elseif ($diff_time < 10)
        $diff_time_str = "5s到10s";
    else
        $diff_time_str = "10s到∞秒";
    return $diff_time_str;
}

/**
 * @desc   返回一条SQL语句对应查询的表名称
 * @author
 * @since  2013-05-29 15:55:46
 * @throws 注意:无DB异常处理
 */
function _sql_table_txt($sql, &$sql_type)
{
    $sql_out = array();
    $sql = strtr($sql, array(
            "\n" => ' ',
            "\r" => " "
        )) . " ";
    $sql_type = '(读)';
    if (stripos($sql, 'select ') !== false) {
        $sql_type = '(读)';
    } else if (stripos($sql, 'insert ') !== false) {
        $sql_type = '(写)';
    } else if (stripos($sql, 'update ') !== false) {
        $sql_type = '(改)';
    } else if (stripos($sql, 'delete ') !== false || stripos($sql, 'truncate ') !== false)
        $sql_type = '(删)';

    $v = '';
    preg_match_all('# from\s+([^ ]+) #iUs', $sql . " ", $sql_out);
    foreach ($sql_out[1] as $v) {
        if (strpos($v, '(') === false)
            break;
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#update\s+([^ ]+)\s(.*)set #iUs', $sql . " ", $sql_out);
        $v = isset($sql_out[1]) ? $sql_out[1] : '';
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#into\s+([^ ]+)[\s|\(]#iUs', $sql . " ", $sql_out);
        $v = isset($sql_out[1]) ? $sql_out[1] : '';
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#table\s+([^ ]+) #iUs', $sql . " ", $sql_out);
        $v = isset($sql_out[1]) ? $sql_out[1] : '';
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#begin\s+(.*)\(#iUS', $sql . " ", $sql_out);
        $v = isset($sql_out[1]) ? "Procedure:" . $sql_out[1] : '';
    }
    //如果不是获取数据,一般需要验证合法性.(最好header.php都定义:$_SERVER['check_sql_safe']='YES')
    //if (strpos($v, '(读)') === false) $_SERVER['check_sql_safe'] = trim($v);
    return trim($v);
}

/*
统计sql请求，需嵌入二行代码
$t1 = microtime(true);
... your sql query ...
apm_status_sql($db_alias, $sql, $t1, $sql_error);
*/
function apm_status_sql($db_alias, $sql, $start_time, $sql_error) {
    $diff_time = sprintf('%.5f', microtime(true) - $start_time);

    //pretty sql
    $sql = preg_replace("/(=|>|<|values|VALUES)[\s\S]+$/", " \\1", $sql);

    //检查in语法
    $out = array();
    preg_match('# in(\s+)?\(#is', $sql, $out);
    if ($out) {
        $sql = substr($sql, 0, stripos($sql, ' in')) . ' in....';
        _status(1, APM_HOST . "(BUG错误)", '问题SQL', "IN语法", "{$db_alias}@" . APM_URI, "{$sql}");
    }

    //curd分类
    $sql_type = NULL;
    $v = _sql_table_txt($sql, $sql_type);
    _status(1, APM_HOST . '(SQL统计)', "{$db_alias}{$sql_type}", strtolower($v) . "@" . APM_URI, $sql, APM_HOSTNAME, $diff_time);

    //耗时分类
    if ($diff_time < 1) {
        _status(1, APM_HOST . '(SQL统计)', '一秒内', _debugtime($diff_time), "{$db_alias}." . strtolower($v) . "@" . APM_URI . APM_HOSTNAME, $sql, $diff_time);
    } else {
        _status(1, APM_HOST . '(SQL统计)', '超时', _debugtime($diff_time), "{$db_alias}." . strtolower($v) . "@" . APM_URI . APM_HOSTNAME, $sql, $diff_time);
    }
    if ($sql_error)
        _status(1, APM_HOST . "(BUG错误)", 'SQL错误', APM_URI, var_export($sql_error, true) . "|" . $sql, APM_HOSTNAME, $diff_time);
}

/*
统计资源请求，支持以下四种Memcache、Sphinx、Couchbase
$t1 = microtime(true);
... your api query ...
apm_status_api('memcache', '10.0.1.20(get)', $t1, $resource);
*/
function apm_status_cache($type, $v2, $start_time, $resource) {
    $diff_time = sprintf('%.5f', microtime(true) - $start_time);
    $type = ucfirst(strtolower($type));

    _status(1, APM_HOST . "({$type})", $v2, APM_URI, var_export((bool) $resource, true), APM_HOSTNAME, $diff_time);
    if ($diff_time < 1) {
        _status(1, APM_HOST . "({$type})", '一秒内', _debugtime($diff_time), $v2, APM_URI, $diff_time);
    } else {
        _status(1, APM_HOST . "({$type})", '超时', _debugtime($diff_time), $v2, APM_URI, $diff_time);
    }
}

/*
统计api请求
$t1 = microtime(true);
... your api query ...
apm_status_api('http://ip.cn/query', $t1, $ch_info);
*/
function apm_status_curl($ch_url, $start_time, $ch_info) {
    $diff_time = sprintf('%.5f', microtime(true) - $start_time);

    $ch_arr = parse_url($ch_url);
    $host = isset($ch_arr['host']) ? $ch_arr['host'] : '未知host';
    $path = isset($ch_arr['path']) ? $ch_arr['path'] : '未知path';
    _status(1, APM_HOST . "(Api)", $host, $path, APM_URI, APM_HOSTNAME, $diff_time);

    if (isset($ch_info['http_code']) && $ch_info['http_code'] != 200) {
        _status(1, APM_HOST . "(BUG错误)", 'Curl错误', $path, var_export($ch_info, true), APM_HOSTNAME, $diff_time);
    }
    if ($diff_time < 1) {
        _status(1, APM_HOST . "(Api)", '一秒内', $path, APM_URI, APM_HOSTNAME, $diff_time);
    } else {
        _status(1, APM_HOST . "(Api)", '超时', $path, APM_URI, APM_HOSTNAME, $diff_time);
    }
}