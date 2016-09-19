@echo off
:start
ping -n 60 127.1>NUL
set p1 = 0

::perminute
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_fix
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_config

::perhour
set /A p1 = %p1% + 1
set /A p1_mod = %p1% %% 60
if %p1_mod% == 0 (
    @echo %p1%
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=web_log
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=report_monitor_group
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=report_monitor_order
)
goto start