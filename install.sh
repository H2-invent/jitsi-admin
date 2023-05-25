#!/bin/bash
cat << "EOF"
     _ _ _      _      _      _       _        ___         _        _ _
  _ | (_) |_ __(_)___ /_\  __| |_ __ (_)_ _   |_ _|_ _  __| |_ __ _| | |___ _ _
 | || | |  _(_-< |___/ _ \/ _` | '  \| | ' \   | || ' \(_-<  _/ _` | | / -_) '_|
  \__/|_|\__/__/_|  /_/ \_\__,_|_|_|_|_|_||_| |___|_||_/__/\__\__,_|_|_\___|_|


EOF
BRANCH=${1:-master}
sudo mkdir -p /var/www

echo ""
echo ******INSTALLING DEPENDENCIES******
echo ""
sudo apt update
sudo service apache2 stop
sudo apt-get purge apache2 apache2-utils apache2.2-bin apache2-common php php-*
sudo apt-get autoremove
sudo apt install -y lsb-release gnupg2 ca-certificates apt-transport-https software-properties-common
sudo add-apt-repository ppa:ondrej/php

sudo apt install -y \
    git curl lsb-release ca-certificates apt-transport-https software-properties-common gnupg2 mysql-server \
    nginx nginx-extras\
    php8.2 php8.2-{bcmath,fpm,xml,mysql,zip,intl,ldap,gd,cli,bz2,curl,mbstring,opcache,soap,cgi,dom,simplexml}
curl -sL https://deb.nodesource.com/setup_18.x | sudo bash -
sudo apt -y install nodejs

clear

echo ""
echo ******INSTALLING JITSI-ADMIN*******
echo ""

pushd /var/www
[ ! -d "/var/www/jitsi-admin" ] && git clone https://github.com/H2-invent/jitsi-admin.git

popd

pushd /var/www/jitsi-admin
git -C /var/www/jitsi-admin checkout $BRANCH
git -C /var/www/jitsi-admin reset --hard
git -C /var/www/jitsi-admin pull

export COMPOSER_ALLOW_SUPERUSER=1
php composer.phar install --no-interaction
php composer.phar dump-autoload
cp -n .env.sample .env.local

sudo mysql -e "CREATE USER 'jitsiadmin'@'localhost' IDENTIFIED  BY 'jitsiadmin';"
sudo mysql -e "GRANT ALL PRIVILEGES ON jitsi_admin.* TO 'jitsiadmin'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

php bin/console app:install

php bin/console cache:clear

php bin/console doctrine:database:create --if-not-exists --no-interaction

php bin/console doctrine:migrations:migrate --no-interaction

php bin/console cache:clear

php bin/console cache:warmup
php bin/console app:system:repair

clear
echo ""
echo *******Build JS and CSS********
echo ""
npm install
npm run build
rm -rf node_modules/

clear
echo ""
echo *******Build Webesocket********
echo ""
popd
pushd /var/www/jitsi-admin/nodejs
npm install
popd

clear

pushd /var/www/jitsi-admin
echo ""
echo *******CONFIGURING SERVICES********
echo ""

crontab -l > cron_bkp
echo "* * * * * php /var/www/jitsi-admin/bin/console cron:run 1>> /dev/null 2>&1" > cron_bkp
crontab cron_bkp
rm cron_bkp

chown -R www-data:www-data var/
chown -R www-data:www-data public/
chown -R www-data:www-data theme/



cp installer/nginx.conf /etc/nginx/sites-enabled/jitsi-admin.conf
rm /etc/nginx/sites-enabled/default
cp installer/jitsi-admin_messenger.service /etc/systemd/system/jitsi-admin_messenger.service
cp installer/jitsi-admin.conf /etc/systemd/system/jitsi-admin.conf

cp -r nodejs /usr/local/bin/websocket
cp installer/jitsi-admin_websocket.service /etc/systemd/system/jitsi-admin_websocket.service
mkdir /var/log/websocket/


service php*-fpm restart
service nginx restart

systemctl daemon-reload
service  jitsi-admin* stop

service  jitsi-admin_messenger start
service  jitsi-admin_messenger restart

systemctl enable jitsi-admin_messenger

systemctl daemon-reload

service  jitsi-admin_websocket start
service  jitsi-admin_websocket restart

systemctl enable jitsi-admin_websocket


popd

cat << "EOF"
  ___         _        _ _        _                            __      _
 |_ _|_ _  __| |_ __ _| | |___ __| |  ____  _ __ __ ___ ______/ _|_  _| |
  | || ' \(_-|  _/ _` | | / -_/ _` | (_-| || / _/ _/ -_(_-(_-|  _| || | |
 |___|_||_/__/\__\__,_|_|_\___\__,_| /__/\_,_\__\__\___/__/__|_|  \_,_|_|
EOF

