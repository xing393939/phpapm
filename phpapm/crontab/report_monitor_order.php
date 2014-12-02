<?php

/**
 * @desc   初始化排序
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_order
{
    function _initialize()
    {
        #每小时执行一次
        if (date('i') != 30) {
            exit();
        }

        $conn_db = apm_db_logon(APM_DB_ALIAS);
        $sql = "select * from ".APM_DB_PREFIX."monitor_config order by v1, orderby,v2 ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $this->all = $_row = array();
        while ($_row = oci_fetch_assoc($stmt)) {
            $this->all[$_row['V1']][] = $_row;
        }
        //排序更新初始化
        foreach ($this->all as $k => $v) {
            foreach ($v as $kk => $vv) {
                $sql = "update  ".APM_DB_PREFIX."monitor_config  set orderby=:orderby where v1=:v1 and v2=:v2  ";
                $stmt = apm_db_parse($conn_db, $sql);
                //每次都独立提交,所以这样绑定(相同变量$k,$v)没问题
                apm_db_bind_by_name($stmt, ':v1', $vv['V1']);
                apm_db_bind_by_name($stmt, ':v2', $vv['V2']);
                apm_db_bind_by_name($stmt, ':orderby', intval($kk + 1));
                $oci_error = apm_db_execute($stmt);
            }
        }
        echo 'ok';
    }
}

?>