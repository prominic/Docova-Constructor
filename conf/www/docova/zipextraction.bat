@echo off

REM Change Directory to the folder containing your script
CD C:\inetpub\wwwroot\docova

REM Execute
php bin/console docova:extractzip %1 %2 > extract.log

exit /b