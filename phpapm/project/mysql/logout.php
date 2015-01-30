<?php

/**
 * @desc   首页
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class logout
{
    function _initialize()
    {
        setcookie('admin_user', NULL, time() - 1);
        header("location: ./project.php");
        exit();
    }
}

?>