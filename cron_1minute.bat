@echo off
:start
ping -n 60 127.1>NUL
set p1 = 0

::perminute
::D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_fix
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=file_change
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=sysload
D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_config del=1 master=yes

::perhour
set /A p1 = %p1% + 1
set /A p1_mod = %p1% %% 60
if %p1_mod% == 0 (
    @echo %p1%
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=web_log
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=P1D_ClickStats master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=report_monitor_group master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=report_monitor_order master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=crontab_report_pinfen master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_duty master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=monitor_check master=yes
    D:\wamp\bin\php\php5.4.12\php.exe %cd%\phpapm\crontab.php act=oci_explain master=yes
)
goto start