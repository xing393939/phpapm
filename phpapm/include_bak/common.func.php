<?php
/**
 * @desc   WHAT?
 * @author
 * @since  2013-02-01 10:41:07
 * @throws 注意:无DB异常处理
 */
function _file_get_contents($filename)
{
    $tt1 = microtime(true);
    $data = file_get_contents($filename);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (strpos($filename, '/vodguide/html/') === false) {
        $status_filename = $filename;
    } else {
        $status_filename = dirname($filename);
    }
    _status(1, APM_HOST . '(BUG错误)', '文件读写', APM_VIP . APM_PROJECT, "{$status_filename}@file:" . APM_URI, APM_VIP, $diff_time);
    return $data;
}

/**
 * @desc   WHAT?
 * @author
 * @since  2013-02-01 10:41:07
 * @throws 注意:无DB异常处理
 */
function _file_put_contents($filename, $data)
{
    $tt1 = microtime(true);
    $int = file_put_contents($filename, $data);
    $diff_time = sprintf('%.5f', microtime(true) - $tt1);
    if (strpos($filename, '/vodguide/html/') === false) {
        $status_filename = $filename;
    } else {
        $status_filename = dirname($filename);
    }
    _status(1, APM_HOST . '(BUG错误)', '文件读写', APM_VIP . APM_PROJECT, "{$status_filename}@file:" . APM_URI, APM_VIP, $diff_time);
    return $int;
}

/**
 * @desc   WHAT?
 * @author
 * @since  2012-06-17 23:04:10
 * @throws 注意:无Db异常处理
 */
function _curl(&$chinfo, $url, $post_data = null, $config = array(), $upload_file = array())
{
    settype($config, 'array');
    $ch = curl_init();
    $chinfo = array();
    if (substr($url, 0, 5) == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_ENCODING, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Expect:',
        'Accept-Encoding:gzip,deflate,sdch',
        'User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:2.0) Gecko/20100101 Firefox/4.0' . trim(APM_URI),
        "Referer:{$url}"
    ));
    foreach ($config as $k => $v)
        curl_setopt($ch, $k, $v);
    if ($post_data) {
        if (function_exists('http_build_query'))
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        else
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    if ($upload_file)
        curl_setopt($ch, CURLOPT_POSTFIELDS, (array)$upload_file + (array)$post_data);

    $curl_error_tmp = $curl_error = NULL;
    $total_time = $i = 0;
    while (!$chinfo['http_code'] && $i <= 0) {
        $curl_data = curl_exec($ch);
        $curl_error_tmp = curl_error($ch);
        if ($curl_error_tmp)
            $curl_error = $curl_error_tmp;
        $chinfo = curl_getinfo($ch);
        $i++;
        $total_time += $chinfo['total_time'];
    }
    curl_close($ch);
    $url_path = explode('?', $chinfo['url']);
    $_SERVER['last_curl_info'][$url_path[0]] = $chinfo['url'];
    $_SERVER['last_curl_info_num']++;
    $chinfo['total_time'] = $total_time;

    $url_arr = parse_url($url);
    $url_arr_list = explode('.', $url_arr['host']);
    $url_arr_list_str = $url_arr_list[count($url_arr_list) - 2] . '.' . $url_arr_list[count($url_arr_list) - 1];

    $debug_backtrace_str = NULL;
    foreach (debug_backtrace() as $vv)
        $debug_backtrace_str .= "line:({$vv['line']}){$vv['function']}@file:{$vv['file']}\n";
    //
    if ($chinfo['http_code'] != '200' && $chinfo['http_code'][0] != '3') {
        _status(1, APM_HOST . '(BUG错误)', "网址抓取", "{$url_arr['host']}{$url_arr['path']}({$chinfo['http_code']})err:" . $curl_error, APM_URI . "\n{$chinfo['url']}\n{$debug_backtrace_str}", APM_VIP, $total_time);
    } else
        _status(1, APM_HOST . '(网址抓取)', $url_arr_list_str, "{$url_arr['host']}/{$url_arr['path']} ({$chinfo['http_code']})", APM_URI . "\n{$debug_backtrace_str}", APM_VIP, $total_time);
    //超时错误记录
    if ($total_time < 1) {
        _status(1, APM_HOST . '(网址抓取)', '一秒内', _debugtime($total_time), $url_arr['host'] . "{$url_arr['path']} ({$chinfo['http_code']})" . $curl_error, APM_URI . "\n{$debug_backtrace_str}", $total_time);
    } else {
        _status(1, APM_HOST . '(网址抓取)', '超时', _debugtime($total_time), $url_arr['host'] . "{$url_arr['path']} ({$chinfo['http_code']})" . $curl_error, APM_URI . "\n{$debug_backtrace_str}", $total_time);
    }
    return $curl_data;
}

/**
 * @param $data
 * @param $key
 * @param $encodeing
 * @author
 */
function _iconv($data, $key, $encodeing)
{
    if (function_exists('mb_convert_encoding')) {
        if ((!empty($data) && !is_numeric($data)))
            $data = mb_convert_encoding($data, $encodeing[1], $encodeing[0]);
    } else
        if ((!empty($data) && !is_numeric($data)))
            $data = iconv($encodeing[0], $encodeing[1], $data);
    return $data;
}

/**
 * @param $data
 * @return array|object|string
 * @author
 */
function gbktoutf8($data)
{
    if (is_array($data)) {
        return array_map('gbktoutf8', $data);
    } elseif (is_object($data)) {
        return array_map('gbktoutf8', get_object_vars($data));
    } else {
        return _iconv($data, NULL, array('gbk', 'utf-8'));
    }
}

/**
 * @param $data
 * @return array|object|string
 * @author
 */
function utf8togbk($data)
{
    if (is_array($data)) {
        return array_map('utf8togbk', $data);
    } elseif (is_object($data)) {
        return array_map('utf8togbk', get_object_vars($data));
    } else {
        return _iconv($data, NULL, array('utf-8', 'gbk'));
    }
}
?>