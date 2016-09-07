<?php

/**
 * @desc   首页
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class table
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        //清空表
        if (!empty($_GET['truncate'])) {
            $sql = "delete from " . APM_DB_PREFIX . $_GET['truncate'];
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_execute($stmt);
            header("location: {$_SERVER['HTTP_REFERER']}");
            die();
        }
        $arr = array(
            'monitor_config', 'monitor_v1', 'monitor_hour', 'monitor_date', 'monitor_queue', 'monitor',
        );
        echo '<table border="1">';
        foreach ($arr as $table) {
            $sql = "select count(*) as count from " . APM_DB_PREFIX . $table;
            $stmt = apm_db_parse($conn_db, $sql);
            apm_db_execute($stmt);
            $_row = apm_db_fetch_assoc($stmt);
            echo "<tr><td>{$table}</td><td>{$_row['COUNT']}</td><td><a href='?act=table&truncate={$table}'>truncate</a></td></tr>";
        }
        echo '</table>';
    }
}

?>