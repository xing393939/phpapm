PHPAPM
======
PHP项目的监控程序，提倡面向效果编程，为优化维护项目提供数据监控服务  
APM = Application Performance Management，应用性能管理，对企业系统即时监控以实现对应用程序性能管理和故障管理的系统化的解决方案。
## Requirements
PHP > 5<br />
Mysql > 5<br />
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
> */1 * * * * /path/www/site/cron_1minute.sh 1<br />
> 30  * * * * /path/www/site/cron_1hour.sh 1<br />

## Config
配置文件：header.php<br />
监控URL：APM_URI，这里自己定义，类似goods.php?id=*<br />
数据库配置：APM_DB_USERNAME，APM_DB_PASSWORD，APM_DB_TNS，APM_DB_NAME<br />
管理员帐号：APM_ADMIN_USER<br />

## Usage
查看数据可访问http://path_to_dir/phpapm/project.php<br /><br />
一，监控sql：系统默认只监控定时任务的Sql查询，若要监控自己项目的Sql查询，请在自己项目的公共数据库查询类加上监控代码，如下：
```javascript
$t1 = microtime(true);
$stmt = mysql_query($sql, $conn_db);
apm_status_sql('MY_APP', $sql, $t1, mysql_error($conn_db));
/* 其中第一第三行是新加的代码 */
```

二，监控memcache，示例如下：
$t1 = microtime(true);
$bool = $this->memcacheObj->get($key);
apm_status_api('memcache', '10.0.1.20(get)', $t1, $bool);
/* 其中第一第三行是新加的代码 */
```

三，监控其他资源调用，参考监控memcache即可<br />
四，监控业务数据：使用_status函数在程序处理业务的地方加上监控代码即可，您可以监控每天用户的登录退出次数，各终端访问首页的占比，每天用户的充值情况等等Everything！如下：<br />
> if (checkUserLogin()) {<br />
>     _status(1, '访问首页登录用户的占比', "登录用户", 'v3', 'v4');<br />
> } else {<br />
>     _status(1, '访问首页登录用户的占比', "未登录用户", 'v3', 'v4');<br />
> }<br />
> /*简单几行代码，监控后台即会展现对应的每天每小时的数据变化，比起直接使用sql查询更简单直观*/<br />

