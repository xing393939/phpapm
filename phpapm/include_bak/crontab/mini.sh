#!/bin/sh
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


#���TAG��Ӧ�İ汾�ļ�.���ҷ��������Ի�������ȥ.
php crontab.php act=tags model_id=417503900  exec="/usr/local/php/bin/php crontab.php" test_rsync="10.1.20.42::disk/project_lib.com";
