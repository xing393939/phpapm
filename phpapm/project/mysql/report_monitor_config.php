<?php

/**
 * @desc   编辑v1 v2相关参数
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_config
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);

        $sql = "select t.* from ".APM_DB_PREFIX."monitor_v1 t where v1=:v1 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = apm_db_execute($stmt);
        $this->row_config = apm_db_fetch_assoc($stmt);

        $sql = "select t.*, ifnull(as_name, v1) as_name1
                from ".APM_DB_PREFIX."monitor_v1 t
                order by as_name1";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $this->v1_config_group = $this->v1_config = $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $v1_config_group[$_row['GROUP_NAME_1']][$_row['GROUP_NAME_2']][$_row['GROUP_NAME']][] = $_row;
            if ($_REQUEST['v1'] == $_row['V1'])
                $this->v1_config_act = $_row;
        }
        $this->v1_config = $v1_config_group[$this->row_config['GROUP_NAME_1']][$this->row_config['GROUP_NAME_2']][$this->row_config['GROUP_NAME']];
        $sql = "select * from ".APM_DB_PREFIX."monitor_config where v1=:v1 order by orderby ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_REQUEST['v1']);
        $oci_error = apm_db_execute($stmt);
        $this->all = $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            $this->all[] = $_row;
        }

        include APM_PATH . "./project_tpl/report_monitor_config.html";
    }
}

?>