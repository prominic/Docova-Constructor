ECHO OFF
ECHO --------- Execute elastica population command ---------

REM Change Directory to the folder containing your script
REM CD C:\inetpub\wwwroot\docova

php bin/console docova:indexdocuments --no-debug > autoindex.log

ECHO ----------------- Population is done ------------------