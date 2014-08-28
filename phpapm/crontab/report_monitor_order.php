<?php

/**
 * @desc   初始化排序
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_order extends project_config
{
    function _initialize()
    {
        #每小时执行一次
        if (date('i') != 30) {
            exit();
        }

        $conn_db = _ocilogon($this->db);
        $sql = "select * from {$this->report_monitor_config} order by v1, orderby,v2 ";
        $stmt = _ociparse($conn_db, $sql);
        $oci_error = _ociexecute($stmt);
        $this->all = $_row = array();
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $this->all[$_row['V1']][] = $_row;
        }
        //排序更新初始化
        foreach ($this->all as $k => $v) {
            foreach ($v as $kk => $vv) {
                $sql = "update  {$this->report_monitor_config}  set orderby=:orderby where v1=:v1 and v2=:v2  ";
                $stmt = _ociparse($conn_db, $sql);
                //每次都独立提交,所以这样绑定(相同变量$k,$v)没问题
                _ocibindbyname($stmt, ':v1', $vv['V1']);
                _ocibindbyname($stmt, ':v2', $vv['V2']);
                _ocibindbyname($stmt, ':orderby', intval($kk + 1));
                $oci_error = _ociexecute($stmt);
            }
        }
        echo 'ok';
    }
}

?>