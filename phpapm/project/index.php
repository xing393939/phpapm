<?php

/**
 * @desc   首页
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class index extends project_config
{
    function _initialize()
    {
        include PHPAPM_PATH . "./project_tpl/index.html";
    }
}

?>