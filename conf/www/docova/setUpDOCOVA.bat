ECHO OFF
SETLOCAL
ECHO --------------------------------------------------------------
ECHO 			Setup DOCOVA SE                       
ECHO --------------------------------------------------------------
:PROMPT
ECHO WARNING: This process will recreate your DOCOVA SE database
ECHO          if it already exists. Only use this option for 
ECHO          brand new installations.
SET /P AREYOUSURE=Are you sure you want to continue (Y/[N])?
IF /I "%AREYOUSURE%" NEQ "Y" GOTO END
ECHO .
ECHO *** Any existing DOCOVA SE database and data is going to be deleted! ***
SET /P AREYOUSURE2=Are you really sure you want to continue (Y/[N])?
IF /I "%AREYOUSURE2%" NEQ "Y" GOTO END
ECHO Drop docova_db database
REM Execute
php bin/console doctrine:database:drop --force
ECHO docova_db delete complete. 
ECHO .     

ECHO Creating docova_db ... 
REM Execute
php bin/console doctrine:database:create
ECHO docova_db created 
ECHO .              

ECHO Creating schema ... 
REM Execute
php bin/console doctrine:schema:create
ECHO Schema creation complete 
ECHO .                  
 
ECHO Loading datafixtures ...
REM Execute
php bin/console doctrine:fixtures:load --no-interaction
ECHO Completed loading datafixture
ECHO .                
      
ECHO Installing assets
REM Execute
php bin/console assets:install --no-debug
ECHO Assets installation complete
ECHO .                   

ECHO Clearing prod cache
REM Execute
php bin/console cache:clear --env=prod --no-debug --no-warmup
ECHO clear cache completed
ECHO .                   

ECHO Clearing dev cache
REM Execute
php bin/console cache:clear --env=dev --no-debug --no-warmup
ECHO clear cache completed
ECHO .                   

ECHO -------------------------------------------------------------
ECHO .
:END
ENDLOCAL