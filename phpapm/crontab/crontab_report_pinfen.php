<?php

/**
 * @desc   额外的评分
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class crontab_report_pinfen
{
    function _initialize()
    {
        #每小时执行一次
        if (date('i') != 30) {
            exit();
        }

        $conn_db = _ocilogon(APM_DB_ALIAS);
        //获取V1级别的评分要求
        $_row_infos = array();
        $sql = "select * from ".APM_DB_PREFIX."monitor_v1 t where pinfen_rule is not null ";
        $stmt_list = _ociparse($conn_db, $sql);
        $oci_error = _ociexecute($stmt_list);
        $_row = array();
        while (ocifetchinto($stmt_list, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $_row = unserialize($_row['PINFEN_RULE']);
            if ($_row['pinfen_name'] && $_row['koufen_name'] && $_row['base_num'] && $_row['just_rule'] && $_row['pinfen_step'] && $_row['rule_num'])
                $_row_infos[] = $_row;
        }
        //获取V2级别的评分要求
        $sql = "select * from ".APM_DB_PREFIX."monitor_config t where pinfen_rule is not null ";
        $stmt_list = _ociparse($conn_db, $sql);
        $oci_error = _ociexecute($stmt_list);
        $_row = array();
        while (ocifetchinto($stmt_list, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $_row = unserialize($_row['PINFEN_RULE']);
            if ($_row['pinfen_name'] && $_row['koufen_name'] && $_row['base_num'] && $_row['just_rule'] && $_row['pinfen_step'] && $_row['rule_num'])
                $_row_infos[] = $_row;
        }
        print_r($_row_infos);
        foreach ($_row_infos as $_row_info) {
            if ($_row_info['v2']) {
                if ($_row_info['just_rule'] == '>') {
                    $sql = "select   case  when nvl(t.fun_count,0) > :base_num then  - round((nvl(t.fun_count,0) - :base_num) / :pinfen_step)  else  0  end as num from ".APM_DB_PREFIX."monitor_date t where v1 = :v1  and v2 = :v2  and cal_date = trunc(sysdate) ";
                } else {
                    $sql = "select t.fun_count, case  when nvl(t.fun_count,0) < :base_num then  - round((:base_num - nvl(t.fun_count,0)) / :pinfen_step)  else  0  end as num from ".APM_DB_PREFIX."monitor_date t where v1 = :v1   and v2 = :v2  and cal_date = trunc(sysdate) ";
                }
            } else {
                if ($_row_info['just_rule'] == '>') {
                    $sql = "select  case  when sum(nvl(t.fun_count,0)) > :base_num then  - round((  sum(nvl(t.fun_count,0)) - :base_num) / :pinfen_step)  else  0  end as num from ".APM_DB_PREFIX."monitor_date t where v1 = :v1    and cal_date = trunc(sysdate) ";
                } else {
                    $sql = "select  case  when  sum(nvl(t.fun_count,0)) < :base_num then  - round((:base_num -  sum(nvl(t.fun_count,0)) ) / :pinfen_step)  else  0  end as num from ".APM_DB_PREFIX."monitor_date t where v1 = :v1     and cal_date = trunc(sysdate) ";
                }

            }
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':base_num', $_row_info['base_num']);
            _ocibindbyname($stmt, ':pinfen_step', $_row_info['pinfen_step']);
            _ocibindbyname($stmt, ':v1', $_row_info['v1']);
            if ($_row_info['v2'])
                _ocibindbyname($stmt, ':v2', $_row_info['v2']);
            $oci_error = _ociexecute($stmt);
            print_r($oci_error);

            $_row_num = array();
            ocifetchinto($stmt, $_row_num, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
            _status($_row_num['NUM'], $_row_info['pinfen_name'], $_row_info['koufen_name'], $_row_info['v1'] . "@" . $_row_info['v2']);
            print_r($_row_num);
        }
    }
}

?>