#! /bin/sh
# in different distributions paths might be different; change the log, php and DOCOVA paths if required
exec @> /var/log/syslog ;

cd /var/html/www/docova ;

echo "**************************************************************" ;
echo "                  Application Agent Runner                    " ;
php bin/console docova:scheduledagents --debugmode=false ;
echo "Applicatoin agent runner complete" ;
echo "**************************************************************" ;
exit /b