echo --------------Schutdown Apache------------------------------------------
#service apache2 stop


echo ------------ install latest packages-------------
php composer.phar install
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo --------------------------------------------------------------------------
php bin/console doctrine:schema:create
php bin/console cache:clear
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:version --add --all
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
echo --------------------------------------------------------------------------
echo ----------------Create Upload Folder and Set permissions------------------
echo --------------------------------------------------------------------------
mkdir public/uploads
mkdir public/uploads/images
chown -R www-data:www-data public/uploads/images
chmod -R 775 public/uploads/images
echo --------------------------------------------------------------------------
echo -----------------------Install NPM and Assets----------------------------
echo --------------------------------------------------------------------------
npm install
npm run build
echo --------------------------------------------------------------------------
echo -----------------------Installed the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------



