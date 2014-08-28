#!/bin/sh
#此文件要赋予执行权限，且让git忽略对文件权限的改变：git config core.filemode false

log_date=/dev/shm/`date +%Y_%m_%d`
rm -f ${log_date}_wget.log
#保证是单一进程在跑.
function callact()
{
    exec_pwd=`pwd`
    cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
    if ps aux | grep -v 'grep ' | grep -q "$cmd" ; then
        echo `date +"%Y-%m-%d %H:%M:%S"` ${exec_pwd} [x]PHP_Fail:$cmd >> ${log_date}_wget.log
    else
        echo `date +"%Y-%m-%d %H:%M:%S"` ${exec_pwd} call@$cmd >> ${log_date}_wget.log 2>&1 &
        nohup $cmd > /dev/null 2>&1 &
    fi
}
#保证是单一进程在跑.阻塞模式跑
function callact2()
{
    exec_pwd=`pwd`
    cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
    if ps aux | grep -v 'grep ' | grep -q "$cmd" ; then
        echo `date +"%Y-%m-%d %H:%M:%S"` ${exec_pwd} [x]PHP2_Fail:$cmd >> ${log_date}_wget.log
    else
        echo `date +"%Y-%m-%d %H:%M:%S"` ${exec_pwd} call@$cmd >> ${log_date}_wget.log 2>&1 &
        $cmd > /dev/null
    fi
}

project_pwd=`dirname $0`
cd $project_pwd

#test sh
/bin/sh test.sh

#perminute
#callact "/alidata/server/php/bin/php crontab.php act=monitor_fix"
callact "/alidata/server/php/bin/php crontab.php act=file_change"
callact2 "/alidata/server/php/bin/php crontab.php act=monitor go=1"
callact2 "/alidata/server/php/bin/php crontab.php act=sysload"
callact2 "/alidata/server/php/bin/php crontab.php act=monitor_config del=1 master=yes"
#perhour
callact2 "/alidata/server/php/bin/php crontab.php act=web_log"
callact2 "/alidata/server/php/bin/php crontab.php act=P1D_ClickStats master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=report_monitor_group master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=report_monitor_order master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=crontab_report_pinfen master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=monitor_duty master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=monitor_check master=yes"
callact2 "/alidata/server/php/bin/php crontab.php act=oci_explain master=yes"