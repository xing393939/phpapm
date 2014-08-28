#!/bin/sh
log_date=/dev/shm/test_`date +%Y_%m_%d`.log
php crontab.php act=monitor_fix mod=0 total=2 > $log_date 2>&1