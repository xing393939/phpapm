<?php

/**
 * @desc   按天统计点击数
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class P1D_ClickStats extends project_config
{
    function _initialize()
    {
        #每小时执行一次，每天6点执行
        if (date('i') != 30 && date('H') != 6) {
            exit();
        }

        $date = date('Y-m-d', time() - 3600 * 24);
        $conn_db = _ocilogon($this->db);
        $sql = "select v1, v2, v2_config_other from {$this->report_monitor_config} where v2_config_other like '%stats_flag%'";
        $stmt = _ociparse($conn_db, $sql);
        $_row = array();
        $error = _ociexecute($stmt);
        print_r($error);
        while (ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            print_r($_row);
            $_row['V2_CONFIG_OTHER'] = unserialize($_row['V2_CONFIG_OTHER']);
            $stats_flag = $_row['V2_CONFIG_OTHER']['stats_flag'];
            $this->_get_stats_flag_data($conn_db, $_row['V1'], $_row['V2'], $stats_flag, $date);
        }
        echo 'ok';
    }

    function _get_stats_flag_data($conn_db, $v1, $v2, $stats_flag, $date)
    {
        $v2_index = '';
        $v2_index = strstr($v2, '点击数(总量)') ? 'TOTAL_CLI' : $v2_index;
        $v2_index = strstr($v2, '点击数(独立IP)') ? 'TOTAL_IP' : $v2_index;
        $v2_index = strstr($v2, '点击数(人均)') ? 'AVG' : $v2_index;
        if ($v2_index == '') {
            echo 'v2 参数错误';
            return false;
        }
        $url = "http://partner.site.com/interface/rd/sc?ud={$date}&cli={$stats_flag}";
        $chinfo = null;
        $result = _curl($chinfo, $url);
        if ($result == '') {
            echo "接口数据有误";
            return fasle;
        }
        $result = json_decode($result, true);
        $result = $result[0];
        if ($v2_index == 'AVG' && $result['TOTAL_IP']) {
            $result['AVG'] = $result['TOTAL_CLI'] / $result['TOTAL_IP'];
        } else {
            $result['AVG'] = 0;
        }
        //更新配置表
        $sql = "update {$this->report_monitor} set fun_count=:fun_count where v1=:v1 and v2=:v2 and cal_date =to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $v1);
        _ocibindbyname($stmt, ':v2', $v2);
        _ocibindbyname($stmt, ':cal_date', $date);
        _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
        $error = _ociexecute($stmt);
        if (ocirowcount($stmt)) {
            $sql = "insert into {$this->report_monitor}(id, v1, v2, v3, v5,  fun_count, cal_date, md5, add_time ) values
                (seq_{$this->report_monitor}.nextval, :v1, :v2, :vip, :vip, :fun_count, to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss'), :md5, sysdate)";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $v1);
            _ocibindbyname($stmt, ':v2', $v2);
            _ocibindbyname($stmt, ':vip', VIP);
            _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
            _ocibindbyname($stmt, ':cal_date', $date);
            _ocibindbyname($stmt, ':md5', md5($v1 . $v2 . $date));
            $error = _ociexecute($stmt);
        }
        //更新天表数据
        $sql = "update {$this->report_monitor_date} set fun_count=:fun_count where v1=:v1 and v2=:v2 and cal_date =to_date(:cal_date,'yyyy-mm-dd')";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $v1);
        _ocibindbyname($stmt, ':v2', $v2);
        _ocibindbyname($stmt, ':cal_date', $date);
        _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
        $error = _ociexecute($stmt);
        var_dump($error);
        if (!ocirowcount($stmt)) {
            $sql = "insert into {$this->report_monitor_date}( v1, v2, fun_count, cal_date) values
                ( :v1, :v2, :fun_count, to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss'))";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $v1);
            _ocibindbyname($stmt, ':v2', $v2);
            _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
            _ocibindbyname($stmt, ':cal_date', $date);
            $error = _ociexecute($stmt);
            var_dump($error);
        }
        //更新小时表数据
        $sql = "update {$this->report_monitor_hour} set fun_count=:fun_count where v1=:v1 and v2=:v2 and cal_date =to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss')";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $v1);
        _ocibindbyname($stmt, ':v2', $v2);
        _ocibindbyname($stmt, ':cal_date', $date);
        _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
        $error = _ociexecute($stmt);
        var_dump($error);
        if (!ocirowcount($stmt)) {
            $sql = "insert into {$this->report_monitor_hour}( v1, v2, v3, fun_count, cal_date ) values
                (:v1, :v2, :v3, :fun_count, to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss'))";
            $stmt = _ociparse($conn_db, $sql);
            _ocibindbyname($stmt, ':v1', $v1);
            _ocibindbyname($stmt, ':v2', $v2);
            _ocibindbyname($stmt, ':v3', VIP);
            _ocibindbyname($stmt, ':fun_count', $result[$v2_index]);
            _ocibindbyname($stmt, ':cal_date', $date);
            $error = _ociexecute($stmt);
            var_dump($error);
        }
    }
}

?>