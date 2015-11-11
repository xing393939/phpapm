#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

callact "/alidata/server/php/bin/php crontab.php act=monitor_fix"
callact "/alidata/server/php/bin/php crontab.php act=file_change"
for (( i=0; i<10; i=i++ )); do
    callact2 "/alidata/server/php/bin/php crontab.php act=monitor mod=$i"
done
#callact2 "/alidata/server/php/bin/php crontab.php act=sysload"
if [ 1 -eq $1 ] ; then
    callact2 "/alidata/server/php/bin/php crontab.php act=monitor_config del=1 master=yes"
fi