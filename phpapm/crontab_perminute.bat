@echo off
:start
ping -n 60 127.1>NUL

set dir=E:\backup\dxslaw\CDN\phpapm
::perminute
::D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=monitor_fix
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=file_change
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=monitor go=1
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=sysload
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=monitor_config del=1 master=yes

::perhour
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=web_log
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=P1D_ClickStats master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=report_monitor_group master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=report_monitor_order master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=crontab_report_pinfen master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=monitor_duty master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=monitor_check master=yes
D:\wamp\bin\php\php5.4.12\php.exe %dir%\crontab.php act=oci_explain master=yes

goto start