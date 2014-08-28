<?php
ini_set('date.timezone', 'PRC');
define('START_TIME', microtime(true));
define('START_TIME_DATE', date('Y-m-d H:i:s',START_TIME));

//矫正apache在某些环境下返回全路径的错误
if (strpos($_SERVER['REQUEST_URI'], '://') !== false) $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];

//是否项目文件
define('GET_INCLUDED_FILES', $_SERVER['PHP_SELF'] . (isset($_GET['act']) ? '?act=' . $_GET['act'] : ''));

if (strpos(GET_INCLUDED_FILES, 'header_funtion.php') !== false || strpos(GET_INCLUDED_FILES, 'project') !== false || strpos(GET_INCLUDED_FILES, 'header.php') !== false)
    define('ADD_PROJECT', "[项目]");
else
    define('ADD_PROJECT', NULL);

//服务器是否已经到达极限的标志.
if (is_file('/proc/loadavg') && filemtime('/proc/loadavg') > time() - 120 && ($cache_dieoff = array_shift(explode(" ", trim(file_get_contents('/proc/loadavg'))))) > 30 && $cache_dieoff) {
    _status(1, VHOST . "(WEB日志分析)", "挂机", VIP . "LOAD", GET_INCLUDED_FILES . '|' . $cache_dieoff);
    define('DIEOFF', true);
}
if (is_file('/dev/shm/cache_tcp') && filemtime('/dev/shm/cache_tcp') > time() - 120 && ($cache_dieoff = count(file('/dev/shm/cache_tcp'))) > 500 && $cache_dieoff) {
    _status(1, VHOST . "(WEB日志分析)", "挂机", VIP . "TCP", GET_INCLUDED_FILES . '|' . $cache_dieoff);
    if (!defined('DIEOFF'))
        define('DIEOFF', true);
}
#最少需要2G的剩余内存
if (is_file('/dev/shm/cache_mem') && filemtime('/dev/shm/cache_mem') > time() - 120 && ($cache_dieoff = trim(file_get_contents('/dev/shm/cache_mem'))) < 1 && $cache_dieoff) {
    _status(1, VHOST . "(WEB日志分析)", "挂机", VIP . "Mem", GET_INCLUDED_FILES . '|' . $cache_dieoff);
    if (!defined('DIEOFF'))
        define('DIEOFF', true);
}

if (!defined('DIEOFF'))
    define('DIEOFF', false);

//对内服务IP
if ($_SERVER['REMOTE_ADDR'] && (substr($_SERVER['REMOTE_ADDR'], 0, strpos($_SERVER['REMOTE_ADDR'], '.', 4)) == '192.168' || substr($_SERVER['REMOTE_ADDR'], 0, strpos($_SERVER['REMOTE_ADDR'], '.')) == '10') || substr($_SERVER['SERVER_ADDR'], 0, strrpos($_SERVER['SERVER_ADDR'], '.')) == substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.')) || strpos($_SERVER['REMOTE_ADDR'], '58.83.190.') === 0) {
    define('IP_NEI', $_SERVER['REMOTE_ADDR']);
}

define('VIMAGE', '/project/images/');
define('VIMAGE_PATH', './project/images/');
//签名模式.在更改数据库(insert/update/delete)数据的时候,必须存在常量判断 SING==true
//验证当前连接是不是真的来自本站的点击&sign=<\?=SIGN_KEY?\>
define('SIGN_KEY', md5($_SERVER['REMOTE_ADDR'] . VHOST . '67yu^YHN'));
define('SIGN', SIGN_KEY == $_REQUEST['sign']);

//用于不需要缓存功能,对应的唯一memcache-key,防止万一负载高,临时应付
define('MEMKEY', md5($_SERVER['REQUEST_URI'] . join(',', $_REQUEST)));

/**
 * @desc   检测系统负载过大,防止雪崩保护,返回true,意味系统要崩溃了
 * @author
 * @since  2013-07-07 15:27:33
 * @throws 注意:无DB异常处理
 */
function _sys_overload()
{
    define('_sysload_df', true);
    return DIEOFF;
}

/**
 * @desc   检测系统负载过大,防止雪崩保护
 * @author
 * @since  2013-07-07 15:27:33
 * @throws 注意:无DB异常处理
 */
function _db_overload($DB)
{
    define('_dbload_df_' . $DB, true);
    return defined("db_overload_" . $DB);
}

/**
 * @desc   WHAT?
 * @author
 * @since  2013-07-07 16:09:36
 * @throws 注意:无DB异常处理
 */
function _curl_overload($chinfo)
{
    $url_path = explode('?', $chinfo['url']);
    unset($_SERVER['last_curl_info'][$url_path[0]]);
    if ($chinfo['http_code'] != '200' && $chinfo['http_code'][0] != '3')
        return true;
    return false;
}

/**
 * @desc   网站信任度检测,必须是通过验证的可靠来源
 * @author
 * @since  2013-07-08 00:47:47
 * @throws 注意:无DB异常处理
 */
function _check_sign($sign)
{
    define('_check_sign', true);
    return $sign;
}

/**
 * @desc   WHAT? $uptype=replace/utf-8
 * @author
 * @since  2012-06-22 20:14:54
 * @throws 注意:无DB异常处理
 */
function _status($num, $v1, $v2, $v3 = VIP, $v4 = null, $v5 = VIP, $diff_time = 0, $uptype = null, $time = null, $add_array = array())
{
    if (!$time)
        $START_TIME_DATE = START_TIME_DATE;
    else
        $START_TIME_DATE = date('Y-m-d H:i:s',$time);

    $includes = array();
    if ($v2 == $v3)
        $v3 = VIP;

    //累计_status
    static $_status_sql = '';

    if ($v3 == NULL)
        $v3 = VIP;
    if ($v5 == VIP)
        $v5 = NULL;
    $_uptype = $code = NULL;
    list($_uptype, $code) = explode('/', $uptype);
    settype($add_array, 'array');
    $array = array(
            'vhost' => VHOST,
            'includes' => $includes,
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
            'uptype' => $_uptype
        ) + $add_array;
    $_status_sql .= "('" . addslashes(serialize($array)) . "'),";

    //入队列
    if ($v1 == VHOST . "(BUG错误)" && in_array($v2, array('定时', '内网接口', '页面操作', '其他功能'))) {
        $project_config = new project_config();
        $db_config = new oracleDB_config();
        $db_config = $db_config->dbconfig[$project_config->db];
        mysql_connect($db_config['TNS'], $db_config['user_name'], $db_config['password']) or die();
        mysql_select_db($db_config['db']) or die();
        $_status_sql = rtrim($_status_sql, ',');
        mysql_query("SET NAMES 'utf8'");
        mysql_query("insert into {$project_config->report_monitor_queue} (`queue`) values {$_status_sql}");
    }
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
    //if (strpos(PHP_OS, 'WIN') === false) {
    if (1) {
        if (connection_aborted())
            _status(1, VHOST . "(WEB日志分析)", '被断开', GET_INCLUDED_FILES, IP_NEI);
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
            if (ADD_PROJECT !== null)
                _status($get_included_files_count, VHOST . "(PHPAPM)", "包含文件", ADD_PROJECT, $diff_time_str, GET_INCLUDED_FILES, var_export($get_included_files_2, true) . "\n");
            else
                _status($get_included_files_count, VHOST . "(PHPAPM)", "包含文件", $diff_time_str, GET_INCLUDED_FILES, var_export($get_included_files_2, true));
        }

        $is_html = (bool)strpos(array_pop($get_included_files_2), '.html');
        if (!$is_html)
            $is_html = (bool)strpos(array_pop($get_included_files_2), '.html');

        if (PHP_VERSION > '5.2') {
            $debug_backtrace_str = NULL;
            foreach (debug_backtrace() as $vv)
                $debug_backtrace_str .= "line:({$vv['line']}){$vv['function']}@file:{$vv['file']}\n";
            $e = error_get_last();
            if (strpos($e['message'], 'Call to undefined') !== false && $_SERVER['REMOTE_ADDR'] <> '180.168.136.230')
                return _status(1, VHOST . "(BUG错误)", '致命错误', "未定义函数", GET_INCLUDED_FILES, "userIP:{$_SERVER['REMOTE_ADDR']}@referfer:{$_SERVER['HTTP_REFERER']}|" . var_export($e, true) . "|" . var_export($_REQUEST, true) . "|" . var_export($_COOKIE, true) . '|' . VIP, $diff_time);
            else if ($e['type'] == E_ERROR)
                return _status(1, VHOST . "(BUG错误)", 'PHP错误', GET_INCLUDED_FILES, "userIP:{$_SERVER['REMOTE_ADDR']}@referfer:{$_SERVER['HTTP_REFERER']}|" . var_export($e, true) . "|" . var_export($_REQUEST, true) . "|" . var_export($_COOKIE, true), VIP, $diff_time);
        }

        if ($_SERVER['HTTP_HOST'] && $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && !ADD_PROJECT) {
            if ($diff_time < 1) {
                _status(1, VHOST . '(BUG错误)', '一秒内', _debugtime($diff_time), GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}):" . VIP, $diff_time);
            } else {
                _status(1, VHOST . '(BUG错误)', '超时', _debugtime($diff_time), GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}):" . VIP, $diff_time);
            }

        }
        //本次执行,各项过载保护检测
        //WEB服务器,只对接口请求有效,定时任务不做限制.
        $_sysload_df = NULL;
        if ($_SERVER['HTTP_HOST'] && !defined('_sysload_df'))
            $_sysload_df = '[没有过载保护]';

        //DB负载过载保护检测
        $_dbload_df = NULL;
        settype($_SERVER['last_oci_link'], 'array');
        settype($_SERVER['last_mysql_link'], 'array');
        foreach (array_unique(array_values($_SERVER['last_oci_link'])) as $db_overload) {
            if (!defined('_dbload_df_' . $db_overload))
                $_dbload_df .= "[{$db_overload}没有OIC_DB保护]";
        }
        foreach (array_unique(array_values($_SERVER['last_mysql_link'])) as $db_overload) {
            if (!defined('_dbload_df_' . $db_overload))
                $_dbload_df .= "[{$db_overload}没有Mysql_DB保护]";
        }
        //接口获取保护
        $_curl_df = NULL;
        if (!empty($_SERVER['last_curl_info']))
            $_curl_df = "[没有检测接口(" . count($_SERVER['last_curl_info']) . "个)]";
        //身份认证
        $_check_sign = NULL;
        if (!defined('_check_sign') && $_SERVER['check_sql_safe'])
            $_check_sign = '[无身份验证]';

        //内存消耗统计
        $add_array = array();
        if (function_exists('getrusage')) {
            $data = getrusage();
            $add_array['user_cpu'] = $data['ru_utime.tv_sec'] + $data['ru_utime.tv_usec'] / 1000000;
            $add_array['sys_cpu'] = $data['ru_stime . tv_sec'] + $data['ru_stime . tv_usec'] / 1000000;
            if (function_exists('memory_get_peak_usage'))
                $add_array['memory'] = memory_get_peak_usage() / 1024 / 1024 / 1024;
        }
        $GET_INCLUDED_FILES = GET_INCLUDED_FILES; //. "{$_sysload_df}{$_dbload_df}{$_curl_df}{$_check_sign}";
        //服务对象的IP统计
        if (!$_SERVER['HTTP_HOST'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1')
            _status(1, VHOST . "(BUG错误)", "定时", $GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|" . var_export($_SERVER['argv'], true), VIP, $diff_time, NULL, NULL, $add_array);
        else if (defined('IP_NEI'))
            _status(1, VHOST . "(BUG错误)", "内网接口", $GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|", VIP, $diff_time, NULL, NULL, $add_array);
        else if ($is_html) {
            _status(1, VHOST . "(BUG错误)", "页面操作", $GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|", VIP, $diff_time, NULL, NULL, $add_array);
        } else {
            _status(1, VHOST . "(BUG错误)", "其他功能", $GET_INCLUDED_FILES, IP_NEI . "(HOST:{$_SERVER['HTTP_HOST']}){$_SERVER['last_curl_info_num']}次|", VIP, $diff_time, NULL, NULL, $add_array);
        }
    }
}

set_error_handler("_myErrorHandler");

/**
 * @desc   接管PHP的异常处理信息,仍到队列后台处理
 * @author
 * @since  2012-04-02 09:50:31
 * @throws 无DB异常处理
 */
function _myErrorHandler($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
        case E_NOTICE:
        case E_USER_ERROR:
        case E_USER_NOTICE:
        case E_STRICT:
            return;
    }
    if ($errstr == 'Division by zero')
        return;
    if ($errstr == 'Invalid argument supplied for foreach()')
        return;
    if (strpos($errstr, 'current()') === 0)
        return;
    if (strpos($errstr, 'next()') === 0)
        return;
    if (strpos($errstr, 'ftp_mkdir()') === 0)
        return;
    if (strpos($_GET['act'], 'monitor') === 0 && strpos($errstr, 'msg_send') !== false)
        return;
    if (strpos($errstr, 'UTF-8 sequence') !== false)
        return;
    if (strpos($errstr, 'fopen(') !== false)
        $errstr = preg_replace("#fopen\((.*)\)#", "[file]", $errstr);

    $debug_backtrace_str = NULL;
    foreach (debug_backtrace() as $vv)
        $debug_backtrace_str .= "line:({$vv['line']}){$vv['function']}@file:{$vv['file']}\n";
    if (strpos($errstr, 'oci') === 0 || strpos($errstr, 'mysql_') === 0)
        _status(1, VHOST . '(BUG错误)', "SQL错误", GET_INCLUDED_FILES, "(file:{$errfile} | line:{$errline}){$errstr}\n{$debug_backtrace_str}");
    elseif (strpos($errstr, 'Memcache') === 0) {
        _status(1, VHOST . '(BUG错误)', "Memcache错误", GET_INCLUDED_FILES, "(file:{$errfile} | line:{$errline}){$errstr}\n{$debug_backtrace_str}");
    } else {
        if (strpos($errstr, 'msg_send') !== false)
            _status(1, VHOST . '(BUG错误)', "PHP错误", GET_INCLUDED_FILES, "(file:{$errfile} | line:{$errline}){$errstr}\n");
        else
            _status(1, VHOST . '(BUG错误)', "PHP错误", GET_INCLUDED_FILES, "(file:{$errfile} | line:{$errline}){$errstr}\n|" . var_export($_SERVER['argv'], true) . "\n{$debug_backtrace_str}");
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
 * @desc   连接数据库
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function _mysqllogon($DB)
{
    if (!$DB)
        return null;
    $_SERVER['mysql_oci_sql_ociexecute'] = $_SERVER['oci_sql_ociexecute'];
    $oracleDB_config = new oracleDB_config;

    $dbconfig = $oracleDB_config->dbconfig;
    $DBS = explode('|', $DB);
    $DB = $DBS[time() % count($DBS)];
    $dbconfiginterface = $dbconfig[$DB];
    if (!$dbconfiginterface) {
        _status(1, VHOST . '(BUG错误)', "SQL错误", "未定义数据库:" . $DB, GET_INCLUDED_FILES, VIP);
        return null;
    }
    $tt1 = microtime(true);
    $conn_db = mysql_connect($dbconfiginterface['TNS'], $dbconfiginterface['user_name'], $dbconfiginterface['password'], true);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (!is_resource($conn_db)) {
        _status(1, VHOST . '(BUG错误)', "SQL错误", $DB . '@' . mysql_error($conn_db), GET_INCLUDED_FILES, VIP, $diff_time);
        return null;
    }
    $bool = mysql_select_db($dbconfiginterface['db'], $conn_db);
    if (!$bool)
        _status(1, VHOST . '(BUG错误)', "SQL错误", $DB . '@' . mysql_error($conn_db), GET_INCLUDED_FILES, VIP);
    //凡是使用Mysql的一律是utf-8
    mysql_query("SET NAMES 'utf8'");

    $_SERVER['last_mysql_link'][$conn_db] = $DB;
    return $conn_db;
}

/**
 * @author
 * @since  2012-04-02 22:32:01
 * @throws 注意:无DB异常处理
 */
function _mysqlparse(&$conn_db, $sql)
{
    $_SERVER['last_mysql_conn'] = $_SERVER['last_mysql_link'][$conn_db];
    return array(
        '$conn_db' => $conn_db,
        '$sql' => $sql
    );
}

/**
 * @desc   修改mysql的绑定字符
 * @author
 * @since  2012-04-02 22:29:42
 * @throws 注意:无DB异常处理
 */
function _mysqlbindbyname($stmt, $key, $value, $int = false)
{
    settype($_SERVER['last_mysql_bindname'], 'Array');
    if (!$int)
        $_SERVER['last_mysql_bindname'] += array(
            $key => $value === null ? 'null' : "'" . mysql_real_escape_string($value) . "'"
        );
    else
        $_SERVER['last_mysql_bindname'] += array(
            $key => $value === null ? '0' : (int)mysql_real_escape_string($value)
        );
}

/**
 * @desc   修改mysql的绑定字符
 * @author
 * @since  2012-04-02 22:29:42
 * @throws 注意:无DB异常处理
 */
function _mysqlbindbyname2($stmt, $key, $value, $int = false)
{
    settype($_SERVER['last_mysql_bindname'], 'Array');
    if (!$int)
        $_SERVER['last_mysql_bindname'] += array(
            $key => $value === null ? "''" : "'" . mysql_escape_string($value) . "'"
        );
    else
        $_SERVER['last_mysql_bindname'] += array(
            $key => $value === null ? '0' : (int)mysql_escape_string($value)
        );
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

/**
 * @desc   执行SQL语句
 * @author
 * @since  2012-04-02 22:29:12
 * @throws 注意:无DB异常处理
 */
function _mysqlexecute(&$stmt)
{
    $conn_db = $stmt['$conn_db'];
    $_SERVER['last_mysql_sql'] = strtr($stmt['$sql'], array(
        "\n" => ' ',
        "\r" => " "
    ));
    settype($_SERVER['last_mysql_bindname'], 'Array');
    $sql = strtr($stmt['$sql'], $_SERVER['last_mysql_bindname'] + array(
            'sysdate' => 'now()',
            'SYSDATE' => 'now()'
        ));

    $t1 = microtime(true);
    $stmt = mysql_query($sql, $conn_db);
    $diff_time = sprintf('%.5f', microtime(true) - $t1);

    //表格与函数关联
    $sql_type = NULL;
    $v = _sql_table_txt($_SERVER['last_mysql_sql'], $sql_type);

    $last_oci_sql = $_SERVER['last_mysql_sql'];
    //thinkPHP start
    $last_oci_sql = preg_replace("/(=|>|<|values|VALUES)[\s\S]+$/", " \\1", $last_oci_sql);
    //thinkPHP end
    $out = array();
    preg_match('# in(\s+)?\(#is', $last_oci_sql, $out);
    if ($out) {
        $last_oci_sql = substr($last_oci_sql, 0, stripos($last_oci_sql, ' in')) . ' in....';
        _status(1, VHOST . "(BUG错误)", '问题SQL', "IN语法" . ADD_PROJECT, "{$_SERVER['last_mysql_conn']}@" . GET_INCLUDED_FILES, "{$last_oci_sql}");
    }
    _status(1, VHOST . '(MySQL统计)', "{$_SERVER['last_mysql_conn']}{$sql_type}", strtolower($v) . "@" . GET_INCLUDED_FILES, $last_oci_sql, VIP, $diff_time);

    $diff_time_str = _debugtime($diff_time);
    if ($diff_time < 1) {
        _status(1, VHOST . '(MySQL统计)', '一秒内', _debugtime($diff_time), "{$_SERVER['last_mysql_conn']}." . strtolower($v) . "@" . GET_INCLUDED_FILES . VIP, $_SERVER['last_mysql_sql'], $diff_time);
    } else {
        _status(1, VHOST . '(MySQL统计)', '超时', _debugtime($diff_time), "{$_SERVER['last_mysql_conn']}." . strtolower($v) . "@" . GET_INCLUDED_FILES . VIP, $_SERVER['last_mysql_sql'], $diff_time);
    }
    $ocierror = mysql_error($conn_db);
    if ($ocierror)
        _status(1, VHOST . "(BUG错误)", 'SQL错误', GET_INCLUDED_FILES, var_export($ocierror, true) . "|" . var_export($_GET, true) . "|" . $_SERVER['last_mysql_sql'], VIP, $diff_time);

    //清空上次的数据
    $_SERVER['last_mysql_bindname'] = array();
    return $ocierror;
}

/**
 * @desc   WHAT?
 * @author
 * @since  2013-01-29 14:57:50
 * @throws 注意:无DB异常处理
 */
function _mysqlclose(&$conn_db)
{
    if ($conn_db) {
        mysql_close($conn_db);
        $DB = $_SERVER['last_mysql_link'][$conn_db];
    }
}

/**
 * @desc   连接数据库
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function _ocilogon($DB)
{
    if (!$DB)
        return null;
    $oracleDB_config = new oracleDB_config;

    $dbconfig = $oracleDB_config->dbconfig;
    $DBS = explode('|', $DB);
    $DB = $DBS[time() % count($DBS)];
    $dbconfiginterface = $dbconfig[$DB];
    if (!$dbconfiginterface) {
        _status(1, VHOST . '(BUG错误)', "SQL错误", "未定义数据库:" . $DB, GET_INCLUDED_FILES, VIP);
        return null;
    }
    $tt1 = microtime(true);
    $conn_db = ocinlogon($dbconfiginterface['user_name'], $dbconfiginterface['password'], $dbconfiginterface['TNS']);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (!is_resource($conn_db)) {
        $err = ocierror();
        _status(1, VHOST . '(BUG错误)', "SQL错误", $DB . '@' . $err['message'], GET_INCLUDED_FILES, VIP, $diff_time);
        return null;
    }
    $_SERVER['last_oci_link'][$conn_db] = $DB;
    return $conn_db;
}

/**
 * @desc   绑定查询语句
 * @author
 * @since  2012-04-02 09:51:16
 * @param string $db_conn 数据库连接
 * @param string $sql SQL语句
 * @return resource $stmt
 * @throws 无DB异常处理
 */
function _ociparse($conn_db, $sql)
{
    $_SERVER['last_db_conn'] = $_SERVER['last_oci_link'][$conn_db];
    //SQL性能分析准备,定时任务的SQL不参与分析
    if (is_writable('/dev/shm/') && $_SERVER['last_oci_sql'] <> $sql && !((!$_SERVER['HTTP_HOST'] || $_SERVER['REMOTE_ADDR'] == '127.0.0.1'))) {
        $out = array();
        preg_match('# in(\s+)?\(#is', $sql, $out);
        if (!$out) {
            $get_included_files = $_SERVER['PHP_SELF'];
            $basefile = '/dev/shm/sql_' . VHOST;
            if (is_writable($basefile))
                $sqls = unserialize(file_get_contents($basefile));
            else
                $sqls = array();
            $sign = md5($_SERVER['last_db_conn'] . $sql);
            if (count($sqls) < 100 && !$sqls[$sign]) {
                $sqls[$sign] = array(
                    'sql' => $sql,
                    'add_time' => date('Y-m-d H:i:s'),
                    'db' => $_SERVER['last_db_conn'],
                    'type' => 'oci',
                    'vhost' => VHOST,
                    'act' => "{$get_included_files}/{$_REQUEST['act']}"
                );
                file_put_contents($basefile, serialize($sqls));
            }
        }
    }
    $_SERVER['last_oci_sql'] = $sql;
    return ociparse($conn_db, $sql);
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-11-25 17:33:09
 * @throws 注意:无DB异常处理
 */
function _ocibindbyname($stmt, $key, $value)
{
    settype($_SERVER['last_oci_bindname'], 'Array');
    $_SERVER['last_oci_bindname'][$key] = $value;
    ocibindbyname($stmt, $key, $value);
}

/**
 * @desc   执行SQL查询语句
 * @author
 * @since  2012-04-02 09:53:56
 * @param resource $stmt 数据库句柄资源
 * @return resource $error 错误信息
 * @throws 无DB异常处理
 */
function _ociexecute($stmt, $mode = OCI_COMMIT_ON_SUCCESS)
{
    $last_oci_sql = $_SERVER['last_oci_sql'];
    $ADD_PROJECT = ADD_PROJECT;
    if (!is_resource($stmt)) {
        $debug_backtrace = debug_backtrace();
        array_walk($debug_backtrace, create_function('&$v,$k', 'unset($v["function"],$v["args"]);'));
        _status(1, VHOST . "(BUG错误)", "SQL错误", GET_INCLUDED_FILES, "非资源\$stmt | " . var_export($_SERVER['last_oci_bindname'], true) . "|" . var_export($_GET, true) . "|" . $last_oci_sql . "|" . var_export($debug_backtrace, true));
    }
    if (PROJECT_SQL === true)
        $ADD_PROJECT = '[项目]';
    $_SERVER['oci_sql_ociexecute']++;
    $t1 = microtime(true);
    ociexecute($stmt, $mode);
    $diff_time = sprintf('%.5f', microtime(true) - $t1);

    //表格与函数关联
    $sql_type = NULL;
    $v = _sql_table_txt($last_oci_sql, $sql_type);
    $out = array();
    preg_match('# in(\s+)?\(#is', $last_oci_sql, $out);
    if ($out) {
        $last_oci_sql = substr($last_oci_sql, 0, stripos($last_oci_sql, ' in')) . ' in....';
        _status(1, VHOST . "(BUG错误)", '问题SQL', "IN语法" . $ADD_PROJECT, "{$_SERVER['last_db_conn']}@" . GET_INCLUDED_FILES . "/{$_REQUEST['act']}", "{$last_oci_sql}");
    }

    _status(1, VHOST . '(SQL统计)' . $ADD_PROJECT, "{$_SERVER['last_db_conn']}{$sql_type}", strtolower($v) . "@" . GET_INCLUDED_FILES, $last_oci_sql, VIP, $diff_time);

    $diff_time_str = _debugtime($diff_time);
    if ($diff_time < 1) {
        _status(1, VHOST . '(SQL统计)', '一秒内', _debugtime($diff_time), "{$_SERVER['last_db_conn']}." . strtolower($v) . "@" . GET_INCLUDED_FILES . VIP, $last_oci_sql, $diff_time);
    } else {
        _status(1, VHOST . '(SQL统计)', '超时', _debugtime($diff_time), "{$_SERVER['last_db_conn']}." . strtolower($v) . "@" . GET_INCLUDED_FILES . VIP, $last_oci_sql, $diff_time);
    }
    $ocierror = ocierror($stmt);
    if ($ocierror) {
        $debug_backtrace = debug_backtrace();
        array_walk($debug_backtrace, create_function('&$v,$k', 'unset($v["function"],$v["args"]);'));
        _status(1, VHOST . "(BUG错误)", "SQL错误", GET_INCLUDED_FILES, var_export($ocierror, true) . '|' . var_export($_SERVER['last_oci_bindname'], true) . "|GET:" . var_export($_GET, true) . '|POST:' . var_export($_POST, true) . "|" . $last_oci_sql . "|" . var_export($debug_backtrace, true), VIP, $diff_time);
    }

    $_SERVER['last_oci_bindname'] = array();
    return $ocierror;
}

/**
 * @desc   关闭数据库连接
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function _ocilogoff(&$conn_db)
{
    if ($conn_db) {
        ocilogoff($conn_db);
        $DB = $_SERVER['last_oci_link'][$conn_db];
    }
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-06-16 12:11:22
 * @throws 注意:无DB异常处理
 */
function _p($pageID, $is_page = true, $pagefirst = null)
{
    static $page_tp, $page_first;
    if ($is_page) {
        if ($pageID < 2) {
            return $page_first;
        } else
            return str_replace('{p}', $pageID, $page_tp);
    } else {
        $page_tp = $pageID;
        $page_first = $pagefirst;
    }
}