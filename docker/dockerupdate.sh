#!/usr/bin/env bash
echo --------------Schutdown Apache------------------------------------------
#service apache2 stop

APP=/var/www/html
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo ----------------Please Backup your database-------------------------------
echo --------------------------------------------------------------------------
php $APP/bin/console cache:clear
php $APP/bin/console doctrine:mig:mig --no-interaction
php $APP/bin/console app:system:repair
#php bin/console doctrine:migrations:migrate --no-interaction
echo --------------------------------------------------------------------------
echo -----------------Clear Cache----------------------------------------------
echo --------------------------------------------------------------------------
php $APP/bin/console cache:clear
php $APP/bin/console cache:warmup
echo --------------------------------------------------------------------------
echo -----------------------Updated the Jitsi-Admin correct--------------------
echo --------------------------------------------------------------------------



