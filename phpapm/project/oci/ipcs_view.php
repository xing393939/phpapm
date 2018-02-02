<?php

/**
 * @desc   首页
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class ipcs_view
{
    function _initialize()
    {
        if (!$_GET['key'])
            die("缺少参数:key=?\n");
        $MSGKey = $_GET['key'];
        $seg = msg_get_queue($MSGKey, 0600);
        $msg_type = 1;
        $msg_array = array();
        //读取第一条队列
        msg_receive($seg, $msg_type, $msg_type, 1024 * 1024 * 10, $msg_array, true, MSG_IPC_NOWAIT);
        print_r($msg_array);
        print_r("<br>\n");
        //写回去队列
        msg_send($seg, 1, $msg_array, true, false);
    }
}

?>