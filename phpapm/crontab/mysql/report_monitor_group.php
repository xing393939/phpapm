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
        $conn_db = apm_db_logon(APM_DB_ALIAS);

        //监控消耗
        $sql = "update ".APM_DB_PREFIX."monitor_config t set COMPARE_GROUP=REPLACE(v1, '基本统计', '监控消耗') where v1 like '%(基本统计)%' and (v2 = 'PHP错误' or v2 = 'SQL错误' or v2 = '一秒内' or v2 = '超时')";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='数据库',as_name=null  where  V1 like '%(SQL统计)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='1.项目', GROUP_NAME='基本统计',as_name=null  where    V1 like '%(基本统计)%'    or v1 like '%(断点耗时)'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='Memcache',as_name=null  where   v1 like '%(Memcache)%'  or v1 like '%(Memcache)%' or v1 like '%(Memcache状态)%' or v1 like '%(Memcahe连接)%' or v1 like '%(Couchbase)%'  ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='API接口',as_name=null  where v1 like '%(Api)%'  or v1 like '%(FTP)%'  ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='邮件',as_name=null  where v1 like '%(邮件系统)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='程序监控', GROUP_NAME_2='1.项目', GROUP_NAME='得分',as_name=null  where V1 like '%(项目满意分)%' or V1 like '%(项目文档满意分)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='Redis',as_name=null  where V1 like '%(Redis)%' or V1 like '%(Redis连接)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  GROUP_NAME_1='程序监控', GROUP_NAME_2='2.资源', GROUP_NAME='Redis',as_name=null  where V1 like '%(Redis效率BUG)%' or V1 like '%(Redis连接效率)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update ".APM_DB_PREFIX."monitor_v1 t set GROUP_NAME_1='程序监控', GROUP_NAME_2='3.其他', GROUP_NAME='监控消耗', as_name=null where v1 like '%(监控消耗)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_v1  t set  AS_NAME='" . APM_HOST . "(技术的满意分)' where V1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);

        foreach (array(
                     APM_DB_PREFIX . 'monitor_v1',
                     APM_DB_PREFIX . 'monitor_config'
                 ) as $table) {
            $whereV2 = '';
            if (strpos($table, 'monitor_config') !== false)
                $whereV2 = " or v2='Mem内存剩余' or v2='运行天数'";
            $sql = "update {$table} t set hour_count_type=4 ,day_count_type=1 where v1 like '%(Memcache状态)%' {$whereV2}";
            $stmt = apm_db_parse($conn_db, $sql);
            $oci_error = apm_db_execute($stmt);

            $sql = "update {$table} t set day_count_type=5,hour_count_type=4 where V1 like '%(项目满意分)%' or V1 like '%(项目文档满意分)%'";
            $stmt = apm_db_parse($conn_db, $sql);
            $oci_error = apm_db_execute($stmt);
        }

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
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='E.基础考核'  where  (t.V2 = 'Memcache回源率' or t.V2 = '扣分:单小时SQL上限' or t.v2='扣分:执行超时') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='F.运维考核'  where  (t.V2 = '扣分:5xx错误' or t.V2 = '扣分:CPU LOAD' or t.v2='扣分:机器重启') and v1 like '%(项目满意分)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        //别名换算
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:SQL上限' where v2='扣分:单小时SQL上限'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:负载' where v2='扣分:CPU LOAD'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:重启' where v2='扣分:机器重启'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='TCP' where v2='TCP连接数'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='错误' where v2='PHP+SQL错误率'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:超时' where v2='扣分:执行超时'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='扣:sql' where v2='扣分:问题sql'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='天数' where v2='运行天数'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='ip前十' where v2='ip统计前十'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        //所有v2 中有‘s到’的不计入统计；
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_CONFIG_OTHER=:V2_CONFIG_OTHER where v2 ='超时' or v2 = '一秒内' ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':V2_CONFIG_OTHER', serialize(array("NO_COUNT" => true)));
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='数据库' where (v2='SQL错误' or v2 ='问题SQL' or v2='数据库连接错误') and v1 like '%(基本统计)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='技术错误' where v1 like '%(基本统计)' and (v2='PHP错误' or v2='Curl错误' or v2='Memcache错误' ) ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='验收责任' ,V2_GROUP='问题' where v2='验收责任未到位' and v1 like '%(基本统计)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set AS_NAME='木马',V2_GROUP='问题'  where v2='上传木马入侵' and v1 like '%(基本统计)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='项目'  where v2 like '%[项目]'  and v1 like '%(基本统计)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        //修改CPU,load的计算方式
        $sql = "update  ".APM_DB_PREFIX."monitor_config set day_count_type=6,hour_count_type=4  where V1 like '%(监控消耗)%' and (v2='CPU' OR  v2='Load') ";
        $stmt = apm_db_parse($conn_db, $sql);
        $oci_error = apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set  day_count_type=5,hour_count_type=4  where  t.V2 = '压缩比例'  and v1 like '%(队列服务)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set  day_count_type=5,hour_count_type=4  where  t.V2 = 'QPS'  and v1 like '%(监控消耗)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='功能执行' where (t.V2 = '外网' or t.v2='内网' or t.V2 = '脚本') and v1 like '%(基本统计)'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='效率'  where  (t.V2 = '一秒内' or t.V2 = '超时') ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='A.正常'  where  (t.V2 like '2%' or t.V2 like '3%')  and v1 like '%(监控消耗)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='B.地址异常'  where  t.V2 like '4%' and v1 like '%(监控消耗)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);
        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='C.服务器异常'  where  t.V2 like '5%'  and v1 like '%(监控消耗)%'";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        $sql = "update  ".APM_DB_PREFIX."monitor_config t set V2_GROUP='D.服务器'  where  ( t.V2 = '队列' or t.V2 ='Load' or t.V2 ='IO' or t.V2 ='磁盘' or t.V2 ='Mem内存剩余' or t.V2 ='TCP连接' or t.V2 ='CPU' or t.V2 ='运行天数')  and v1 like '%(监控消耗)%' ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_execute($stmt);

        echo 'ok';
    }
}

?>