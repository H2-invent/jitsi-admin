echo Welcome to the installer:


echo ------------ install latest packages-------------
php composer.phar install
cp .env.sample .env.local
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo --------------------------------------------------------------------------
read -p "Want to enter Database DSN directly or should we ask you for your mysql-credentials? Type dsn or hit enter: " dsnOrCreds

if [[ "$dsnOrCreds" == "dsn" ]]
then
    echo Example DSN: sqlite3:////opt/jitsi-admin/jitsi-admin.sqlite3?mode=0666
    echo See here for documentation: https://www.doctrine-project.org/projects/doctrine1/en/latest/manual/introduction-to-connections.html
    echo Please ensure, you installed and activated needed php-modules!!!
    read -p "Enter Database DSN: " DatabaseDSN
    sed -i "s%^DATABASE_URL=.*%DATABASE_URL=${DatabaseDSN}%g" .env.local
else
    read -p "Enter the database Host: " databaseHost
    read -p "Enter the database port[3306]: " databasePort
    read -p "Enter the database name: " databaseName
    read -p "Enter the database username: " databaseUsername
    read -p"Enter the database password: " databasePassword
    databasePort=${databasePort:-3306}
    sed -i "s%^DATABASE_URL=.*%DATABASE_URL=mysql://$databaseUsername:$databasePassword@$databaseHost:$databasePort/$databaseName%g" .env.local
fi

php bin/console doctrine:mig:mig --no-interaction
php bin/console cache:clear
crontab -l > cron_bkp
echo "* * * * * php /var/www/jitsi-admin/bin/console cron:run 1>> /dev/null 2>&1" > cron_bkp
crontab cron_bkp
rm cron_bkp
echo --------------------------------------------------------------------------
echo -----------------We looking for all the other parameters-------------------
echo --------------------------------------------------------------------------
echo -------------------------------------------------------------
echo -----------------Mailer--------------------------------------
echo -------------------------------------------------------------
read -p "Enter smtp host: " smtpHost
read -p "Enter smtp port: " smtpPort
read -p "Enter smtp username: " smtpUsername
read -p "Enter smtp password: " smtpPassword

sed -i "s/<smtpHost>/$smtpHost/" .env.local
sed -i "s/<smtpPort>/$smtpPort/" .env.local
sed -i "s/<smtpUsername>/$smtpUsername/" .env.local
sed -i "s/<smtpPassword>/$smtpPassword/" .env.local

echo -------------------------------------------------------------
echo -----------------Keycloak--------------------------------------
echo -------------------------------------------------------------
read -p "Enter the base url of the Jitsi-Admin: " baseUrl
read -p "Enter the URL to keycloak with /auth at the end: " keycloakServer
read -p "Keycloak realm: " keycloakRealm
read -p "Keycloak Client Id: " keycloakClientId
read -p "Keycloak Client Secret: " keycloakClientSecret

sed -i "s%<baseUrl>%$baseUrl%" .env.local

sed -i "s%<keycloakServer>%$keycloakServer%" .env.local
sed -i "s/<keycloakRealm>/$keycloakRealm/" .env.local
sed -i "s/<keycloakClientId>/$keycloakClientId/" .env.local
sed -i "s/<keycloakClientSecret>/$keycloakClientSecret/" .env.local
echo --------------------------------------------------------------------------
echo -----------------They are many more parameters explore them by yourself---
echo --------------------------------------------------------------------------

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
chown -R www-data:www-data public/uploads/images
chmod -R 775 public/uploads/images
echo --------------------------------------------------------------------------
echo -----------------------Install NPM and Assets----------------------------
echo --------------------------------------------------------------------------
npm install
npm run build
rm -rf node_modules/
echo --------------------------------------------------------------------------
echo -----------------------Install Worker for Async Work----------------------
echo --------------------------------------------------------------------------
cp jitsi-admin_messenger.service /etc/systemd/system/jitsi-admin_messenger.service
systemctl daemon-reload
service start jitsi-admin_messenger
restart start jitsi-admin_messenger
service enable jitsi-admin_messenger
echo --------------------------------------------------------------------------
echo -----------------------Installed the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------