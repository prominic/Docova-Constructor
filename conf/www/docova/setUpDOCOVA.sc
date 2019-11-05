#!/bin/bash
echo  --------------------------------------------------------------
echo                    Setup DOCOVA SE
echo  --------------------------------------------------------------


while true; do
    echo WARNING: This process will recreate your DOCOVA SE database
    echo          if it already exists. Only use this option for
    echo          brand new installations.

    read -p "Are you sure you wish to continue?" yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

echo Drop docova_db database
# Execute
php bin/console doctrine:database:drop --force >null
echo docova_db delete complete.
echo

echo Creating docova_db ...
# Execute
php bin/console doctrine:database:create >null
echo docova_db created
echo

echo Creating schema ...
# Execute
php bin/console doctrine:schema:create >null
echo Schema creation complete
echo

echo Loading datafixtures ...
# Execute
php bin/console doctrine:fixtures:load --no-interaction >null
echo Completed loading datafixture
echo

echo Installing assets
# Execute
php bin/console assets:install --no-debug >null
echo Assets installation complete
echo

echo Clearing prod cache
# Execute
php bin/console cache:clear --env=prod --no-debug --no-warmup >null
echo clear cache completed
echo

echo Clearing dev cache
# Execute
php bin/console cache:clear --env=dev --no-debug --no-warmup >null
echo clear cache completed
echo

echo -------------------------------------------------------------
echo
