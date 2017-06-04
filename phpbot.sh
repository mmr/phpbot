#!/bin/sh
SU='/usr/bin/su'
PHP='/usr/local/bin/php'
BOT='/home/phpbot/phpbot.php'
PHP_FLAGS='-d safe_mode=off -d output_buffering=off'
BOT_USER='phpbot'
BOT_LOGFILE='/home/phpbot/bot.log'
BOT_PIDFILE='/home/phpbot/bot.pid'

case "$1" in
  start)
    BOT_FLAGS="--logfile $BOT_LOGFILE --pidfile $BOT_PIDFILE --debuglevel 3 --run &"
    ;;
  stop)
    BOT_FLAGS="--stop --pidfile $BOT_PIDFILE"
    ;;
  status)
    [ -f "${BOT_PIDFILE}" ] && echo "Bot is running" || echo "Bot is not running."
    exit
    ;;
  restart)
    $0 stop && $0 start
    ;;
  *)
    echo "$(basename $0) start|stop|restart|status"
    exit
    ;;
esac
  
$SU $BOT_USER -c "$PHP $PHP_FLAGS $BOT $BOT_FLAGS"
