PHPAPM
======
PHP项目的监控程序，提倡面向效果编程，为优化维护项目提供数据监控服务 
APM = Application Performance Management，应用性能管理，对企业系统即时监控以实现对应用程序性能管理和故障管理的系统化的解决方案。
## Requirements
PHP，Mysql(用于记录统计数据)
## Setup
* 1，将phpapm加入到你现有PHP项目中
* 2，将phpapm/common/phpapm.sql的5张表导入到Mysql中
* 3，配置header.php，重命名header_bak.php为header.php，修改类oracleDB_config里面的数据库配置
* 4，在PHP项目中公共文件中引用header.php
* 5，加入定时任务：windows平台，运行phpapm/crontab_perminute.bat(需要编辑bat文件修改路径)；linux平台，创建crontab，定时1分钟运行phpapm/crontab_perminute.sh

## Usage
确保可以访问http://path_to_dir/phpapm/project.php即可，访问权限限制请自行加入

