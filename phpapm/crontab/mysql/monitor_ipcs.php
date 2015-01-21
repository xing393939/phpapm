<?php

/**
 * @desc   处理页面访问队列
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class monitor_ipcs
{
    function _initialize()
    {
        $xxi = 0;
        $this->_ipcs();
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        if (!$conn_db)
            exit('no db');
        $get_included_files = basename(array_shift(get_included_files()));

        $tt1 = microtime(true);
        echo "<pre> 准备压缩数据:\n";
        $monitor_count = $files = $monitor = $monitor_min = array();
        $IPCS = explode('|', APM_IPCS);
        shuffle($IPCS);
        $ic = 0;
        print_r($IPCS);
        $config_data = array();
        foreach ($IPCS as $ipcs) {
            $seg = msg_get_queue($ipcs, 0600);
            $msgtype = 1;
            $msg_array = array();
            //读取队列数据
            while (msg_receive($seg, $msgtype, $msgtype, 1024 * 1024 * 5, $msg_array, true, MSG_IPC_NOWAIT)) {
                if ($msg_array['v5'] == null)
                    $msg_array['v5'] = APM_VIP;
                //专门对付SQL不规范的写法
                if (strpos($msg_array['v1'], 'SQL') !== false) {
                    $out = array();
                    preg_match('# in(\s+)?\(#is', $msg_array['v4'], $out);
                    if ($out)
                        $msg_array['v4'] = substr($msg_array['v4'], 0, strpos($msg_array['v4'], ' in')) . ' in....';
                }
                if (strpos($msg_array['v1'], 'SQL') !== false) {
                    preg_match('# in(\s+)?\(#is', $msg_array['v3'], $out);
                    if ($out)
                        $msg_array['v3'] = substr($msg_array['v3'], 0, strpos($msg_array['v3'], ' in')) . ' in....';
                }

                foreach ((array)$msg_array['includes'] as $file)
                    $files[$msg_array['vhost']][$file] = $file;
                //查看命中了哪些监控
                $config_data[$msg_array['v1']][$msg_array['v2']]++;
                //日志数据,不会被删除
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['uptype'] = $msg_array['uptype'];
                if ($msg_array['uptype'] == 'replace')
                    $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] = $msg_array['num'];
                else
                    $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['count'] += $msg_array['num'];
                //最大耗时
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['diff_time'], abs($msg_array['diff_time']));
                //总耗时
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['total_diff_time'] += abs($msg_array['diff_time']);

                //内存单次最大消耗
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_max'], abs($msg_array['memory']));
                //内存消耗.总
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['memory_total'] += abs($msg_array['memory']);

                // 用户消耗CPU,单次最大
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_max'], abs($msg_array['user_cpu']));
                //用户消耗CPU,总
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_user_time_total'] += abs($msg_array['user_cpu']);

                //系统消耗CPU,单次最大
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_max'] = max($monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_max'], abs($msg_array['sys_cpu']));
                //系统消耗CPU,总
                $monitor[date('Y-m-d H', strtotime($msg_array['time']))][$msg_array['v1']][$msg_array['v2']][$msg_array['v3']][$msg_array['v4']][$msg_array['v5']]['cpu_sys_time_total'] += abs($msg_array['sys_cpu']);

                $monitor_count[md5(date('Y-m-d H', strtotime($msg_array['time'])) . $msg_array['v1'] . $msg_array['v2'] . $msg_array['v3'] . $msg_array['v4'] . $msg_array['v5'])] = 1;

                if ($ic++ > 10 * 10000)
                    break;
            }
        }
        $diff_time = sprintf('%.5f', microtime(true) - $tt1);
        echo "\n从{$ic}个压缩到" . count($monitor_count) . "(耗时:{$diff_time})\n";
        echo "命中的类型:\n";
        print_r($config_data);
        echo "\n\n";
        $conn_db = apm_db_logon(APM_DB_ALIAS);

        foreach ($monitor as $time => $vtype) {
            foreach ($vtype as $type => $vhost) {
                foreach ($vhost as $host => $vact) {
                    foreach ($vact as $act => $vkey) {
                        foreach ($vkey as $key => $vhostip) {
                            foreach ($vhostip as $hostip => $v) {
                                if (!$host)
                                    $host = 'null';
                                //截取4000字节
                                if (strlen($key) > 4000)
                                    $key = substr($key, 0, 4000);
                                if (strlen($hostip) > 200)
                                    $hostip = substr($hostip, 0, 200);
                                if (strlen($act) > 200)
                                    $act = substr($act, 0, 200);
                                //去掉回车
                                $act = strtr($act, array(
                                    "\n" => null,
                                    "\r" => null
                                ));
                                if ($v['uptype'] == 'replace')
                                    //memory_max=,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total
                                    $sql = "update ".APM_DB_PREFIX."monitor set fun_count=:fun_count,oci_unique=".round(lcg_value() * 100000000).",v6=:v6, total_diff_time=:total_diff_time,
									memory_max=:memory_max, memory_total=:memory_total, cpu_user_time_max=:cpu_user_time_max, cpu_user_time_total=:cpu_user_time_total, cpu_sys_time_max=:cpu_sys_time_max, cpu_sys_time_total=:cpu_sys_time_total where md5=:md5 ";
                                else
                                    $sql = "update ".APM_DB_PREFIX."monitor set fun_count=fun_count+:fun_count,oci_unique=".round(lcg_value() * 100000000).",v6=:v6, total_diff_time=:total_diff_time,
								    memory_max=:memory_max, memory_total=:memory_total, cpu_user_time_max=:cpu_user_time_max, cpu_user_time_total=:cpu_user_time_total, cpu_sys_time_max=:cpu_sys_time_max, cpu_sys_time_total=:cpu_sys_time_total where md5=:md5 ";
                                $stmt = apm_db_parse($conn_db, $sql);
                                apm_db_bind_by_name($stmt, ':md5', md5($time . $type . $host . $act . $key . $hostip));
                                apm_db_bind_by_name($stmt, ':fun_count', $v['count']);
                                apm_db_bind_by_name($stmt, ':v6', abs($v['diff_time']));
                                apm_db_bind_by_name($stmt, ':total_diff_time', $v['total_diff_time']);
                                apm_db_bind_by_name($stmt, ':memory_max', $v['memory_max']);
                                apm_db_bind_by_name($stmt, ':memory_total', $v['memory_total']);
                                apm_db_bind_by_name($stmt, ':cpu_user_time_max', $v['cpu_user_time_max']);
                                apm_db_bind_by_name($stmt, ':cpu_user_time_total', $v['cpu_user_time_total']);
                                apm_db_bind_by_name($stmt, ':cpu_sys_time_max', $v['cpu_sys_time_max']);
                                apm_db_bind_by_name($stmt, ':cpu_sys_time_total', $v['cpu_sys_time_total']);
                                $oci_error = apm_db_execute($stmt);
                                print_r($oci_error);
                                if ($oci_error)
                                    _status(1, APM_HOST . "(BUG错误)", 'SQL错误', "{$get_included_files}/{$_GET['act']}", var_export(array(
                                            'cal_date' => $time,
                                            'v1' => $type,
                                            'v2' => $host,
                                            'v3' => $act,
                                            'v4' => $key,
                                            'v5' => $hostip,
                                            'fun_count' => $v['count'],
                                            'v6' => abs($v['diff_time']),
                                            'total_diff_time' => $v['total_diff_time'],
                                            'memory_max' => $v['memory_max'],
                                            'memory_total' => $v['memory_total'],
                                            'cpu_user_time_max' => $v['cpu_user_time_max'],
                                            'cpu_user_time_total' => $v['cpu_user_time_total'],
                                            'cpu_sys_time_max' => $v['cpu_sys_time_max'],
                                            'cpu_sys_time_total' => $v['cpu_sys_time_total']
                                        ), true) . "|" . var_export($oci_error, true), APM_VIP);
                                else
                                    _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $type, 'monitor(update)', APM_VIP);
                                $_row_count = apm_db_row_count($stmt);
                                if (!$_row_count) {
                                    $xxi++;
                                    echo "{$xxi}:[$time . $type . $host . $act . $key . $hostip]\n";
                                    $sql = "insert into ".APM_DB_PREFIX."monitor (id,v1,v2,v3,v4,v5,fun_count,cal_date,v6,total_diff_time,memory_max,memory_total, cpu_user_time_max,cpu_user_time_total,cpu_sys_time_max,cpu_sys_time_total,md5)
                                    values(NULL,:v1,:v2,:v3,:v4,:v5,:fun_count,to_date(:cal_date,'yyyy-mm-dd hh24:mi:ss'),:v6,:total_diff_time,:memory_max,:memory_total, :cpu_user_time_max,:cpu_user_time_total,:cpu_sys_time_max,:cpu_sys_time_total,:md5)";
                                    $stmt = apm_db_parse($conn_db, $sql);
                                    apm_db_bind_by_name($stmt, ':md5', md5($time . $type . $host . $act . $key . $hostip));
                                    apm_db_bind_by_name($stmt, ':cal_date', $time);
                                    apm_db_bind_by_name($stmt, ':v1', $type);
                                    apm_db_bind_by_name($stmt, ':v2', $host);
                                    apm_db_bind_by_name($stmt, ':v3', $act);
                                    apm_db_bind_by_name($stmt, ':v4', $key);
                                    apm_db_bind_by_name($stmt, ':v5', $hostip);
                                    apm_db_bind_by_name($stmt, ':fun_count', $v['count']);
                                    apm_db_bind_by_name($stmt, ':v6', abs($v['diff_time']));
                                    apm_db_bind_by_name($stmt, ':total_diff_time', $v['total_diff_time']);
                                    apm_db_bind_by_name($stmt, ':memory_max', $v['memory_max']);
                                    apm_db_bind_by_name($stmt, ':memory_total', $v['memory_total']);
                                    apm_db_bind_by_name($stmt, ':cpu_user_time_max', $v['cpu_user_time_max']);
                                    apm_db_bind_by_name($stmt, ':cpu_user_time_total', $v['cpu_user_time_total']);
                                    apm_db_bind_by_name($stmt, ':cpu_sys_time_max', $v['cpu_sys_time_max']);
                                    apm_db_bind_by_name($stmt, ':cpu_sys_time_total', $v['cpu_sys_time_total']);
                                    $oci_error = apm_db_execute($stmt);
                                    print_r($oci_error);
                                    if ($oci_error)
                                        _status(1, APM_HOST . "(BUG错误)", 'SQL错误', "{$get_included_files}/{$_GET['act']}", var_export(array(
                                                'cal_date' => $time,
                                                'time' => date('Y-m-d H:i:s'),
                                                'md5' => md5($time . $type . $host . $act . $key . $hostip),
                                                'v1' => $type,
                                                'v2' => $host,
                                                'v3' => $act,
                                                'v4' => $key,
                                                'v5' => $hostip,
                                                'fun_count' => $v['count'],
                                                'v6' => abs($v['diff_time']),
                                                'memory_max' => $v['memory_max'],
                                                'memory_total' => $v['memory_total'],
                                                'cpu_user_time_max' => $v['cpu_user_time_max'],
                                                'cpu_user_time_total' => $v['cpu_user_time_total'],
                                                'cpu_sys_time_max' => $v['cpu_sys_time_max'],
                                                'cpu_sys_time_total' => $v['cpu_sys_time_total']
                                            ), true) . "|" . var_export($oci_error, true), APM_VIP);
                                    else
                                        _status(1, APM_HOST . "(PHPAPM)", "统计消耗", $type, 'monitor', APM_VIP);
                                }
                            }
                        }
                    }
                }
            }
        }
        apm_db_logoff($conn_db);
        if (!is_writable('/dev/shm'))
            exit('no writable shm');
        if (!file_exists($dir = '/dev/shm/' . APM_HOST . '/'))
            mkdir($dir);
        if (!file_exists($dir1 = '/dev/shm/xss_' . APM_HOST . '/'))
            mkdir($dir1);

        include APM_PATH . "./include/project.func.php";
        $project_function = new project_function;
        $check_files = array();
        if (date('H') > 8 && date('H') <= 19)
            $time_area = '白天';
        else
            $time_area = '晚上';
        //文件记录
        foreach ($files as $module_name => $_files) {
            foreach (array_unique($_files) as $file) {
                if (!is_file($file))
                    continue;
                //文件修改时间
                $new_file = $dir . md5($file);
                //
                if (is_file($new_file) && (filectime($new_file) < filectime($file))) {
                    echo "代码改动\n";
                    _status(1, $module_name . "(代码改动)", "文件改动-{$time_area}", $file, "", APM_VIP, 0);
                    touch($new_file, filectime($file));
                } elseif (!is_file($new_file)) {
                    _status(1, $module_name . "(代码改动)", "新增文件-{$time_area}", $file, "", APM_VIP, 0);
                    touch($new_file, filectime($file));
                }
                //安全校验
                $new_file = $dir1 . md5($file);
                if (is_file($new_file) && (filectime($new_file) < filectime($file)))
                    $check_files[$file] = $module_name;
                elseif (!is_file($new_file))
                    $check_files[$file] = $module_name;
            }
        }
        foreach ($check_files as $file => $module_name) {
            $token = token_get_all(file_get_contents($file));
            //代码所有人统计
            if (strpos($file, '/phpCas/') === false || strpos($file, '/PHPMailer/') === false) {
                $project_function->_function_author($token, $module_name, $file);
                $project_function->_function_count($token, $module_name, $file);
                $project_function->_xss($token, $module_name, $file);
                $project_function->_sign($token, $module_name, $file);
                $project_function->_disable_function($token, $module_name, $file);
            }
            touch($dir1 . md5($file), filectime($file));
        }
        die("\n" . date("Y-m-d H:i:s") . ',file:' . __FILE__ . ',line:' . __LINE__ . "\n");
    }

    function _ipcs()
   	{
   		//监控当前系统的队列个数
   		$out = NULL;
   		exec('ipcs', $out);
   		foreach ($out as $k => $v) {
   			if (strpos($v, '0x') === false) {
   				unset($out[$k]);
   				continue;
   			}
   			$out[$k] = array_diff(explode(" ", $v), array(
   				""
   			));
   		}
   		$_num = $_name = null;
        $ipcs_out = array();
   		foreach ($out as $k => $v) {
   			if (count($v) != 6)
   				continue;
   			$i = 0;
   			foreach ($v as $vv) {
   				$i++;
   				if ($i == 1)
   					$_name = (string)$vv;
   				if ($i == 5)
   					$_num = $vv / 1048576;
   			}
   			$ipcs_out[] = array(
   				'num'  => $_num,
   				'name' => $_name
   			);
   		}
   		foreach ($ipcs_out as $k => $v)
   			_status($v['num'], APM_HOST . '(WEB日志分析)', "队列", $v['name'], date('Y-m-d H:i:s'), APM_VIP);
   	}
}

?>