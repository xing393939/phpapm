#!/bin/sh
project_pwd=`dirname $0`
source $project_pwd/phpapm/cron.inc.sh
cd $project_pwd/phpapm

while sleep 60
do
    log_minute=`date +%M`

    callact2 "/usr/local/php/bin/php crontab.php act=monitor mod=1"
    callact2 "/usr/local/php/bin/php crontab.php act=monitor_config del=1 master=yes"
    log_date=/dev/shm/`date +%Y_%m_%d_%H_%M_%S`
    touch ${log_date}_wget.log
    
    if ((${log_minute} == 30))
    then
        callact2 "/usr/local/php/bin/php crontab.php act=web_log"
        callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_group master=yes"
        callact2 "/usr/local/php/bin/php crontab.php act=report_monitor_order master=yes"
    fi
done

#usage: nohup /bin/sh -x /mnt/mesos/sandbox/scc/public/cron_docker.sh > /dev/null 2>&1 &