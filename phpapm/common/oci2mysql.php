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
if (!class_exists('Memcache')) {
    class Memcache {
        function connect() {
            return false;
        }

        function getStats() {
            return false;
        }
    }
}
if (!function_exists('msg_get_queue')) {
    function msg_get_queue() {}
}
if (!function_exists('ocinlogon')) {
    function ocinlogon($user_name, $password, $tns)
    {
        $config = new project_config();
        $mysql_db = _mysqllogon($config->db);
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

    function ocifetchinto($null, &$_row, $type)
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

    function ociexecute($null, $mode = false)
    {
        _mysqlbindbyname($GLOBALS['$stmt'], ':oci_unique', round(lcg_value() * 100000000));
        preg_match('/^[\s]*([\w]+)/', $GLOBALS['$stmt']['$sql'], $matchs);
        $GLOBALS['$stmt_mode'] = empty($matchs[1]) ? 'default' : strtolower($matchs[1]);
        $GLOBALS['$stmt']['$sql'] = preg_replace(array(
            '/[\w_]+_doc_list[\s\S]+where l\.list_id=d\.list_id\(\+\)/', //������

            "/\(\+\)[\s]+/", //�����ӣ�(+)
            '/decode\(([^,]+),([^,]+),([^,]+),([^,]+)\)/', //oracle��decode����
            '/[\w\_]+\.nextval/', //����id
            '/to_char\(([^,\'"]+),([^\)]+)\)/es', //to_char(d.add_time, 'yyyy-mm-dd hh24:mi:ss')
            '/to_date\(([^,\)]+),([^\)]+)\)([\s\d\+\-\/]*)/es', //to_date('2013-11-08 17:00:00','yyyy-mm-dd hh24:mi:ss')+1/24
            '/nvl\(([^,]+),([^\)]+)\)/', //nvl(v6,0)
            '/^[\s]*select(.*)\.currval(.*)dual[\s]*$/', //select SEQ_{$this->report_doc}.currval doc_id from dual
            '/^[\s]*(insert[^\(]+)([\s]+t[\s]+)\(/', //insert into tuijian_doc_list t (...)
            '/(regexp_substr|regexp_replace)\(([^,\)]+),.*\'\)/U', //regexp_substr(V2, '[^_]+$')
            '/trunc\(([^,\)]+)(\)|,([^\)]+)\))/es', //trunc(t.cal_date, 'hh24')��������sysdate֮ǰ�滻
            '/(sysdate|SYSDATE)([ \d\+\-\/]*)/es', //sysdate-10/24
            '/([\s]*)LOAD([\s]*)/', //`LOAD`
            '/^[\s]*alter[\s]*session.*$/', //alter session set nls_date_format=...
            '/^([\s]*update[\s]+.*[\s]+(set|SET)[\s]+)/', //ģ��ocirowcount
            '/=[\s]+(null|NULL)[\s]+/', //=null���⣬http://www.tiandone.com/td/747.html
        ), array(
            'tuijian_doc_list l left join tuijian_doc_detail d on l.list_id=d.list_id where 1',

            ' ',
            ' case when \\1 = \\2 then \\3  else \\4 End ',
            'NULL',
            '_oci_to_char("\\1", "\\2")',
            '_oci_to_date("\\1", "\\2", "\\3")',
            'ifnull(\\1,\\2)',
            'SELECT last_insert_id()',
            '\\1 (',
            ' \\2 ',
            '_oci_trunc("\\1", "\\3")',
            '_oci_sysdate("\\2")',
            '\\1`LOAD`\\2',
            'select 1',
            '\\1 oci_unique=:oci_unique, ',
            ' is NULL '
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

    function _oci_sysdate($delay = '')
    {
        $delay = trim($delay);
        if (empty($delay)) {
            $return = "NOW() ";
        } else {
            if (strpos($delay, '/') !== false) {
                $delay = preg_replace('/([\d]+)[\s\/]+([\d]+)/es', '_oci_get_hour("\\1", "\\2")', $delay);
                $return = "NOW() + INTERVAL $delay HOUR ";
            } else {
                $return = "NOW() + INTERVAL $delay DAY ";
            }
        }
        return $return;
    }

    function _oci_to_date($date, $format, $delay = '')
    {
        $delay = trim($delay);
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
                $delay = preg_replace('/([\d]+)[\s\/]+([\d]+)/es', '_oci_get_hour("\\1", "\\2")', $delay);
                $return = "DATE_FORMAT($date, {$format_mysql}) + INTERVAL $delay HOUR ";
            } else {
                $return = "DATE_FORMAT($date, {$format_mysql}) + INTERVAL $delay DAY ";
            }
        }
        return $return;
    }

    function _oci_to_char($date, $format)
    {
        $format = trim($format);
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

    function _oci_get_hour($a, $b)
    {
        return $a / $b * 24;
    }

    function _oci_trunc($date, $format = '')
    {
        $format = trim($format);
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
                       from tuijian_monitor_hour t
                       where cal_date>=to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')-1 and cal_date<to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')+1
                       and v1=:v1 and v2=:v2 and v3=:v3   order by fun_count desc";
    ociexecute(0);
    exit($GLOBALS['lastSql']);*/
}
/*$conn = _ocilogon('PPS_73');
$stmt = _ociparse($conn, "update v_monitor set v1=4 where id=1");
$error = _ociexecute($stmt);
var_dump(ocirowcount($stmt));
exit();*/
?>