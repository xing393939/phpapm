#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

callact2 "/usr/local/php/bin/php crontab.php act=web_log"
if [ 1 -eq $1 ] ; then
    callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_group master=yes"
    callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_order master=yes"
fi