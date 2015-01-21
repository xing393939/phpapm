<?php

/**
 * @desc   数据统计的归组
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_group
{
    function _initialize()
    {
        #每小时执行一次
        if (date('i') != 30) {
            exit();
        }

        $conn_db = apm_db_logon(APM_DB_ALIAS);

        //1.汇总的生成
        $sql = "update ".APM_DB_PREFIX."monitor_config t set COMPARE_GROUP='1.汇总' where (v1 like '%(BUG错误)%' and (v2 = 'PHP错误' or v2 = 'SQL错误' or v2 = '一秒内' or v2 = '超时')) or (v1 like '%(Couchbase)%' and (v2 = '超时' or v2 = '一秒内')) or (v1 like '%(SQL统计)%' and (v2 = '超时' or v2 = '一秒内')) or (v1 like '%(Memcache)%' and (v2 = '超时' or v2 = '一秒内')) or (v1 like '%(Sphinx)%' and (v2 = '超时' or v2 = '一秒内')) or (v1 like '%(网址抓取)%' and (v2 = '超时' or v2 = '一秒内')) or (v1 like '%(WEB日志分析)%' and (v2 = 'QPS' or v2 = 'TCP连接' or v2 = '400' or v2 = '403' or v2 = '499' or v2 = '500' or v2 = '502'))";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //排查.BUG
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='排查.BUG' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(BUG错误)_PHP错误' or v2 like '%(BUG错误)_SQL错误')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //排查.机器
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='排查.机器' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(WEB日志分析)_QPS' or v2 like '%(WEB日志分析)_TCP连接')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //排查.BUG
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='排查.BUG' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(BUG错误)_PHP错误' or v2 like '%(BUG错误)_SQL错误')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //资源.Couchbase
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='资源.Couchbase' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(Couchbase)_超时' or v2 like '%(Couchbase)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //资源.Memcache
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='资源.Memcache' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(Memcache)_超时' or v2 like '%(Memcache)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //资源.Sql
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='资源.Oracle' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(SQL统计)_超时' or v2 like '%(SQL统计)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //资源.Sphinx
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='资源.Sphinx' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(Sphinx)_超时' or v2 like '%(Sphinx)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //资源.接口网址
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='资源.接口网址' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(网址抓取)_超时' or v2 like '%(网址抓取)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //总PV
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='总PV' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(BUG错误)_超时' or v2 like '%(BUG错误)_一秒内')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        //总PV.错误
        $sql = "update ".APM_DB_PREFIX."monitor_config t set AS_NAME=V2, V2_GROUP='总PV.错误' where V1 = '1.汇总' and V2_GROUP is null and (v2 like '%(WEB日志分析)_400' or v2 like '%(WEB日志分析)_403' or v2 like '%(WEB日志分析)_499' or v2 like '%(WEB日志分析)_500' or v2 like '%(WEB日志分析)_502')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='数据库',as_name=null  where  V1 like '%(SQL统计)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='1.项目', GROUP_NAME='基本统计',as_name=null  where    V1 like '%(BUG错误)%'    or v1 like '%(断点耗时)' or  v1 like '%(WEB日志分析)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='Memcache',as_name=null  where   v1 like '%(Memcache)%'  or v1 like '%(Memcache)%' or v1 like '%(Memcache状态)%' or v1 like '%(Memcahe连接)%' or v1 like '%(Couchbase)%'  ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='API接口',as_name=null  where v1 like '%(网址抓取)%'  or v1 like '%(FTP)%'  ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='邮件',as_name=null  where v1 like '%(邮件系统)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='数据指标', GROUP_NAME_2='1.项目', GROUP_NAME='得分',as_name=null  where V1 like '%(项目满意分)%' or V1 like '%(项目文档满意分)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='Redis',as_name=null  where V1 like '%(Redis)%' or V1 like '%(Redis连接)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='数据指标', GROUP_NAME_2='2.资源', GROUP_NAME='Redis',as_name=null  where V1 like '%(Redis效率BUG)%' or V1 like '%(Redis连接效率)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='数据指标', GROUP_NAME_2='3.作废', GROUP_NAME='作废',as_name=null  where   v1 like '%(Memcahe连接错误)%' or v1 like '%(Memcache)NEW%' or v1 like '%(Memcache使用错误)%' or v1 like '%(Memcahe错误)%'  or v1 like '%(Memcahe连接)%' or v1 like '%(Memcache移动)' or v1 like '%(Memcahe连接效率)%' or v1 like '%(Memcahe整体耗时)%' or v1 like '%(安全BUG)%' or v1 like '%(Memcahe效率BUG)%' or v1 like '%(程序效率BUG)%' or v1 like '%(数据库被连接)%' or v1 like '%(接口效率)%' or  v1 like '%SQL效率BUG)%' or v1 like '%(数据库连接%'  or  v1 like '%(SQL统计)[项目]%' or v1 like '%(数据库表大小)%'   or v1 like '%(数据库表空间)%' or v1 like '%(队列服务)%' or  v1 like '%(登录日志%' or v1 like '%(包含文件)[项目]%' or v1 like '%(包含文件)%' or v1 like '%(问题SQL)%' or v1 like '%(服务器)%' or v1 like '%(接口测试)%' or v1 like '%文件系统读写%' or v1 like '%(功能执行)%' or v1 like '%(服务器进程)%'  or v1 like '%(代码%'  or v1 like '%(FTP效率BUG)%'   or v1 like '%(服务器进程)%' or v1 like '%(队列信息)' or v1 like '%(账户日志)%' or v1 like '%(函数分布)%' or v1 like '%(文件系统%' or v1 = '1.汇总' or v1 like '%(PHPAPM)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  AS_NAME='" . APM_HOST . "(技术的满意分)' where V1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        foreach (array(
                     APM_DB_PREFIX . 'monitor_v1',
                     APM_DB_PREFIX . 'monitor_config'
                 ) as $table) {
            $sql = "update  {$table} t set   hour_count_type=4 ,day_count_type=1   where v1 like '%(Memcache状态)%'  ";
            $stmt = apm_db_parse($conn_db, $sql);
            $oci_error = apm_db_execute($stmt);

            $sql = "update  {$table} t set day_count_type=5,hour_count_type=4  where V1 like '%(项目满意分)%' or V1 like '%(项目文档满意分)%'";
            $stmt = apm_db_parse($conn_db, $sql);
            $oci_error = apm_db_execute($stmt);
        }

        //内置的系统环境都不需要验收
        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set is_duty=1  where  (t.GROUP_NAME_1 = '数据指标' or t.GROUP_NAME_1 = '数据指标')  ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        //v2分组
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='A.态度'  where  t.V2 = '扣:故障'  and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='B.责任考核'  where  (t.V2 = 'SQL回源率' or t.V2 = 'TCP连接数' or t.v2='项目验收') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='C.编程能力'  where  (t.V2 = 'PHP+SQL错误率' or t.V2 = '扣分:问题sql') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='D.安全'  where  t.V2 = '扣:安全'  and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='E.维护成本'  where  t.V2 = '扣分:包含文件' and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='F.基础考核'  where  (t.V2 = 'Memcache回源率' or t.V2 = '扣分:单小时SQL上限' or t.v2='扣分:执行超时') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='G.运维考核'  where  (t.V2 = '扣分:5xx错误' or t.V2 = '扣分:CPU LOAD' or t.v2='扣分:机器重启') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        //别名换算
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:SQL上限' where v2='扣分:单小时SQL上限'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:负载' where v2='扣分:CPU LOAD'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:重启' where v2='扣分:机器重启'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='TCP' where v2='TCP连接数'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:文件数' where v2='扣分:包含文件'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='错误' where v2='PHP+SQL错误率'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:超时' where v2='扣分:执行超时'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:sql' where v2='扣分:问题sql'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='天数' where v2='运行天数'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='ip前十' where v2='ip统计前十'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='已用磁盘比' where v2='磁盘' and v1 like '%(WEB日志分析)' ";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='内存' where v2='Mem内存剩余' and v1 like '%(WEB日志分析)' ";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        //所有v2 中有‘s到’的不计入统计；
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_CONFIG_OTHER=:V2_CONFIG_OTHER where v2 ='超时' or v2 = '一秒内' ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':V2_CONFIG_OTHER', serialize(array("NO_COUNT" => true)));
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='数据库' where (v2='SQL错误' or v2 ='问题SQL' or v2='数据库连接错误') and v1 like '%(BUG错误)'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='技术错误' where v1 like '%(BUG错误)' and (v2='PHP错误' or v2='脚本错误'  or v2='致命错误' or v2='Memcache错误' ) ";
        apm_db_execute(apm_db_parse($conn_db, $sql));

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='验收责任' ,V2_GROUP='问题' where v2='验收责任未到位' and v1 like '%(BUG错误)'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='木马',V2_GROUP='问题'  where v2='上传木马入侵' and v1 like '%(BUG错误)'";
        apm_db_execute(apm_db_parse($conn_db, $sql));
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='项目'  where v2 like '%[项目]'  and v1 like '%(BUG错误)'";
        apm_db_execute(apm_db_parse($conn_db, $sql));

        //定时更新不计入统计的项目
        $sql = "select * from ".APM_DB_PREFIX."monitor_config where ( v1 like '%(WEB日志分析)' and (v2='文件' or v2 = 'QPS' or v2 = 'ip统计前十' or v2 = '独立ip') )
           or  (v1 like '%(队列服务)' and v2='压缩比例' )";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        while ($_row = apm_db_fetch_assoc($stmt)) {
            $v2_config_other = unserialize($_row['V2_CONFIG_OTHER']);
            $v2_config_other['NO_COUNT'] = true;
            $v2_config_other = serialize($v2_config_other);
            $sql = "update ".APM_DB_PREFIX."monitor_config set v2_config_other=:v2_config_other where v2=:v2";
            $stmt2 = apm_db_parse($conn_db, $sql);
            apm_db_bind_by_name($stmt2, ':v2_config_other', $v2_config_other);
            apm_db_bind_by_name($stmt2, ':v2', $_row['V2']);
            $oci_error = apm_db_execute($stmt2);
        }

        //修改CPU,load的计算方式
        $sql = "update  ".APM_DB_PREFIX."monitor_config set day_count_type=6,hour_count_type=4  where V1 like '%(WEB日志分析)%' and (v2='CPU' OR  v2='Load') ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set  day_count_type=5,hour_count_type=4  where  t.V2 = '压缩比例'  and v1 like '%(队列服务)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set  day_count_type=5,hour_count_type=4  where  t.V2 = 'QPS'  and v1 like '%(WEB日志分析)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='功能执行'  where  (t.V2 = '其他功能' or t.V2 = '页面操作' or t.v2='内网接口' or t.V2 = '定时') and v1 like '%(BUG错误)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='效率'  where  (t.V2 = '一秒内' or t.V2 = '超时') ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='A.正常'  where  (t.V2 like '2%' or t.V2 like '3%')  and v1 like '%(WEB日志分析)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='B.地址异常'  where  t.V2 like '4%' and v1 like '%(WEB日志分析)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='C.服务器异常'  where  t.V2 like '5%'  and v1 like '%(WEB日志分析)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='D.服务器'  where  ( t.V2 = '队列' or t.V2 ='Load' or t.V2 ='IO' or t.V2 ='磁盘' or t.V2 ='Mem内存剩余' or t.V2 ='TCP连接' or t.V2 ='CPU' or t.V2 ='运行天数')  and v1 like '%(WEB日志分析)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        echo 'ok';
    }
}

?>