<?php

/**
 * @desc   修改v2比较分组名
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class report_monitor_compare_group extends project_config
{
    function _initialize()
    {
        $conn_db = _ocilogon($this->db);
        //config表
        $sql = "select * from {$this->report_monitor_config} where id=:id ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':id', $_POST['id']);
        $oci_error = _ociexecute($stmt);
        $_row = array();
        ocifetchinto($stmt, $_row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        //v1表
        $sql = "select * from {$this->report_monitor_v1} where v1=:v1 ";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':v1', $_row['V1']);
        $oci_error = _ociexecute($stmt);
        $_row_v1 = array();
        ocifetchinto($stmt, $_row_v1, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);
        $sql = "update {$this->report_monitor_config} set COMPARE_GROUP=:compare_group where v2=:v2  and v1=:v1";
        $stmt = _ociparse($conn_db, $sql);
        _ocibindbyname($stmt, ':compare_group', $_POST['compare_group']);
        _ocibindbyname($stmt, ':v2', $_row['V2']);
        _ocibindbyname($stmt, ':v1', $_row['V1']);
        $oci_error = _ociexecute($stmt);

        //比较分类拆分 比较原先数据 插入或者删除虚列
        $arr_com = explode('|', $_row['COMPARE_GROUP']);
        $arr = explode('|', $_POST['compare_group']);
        $arr_add = array_diff($arr, $arr_com);
        $arr_del = array_diff($arr_com, $arr);
        //新增
        foreach ($arr_add as $v) {
            if ($v != '') {
                $sql = "insert into {$this->report_monitor_config}
                            (V1,V2,COUNT_TYPE,V3_LINK,V4_LINK,ORDERBY,PHONE,PHONE_ORDER,PHONE_ORDER_LESS,
                           ID,AS_NAME,DAY_COUNT_TYPE,HOUR_COUNT_TYPE,PERCENT_COUNT_TYPE,V2_GROUP,VIRTUAL_COLUMNS) values(:V1,:V2,:COUNT_TYPE,:V3_LINK,
                           :V4_LINK,:ORDERBY,:PHONE,:PHONE_ORDER,:PHONE_ORDER_LESS,
                           seq_{$this->report_monitor}.nextval,:AS_NAME,:DAY_COUNT_TYPE,:HOUR_COUNT_TYPE,:PERCENT_COUNT_TYPE,:V2_GROUP,1)";
                $stmt = _ociparse($conn_db, $sql);
                $as_name = $_row['AS_NAME'] ? $_row['AS_NAME'] : $_row['V2'];
                _ocibindbyname($stmt, ':V1', $v);
                _ocibindbyname($stmt, ':V2', $_row['V1'] . '_' . $_row['V2']);
                _ocibindbyname($stmt, ':COUNT_TYPE', $_row['id']);
                _ocibindbyname($stmt, ':V3_LINK', $_row['V3_LINK']);
                _ocibindbyname($stmt, ':V4_LINK', $_row['V4_LINK']);
                _ocibindbyname($stmt, ':ORDERBY', $_row['ORDERBY']);
                _ocibindbyname($stmt, ':PHONE', $_row['PHONE']);
                _ocibindbyname($stmt, ':PHONE_ORDER', $_row['PHONE_ORDER']);
                _ocibindbyname($stmt, ':PHONE_ORDER_LESS', $_row['PHONE_ORDER_LESS']);
                _ocibindbyname($stmt, ':AS_NAME', $as_name);
                _ocibindbyname($stmt, ':DAY_COUNT_TYPE', $_row['DAY_COUNT_TYPE']);
                _ocibindbyname($stmt, ':HOUR_COUNT_TYPE', $_row['HOUR_COUNT_TYPE']);
                _ocibindbyname($stmt, ':PERCENT_COUNT_TYPE', $_row['PERCENT_COUNT_TYPE']);
                _ocibindbyname($stmt, ':V2_GROUP', $_row['V1']);
                $oci_error = _ociexecute($stmt);
                //插入v1表
                $sql = "insert into {$this->report_monitor_v1}
                            (V1,COUNT_TYPE,CHAR_TYPE,START_CLOCK,SHOW_TEMPLATE,SHOW_ALL,ID,DAY_COUNT_TYPE,HOUR_COUNT_TYPE,PERCENT_COUNT_TYPE,SHOW_AVG,IS_DUTY)
                      values(:V1,:COUNT_TYPE,:CHAR_TYPE,:START_CLOCK,:SHOW_TEMPLATE,:SHOW_ALL,
                           seq_{$this->report_monitor}.nextval,:DAY_COUNT_TYPE,:HOUR_COUNT_TYPE,:PERCENT_COUNT_TYPE,:SHOW_AVG,1)";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':V1', $v);
                _ocibindbyname($stmt, ':COUNT_TYPE', $_row_v1['COUNT_TYPE']);
                _ocibindbyname($stmt, ':CHAR_TYPE', $_row_v1['CHAR_TYPE']);
                _ocibindbyname($stmt, ':START_CLOCK', $_row_v1['START_CLOCK']);
                _ocibindbyname($stmt, ':SHOW_TEMPLATE', $_row_v1['SHOW_TEMPLATE']);
                _ocibindbyname($stmt, ':SHOW_ALL', $_row_v1['SHOW_ALL']);
                _ocibindbyname($stmt, ':DAY_COUNT_TYPE', $_row_v1['DAY_COUNT_TYPE']);
                _ocibindbyname($stmt, ':HOUR_COUNT_TYPE', $_row_v1['HOUR_COUNT_TYPE']);
                _ocibindbyname($stmt, ':PERCENT_COUNT_TYPE', $_row_v1['PERCENT_COUNT_TYPE']);
                _ocibindbyname($stmt, ':SHOW_AVG', $_row_v1['SHOW_AVG']);
                $oci_error = _ociexecute($stmt);
                var_dump($oci_error);
            }

        }
        //删除
        foreach ($arr_del as $v) {
            if ($v != '') {
                $sql = "delete from {$this->report_monitor_config} where v1=:v1 and v2=:v2";
                $stmt = _ociparse($conn_db, $sql);
                _ocibindbyname($stmt, ':v1', $v);
                _ocibindbyname($stmt, ':v2', $_row['V1'] . '_' . $_row['V2']);
                $oci_error = _ociexecute($stmt);

            }
        }
    }
}

?>