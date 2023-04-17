cat << "EOF"
     _ _ _      _      _      _       _        ___         _        _ _
  _ | (_) |_ __(_)___ /_\  __| |_ __ (_)_ _   |_ _|_ _  __| |_ __ _| | |___ _ _
 | || | |  _(_-< |___/ _ \/ _` | '  \| | ' \   | || ' \(_-<  _/ _` | | / -_) '_|
  \__/|_|\__/__/_|  /_/ \_\__,_|_|_|_|_|_||_| |___|_||_/__/\__\__,_|_|_\___|_|

EOF

sudo mkdir -p /var/www

echo ""
echo ******INSTALLING DEPENDENCIES******
echo ""
sudo apt update
sudo apt install -y \
    git curl lsb-release ca-certificates apt-transport-https software-properties-common gnupg2 mysql-server \
    nginx nginx-extras\
    php php-bcmath php-fpm php-xml php-mysql php-zip php-intl php-ldap php-gd php-cli php-bz2 php-curl php-mbstring \
    php-opcache php-soap php-cgi php-dom php-simplexml
curl -sL https://deb.nodesource.com/setup_16.x | sudo bash -
sudo apt -y install nodejs

echo ""
echo ******INSTALLING JITSI-ADMIN*******
echo ""

pushd /var/www
git clone https://github.com/H2-invent/jitsi-admin.git
popd

pushd /var/www/jitsi-admin
git checkout installer

export COMPOSER_ALLOW_SUPERUSER=1
php composer.phar install --no-interaction
php composer.phar dump-autoload
cp .env.sample .env.local

php bin/console app:install
cp jitsi-admin-env /etc/systemd/jitsi-admin.conf
rm jitsi-admin-env

php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

php bin/console cache:clear
php bin/console cache:warmup

npm install
npm run build
rm -rf node_modules/

popd
pushd /var/www/jitsi-admin/nodejs
npm install
popd
pushd /var/www/jitsi-admin

echo ""
echo *******CONFIGURING SERVICES********
echo ""

crontab -l > cron_bkp
echo "* * * * * php /var/www/jitsi-admin/bin/console cron:run 1>> /dev/null 2>&1" > cron_bkp
crontab cron_bkp
rm cron_bkp

chown -R www-data:www-data var/cache
chmod -R 775 var/cache
chown -R www-data:www-data public/uploads/images
chmod -R 775 public/uploads/images

cp jitsi-admin.conf /etc/nginx/sites-enabled/
cp jitsi-admin_messenger.service /etc/systemd/system/jitsi-admin_messenger.service
cp -r nodejs /usr/local/bin/websocket
cp nodejs/config/websocket.service /etc/systemd/system/jitsi-admin-websocket.service
mkdir /var/log/websocket/

service php8.1-fpm restart
systemctl daemon-reload
service  jitsi-admin_messenger start
service  jitsi-admin_messenger restart
systemctl enable jitsi-admin_messenger
systemctl daemon-reload
service  jitsi-admin-websocket start
service  jitsi-admin-websocket restart
systemctl enable jitsi-admin-websocket

popd

cat << "EOF"
  ___         _        _ _        _                            __      _
 |_ _|_ _  __| |_ __ _| | |___ __| |  ____  _ __ __ ___ ______/ _|_  _| |
  | || ' \(_-|  _/ _` | | / -_/ _` | (_-| || / _/ _/ -_(_-(_-|  _| || | |
 |___|_||_/__/\__\__,_|_|_\___\__,_| /__/\_,_\__\__\___/__/__|_|  \_,_|_|
EOF