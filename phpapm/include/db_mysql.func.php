<?php
/**
 * @desc   连接数据库
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function apm_db_logon($DB)
{
    if (!$DB)
        return null;
    $apm_db_config = new apm_db_config;

    $dbconfig = $apm_db_config->dbconfig;
    $DBS = explode('|', $DB);
    $DB = $DBS[time() % count($DBS)];
    $dbconfiginterface = $dbconfig[$DB];
    if (!$dbconfiginterface) {
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", "未定义数据库:" . $DB, APM_URI, APM_VIP);
        return null;
    }
    $tt1 = microtime(true);
    $conn_db = mysql_connect($dbconfiginterface['TNS'], $dbconfiginterface['user_name'], $dbconfiginterface['password'], true);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (!is_resource($conn_db)) {
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", $DB . '@' . mysql_error($conn_db), APM_URI, APM_VIP, $diff_time);
        return null;
    }
    $bool = mysql_select_db($dbconfiginterface['db'], $conn_db);
    if (!$bool)
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", $DB . '@' . mysql_error($conn_db), APM_URI, APM_VIP);
    //凡是使用Mysql的一律是utf-8
    mysql_query("SET NAMES 'utf8'");
    mysql_query("SET character_set_client=binary");

    $_SERVER['last_mysql_link'][$conn_db] = $DB;
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
function apm_db_parse(& $conn_db, $sql)
{
    $_SERVER['last_mysql_conn'] = $_SERVER['last_mysql_link'][$conn_db];
    return array(
        '$conn_db' => $conn_db,
        '$sql' => $sql
    );
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-11-25 17:33:09
 * @throws 注意:无DB异常处理
 */
function apm_db_bind_by_name($stmt, $key, $value, $int = false)
{
    $key = $key == ':DES' ? ':des' : $key;
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
 * @desc   执行SQL查询语句
 * @author
 * @since  2012-04-02 09:53:56
 * @param resource $stmt 数据库句柄资源
 * @return resource $error 错误信息
 * @throws 无DB异常处理
 */
function apm_db_execute(& $stmt, $mode = OCI_COMMIT_ON_SUCCESS)
{
    $conn_db = $stmt['$conn_db'];
    settype($_SERVER['last_mysql_bindname'], 'Array');
    $sql = strtr($stmt['$sql'], $_SERVER['last_mysql_bindname']);
    //start
    $sql = preg_replace_callback(
        '/to_date\(([^,\)]+),([^\)]+)\)([\s\d\+\-\/]*)/', '_oci_to_date', $sql);
    $sql = preg_replace_callback(
        '/trunc\(([^,\)]+)(\)|,([^\)]+)\))/', '_oci_truncate', $sql);
    $sql = preg_replace_callback(
        '/(sysdate|SYSDATE)([ \d\+\-\/]*)/', '_oci_sysdate', $sql);
    //end

    $t1 = microtime(true);
    $stmt = mysql_query($sql, $conn_db);
    $GLOBALS['lastSql'] = $sql;
    $mysql_error = mysql_error($conn_db);

    //apm start
    apm_status_mysql($_SERVER['last_mysql_conn'], $sql, $t1, $mysql_error);

    //清空上次的数据
    $_SERVER['last_mysql_bindname'] = array();
    return $mysql_error;
}

/**
 * @desc   关闭数据库连接
 * @author
 * @since  2012-06-20 18:30:44
 * @throws 注意:无DB异常处理
 */
function apm_db_logoff(&$conn_db)
{
    if ($conn_db) {
        mysql_close($conn_db);
    }
}

function apm_db_error($stmt = null)
{
    $conn_db = $stmt['$conn_db'];
    return mysql_error($conn_db);
}

function apm_db_row_count($stmt = null)
{
    return mysql_affected_rows();
}

function apm_db_fetch_assoc($stmt = false)
{
    $_row = mysql_fetch_assoc($stmt);
    $_row = !empty($_row) ? array_change_key_case($_row, CASE_UPPER) : $_row;
    if (!empty($_row['FUN_COUNT'])) {
        $_row['FUN_COUNT'] = preg_replace("/\.00$/", '', $_row['FUN_COUNT']);
    }
    return $_row;
}

function _oci_sysdate($matches)
{
    $delay = trim($matches[2]);
    if (empty($delay)) {
        $return = "NOW() ";
    } else {
        if (strpos($delay, '/') !== false) {
            $delay = preg_replace_callback('/([\d]+)[\s\/]+([\d]+)/', '_oci_get_hour', $delay);
            $return = "NOW() + INTERVAL $delay HOUR ";
        } else {
            $return = "NOW() + INTERVAL $delay DAY ";
        }
    }
    return $return;
}

function _oci_to_date($matches)
{
    $date = $matches[1];
    $format = $matches[2];
    $delay = trim($matches[3]);
    $return = '';
    $format_mysql = preg_replace(array(
        '/yyyy/',
        '/mm/',
        '/dd/',
        '/hh24/',
        '/mi/',
        '/ss/',
        "/(^\\\\'|\\\\'$)/",
    ), array(
        '%Y',
        '%m',
        '%d',
        '%H',
        '%i',
        '%s',
        "'",
    ), $format);
    if (empty($delay)) {
        $return = "DATE_FORMAT($date, {$format_mysql}) ";
    } else {
        if (strpos($delay, '/') !== false) {
            $delay = preg_replace_callback('/([\d]+)[\s\/]+([\d]+)/', '_oci_get_hour', $delay);
            $return = "DATE_FORMAT($date, {$format_mysql}) + INTERVAL $delay HOUR ";
        } else {
            $return = "DATE_FORMAT($date, {$format_mysql}) + INTERVAL $delay DAY ";
        }
    }
    return $return;
}

function _oci_get_hour($matches)
{
    return $matches[1] / $matches[2] * 24;
}

function _oci_truncate($matches)
{
    $date = $matches[1];
    $format = trim($matches[3]);
    $format_mysql = preg_replace(array(
        '/hh24/',
        "/(^\\\\'|\\\\'$)/",
    ), array(
        '%Y-%m-%d %H',
        "'",
    ), $format);
    $format_mysql = $format_mysql ? $format_mysql : "'%Y-%m-%d'";
    $return = "DATE_FORMAT($date, $format_mysql)";
    return $return;
}