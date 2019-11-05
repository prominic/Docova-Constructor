#! /bin/sh
# in different distributions paths might be different; change the log, php and Symfony paths if required
exec @> /var/log/syslog ;

cd /var/html/www/docova ;
 
echo "**************************************************************" ; 
echo "*          start time: $(date)          *" ;
echo "**************************************************************" ;
echo "              CRON Task: DOCOVA Daily Life Cycle              " ;
php bin/console docova:dailylifecycle --env=prod ;
echo "daily life cycle task completed." ;
echo "--------------------------------------------------------------" ;

echo "               CRON Task: DOCOVA Daily Cleanup                " ;
php bin/console docova:dailycleanup --env=prod ;
echo "daily cleanup task completed." ;
echo "--------------------------------------------------------------" ;

echo "                CRON Task: DOCOVA App Cleanup                 " ;
php bin/console docova:appcleanup --env=prod ;
echo "app cleanup task completed." ;
echo "--------------------------------------------------------------" ;

echo "              CRON Task: DOCOVA Daily Archiving               " ;
php bin/console docova:archiveperlibrary --env=prod ;
echo "daily archive task completed." ;
echo "--------------------------------------------------------------" ;

echo "**************************************************************" ;
echo "*            end time: $(date)            *" ;
echo "**************************************************************" ;
