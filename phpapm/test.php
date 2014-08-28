<?php
include 'header.php';
echo '<pre>';
_status(1, 'v1', 'v2', 'v3', 'v4');
_status(1, 'v1', 'v2', 'v3', 'v4');

$cache = new secache;
$seg = $cache->workat('cache_data');
$cacheNum = round(microtime(1) * 10);
$cacheNumArr = range($cacheNum - 1000, $cacheNum);
foreach ($cacheNumArr as $cacheNum) {
    //var_dump($cacheNum);
    $msg_array = array();
    $cacheNumInc = $cacheNum + 0.01;
    while ($cache->fetch(md5($cacheNumInc), $msg_array)) {
        echo $cacheNumInc, '<br/>';
        //$cache->delete(md5($cacheNumInc));
        $cacheNumInc += 0.01;
    }
}

/*$cache = new secache;
$cache->fetch(md5('14086157241.04'),$value);
var_dump($value);*/
?>