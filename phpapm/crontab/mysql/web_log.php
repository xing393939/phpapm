<?php

/**
 * @desc   统计web日志
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class web_log
{
    function _initialize()
    {
        #每小时执行一次
        if (date('i') != 30) {
            exit();
        }

        exec("rm -f  /home/webid/logs/" . date('Y_m_d', strtotime('-7 day')) . "*.log");
        $qps_stats = 0;
        if (APM_LOG_PATH) {
            //web日志
            if ($_GET['gz']) {
                for ($i = strtotime(date('Y-m-d H:00:00', strtotime('-1 hours'))); $i < strtotime(date('Y-m-d H:00:00', strtotime('-0 hours'))); $i += 600) {
                    $gz_dir = $_GET['gz'] . '/' . date('Y-m-d', $i) . '/' . APM_HOST;
                    $logfilename = date('Y_m_d_H_i', $i) . APM_HOST . '_access.log';
                    $i_linux = substr(strval($i), 0, -1);
                    $tt1 = microtime(true);
                    echo "tar zxvf {$gz_dir}*{$i_linux}* -O >/home/webid/logs/{$logfilename}\n";
                    exec("tar zxvf {$gz_dir}*{$i_linux}* -O >/home/webid/logs/{$logfilename}");
                    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
                    _status(1, APM_HOST . '(BUG错误)', '文件读写', APM_VIP . APM_PROJECT, "{$gz_dir}*{$i_linux}*@file:" . APM_URI . "/{$_GET['act']}", APM_VIP, $diff_time);
                    $qps_stats = max($qps_stats, $this->_web_log("/home/webid/logs/{$logfilename}"));
                }
            } else {
                $logfilename = APM_HOST . date('_Y_m_d_H', strtotime('-1 hours')) . '_access.log';
                copy(APM_LOG_PATH . $logfilename, "/home/webid/logs/{$logfilename}");
                $qps_stats = $this->_web_log("/home/webid/logs/{$logfilename}");
            }
            _status($qps_stats, APM_HOST . '(WEB日志分析)', 'QPS', 'QPS', null, APM_VIP, 0, NULL, strtotime('-1 hours'));
        }

        //js脚本错误记录.
        if (APM_ERR_LOG_PATH && method_exists($this, '_err_weblog')) {
            //web日志
            if ($_GET['gz']) {
                for ($i = strtotime(date('Y-m-d H:00:00', strtotime('-1 hours'))); $i < strtotime(date('Y-m-d H:00:00', strtotime('-0 hours'))); $i += 600) {
                    $gz_dir = $_GET['gz'] . '/' . date('Y-m-d', $i) . '/' . APM_ERR_LOG_PATH;
                    $logfilename = date('Y_m_d_H_i', $i) . APM_ERR_LOG_PATH . '_access.log';
                    $i_linux = substr(strval($i), 0, -1);
                    $tt1 = microtime(true);
                    echo "tar zxvf {$gz_dir}*{$i_linux}* -O >/home/webid/logs/{$logfilename}\n";
                    exec("tar zxvf {$gz_dir}*{$i_linux}* -O >/home/webid/logs/{$logfilename}");
                    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
                    _status(1, APM_HOST . '(BUG错误)', '文件读写', APM_VIP . APM_PROJECT, "{$gz_dir}*{$i_linux}*@file:" . APM_URI . "/{$_GET['act']}", APM_VIP, $diff_time);
                    $this->_err_weblog("/home/webid/logs/{$logfilename}");
                }
            } else {
                $logfilename = date('Y_m_d_H', strtotime('-1 hours')) . '_access.log';
                copy(APM_ERR_LOG_PATH . $logfilename, "/home/webid/logs/{$logfilename}");
                $this->_err_weblog(APM_ERR_LOG_PATH . $logfilename);
            }
        }

        //php错误日志
        $arr = file('/home/webid/logs/php_error.log');
        foreach ($arr as $k => $v) {
            if (trim($v) !== '' || $v != 0) {
                if (strpos($v, 'PHP Warning')) {
                    _status(1, APM_HOST . '(BUG错误)', 'PHP错误', 'PHP错误日志', NULL, APM_VIP);
                } else {
                    $v = substr($v, 22);
                    _status(1, APM_HOST . '(BUG错误)', 'PHP错误', 'PHP错误日志', $v, APM_VIP);
                }
                if (strpos($v, 'Fatal error'))
                    _status(1, APM_HOST . '(BUG错误)', '致命错误', 'PHP错误日志', NULL, APM_VIP);
            }
        }
    }

    function _web_log($log_file)
    {
        echo $log_file, "\n";
        $total = exec("cat " . $log_file . " | wc -l");
        echo "\n line:" . $total;
        $status_arr = array(200, 206, 301, 302, 304, 400, 403, 404, 405, 408, 413, 417, 499, 500, 501, 502, 504);
        $count_arr = array();
        $count_sum = 0;
        foreach ($status_arr as $key => $status_code) {
            $cmd = "cat " . $log_file . " | grep '\" " . $status_code . " ' | wc -l";
            echo $cmd . "\n";
            $count_arr[$status_code] = exec($cmd);
            $count_sum += $count_arr[$status_code];
            $error_ips_str = "";
            if ($status_code >= 500) {
                $ip_cmd = "cat   $log_file | grep '\" " . $status_code . " ' | awk '{print $7}'   |   awk 'BEGIN { FS=\"?\" } {print $1}'   | uniq";
                $error_ips = array();
                exec($ip_cmd, $error_ips);

                $error_ips = array_count_values($error_ips);
                foreach ($error_ips as $ip => $count) {
                    # code...
                    $error_ips_str .= "{$ip}({$count})\n";
                }
            }
            _status($count_arr[$status_code], APM_HOST . '(WEB日志分析)', $status_code, $status_code, $error_ips_str, APM_VIP, 0, NULL, strtotime('-1 hours'));
        }
        _status(($total - $count_sum), APM_HOST . '(WEB日志分析)', "其它", "其它", $error_ips_str, APM_VIP, 0, NULL, strtotime('-1 hours'));

        //ip 统计
        $cmd_ip_stats = "cat {$log_file} |  awk '($9~/20|30/){print}' | awk '{print $1}' | sort -n| uniq -c | sort -r";
        $ip_stats = array();
        exec($cmd_ip_stats, $ip_stats);
        _status(count($ip_stats), APM_HOST . '(WEB日志分析)', '独立ip', '独立ip', null, APM_VIP, 0, NULL, strtotime('-1 hours'));
        for ($i = 0; $i < 10; $i++) {
            if (isset($ip_stats[$i])) {
                list($count, $ip) = explode(" ", trim($ip_stats[$i]), 2);
                //小于100的ip不进行统计
                if ($count < 100) {
                    break;
                }
                _status($count, APM_HOST . '(WEB日志分析)', 'ip统计前十', $ip, null, APM_VIP, 0, NULL, strtotime('-1 hours'));
            }
        }
        //QPS统计
        $qps_stats = exec("cat {$log_file} | awk '{print $4}' | sort |uniq -c | sort -n -r | head -n1 | awk '{print $1}'");
        //日志明文处理完毕之后,压缩回去
        $base_name_log_file = basename($log_file);
        chdir("/home/webid/logs/");
        exec("tar -czvf {$base_name_log_file}.tar.gz {$base_name_log_file}");
        //日志只保留zip压缩文件
        if (is_file("{$base_name_log_file}.tar.gz"))
            unlink($base_name_log_file);
        return $qps_stats;
    }
}

?>