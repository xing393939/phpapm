<?php

/**
 * @desc   检查有问题的sql
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class oci_explain
{
    function _initialize()
    {
        //暂时关闭
        exit();

        #每小时执行一次
        if (is_writable('/dev/shm/')) {
            $change = false;
            $basefile = '/dev/shm/sql_' . APM_HOST;
            $sqls = unserialize(file_get_contents($basefile));
            if (empty($sqls)) {
                echo "empty sqls\n";
                $change = true;
            }
            echo "sql_count:" . count($sqls) . "\n";
            foreach ($sqls as $k => $v) {
                if ($v['type'] <> 'oci' || $v['paser_txt'] || $v['vhost'] <> APM_HOST)
                    continue;
                if (strpos($v['sql'], 'alter session') !== false)
                    continue;

                $conn_db = apm_db_logon($v['db']);
                $sql = "EXPLAIN PLAN SET STATEMENT_ID='pps' FOR " . $v['sql'];
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_execute($stmt);

                $sql = "SELECT * FROM TABLE(DBMS_XPLAN.DISPLAY('PLAN_TABLE','pps','BASIC'))";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_execute($stmt);
                $_row = array();
                $row_text = NULL;
                while ($_row = apm_db_fetch_assoc($stmt)) {
                    echo "change:explain\n";
                    $change = true;
                    $row_text .= "\n" . $_row['PLAN_TABLE_OUTPUT'];
                }
                apm_db_logoff($conn_db);
                $sqls[$k]['paser_txt'] = $row_text;
                $sql_type = NULL;
                $vv = _sql_table_txt($v['sql'], $sql_type);
                //
                $type = NULL;
                if (strpos($v['act'], 'project') !== false)
                    $type = "(项目)";
                if (strpos($row_text, 'TABLE ACCESS FULL') !== false)
                    _status(1, APM_HOST . "(BUG错误)", "问题SQL", "全表扫描{$type}", "{$v['db']}.{$vv}@{$v['act']}", $v['sql'] . "\n" . $row_text);
                if (strpos($row_text, ' JOIN ') !== false)
                    _status(1, APM_HOST . "(BUG错误)", "问题SQL", "多表查询{$type}", "{$v['db']}.{$vv}@{$v['act']}", $v['sql'] . "\n" . $row_text);
            }
            foreach ($sqls as $k => $v) {
                if (time() > strtotime($v['add_time']) + 3600) {
                    echo "change:time\n";
                    $change = true;
                    unset($sqls[$k]);
                }
            }
            if ($change) {
                echo "write file.\n";
                file_put_contents($basefile, serialize($sqls));
            }
            die("OK\n");
        }
    }
}

?>