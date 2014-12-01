<?php

/**
 * @desc   三个数字
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class nbi_num
{
    function _initialize()
    {
        header("Expires: " . date('r', strtotime('+ 30 min')));
        $conn_db = _ocilogon(APM_DB_ALIAS);

        //剩下没解决的
        $sql = "select sum(t.fun_count) c from ".APM_DB_PREFIX."monitor_date t where t.v1 like '%(项目满意分)'  and t.cal_date = trunc(sysdate)";
        $stmt = _ociparse($conn_db, $sql);
        _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        $_row['C'] = sprintf('%02d', $_row['C']);

        $sql = "select sum(t.fun_count) c from ".APM_DB_PREFIX."monitor_date t where t.v1 like '%(项目文档满意分)'  and t.cal_date = trunc(sysdate)";
        $stmt = _ociparse($conn_db, $sql);
        _ociexecute($stmt);
        $_row2 = array();
        ocifetchinto($stmt, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        $_row2['C'] = sprintf('%02d', $_row2['C']);

        if ($_row['C'])
            echo "\$('#nbi_num_xm').html('文档:{$_row2['C']}分');\$('#nbi_num_1').html('技术:{$_row['C']}分');";

        //显示其他定制的分数
        $sql = "select *  from ".APM_DB_PREFIX."monitor_v1 t where t.PINFEN_RULE_NAME is not null ";
        $stmt_list = _ociparse($conn_db, $sql);
        _ociexecute($stmt_list);
        $_row = $_row2 = array();
        $ki = 1;
        while (ocifetchinto($stmt_list, $_row2, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $_row = unserialize($_row2['PINFEN_RULE']);
            if ($_row2['PINFEN_RULE_NAME'] && $_row['pinfen_name'] && $_row['koufen_name'] && $_row['base_num'] && $_row['just_rule'] && $_row['pinfen_step'] && $_row['rule_num']) {
                $ki++;
                $sql = "select sum(t.fun_count) c from ".APM_DB_PREFIX."monitor_date t where t.v1 =:v1  and t.cal_date = trunc(sysdate)";
                $stmt = _ociparse($conn_db, $sql);
                ocibindbyname($stmt, ':v1', $_row['pinfen_name']);
                _ociexecute($stmt);
                $_row3 = array();
                ocifetchinto($stmt, $_row3, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
                $_row3['C'] = sprintf('%02d', $_row3['C']);
                echo "try{\$('#nbi_num_{$ki}').html('{$_row2['PINFEN_RULE_NAME']}:{$_row3['C']}分');}catch(e){}";
            }
        }
        //总pv量
        $all_num = 0;
        $sql = "select *  from ".APM_DB_PREFIX."monitor_config t where v1 like '%(WEB日志分析)' and v2<>'汇总'";
        $stmt = _ociparse($conn_db, $sql);
        _ociexecute($stmt);
        $this->host = $_row = array();
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            $this->host[$_row['V2']] = $_row;
        }
        $s1 = date('Y-m-d');
        $sql = "select t.*,to_char(cal_date, 'yyyy-mm-dd')  CAL_DATE_F  from
                ".APM_DB_PREFIX."monitor_date t where
                cal_date>=to_date(:s1,'yyyy-mm-dd') and v1 like '%(WEB日志分析)' and v2<>'汇总' ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':s1', $s1);
        $oci_error = _ociexecute($stmt);
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            if (!$this->host[$_row['V2']]['V2_CONFIG_OTHER']['NO_COUNT']) {
                $all_num += $_row['FUN_COUNT'];
            }
        }
        if ($all_num > 10000 * 10000) {
            $all_num = round($all_num / 10000 / 10000, 1) . '亿';
        } elseif ($all_num > 10000) {
            $all_num = round($all_num / 10000, 1) . '万';
        }
        echo "\$('#nbi_num_pv').html('pv:{$all_num}');";
    }
}

?>