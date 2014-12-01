#!/bin/sh
source /home/webid/.bash_profile
if [ -f /usr/local/php5_nginx/bin/php ] ; then
    export PATH=$PATH:/home/httpd:/usr/local/php5_nginx/bin
else
    export PATH=$PATH:/home/httpd:/usr/local/php/bin
fi
export ORACLE_BASE=/home/oracle
export ORACLE_SID=pps	
export ORACLE_HOME=/home/oracle/product/10.2.0
export PATH=$PATH:$ORACLE_HOME/bin
export NLS_LANG="Simplified Chinese_china".ZHS16GBK

#日志文件的存储位置.
log_date=/home/webid/logs/`date +%Y_%m_%d`
if [ ! -d "/home/webid/logs/" ]; then
    mkdir "/home/webid/logs/"
fi

#用PHP来获取网页地址,调用_status进行统计
function phpwget()
{
        exec_pwd=`pwd`
        cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        if ps aux |  grep -v 'grep ' | grep -q "$cmd" ; then
                echo `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd [x]PHPWGET_Fail:$cmd >> ${log_date}_wget.log
        else
                echo  `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd phpwget@$cmd to $2>> ${log_date}_wget.log
                nohup php project.php act=wget url="$cmd" file="$2" > /dev/null  2>&1 &
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


#wget一个网址,保证是单一进程在跑.
function callact()
{
        exec_pwd=`pwd`
        cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        cmd2=`echo $2 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        if ps aux |  grep -v 'grep ' | grep -q "$cmd" ; then
                echo `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd [x]PHP_Fail:$cmd >> ${log_date}_wget.log
        else
                echo  `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd call@$cmd>> ${log_date}_wget.log
                is_exist_legth=${#cmd2}
                if [ $is_exist_legth -gt 0 ] ; then
                    nohup $cmd >>$cmd2   2>&1 &
                else
                    nohup $cmd >/dev/null    2>&1 &
                fi
        fi
}

#wget一个网址,保证是单一进程在跑.阻塞模式跑
function callact2()
{
        exec_pwd=`pwd`
        cmd=`echo $1 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        cmd2=`echo $2 |sed -e 's/\(^ *\)//' -e 's/\( *$\)//' `
        if ps aux |  grep -v 'grep ' | grep -q "$cmd" ; then
                echo `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd [x]PHP_Fail:$cmd >> ${log_date}_wget.log
        else
                echo  `date +"%Y-%m-%d %H:%M:%S"` $exec_pwd call@$cmd>> ${log_date}_wget.log
                is_exist_legth=${#cmd2}
                if [ $is_exist_legth -gt 0 ] ; then
                    $cmd >>$cmd2
                else
                    $cmd >/dev/null
                fi
        fi
}


############################每小时跑一次############################################

#以下是示例

#30 */1 * * *   /home/httpd/api.tuijian.pps.tv/crontab/monitorphp_pre_1hour.sh api.tuijian.pps.tv gz 1
#*/1 * * * *  /home/httpd/api.tuijian.pps.tv/crontab/monitorphp_pre_1min.sh  api.tuijian.pps.tv 2 1


#*/1 * * * * /home/httpd/api.tuijian.pps.tv/crontab/pre_1min.sh
#*/10 * * * * /home/httpd/api.tuijian.pps.tv/crontab/pre_10min.sh
#30 * * * * /home/httpd/api.tuijian.pps.tv/crontab/pre_1hour.sh
#3 4 * * * /home/httpd/api.tuijian.pps.tv/crontab/pre_1day.sh




