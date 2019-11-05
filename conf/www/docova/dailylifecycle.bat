ECHO OFF
ECHO -------------- Execute Daily Life Cycle ---------------

REM Change Directory to the folder containing your script
CD C:\inetpub\wwwroot\docova

REM Execute
php bin/console docova:dailylifecycle

ECHO -------------- Daily Life Cycle is done ---------------
ECHO --------------- Execute Daliy Cleanup -----------------

REM Execute
php bin/console docova:dailycleanup

ECHO --------------- Daliy Cleanup is done -----------------
ECHO --------------- Execute App Cleanup -----------------

REM Execute
php bin/console docova:appcleanup

ECHO --------------- App Cleanup is done -----------------
ECHO -------------- Execute Daliy Archiving ----------------

REM Execute
php bin/console docova:archiveperlibrary

ECHO -------------- Daliy Archiving is done ----------------