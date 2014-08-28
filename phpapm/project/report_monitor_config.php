<?php

/**
 * @desc   编辑v1 v2相关参数
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_config extends project_config
{
    function _initialize()
    {
        if (empty($_COOKIE['admin_user']) || $_COOKIE['admin_user'] != md5(serialize($this->admin_user))) {
            exit();
        }

        $conn_db = _ocilogon($this->db);

        $sql = "select t.* from {$this->report_monitor_v1} t where v1=:v1 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = _ociexecute($stmt);
        $this->row_config = array();
        ocifetchinto($stmt, $this->row_config, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

        $sql = "select t.*,decode(as_name,null,v1,as_name) as_name1 from {$this->report_monitor_v1} t
        order by decode(as_name,null,v1,as_name)  ";
        $stmt = _ociparse($conn_db, $sql);
        $oci_error = _ociexecute($stmt);
        $this->v1_config_group = $this->v1_config = $_row = array();
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $v1_config_group[$_row['GROUP_NAME_1']][$_row['GROUP_NAME_2']][$_row['GROUP_NAME']][] = $_row;
            if ($_REQUEST['v1'] == $_row['V1'])
                $this->v1_config_act = $_row;
        }
        $this->v1_config = $v1_config_group[$this->row_config['GROUP_NAME_1']][$this->row_config['GROUP_NAME_2']][$this->row_config['GROUP_NAME']];
        $sql = "select * from {$this->report_monitor_config} where v1=:v1 order by orderby ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = _ociexecute($stmt);
        $this->all = $_row = array();
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            $this->all[] = $_row;
        }

        include PHPAPM_PATH . "./project_tpl/report_monitor_config.html";
    }
}

?>