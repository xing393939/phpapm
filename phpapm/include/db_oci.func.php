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
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", "未定义数据库:" . $DB, APM_URI, APM_HOSTNAME);
        return null;
    }
    $tt1 = microtime(true);
    $conn_db = ocinlogon($dbconfiginterface['user_name'], $dbconfiginterface['password'], $dbconfiginterface['TNS'], 'AL32UTF8');
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (!is_resource($conn_db)) {
        $err = apm_db_error();
        _status(1, APM_HOST . '(BUG错误)', "SQL错误", $DB . '@' . $err['message'], APM_URI, APM_HOSTNAME, $diff_time);
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
    //SQL性能分析准备,定时任务的SQL不参与分析
    if (is_writable('/dev/shm/') && $_SERVER['last_oci_sql'] <> $sql && APM_REQUEST_TYPE != 'CLI') {
        $out = array();
        preg_match('# in(\s+)?\(#is', $sql, $out);
        if (!$out) {
            $basefile = '/dev/shm/sql_' . APM_HOST;
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
                    'vhost' => APM_HOST,
                    'act' => APM_URI
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
    $last_oci_sql = $_SERVER['last_oci_sql'];
    if (!is_resource($stmt)) {
        $debug_backtrace = debug_backtrace();
        array_walk($debug_backtrace, create_function('&$v,$k', 'unset($v["function"],$v["args"]);'));
        _status(1, APM_HOST . "(BUG错误)", "SQL错误", APM_URI, "非资源\$stmt | " . var_export($_SERVER['last_oci_bindname'], true) . "|" . var_export($_GET, true) . "|" . $last_oci_sql . "|" . var_export($debug_backtrace, true));
    }
    $t1 = microtime(true);
    ociexecute($stmt, $mode);
    $oci_error = apm_db_error($stmt);

    //apm start
    apm_status_sql($_SERVER['last_db_conn'], $last_oci_sql, $t1, $oci_error);

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

function apm_db_error($stmt = null)
{
    return ocierror($stmt);
}

function apm_db_row_count($stmt = null)
{
    return ocirowcount($stmt);
}

function apm_db_fetch_assoc($stmt = false)
{
    return oci_fetch_assoc($stmt);
}