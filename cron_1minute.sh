#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

callact "/usr/local/php/bin/php crontab.php act=monitor_fix"
callact "/usr/local/php/bin/php crontab.php act=file_change"
callact2 "/usr/local/php/bin/php crontab.php act=monitor mod=1"
#callact2 "/usr/local/php/bin/php crontab.php act=sysload"
if [ 1 -eq $1 ] ; then
    callact2 "/usr/local/php/bin/php crontab.php act=monitor_config del=1 master=yes"
fi