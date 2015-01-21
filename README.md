PHPAPM
======
PHP项目的监控程序，提倡面向效果编程，为优化维护项目提供数据监控服务  
APM = Application Performance Management，应用性能管理，对企业系统即时监控以实现对应用程序性能管理和故障管理的系统化的解决方案。
## Requirements
PHP > 5，Mysql或Oracle(用于记录统计数据)
## Setup
> 1，将phpapm加入到你现有PHP项目中
> 2，将phpapm/common/phpapm.sql的5张表导入到Mysql中
> 3，配置header.php
> 4，在PHP项目中公共文件中引用header.php
> 5，加入定时任务：
> windows平台：运行phpapm/crontab_perminute.bat(需要编辑bat文件修改路径)
> linux平台：1，安装php扩展sysvmsg，参考[教程](http://www.banghui.org/2527.html)；2，赋给crontab_perminute.sh可执行权限再创建crontab，定时1分钟运行crontab_perminute.sh

## Config
> 配置文件：header.php
> 监控URL：APM_URI，这里自己定义，类似goods.php?id=*
> 数据库配置：APM_DB_USERNAME，APM_DB_PASSWORD，APM_DB_TNS，APM_DB_NAME
> 管理员帐号：APM_ADMIN_USER

## Usage
确保可以访问http://path_to_dir/phpapm/project.php即可，访问权限限制请自行加入

