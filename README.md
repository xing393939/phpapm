PHPAPM
======
PHP项目的监控程序，提倡面向效果编程，为优化维护项目提供数据监控服务  
APM = Application Performance Management，应用性能管理，对企业系统即时监控以实现对应用程序性能管理和故障管理的系统化的解决方案。
## Requirements
PHP > 5，Mysql或Oracle(用于记录统计数据)
## Setup
一，将phpapm加入到你现有PHP项目中<br />
二，将phpapm/common/phpapm.sql的5张表导入到Mysql中<br />
三，配置header.php<br />
四，在PHP项目中公共文件中引用header.php<br />
五，加入定时任务：<br />
windows平台：运行phpapm/crontab_perminute.bat(需要编辑bat文件修改路径)<br />
linux平台：<br />
1，安装php扩展sysvmsg，参考[教程](http://www.banghui.org/2527.html)；<br />
2，增加消息队列的容量：<br />
> echo 8384000 > /proc/sys/kernel/msgmnb<br />
> echo 41920 > /proc/sys/kernel/msgmax<br />
> echo 30 > /proc/sys/kernel/msgmni<br />

3，赋给cron_1hour.sh、cron_1minute.sh可执行权限再创建crontab，如下：<br />
定时1分钟运行cron_1minute.sh，定时1小时运行cron_1hour.sh<br />
> /path/www/site/cron_1minute.sh 1<br />
> /path/www/site/cron_1hour.sh 1<br />

## Config
配置文件：header.php<br />
监控URL：APM_URI，这里自己定义，类似goods.php?id=*<br />
数据库配置：APM_DB_USERNAME，APM_DB_PASSWORD，APM_DB_TNS，APM_DB_NAME<br />
管理员帐号：APM_ADMIN_USER<br />

## Usage
确保可以访问http://path_to_dir/phpapm/project.php即可，访问权限限制请自行加入

