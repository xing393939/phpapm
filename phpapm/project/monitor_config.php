<?php

/**
 * @desc   重新整理v1下v2的数据
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class monitor_config extends project_config
{
    function _initialize()
    {
        $url = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $stream = stream_context_create(array(
                'http' => array(
                    'timeout' => 30,
                )
            )
        );
        echo file_get_contents("$url", false, $stream);
    }
}

?>