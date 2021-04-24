echo --------------Schutdown Apache------------------------------------------
echo Welcome to the installer:


echo ------------ install latest packages-------------
php composer.phar install
cp .env.sample .env.local
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo --------------------------------------------------------------------------
read -p "Enter the database Host:" databaseHost
read -p "Enter the database port[3306]:" databasePort
databasePort=${databasePort:-3306}
read -p "Enter the database name:" databaseName
read -p "Enter the database username:" databaseUsername
read -p"Enter the database password:" databasePassword

sed -i "s/<user>/$databaseUsername/" .env.local
sed -i "s/<password>/$databasePassword/" .env.local
sed -i "s/<server>/$databaseHost/" .env.local
sed -i "s/<databasePort>/$databasePort/" .env.local
sed -i "s/<database>/$databaseName/" .env.local

php bin/console doctrine:schema:create
php bin/console cache:clear
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:version --add --all
echo --------------------------------------------------------------------------
echo -----------------We looking for all the other parameters-------------------
echo --------------------------------------------------------------------------
echo -------------------------------------------------------------
echo -----------------Mailer--------------------------------------
echo -------------------------------------------------------------
read -p "Enter smtp host:" smtpHost
sed -i "s/<smtpHost>/$smtpHost/" .env.local
read -p"Enter smtp port:" smtpPort
sed -i "s/<smtpPort>/$smtpPort/" .env.local
read -p "Enter smtp username:" smtpUsername
sed -i "s/<smtpUsername>/$smtpUsername/" .env.local
read -p "Enter smtp password:" smtpPassword
sed -i "s/<smtpPassword>/$smtpPassword/" .env.local
read -p "Enter SMTP encrytion tls/ssln/none:" smtpEncryption
sed -i "s/<smtpEncryption>/$smtpEncryption/" .env.local
echo -------------------------------------------------------------
echo -----------------Keycloak--------------------------------------
echo -------------------------------------------------------------
read -p "Enter the base url of the Jits-admin:" baseUrl
sed -i "s/<baseUrl>/$baseUrl/" .env.local
read -p "Enter the server of the keycloak with /auth at the and" keycloakServer
sed -i "s/<keycloakServer>/$keycloakServer/" .env.local
read -p "Keycloak realm:" keycloakRealm
sed -i "s/<keycloakRealm>/$keycloakRealm/" .env.local
read -p "Keycloak Client Id" keycloakClientId
sed -i "s/<keycloakClientId>/$keycloakClientId/" .env.local
read -p "Keycloak Client Secret" keycloakClientSecret
sed -i "s/<keycloakClientSecret>/$keycloakClientSecret/" .env.local
echo --------------------------------------------------------------------------
echo -----------------They are many more parameter explore them by yourself----
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



