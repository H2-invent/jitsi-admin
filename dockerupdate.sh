echo --------------Schutdown Apache------------------------------------------
#service apache2 stop

APP=/var/www/html
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo ----------------Please Backup your database-------------------------------
echo --------------------------------------------------------------------------
php $APP/bin/console cache:clear
php $APP/bin/console doctrine:mig:mig --no-interaction
#php bin/console doctrine:migrations:migrate --no-interaction
echo --------------------------------------------------------------------------
echo -----------------Clear Cache----------------------------------------------
echo --------------------------------------------------------------------------
php $APP/bin/console cache:clear
php $APP/bin/console cache:warmup
echo --------------------------------------------------------------------------
echo ----------------Setting Permissin-----------------------------------------
echo --------------------------------------------------------------------------
chown -R www-data:www-data $APP/public/uploads
chmod -R 775 $APP/public/uploads
echo --------------------------------------------------------------------------
echo -----------------------Updated the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------



