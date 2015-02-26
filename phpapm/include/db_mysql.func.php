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

    $dbConfig = $apm_db_config->dbconfig;
    $DBS = explode('|', $DB);
    $DB = $DBS[time() % count($DBS)];
    $dbConfigInterface = $dbConfig[$DB];
    if (!$dbConfigInterface) {
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", "未定义数据库:" . $DB, APM_URI, APM_VIP);
        return null;
    }
    $tt1 = microtime(true);
    $conn_db = mysqli_connect($dbConfigInterface['TNS'], $dbConfigInterface['user_name'], $dbConfigInterface['password'], $dbConfigInterface['db']);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (mysqli_connect_errno($conn_db)) {
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", $DB . '@' . mysqli_connect_error(), APM_URI, APM_VIP, $diff_time);
        return null;
    }
    //凡是使用Mysql的一律是utf-8
    mysqli_query($conn_db, "SET NAMES 'utf8'");
    mysqli_query($conn_db, "SET character_set_client=binary");
    $conn_db->DB_ALIAS = $DB;
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
    $_SERVER['last_mysql_conn'] = $conn_db;
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
            $key => $value === null ? 'null' : "'" . mysqli_real_escape_string($stmt['$conn_db'], $value) . "'"
        );
    else
        $_SERVER['last_mysql_bindname'] += array(
            $key => $value === null ? '0' : intval($value)
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
        '/trunc\(([^,\)]+)(\)|,([^\)]+)\))/', '_oci_truncate', $sql);
    $sql = preg_replace_callback(
        '/(sysdate|SYSDATE)([ \d\+\-\/]*)/', '_oci_sysdate', $sql);
    //end

    $t1 = microtime(true);
    $stmt = mysqli_query($conn_db, $sql);
    $GLOBALS['lastSql'] = $sql;
    $mysql_error = mysqli_error($conn_db);

    //apm start
    apm_status_sql($conn_db->DB_ALIAS, $sql, $t1, $mysql_error);

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
        mysqli_close($conn_db);
    }
}

function apm_db_error($stmt = null)
{
    $conn_db = $stmt['$conn_db'];
    return mysqli_error($conn_db);
}

function apm_db_row_count($stmt = null)
{
    return mysqli_affected_rows($_SERVER['last_mysql_conn']);
}

function apm_db_fetch_assoc($stmt = false)
{
    $_row = mysqli_fetch_assoc($stmt);
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