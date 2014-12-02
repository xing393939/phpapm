<?php
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
 * @desc   执行SQL语句
 * @author
 * @since  2012-04-02 22:29:12
 * @throws 注意:无DB异常处理
 */
function _mysqlexecute(&$stmt)
{
    $conn_db = $stmt['$conn_db'];
    settype($_SERVER['last_mysql_bindname'], 'Array');
    $sql = strtr($stmt['$sql'], $_SERVER['last_mysql_bindname'] + array(
            'sysdate' => 'now()',
            'SYSDATE' => 'now()'
        ));

    $t1 = microtime(true);
    $stmt = mysql_query($sql, $conn_db);
    $mysql_error = mysql_error($conn_db);

    //apm start
    apm_status_mysql($_SERVER['last_mysql_conn'], $sql, $t1, $mysql_error);

    //清空上次的数据
    $_SERVER['last_mysql_bindname'] = array();
    return $mysql_error;
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
    }
}

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
        return null;
    }
    $conn_db = ocinlogon($dbconfiginterface['user_name'], $dbconfiginterface['password'], $dbconfiginterface['TNS']);
    if (!is_resource($conn_db)) {
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
function apm_db_parse($conn_db, $sql)
{
    $_SERVER['last_db_conn'] = $_SERVER['last_oci_link'][$conn_db];
    $_SERVER['last_oci_sql'] = $sql;
    ociparse($conn_db, $sql);
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-11-25 17:33:09
 * @throws 注意:无DB异常处理
 */
function apm_db_bind_by_name($stmt, $key, $value)
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
function apm_db_execute($stmt, $mode = OCI_COMMIT_ON_SUCCESS)
{
    $_SERVER['oci_sql_ociexecute'] ++;
    $oci_error = ociexecute($stmt, $mode);
    $_SERVER['last_oci_bindname'] = array();
    return $oci_error;
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
        ocilogoff($conn_db);
    }
}