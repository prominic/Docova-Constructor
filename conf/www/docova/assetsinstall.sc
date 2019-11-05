#! /bin/sh
cd /var/html/www/docova ;
php bin/console docova:appassetsinstall $1 --no-debug >> null;