#! /bin/sh
# in different distributions paths might be different; change the log, php and Symfony paths if required

cd /var/html/www/docova ;
 
php bin/console docova:indexdocuments --no-debug >> autoindex.log ;