<?php
/*
 *
 * header_function.php page���page����ĩβ����
 * $this->num_1 = '';
 * $this->num_3 = ' LIMIT ' . $this->limit_1 . ',' . $this->limit_2;
 *
 * project.php monitor_config����copy("project/doc/" . $o_file, "project/doc/doc_cpy/" . $time_file);�����
 * $o_file = $file = mb_convert_encoding($file, 'GBK', 'UTF-8');
*/

if (!function_exists('ocinlogon')) {
    function ocinlogon($user_name, $password, $tns)
    {
        $mysql_db = _mysqllogon(APM_DB_ALIAS);
        mysql_query("SET NAMES 'utf8'");
        mysql_query("SET character_set_client=binary");
        return $mysql_db;
    }

    function ociparse($conn_db, $sql)
    {
        $GLOBALS['$stmt'] = _mysqlparse($conn_db, $sql);
        return $conn_db;
    }

    function ocibindbyname($null, $key, $value)
    {
        _mysqlbindbyname($GLOBALS['$stmt'], $key, $value);

        //����:DES
        if ($key == ':DES') {
            _mysqlbindbyname($GLOBALS['$stmt'], strtolower($key), $value);
        }
    }

    function ociexecute($null, $mode = false)
    {
        _mysqlbindbyname($GLOBALS['$stmt'], ':oci_unique', round(lcg_value() * 100000000));
        preg_match('/^[\s]*([\w]+)/', $GLOBALS['$stmt']['$sql'], $matches);
        $GLOBALS['$stmt_mode'] = empty($matches[1]) ? 'default' : strtolower($matches[1]);
        $GLOBALS['$stmt']['$sql'] = preg_replace(array(
            '/[\w_]+_doc_list[\s\S]+where l\.list_id=d\.list_id\(\+\)/',
            "/\(\+\)[\s]+/",
            '/decode\(([^,]+),([^,]+),([^,]+),([^,]+)\)/',
            '/[\w\_]+\.nextval/',
        ), array(
            'phpapm_doc_list l left join phpapm_doc_detail d on l.list_id=d.list_id where 1',
            ' ',
            ' case when \\1 = \\2 then \\3  else \\4 End ',
            'NULL',
        ), $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace_callback(
            '/to_char\(([^,\'"]+),([^\)]+)\)/', '_oci_to_char', $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace_callback(
            '/to_date\(([^,\)]+),([^\)]+)\)([\s\d\+\-\/]*)/', '_oci_to_date', $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace(array(
            '/nvl\(([^,]+),([^\)]+)\)/', //nvl(v6,0)
            '/^[\s]*select(.*)\.currval(.*)dual[\s]*$/', //select SEQ_".APM_DB_PREFIX."doc}.currval doc_id from dual
            '/^[\s]*(insert[^\(]+)([\s]+t[\s]+)\(/', //insert into phpapm_doc_list t (...)
            '/(regexp_substr|regexp_replace)\(([^,\)]+),.*\'\)/U', //regexp_substr(V2, '[^_]+$')
        ), array(
            'ifnull(\\1,\\2)',
            'SELECT last_insert_id()',
            '\\1 (',
            ' \\2 ',
        ), $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace_callback(
            '/trunc\(([^,\)]+)(\)|,([^\)]+)\))/', '_oci_truncate', $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace_callback(
            '/(sysdate|SYSDATE)([ \d\+\-\/]*)/', '_oci_sysdate', $GLOBALS['$stmt']['$sql']);
        $GLOBALS['$stmt']['$sql'] = preg_replace(array(
            '/([\s]*)LOAD([\s]*)/', //`LOAD`
            '/^[\s]*alter[\s]*session.*$/', //alter session set nls_date_format=...
            '/^([\s]*update[\s]+.*[\s]+(set|SET)[\s]+)/',
            '/=[\s]+(null|NULL)[\s]+/', //=null⣬http://www.tiandone.com/td/747.html
        ), array(
            '\\1`LOAD`\\2',
            'select 1',
            '\\1 oci_unique=:oci_unique, ',
            ' is NULL ',
        ), $GLOBALS['$stmt']['$sql']);
        $GLOBALS['lastSql'] = $GLOBALS['$stmt']['$sql'];
        $mysql_error = _mysqlexecute($GLOBALS['$stmt']);
        if ($mysql_error) {
            var_dump($mysql_error, $GLOBALS['lastSql'], 'mysqlmysql');
        }

        return $mysql_error;
    }

    function ocirowcount($null = false)
    {
        return mysql_affected_rows();
    }

    function oci_fetch_assoc($null = false)
    {
        $trace = debug_backtrace();
        $vLine = file($trace[0]["file"]);
        $fLine = $vLine[$trace[0]['line'] - 1];
        preg_match("#\\$([\w_]+)#", $fLine, $match);
        $hash = '$oci_';
        $hash .= !empty($match[1]) ? $match[1] : 'default';

        if (empty($GLOBALS[$hash]) || (!empty($GLOBALS['$stmt']) && $GLOBALS['$stmt_mode'] == 'select')) {
            $GLOBALS[$hash] = $GLOBALS['$stmt'];
            $GLOBALS['$stmt'] = null;
        }
        $_row = mysql_fetch_assoc($GLOBALS[$hash]);
        $_row = !empty($_row) ? array_change_key_case($_row, CASE_UPPER) : $_row;
        if (!empty($_row['FUN_COUNT'])) {
            $_row['FUN_COUNT'] = preg_replace("/\.00$/", '', $_row['FUN_COUNT']);
        }
        return $_row;
    }

    function ocierror($null = false)
    {
        return mysql_error();
    }

    function ocilogoff($conn_db)
    {
        return _mysqlclose($conn_db);
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

    function _oci_to_char($matches)
    {
        $date = $matches[1];
        $format = trim($matches[2]);
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
        $return = "DATE_FORMAT($date, {$format_mysql}) ";
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

    /*$GLOBALS['$stmt'] = array();
    $GLOBALS['$stmt']['$sql'] = "select t.*,to_char(t.cal_date, 'dd hh24') as cal_date_f
                       from phpapm_monitor_hour t
                       where cal_date>=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')-1 and cal_date<to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')+1
                       and v1=:v1 and v2=:v2 and v3=:v3   order by fun_count desc";
    ociexecute(0);
    exit($GLOBALS['lastSql']);*/
}
/*$conn = apm_db_logon('PPS_73');
$stmt = apm_db_parse($conn, "update v_monitor set v1=4 where id=1");
$error = apm_db_execute($stmt);
var_dump(ocirowcount($stmt));
exit();*/
?>