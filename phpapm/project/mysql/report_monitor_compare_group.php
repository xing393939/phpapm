<?php

/**
 * @desc   修改v2比较分组名
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_compare_group
{
    function _initialize()
    {
        $conn_db = apm_db_logon(APM_DB_ALIAS);
        //config表
        $sql = "select * from ".APM_DB_PREFIX."monitor_config where id=:id ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':id', $_POST['id']);
        $oci_error = apm_db_execute($stmt);
        $_row = array();
        $_row = apm_db_fetch_assoc($stmt);
        //v1表
        $sql = "select * from ".APM_DB_PREFIX."monitor_v1 where v1=:v1 ";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':v1', $_row['V1']);
        $oci_error = apm_db_execute($stmt);
        $_row_v1 = apm_db_fetch_assoc($stmt);
        $sql = "update ".APM_DB_PREFIX."monitor_config set COMPARE_GROUP=:compare_group where v2=:v2  and v1=:v1";
        $stmt = apm_db_parse($conn_db, $sql);
        apm_db_bind_by_name($stmt, ':compare_group', $_POST['compare_group']);
        apm_db_bind_by_name($stmt, ':v2', $_row['V2']);
        apm_db_bind_by_name($stmt, ':v1', $_row['V1']);
        $oci_error = apm_db_execute($stmt);

        //比较分类拆分 比较原先数据 插入或者删除虚列
        $arr_com = explode('|', $_row['COMPARE_GROUP']);
        $arr = explode('|', $_POST['compare_group']);
        $arr_add = array_diff($arr, $arr_com);
        $arr_del = array_diff($arr_com, $arr);
        //新增
        foreach ($arr_add as $v) {
            if ($v != '') {
                $sql = "insert into ".APM_DB_PREFIX."monitor_config
                        (V1,V2,ORDERBY,
                        ID,AS_NAME,DAY_COUNT_TYPE,HOUR_COUNT_TYPE,PERCENT_COUNT_TYPE,V2_GROUP,VIRTUAL_COLUMNS) values
                        (:V1,:V2,:ORDERBY,
                        NULL,:AS_NAME,:DAY_COUNT_TYPE,:HOUR_COUNT_TYPE,:PERCENT_COUNT_TYPE,:V2_GROUP,1)";
                $stmt = apm_db_parse($conn_db, $sql);
                $as_name = $_row['AS_NAME'] ? $_row['AS_NAME'] : $_row['V2'];
                apm_db_bind_by_name($stmt, ':V1', $v);
                apm_db_bind_by_name($stmt, ':V2', $_row['V1'] . '_' . $_row['V2']);
                apm_db_bind_by_name($stmt, ':ORDERBY', $_row['ORDERBY']);
                apm_db_bind_by_name($stmt, ':AS_NAME', $as_name);
                apm_db_bind_by_name($stmt, ':DAY_COUNT_TYPE', $_row['DAY_COUNT_TYPE']);
                apm_db_bind_by_name($stmt, ':HOUR_COUNT_TYPE', $_row['HOUR_COUNT_TYPE']);
                apm_db_bind_by_name($stmt, ':PERCENT_COUNT_TYPE', $_row['PERCENT_COUNT_TYPE']);
                apm_db_bind_by_name($stmt, ':V2_GROUP', $_row['V1']);
                $oci_error = apm_db_execute($stmt);
                //插入v1表
                $sql = "insert into ".APM_DB_PREFIX."monitor_v1
                        (V1,START_CLOCK,
                        ID,DAY_COUNT_TYPE,HOUR_COUNT_TYPE,PERCENT_COUNT_TYPE)
                        values(:V1,:START_CLOCK,
                        NULL,:DAY_COUNT_TYPE,:HOUR_COUNT_TYPE,:PERCENT_COUNT_TYPE)";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':V1', $v);
                apm_db_bind_by_name($stmt, ':START_CLOCK', $_row_v1['START_CLOCK']);
                apm_db_bind_by_name($stmt, ':DAY_COUNT_TYPE', $_row_v1['DAY_COUNT_TYPE']);
                apm_db_bind_by_name($stmt, ':HOUR_COUNT_TYPE', $_row_v1['HOUR_COUNT_TYPE']);
                apm_db_bind_by_name($stmt, ':PERCENT_COUNT_TYPE', $_row_v1['PERCENT_COUNT_TYPE']);
                $oci_error = apm_db_execute($stmt);
                var_dump($oci_error);
            }

        }
        //删除
        foreach ($arr_del as $v) {
            if ($v != '') {
                $sql = "delete from ".APM_DB_PREFIX."monitor_config where v1=:v1 and v2=:v2";
                $stmt = apm_db_parse($conn_db, $sql);
                apm_db_bind_by_name($stmt, ':v1', $v);
                apm_db_bind_by_name($stmt, ':v2', $_row['V1'] . '_' . $_row['V2']);
                $oci_error = apm_db_execute($stmt);

            }
        }
    }
}

?>