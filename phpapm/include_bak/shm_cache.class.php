<?php

//本机内存缓存机制
class shm_mem
{
    var $dir = NULL;

    /**
     * @desc   WHAT?
     * @author
     * @since  2013-07-14 15:55:31
     * @throws 注意:无DB异常处理
     */
    function _dir($key)
    {
        $this->dir = "/dev/shm/" . APM_HOST . '/' . substr(md5($key), 0, 2) . '/';
        if (!is_dir($this->dir))
            mkdir($this->dir);
        return $this->dir . md5($key);
    }

    /**
     * @desc   $max_times 过期时间,获取的时候需要设置.在系统负载压力过大的时候,仍然返回数据,默认保留10个小时
     * @author
     * @since  2013-07-14 15:55:31
     * @throws 注意:无DB异常处理
     */
    function get($key, $max_times = 36000)
    {
        $unserialize = NULL;
        $file = $this->_dir($key);
        if (_sys_overload()) {
            _status(1, APM_HOST . "(本机缓存)", "过载命中");
            $unserialize = unserialize(_file_get_contents($file));
        } elseif (filemtime($file) > time() - $max_times) {
            $unserialize = unserialize(_file_get_contents($file));
        }
        if ($unserialize)
            _status(1, APM_HOST . "(本机缓存)", "命中");
        else
            _status(1, APM_HOST . "(本机缓存)", "未命中");
        return $unserialize;
    }

    /**
     * @desc   WHAT?
     * @author
     * @since  2013-07-14 15:55:31
     * @throws 注意:无DB异常处理
     */
    function set($key, $data)
    {
        $unserialize = _file_put_contents($this->_dir($key), serialize($data));
        _status(1, APM_HOST . "(本机缓存)", "写入缓存");
        return $unserialize;
    }

    /**
     * @desc   WHAT?
     * @author
     * @since  2013-07-14 15:55:31
     * @throws 注意:无DB异常处理
     */
    function delete($key)
    {
        $cache_file = $this->_dir($key);
        if (is_file($cache_file)) {
            unlink($cache_file);
            _status(1, APM_HOST . "(本机缓存)", "删除缓存[命中]");
        } else {
            _status(1, APM_HOST . "(本机缓存)", "删除缓存[未命中]");
        }
    }
}

?>