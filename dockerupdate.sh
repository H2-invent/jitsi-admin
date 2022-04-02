echo --------------Schutdown Apache------------------------------------------
#service apache2 stop


echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo ----------------Please Backup your database-------------------------------
echo --------------------------------------------------------------------------
php bin/console cache:clear
php bin/console doctrine:sche:up --force --no-interaction
#php bin/console doctrine:migrations:migrate --no-interaction
echo --------------------------------------------------------------------------
echo -----------------Clear Cache----------------------------------------------
echo --------------------------------------------------------------------------
php bin/console cache:clear
php bin/console cache:warmup
echo --------------------------------------------------------------------------
echo ----------------Setting Permissin-----------------------------------------
echo --------------------------------------------------------------------------
chown -R www-data:www-data var/cache
chmod -R 775 var/cache
chown -R www-data:www-data public/uploads
chmod -R 775 public/uploads
echo --------------------------------------------------------------------------
echo -----------------------Updated the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------



