#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

#callact2 "/alidata/server/php/bin/php crontab.php act=web_log"
if [ 1 -eq $1 ] ; then
    callact2 "/alidata/server/php/bin/php crontab.php act=P1D_ClickStats master=yes"
    callact2 "/alidata/server/php/bin/php crontab.php act=report_monitor_group master=yes"
    callact2 "/alidata/server/php/bin/php crontab.php act=report_monitor_order master=yes"
    callact2 "/alidata/server/php/bin/php crontab.php act=crontab_report_pinfen master=yes"
    callact2 "/alidata/server/php/bin/php crontab.php act=monitor_duty master=yes"
    #callact2 "/alidata/server/php/bin/php crontab.php act=monitor_check master=yes"
    callact2 "/alidata/server/php/bin/php crontab.php act=oci_explain master=yes"
fi