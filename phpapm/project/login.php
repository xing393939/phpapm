<?php

/**
 * @desc   首页
 * @author xing39393939@gmail.com
 * @since  2013-03-06 22:06:23
 * @throws 注意:无DB异常处理
 */
class login extends project_config
{
    function _initialize()
    {
        if (!empty($_POST)) {
            $arr_str = md5(serialize(array($_POST['v1'], $_POST['v2'])));
            if ($arr_str == md5(serialize($this->admin_user))) {
                setcookie('admin_user', $arr_str);
            }
            header("location: {$_SERVER['HTTP_REFERER']}");
            exit();
        }
        include PHPAPM_PATH . "./project_tpl/login.html";
    }
}

?>