<?php

/**
 * @desc   每分钟内的文件变动，注意代码目录不要存在保存上传文件的目录
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class file_change extends project_config
{
    function _initialize()
    {
        #暂时关闭
        exit();

        $time = date("Y-m-d H:i", strtotime("-1 minutes"));
        $num = exec("ls -R --full-time | grep  '{$time}' | awk '{print $7}' |  awk -F ':' '{print $1\":\"$2}'   |sort |uniq -c  |sort -nr ");
        if ($num) {
            list($num_1, $time_1) = explode(' ', trim($num));
            _status($num_1, VHOST . '(WEB日志分析)', '文件', NULL, $time_1);
        }
    }
}

?>