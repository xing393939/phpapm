<?php
ini_set('date.timezone', 'PRC');
define('START_TIME', microtime(true));

//是否项目文件
if (strpos(APM_URI, 'crontab.php') !== false || strpos(APM_URI, 'header.php') !== false)
    define('APM_PROJECT', "[项目]");
else
    define('APM_PROJECT', null);

//对内服务IP
if (!empty($_SERVER['REMOTE_ADDR'])
    && (strpos($_SERVER['REMOTE_ADDR'], '192.168.') === 0
        || strpos($_SERVER['REMOTE_ADDR'], '10.') === 0
        || strpos($_SERVER['REMOTE_ADDR'], '58.83.190.') === 0
        )
    ) {
    define('IP_NEI', $_SERVER['REMOTE_ADDR']);
}

register_shutdown_function('_php_runtime');

/**
 * @desc   计算脚本执行时间，仍队列
 * @author
 * @since  2012-03-23 14:50:13
 * @throws 无DB异常处理
 */
function _php_runtime()
{
    if (connection_aborted())
        _status(1, APM_HOST . "(WEB日志分析)", '被断开', APM_URI, IP_NEI);
    $diff_time = sprintf('%.5f', microtime(true) - START_TIME);
    $get_included_files_2 = get_included_files();

    //包含文件个数监控
    foreach ($get_included_files_2 as $k => $v)
        if (strpos($v, '/phpCas/') !== false || strpos($v, '/_end.php') !== false)
            unset($get_included_files_2[$k]);
    $get_included_files_count = count($get_included_files_2);
    if ($get_included_files_count < 10) {
        $diff_time_str = $get_included_files_count . "个";
    } else {
        $diff_time_str = "10s到∞个";
    }
    if ($get_included_files_count > 9) {
        if (APM_PROJECT !== null)
            _status($get_included_files_count, APM_HOST . "(PHPAPM)", "包含文件", APM_PROJECT, $diff_time_str, APM_URI, var_export($get_included_files_2, true) . "\n");
        else
            _status($get_included_files_count, APM_HOST . "(PHPAPM)", "包含文件", $diff_time_str, APM_URI, var_export($get_included_files_2, true));
    }

    $e = error_get_last();
    if (strpos($e['message'], 'Call to undefined') !== false)
        return _status(1, APM_HOST . "(BUG错误)", '致命错误', "未定义函数", APM_URI, var_export($e, true) . "|" . var_export($_REQUEST, true) . "|" . APM_VIP, $diff_time);
    else if ($e['type'] == E_ERROR)
        return _status(1, APM_HOST . "(BUG错误)", 'PHP错误', APM_URI, var_export($e, true), APM_VIP, $diff_time);

    if ($_SERVER['HTTP_HOST'] && $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && !APM_PROJECT) {
        if ($diff_time < 1) {
            _status(1, APM_HOST . '(BUG错误)', '一秒内', _debugtime($diff_time), APM_URI, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}):" . APM_VIP, $diff_time);
        } else {
            _status(1, APM_HOST . '(BUG错误)', '超时', _debugtime($diff_time), APM_URI, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}):" . APM_VIP, $diff_time);
        }
    }

    //内存消耗统计
    $add_array = array();
    if (function_exists('getrusage')) {
        $data = getrusage();
        $add_array['user_cpu'] = $data['ru_utime.tv_sec'] + $data['ru_utime.tv_usec'] / 1000000;
        $add_array['sys_cpu'] = $data['ru_stime . tv_sec'] + $data['ru_stime . tv_usec'] / 1000000;
        if (function_exists('memory_get_peak_usage'))
            $add_array['memory'] = memory_get_peak_usage() / 1024 / 1024 / 1024;
    }
    //服务对象的IP统计
    if (!$_SERVER['HTTP_HOST'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
        _status(1, APM_HOST . "(BUG错误)", "定时", APM_URI, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|" . var_export($_SERVER, true), APM_VIP, $diff_time, NULL, NULL, $add_array);
    } else if (defined('IP_NEI')) {
        _status(1, APM_HOST . "(BUG错误)", "内网接口", APM_URI, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|", APM_VIP, $diff_time, NULL, NULL, $add_array);
    } else {
        _status(1, APM_HOST . "(BUG错误)", "其他功能", APM_URI, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|", APM_VIP, $diff_time, NULL, NULL, $add_array);
    }
}

set_error_handler("_myErrorHandler");

/**
 * @desc   接管PHP的异常处理信息,仍到队列后台处理
 * @author
 * @since  2012-04-02 09:50:31
 * @throws 无DB异常处理
 */
function _myErrorHandler($no, $msg, $file, $line)
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
    if (strpos($_GET['act'], 'monitor') === 0 && strpos($msg, 'msg_send') !== false)
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
         _status(1, APM_HOST . '(BUG错误)', "PHP错误", APM_URI, "(file:{$file} | line:{$line}){$msg}\n|" . var_export($_SERVER, true) . "\n{$debug_backtrace_str}");
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

    preg_match_all('# from\s+([^ ]+) #iUs', $sql . " ", $sql_out);
    foreach ($sql_out[1] as $v) {
        if (strpos($v, '(') === false)
            break;
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#update\s+([^ ]+)\s(.*)set #iUs', $sql . " ", $sql_out);
        $v = $sql_out[1];
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#into\s+([^ ]+)[\s|\(]#iUs', $sql . " ", $sql_out);
        $v = $sql_out[1];
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#table\s+([^ ]+) #iUs', $sql . " ", $sql_out);
        $v = $sql_out[1];
    }
    if (!$v) {
        $sql_out = array();
        preg_match('#begin\s+(.*)\(#iUS', $sql . " ", $sql_out);
        $v = "Procedure:" . $sql_out[1];
    }
    //如果不是获取数据,一般需要验证合法性.(最好header.php都定义:$_SERVER['check_sql_safe']='YES')
    if (strpos($v, '(读)') === false)
        $_SERVER['check_sql_safe'] .= trim($v);
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
        _status(1, APM_HOST . "(BUG错误)", '问题SQL', "IN语法" . APM_PROJECT, "{$db_alias}@" . APM_URI, "{$sql}");
    }

    //curd分类
    $sql_type = NULL;
    $v = _sql_table_txt($sql, $sql_type);
    _status(1, APM_HOST . '(SQL统计)', "{$db_alias}{$sql_type}", strtolower($v) . "@" . APM_URI, $sql, APM_VIP, $diff_time);

    //耗时分类
    if ($diff_time < 1) {
        _status(1, APM_HOST . '(SQL统计)', '一秒内', _debugtime($diff_time), "{$db_alias}." . strtolower($v) . "@" . APM_URI . APM_VIP, $sql, $diff_time);
    } else {
        _status(1, APM_HOST . '(SQL统计)', '超时', _debugtime($diff_time), "{$db_alias}." . strtolower($v) . "@" . APM_URI . APM_VIP, $sql, $diff_time);
    }
    if ($sql_error)
        _status(1, APM_HOST . "(BUG错误)", 'SQL错误', APM_URI, var_export($sql_error, true) . "|" . $sql, APM_VIP, $diff_time);
}

/*
统计资源/api请求，支持以下四种Memcache、Api、Sphinx、Couchbase
$t1 = microtime(true);
... your api query ...
apm_status_api('memcache', '10.0.1.20(get)', $t1, $resource);
*/
function apm_status_api($type, $v2, $start_time, $resource) {
    $diff_time = sprintf('%.5f', microtime(true) - $start_time);
    $type = ucfirst(strtolower($type));

    _status(1, APM_HOST . "({$type})", $v2, APM_URI, var_export((bool) $resource, true), APM_VIP, $diff_time);
    if ($diff_time < 1) {
        _status(1, APM_HOST . "({$type})", '一秒内', _debugtime($diff_time), $v2, APM_URI, $diff_time);
    } else {
        _status(1, APM_HOST . "({$type})", '超时', _debugtime($diff_time), $v2, APM_URI, $diff_time);
    }
}