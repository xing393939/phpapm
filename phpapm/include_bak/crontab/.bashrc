# .bashrc

# Source global definitions
if [ -f /etc/bashrc ]; then
        . /etc/bashrc
fi

# User specific aliases and functions

export PS1="\[\e[36;1m\]\u@\[\e[32;1m\]\H \w> \[\e[0m\]"
if [ -f /usr/local/php5_nginx/bin/php ] ; then
    export PATH=$PATH:/home/httpd:/usr/local/php5_nginx/bin
else
    export PATH=$PATH:/home/httpd:/usr/local/php/bin
fi

# alias
if [ -f "/home/httpd/apache.pps.tv/crontab/jc.php" ]; then
    alias jc='php /home/httpd/apache.pps.tv/crontab/jc.php act=php_ini ; php /home/httpd/apache.pps.tv/crontab/jc.php act=db_ini; php /home/httpd/apache.pps.tv/crontab/jc.php act=host_ini; php /home/httpd/apache.pps.tv/crontab/jc.php act=web_ini; php /home/httpd/apache.pps.tv/crontab/jc.php act=config_ini;php /home/httpd/apache.pps.tv/crontab/jc.php act=log_ini;'
else
    alias jc='php /home/webid/jc.php act=php_ini ; php /home/webid/jc.php act=db_ini; php /home/webid/jc.php act=host_ini; php /home/webid/jc.php act=web_ini; php /home/webid/jc.php act=config_ini;php /home/webid/jc.php act=log_ini;'
fi
alias js='jc'