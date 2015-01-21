<?php

/**
 * @desc   记录服务器当前负载
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class sysload
{
    function _initialize()
    {
        if (strpos(PHP_OS, 'WIN') !== false) {
            exit();
        }
        $replace_date = date('H') . ':' . floor(date('i') / 10) * 10;
        exec("uptime", $uptime); //获取系统负载
        exec('cat /proc/sys/kernel/hostname', $hostname); //获取服务器
        print_r($hostname);
        $_POST['hostname'] = $hostname[0];

        //系统负载
        preg_match('#load average: ([0-9|.]+),#iUs', $uptime[0], $out);
        print_r($out);
        _status(round($out[1], 2), APM_HOST . '(WEB日志分析)', 'Load', $_POST['hostname'], $replace_date, APM_VIP, 0, 'replace');

        //IO压力
        $io = NULL;
        $io = exec("top -b -n 1 | awk 'NR==3 {print $6}' | awk -F '%' '{print $1}'"); //mem
        echo "IO\n";
        print_r($io);
        _status($io, APM_HOST . '(WEB日志分析)', 'IO', $_POST['hostname'], date('Y-m-d H'), APM_VIP, 0, 'replace');

        //运行时间
        preg_match('#up ([0-9]+) day#iUs', $uptime[0], $out);
        echo "运行时间\n";
        print_r($out);
        _status($out[1], APM_HOST . '(WEB日志分析)', '运行天数', $_POST['hostname'], date('Y-m-d H'), APM_VIP, 0, 'replace');

        //监控内存剩余
        exec("cat /proc/meminfo | head -2 | tail -1", $mem); //mem
        print_r($mem);
        preg_match('#.*([0-9]+) KB#iUs', $mem[0], $out);
        _status(round($out[1] / 1024, 2), APM_HOST . '(WEB日志分析)', 'Mem内存剩余', $_POST['hostname'], $replace_date, APM_VIP, 0, 'replace');

        //CPU监控
        exec("top -b -n 1 | awk 'NR==3 {print $5}'", $cpu); //cpuinfo
        print_r($cpu);
        $_POST['cpu'] = str_replace('%id,', '', $cpu[0]);
        $_POST['cpu'] = 100 - $_POST['cpu'];
        _status($_POST['cpu'], APM_HOST . '(WEB日志分析)', 'CPU', $_POST['hostname'], $_POST['cpu'] . '%-' . $replace_date, APM_VIP, 0, 'replace');

        //磁盘
        exec("df -h | awk 'NR>1{print $6,$5}'", $disk); //disk
        print_r($disk);
        foreach ($disk as $row) {
            if (strlen(trim($row)) <= 0) {
                continue;
            }
            $tmp = explode(" ", $row);
            $mnt_name = $tmp[0];
            $num = $tmp[1];
            $num = str_replace('%', '', $num);
            _status($num, APM_HOST . '(WEB日志分析)', '磁盘', $_POST['hostname'] . '-' . $mnt_name, $row . '-' . $replace_date, APM_VIP, 0, 'replace');
        }

        //web_link连接数
        exec("cat /dev/shm/cache_tcp | awk '{print $4}' | grep :80$ | wc -l", $web_link);
        print_r($web_link);
        _status($web_link[0], APM_HOST . '(WEB日志分析)', 'TCP连接', '80端口连接数', $replace_date, APM_VIP, 0, 'replace');

        //mysql_link连接数
        exec("cat /dev/shm/cache_tcp | awk '{print $(NF-1)}' | grep :3306$ | grep -v ':ffff:' ", $mysql);
        print_r($mysql);
        foreach ($mysql as $v) {
            _status(1, APM_HOST . '(WEB日志分析)', 'TCP连接', 'Mysql连接数(' . $v . ')', $replace_date, APM_VIP, 0, 'replace');
        }
        //oracle连接数
        exec("cat /dev/shm/cache_tcp | awk '{print $(NF-1)}' | grep :1521$ | grep -v ':ffff:' ", $oracle_link);
        print_r($oracle_link);
        foreach ($oracle_link as $k => $v) {
            _status(1, APM_HOST . '(WEB日志分析)', 'TCP连接', 'oracle连接数(' . $v . ')', $replace_date, APM_VIP, 0, 'replace');
        }
        //memcache_link连接数
        exec("cat /dev/shm/cache_tcp | awk '{print $(NF-1)}' | grep :11[2|3]1[0-9]$ | grep -v ':ffff:' ", $memcache_link);
        print_r($memcache_link);
        foreach ($memcache_link as $v) {
            _status(1, APM_HOST . '(WEB日志分析)', 'TCP连接', 'Memcache连接数(' . $v . ')', $replace_date, APM_VIP, 0, 'replace');
        }
        exec("cat /dev/shm/cache_tcp | awk '{print $(NF-1)}' | grep -v :11[2|3]1[0-9]$ | grep -v :1521$ | grep -v  :3306$ | grep -v :80$| grep -v ':ffff:' ", $other_link);
        foreach ($other_link as $v) {
            $v = substr($v, 0, count($v) - 7);
            _status(1, APM_HOST . '(WEB日志分析)', 'TCP连接', '其它连接数', $replace_date . ' ' . $v, APM_VIP, 0, 'replace');
        }
    }
}

?>