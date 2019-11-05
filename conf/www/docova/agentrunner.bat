ECHO OFF
ECHO -------------- Execute Daily Life Cycle ---------------

REM Change Directory to the folder containing your script
CD C:\inetpub\wwwroot\docova

REM Execute
php bin/console  docova:scheduledagents --debugmode=false > agents.log

exit /b