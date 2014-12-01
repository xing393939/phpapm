<?php

/**
 * 调用方式: $memcache_server = new memcache_server('19');
 * Class memcache_server
 */

if (!class_exists('Memcache')) {
    class Memcache {
        function connect() {
            return false;
        }

        function getStats() {
            return false;
        }
    }
}

class memcache_config
{
    var $config = array(
        '19' => array(
            array(
                'host' => '10.77.6.19',
                'port' => 11211
            ),
            array(
                'host' => '10.77.6.20',
                'port' => 11311
            ),
        )
    );
}

class memcache_server
{

    //配置文件
    var $config = array();
    var $current_host = null;
    var $memcacheObj = null;
    var $start_time = null;
    var $db_link_count = 0;
    var $memcache_config = NULL;

    //配备Mysql的存储
    var $mysql_config;
    var $mysql_conn;

    /**
     * @desc   memcache服务器值分配算法.
     * @author xing39393939@gmail.com
     * @since  2012-04-02 09:58:12
     * @param Array $config Memcache连接配置
     * @return null
     * @throws 无DB异常处理
     */
    function memcache_server($config = array())
    {
        $this->config = array();
        $this->db_link_count = $_SERVER['oci_sql_ociexecute'];
        $this->start_time = microtime(true);
        if (is_array($config)) {
            $this->config = $config;
        } else {
            $this->memcache_config = new memcache_config;
            //主配置
            $this->config = $this->memcache_config->config[$config];
            if ($this->memcache_config->mysqlconfig[$config])
                $this->mysql_config = $this->memcache_config->mysqlconfig[$config];
        }
    }

    /**
     * @desc   定位服务器,如果是自定义的算法, 每次连接,都需要new一个新的memcache对象
     * @author xing39393939@gmail.com
     * @since  2013-01-30 15:33:26
     * @throws 注意:无DB异常处理
     */
    function _key_connect($key = null)
    {
        $key = (string)$key;
        $hashCode = 0;
        for ($i = 0, $len = min(100, strlen($key)); $i < $len; $i++)
            $hashCode = (int)(($hashCode * 33) + ord($key[$i])) & 0x7fffffff;
        $this->current_host = $this->config[$hashCode % count($this->config)];
        if (!$this->current_host)
            _status(1, APM_HOST . '(BUG错误)', "Memcache错误", "没命中当前主机", APM_URI . var_export(debug_backtrace(), true), APM_VIP);
    }

    /**
     * @desc   WHAT?
     * @author xing39393939@gmail.com
     * @since  2013-07-11 22:12:40
     * @throws 注意:无DB异常处理
     */
    function _getMemObj($memcacheObj, $current_host)
    {
        //缓存之前的历史记录
        if (!is_object($memcacheObj)) {
            $memcache = new Memcache;
            $t1 = microtime(true);
            $bool = $memcache->connect($current_host['host'], $current_host['port']);
            //尝试2次
            if (!$bool)
                $bool = $memcache->connect($current_host['host'], $current_host['port']);
            $diff_time = sprintf('%.5f', microtime(true) - $t1);

            _status(1, APM_HOST . '(Memcahe连接)', "{$current_host['host']}:{$current_host['port']}[打开]", APM_URI);
            if (!$bool)
                _status(1, APM_HOST . '(BUG错误)', "Memcache错误", "Memcahe连接错误", "{$current_host['host']}:{$current_host['port']}", APM_URI);
            $memcacheObj = $_SERVER['memcache_server']["{$current_host['host']}:{$current_host['port']}"] = & $memcache;
            $_SERVER['memcache_server_connect']++;
        }
        return $memcacheObj;
    }

    /**
     * @desc   根据KEY来选择数据库
     * @author xing39393939@gmail.com
     * @since  2012-04-02 09:58:12
     * @throws 无DB异常处理
     */
    function connect($key = NULL)
    {
        if ($this->config['host'] && $this->config['bucket']) {
            try {
                $this->memcacheObj = new Couchbase($this->config['host'], $this->config['user'], $this->config['password'], $this->config['bucket'], true);
                $this->memcacheObj->setTimeout(5000000);
                return $this->memcacheObj;
            } catch (Exception $e) {
                return null;
            }

        }

        if (!$key) {
            _status(1, APM_HOST . '(BUG错误)', "Memcache错误", "没有传KEY", APM_URI . var_export(debug_backtrace(), true), APM_VIP);
            return null;
        }
        if (!$this->config) {
            _status(1, APM_HOST . '(BUG错误)', "Memcache错误", "配置文件为空", APM_URI . var_export(debug_backtrace(), true), APM_VIP);
            return null;
        }
        $this->_key_connect($key);
        $this->memcacheObj = $this->_getMemObj($_SERVER['memcache_server']["{$this->current_host['host']}:{$this->current_host['port']}"], $this->current_host);
        return $this->memcacheObj;
    }

    /**
     * @desc   读取数据
     * @author xing39393939@gmail.com
     * @since  2012-04-02 09:58:12
     * @throws 无DB异常处理
     */
    function get($key = NULL)
    {
        $bool = false;
        if ($this->config['host']) {
            if ($this->memcacheObj || $this->connect()) {
                $t1 = microtime(true);
                try {
                    $bool = $this->memcacheObj->get($key);
                } catch (Exception $e) {
                    $bool = false;
                }

                $diff_time = sprintf('%.5f', microtime(true) - $t1);
                _status(1, APM_HOST . '(Couchbase)', "{$this->config['host_alias']}:{$this->config['bucket']}(get)", APM_URI, var_export((bool)$bool, true), APM_VIP, $diff_time);
                //命中计算
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Couchbase)', '一秒内', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Couchbase)', '超时', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                }
            }
        } else {
            if (strpos(get_class($this), 'memcache_server') !== false) {
                $this->connect($key);
            }

            if ($this->memcacheObj) {
                $t1 = microtime(true);
                $bool = $this->memcacheObj->get($key);
                $diff_time = sprintf('%.5f', microtime(true) - $t1);

                _status(1, APM_HOST . '(Memcache)', "{$this->current_host['host']}:{$this->current_host['port']}(get)", APM_URI, var_export((bool)$bool, true), APM_VIP, $diff_time);
                //命中计算
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Memcache)', '一秒内', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(get)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Memcache)', '超时', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(get)" . APM_VIP, APM_URI, $diff_time);
                }
            }

            //memcache未命中,从mysql数据库中读取
            if (!$bool && $this->mysql_config) {
                if (!$this->mysql_conn) {
                    $this->mysql_conn = _mysqllogon($this->mysql_config['db_config']);
                    if ($this->mysql_config['encode'])
                        mysql_query("SET NAMES '{$this->mysql_config['encode']}'");
                }
                if (!$this->mysql_conn) {
                    return null;
                }

                $sql = "select * from {$this->mysql_config['table_name']} where mem_key=:mem_key";
                $stmt = _mysqlparse($this->mysql_conn, $sql);
                _mysqlbindbyname($stmt, ":mem_key", $key);
                $error = _mysqlexecute($stmt);
                $bool = mysql_fetch_assoc($stmt);
                $bool = unserialize($bool['mem_value']);
                //查到数据后
                if ($bool) {
                    $this->set($key, $bool);
                }
            }
        }
        return $bool;
    }

    /**
     * @desc couchbase专用函数
     * @author xing39393939@gmail.com
     * @since  2013-10-22 17:32:12
     * @throws 无DB异常处理
     * Retrieve multiple documents from the cluster.
     * @param array $ids an array containing all of the document identifiers
     * @param array $cas an array to store the cas identifiers of the documents
     * @param int $flags may be 0 or COUCHBASE_GET_PRESERVE_ORDER
     *              当 $flag=0时，返回结果会自动排序，并且会过滤掉空值
     *              当 $flag=COUCHBASE_GET_PRESERVE_ORDER时，返回结果与$ids中顺序一致，并且空值（未命中）也会返回
     * @return array an array containing the documents
     * @throws CouchbaseException if an error occurs
     */
    function getMulti($ids, $cas = array(), $flags = COUCHBASE_GET_PRESERVE_ORDER)
    {
        $bool = false;
        if ($this->config['host']) {
            if ($this->memcacheObj || $this->connect()) {
                $t1 = microtime(true);
                try {
                    $bool = $this->memcacheObj->getMulti($ids, $cas, $flags);
                } catch (Exception $e) {
                    $bool = false;
                }

                $diff_time = sprintf('%.5f', microtime(true) - $t1);
                _status(1, APM_HOST . '(Couchbase)', "{$this->config['host_alias']}:{$this->config['bucket']}(get)", APM_URI, var_export((bool)$bool, true), APM_VIP, $diff_time);
                //命中计算
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Couchbase)', '一秒内', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Couchbase)', '超时', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                }
            }
        }
        return $bool;
    }

    /**
     * @desc couchbase专用函数
     * @author xing39393939@gmail.com
     * @since  2013-10-22 17:32:12
     * @throws 无DB异常处理
     */
    function view($document, $view, $options, $return_errors)
    {
        $bool = false;
        if ($this->config['host']) {
            if ($this->memcacheObj || $this->connect()) {
                $t1 = microtime(true);
                try {
                    $bool = $this->memcacheObj->view($document, $view, $options, $return_errors);
                } catch (Exception $e) {
                    $bool = false;
                }

                $diff_time = sprintf('%.5f', microtime(true) - $t1);
                _status(1, APM_HOST . '(Couchbase)', "{$this->config['host_alias']}:{$this->config['bucket']}(view)", $document . "|" . $view . "|" . var_export($options, true), APM_VIP, $diff_time);
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Couchbase)', '一秒内', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(view)" . APM_VIP, $document . "|" . $view . "|" . var_export($options, true), $diff_time);
                } else {
                    _status(1, APM_HOST . '(Couchbase)', '超时', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(view)" . APM_VIP, $document . "|" . $view . "|" . var_export($options, true), $diff_time);
                }
            }
        }
        return $bool;
    }

    /**
     * @desc   写入修改数据
     * @author xing39393939@gmail.com
     * @since  2012-04-02 09:58:12
     * @throws 无DB异常处理
     */
    function set($key = NULL, $var = null, $flag = MEMCACHE_COMPRESSED, $expire = 0)
    {
        $bool = false;
        if ($this->config['host']) {
            if ($this->memcacheObj || $this->connect()) {
                $t1 = microtime(true);
                try {
                    $bool = $this->memcacheObj->set($key, $var, $expire);
                } catch (Exception $e) {
                    _status(1, APM_HOST . '(Couchbase)', '异常', "{$this->config['host_alias']}:{$this->config['bucket']}(set)", $key . '|' . var_export($var, true), APM_VIP, APM_URI);
                }

                $diff_time = sprintf('%.5f', microtime(true) - $t1);
                _status(1, APM_HOST . '(Couchbase)', "{$this->config['host_alias']}:{$this->config['bucket']}(set)", APM_URI, NULL, APM_VIP, $diff_time);
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Couchbase)', '一秒内', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Couchbase)', '超时', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(set)" . APM_VIP, APM_URI, $diff_time);
                }
            }
        } else {
            if (strpos(get_class($this), 'memcache_server') !== false)
                $this->connect($key);

            if ($this->memcacheObj) {
                $t1 = microtime(true);
                $bool = $this->memcacheObj->set($key, $var, $flag, $expire);
                $diff_time = sprintf('%.5f', microtime(true) - $t1);

                _status(1, APM_HOST . '(Memcache)', "{$this->current_host['host']}:{$this->current_host['port']}(set)", APM_URI, NULL, APM_VIP, $diff_time);
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Memcache)', '一秒内', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(get)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Memcache)', '超时', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(get)" . APM_VIP, APM_URI, $diff_time);
                }
            }

            //写入mysql;
            if (!$this->mysql_conn && $this->mysql_config) {
                $this->mysql_conn = _mysqllogon($this->mysql_config['db_config']);
                if ($this->mysql_config['encode'])
                    mysql_query("SET NAMES '{$this->mysql_config['encode']}'");
            }
            if (!$this->mysql_conn) {
                return $bool;
            }
            $sql = "INSERT INTO memcache (mem_key,mem_value,add_time,update_time,server_host) VALUES (:mem_key,:mem_value,now(),NOW(),:server_host)
ON DUPLICATE KEY UPDATE mem_value=:mem_value, update_time=NOW(), server_host=:server_host";
            $stmt = _mysqlparse($this->mysql_conn, $sql);
            _mysqlbindbyname($stmt, ":mem_key", $key);
            _mysqlbindbyname($stmt, ":mem_value", serialize($var)); //
            _mysqlbindbyname($stmt, ":server_host", $this->current_host['host'] . ":" . $this->current_host['port']);
            _mysqlexecute($stmt);
        }
        return $bool;
    }

    /**
     * @desc   WHAT?
     * @author xing39393939@gmail.com
     * @since  2012-11-29 18:13:14
     * @throws 注意:无DB异常处理
     */
    function delete($key)
    {
        $bool = false;
        if ($this->config['host']) {
            if ($this->memcacheObj || $this->connect()) {
                $t1 = microtime(true);
                try {
                    $bool = $this->memcacheObj->delete($key);
                } catch (Exception $e) {
                    _status(1, APM_HOST . '(Couchbase)', '异常', "{$this->config['host_alias']}:{$this->config['bucket']}(delete)", $key, APM_VIP, APM_URI);

                }
                $diff_time = sprintf('%.5f', microtime(true) - $t1);

                _status(1, APM_HOST . '(Couchbase)', "{$this->config['host_alias']}:{$this->config['bucket']}(delete)", APM_URI, NULL, APM_VIP, $diff_time);
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Couchbase)', '一秒内', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(delete)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Couchbase)', '超时', _debugtime($diff_time), "{$this->config['host_alias']}:{$this->config['bucket']}(delete)" . APM_VIP, APM_URI, $diff_time);
                }

            }
        } else {
            if (strpos(get_class($this), 'memcache_server') !== false)
                $this->connect($key);

            if ($this->memcacheObj) {
                $t1 = microtime(true);
                $bool = $this->memcacheObj->delete($key, 0);
                $diff_time = sprintf('%.5f', microtime(true) - $t1);

                _status(1, APM_HOST . '(Memcache)', "{$this->current_host['host']}:{$this->current_host['port']}(delete)", APM_URI, NULL, APM_VIP, $diff_time);
                if ($diff_time < 1) {
                    _status(1, APM_HOST . '(Memcache)', '一秒内', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(delete)" . APM_VIP, APM_URI, $diff_time);
                } else {
                    _status(1, APM_HOST . '(Memcache)', '超时', _debugtime($diff_time), "{$this->current_host['host']}:{$this->current_host['port']}(delete)" . APM_VIP, APM_URI, $diff_time);
                }
            }

            //mysql中删除;
            if (!$this->mysql_conn && $this->mysql_config) {
                $this->mysql_conn = _mysqllogon($this->mysql_config['db_config']);
                if ($this->mysql_config['encode'])
                    mysql_query("SET NAMES '{$this->mysql_config['encode']}'");
            }
            if (!$this->mysql_conn) {
                return $bool;
            }
            $sql = "delete from  {$this->mysql_config['table_name']} where mem_key=:mem_key";
            $stmt = _mysqlparse($this->mysql_conn, $sql);
            _mysqlbindbyname($stmt, ":mem_key", $key);
            _mysqlexecute($stmt);
        }
        return $bool;
    }

    /**
     * @desc   WHAT?
     * @author xing39393939@gmail.com
     * @since  2013-03-14 21:03:28
     * @throws 注意:无DB异常处理
     */
    function increment($key, $num = 1, $flag = MEMCACHE_COMPRESSED, $expire = 0)
    {
        if (strpos(get_class($this), 'memcache_server') !== false)
            $this->connect($key);
        if ($this->memcacheObj) {
            $t1 = microtime(true);
            $bool = $this->memcacheObj->increment($key, $num);
            if ($bool === false) {
                //更新失败,是因为之前key存在,删除之后,还必须关闭连接再次连接回去
                $this->memcacheObj->delete($key, 0);
                $this->close();
                $this->connect($key);
                $this->memcacheObj->set($key, 0, $flag, $expire);
                $bool = $this->memcacheObj->increment($key, $num);
            }
            $diff_time = sprintf('%.5f', microtime(true) - $t1);

            _status(1, APM_HOST . '(Memcache)', "{$this->current_host['host']}:{$this->current_host['port']}(increment)", APM_URI, NULL, APM_VIP, $diff_time);
            $diff_time_str = _debugtime($diff_time);
            _status(1, APM_HOST . '(Memcache)', $diff_time_str, "{$this->current_host['host']}:{$this->current_host['port']}(increment)", APM_URI, APM_VIP, $diff_time);
            return $bool;
        }
        return false;
    }

    /**
     * @desc   WHAT?
     * @author xing39393939@gmail.com
     * @since  2012-07-03 16:08:37
     * @throws 注意:无DB异常处理
     */
    function close()
    {
        if (is_object($this->memcacheObj) && method_exists($this->memcacheObj, 'close')) {
            $this->memcacheObj->close();
            _status(1, APM_HOST . '(Memcahe连接)', "{$this->current_host['host']}:{$this->current_host['port']}[关闭]", APM_URI);
            $_SERVER['memcache_server']["{$this->current_host['host']}:{$this->current_host['port']}"] = $this->memcacheObj = null;
            unset($this->memcacheObj, $_SERVER['memcache_server']["{$this->current_host['host']}:{$this->current_host['port']}"]);
        }
    }
}

?>