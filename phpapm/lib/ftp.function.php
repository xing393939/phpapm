<?php
/**
 * @desc   WHAT?
 * @author
 * @since  2012-07-23 17:08:27
 * @throws 注意:无DB异常处理
 *         _ftp('iosfile','/ftp/dir/','/home/httpd/ios/xxx.rar')   -> ftp:/ftp/dir/xxx.rar
 */
function _ftp($configName, $dir, $file)
{
    $ftp_config = new ftp_config;
    $interfaceConfig = $ftp_config->config[$configName];
    if (!$interfaceConfig)
        return false;
    //连接FTP
    $t1 = microtime(true);
    $bool = $connRes = ftp_connect($interfaceConfig['host']);
    $diff_time = sprintf('%.5f', microtime(true) - $t1);
    if ($diff_time > 3)
        _status(1, VHOST . '(BUG错误)', 'FTP超时(连接)', "{$configName}" . "@" . GET_INCLUDED_FILES, NULL, VIP, $diff_time);

    _status(1, VHOST . '(FTP)', $interfaceConfig['host'], GET_INCLUDED_FILES, NULL, VIP, $diff_time);

    $t1 = microtime(true);
    $bool = ftp_login($connRes, $interfaceConfig['user_name'], $interfaceConfig['user_pass']);
    if (!$bool)
        return false;
    if ($interfaceConfig['dir'] <> '/')
        $bool = ftp_chdir($connRes, $interfaceConfig['dir']);

    $dir_array = explode('/', $dir);
    if (count($dir_array)) {
        foreach ($dir_array as $v) {
            if (!$v)
                continue;
            ftp_mkdir($connRes, $v);
            ftp_chdir($connRes, $v);
        }
    }

    $diff_time = sprintf('%.5f', microtime(true) - $t1);
    if ($diff_time > 3)
        _status(1, VHOST . '(BUG错误)', 'FTP超时(登录切换目录)', "{$configName}" . "@" . GET_INCLUDED_FILES, NULL, VIP, $diff_time);

    $t1 = microtime(true);
    //上传文件
    ftp_pasv($connRes, true);
    $bool = ftp_put($connRes, basename($file), $file, FTP_BINARY);
    $diff_time = sprintf('%.5f', microtime(true) - $t1);
    if ($diff_time > 3)
        _status(1, VHOST . '(BUG错误)', 'FTP超时(上传)', "{$configName}" . "@" . GET_INCLUDED_FILES, NULL, VIP, $diff_time);

    $diff_time_str = _debugtime($diff_time);
    if ($diff_time < 1) {
        _status(1, VHOST . '(FTP)', '一秒内', _debugtime($diff_time), $interfaceConfig['host'] . "@" . GET_INCLUDED_FILES . VIP, $configName, $diff_time);
    } else {
        _status(1, VHOST . '(FTP)', '超时', _debugtime($diff_time), $interfaceConfig['host'] . "@" . GET_INCLUDED_FILES . VIP, $configName, $diff_time);
    }
    return ftp_close($connRes);
}
?>