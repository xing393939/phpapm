#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

while sleep 60
do
    log_minute=`date +%M`

    callact2 "/usr/local/php/bin/php crontab.php act=monitor_fix"
    callact2 "/usr/local/php/bin/php crontab.php act=monitor mod=1"
    callact2 "/usr/local/php/bin/php crontab.php act=monitor_config"

    if ((${log_minute} == 30))
    then
        callact2 "/usr/local/php/bin/php crontab.php act=web_log"
        callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_group"
        callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_order"
    fi
done

#usage: nohup /bin/sh -x /mnt/mesos/sandbox/scc/public/cron_docker.sh > /dev/null 2>&1 &