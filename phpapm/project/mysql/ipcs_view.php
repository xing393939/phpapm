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
        if (!isset($_GET['key']))
            die("缺少参数:key=?\n");
        $MSGKey = $_GET['key'];

        if (APM_QUEUE_TYPE == 'ipc') {
            $seg = msg_get_queue($MSGKey, 0600);
            $msg_type = 1;
            $msg_array = array();
            //读取第一条队列
            msg_receive($seg, $msg_type, $msg_type, 1024 * 1024 * 10, $msg_array, true, MSG_IPC_NOWAIT);
            print_r($msg_array);
            print_r("<br>\n");
            //写回去队列
            msg_send($seg, 1, $msg_array, true, false);
        } elseif (APM_QUEUE_TYPE == 'redis') {
            $redis = new Redis();
            $redis_tns = parse_url(APM_QUEUE_TNS);
            $redis->connect($redis_tns['host'], $redis_tns['port'], 2);

            $names = explode('|', APM_QUEUE_NAMES);
            $redis->multi(Redis::PIPELINE);
            foreach ($names as $key) {
                $redis->lLen("phpapm:{$key}");
            }
            $redis->lRange("phpapm:{$MSGKey}", 0, 0);
            $arList = $redis->exec();

            print_r($arList);
        }
    }
}

?>