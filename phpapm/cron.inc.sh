#!/bin/sh

log_date=/dev/shm/`date +%Y_%m_%d`

#无阻塞的跑
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

#阻塞的跑，没跑完下面的cron不能跑
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

#wget一个网址,保证是单一进程在跑.
function callurl()
{
        exec_pwd=`pwd`
        cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        if ps aux |  grep -v 'grep ' | grep -q "$cmd" ; then
                echo `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd [x]WGET_Fail:$cmd >> ${log_date}_wget.log
        else
                nohup wget $cmd  -O /dev/null > /dev/null  >> ${log_date}_wget.log   2>&1 &
        fi
}

#wget一个网址,阻塞模式跑
function callurl2()
{
        cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        if ps aux |  grep -v 'grep ' | grep -q "$cmd" ; then
                echo `date +"%Y-%m-%d %H:%M:%S"`[x]WGET2_Fail:$cmd >> ${log_date}_wget.log
        else
                wget $cmd -O /dev/null >> ${log_date}_wget.log   2>&1
        fi
}