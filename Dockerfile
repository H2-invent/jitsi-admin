FROM thecodingmachine/php:7.4.27-v4-apache-node16
ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV TZ=Europe/Berlin
ENV CRON_USER_1=root
ENV CRON_SCHEDULE_1="* * * * *"
ENV CRON_COMMAND_1="php /var/www/html/bin/console cron:run"
USER root
RUN usermod -a -G www-data docker
COPY . /var/www/html
RUN npm install
RUN composer install
RUN mkdir -m 777 -p public/build
RUN npm run build
RUN rm -rf node_modules/
RUN chmod -R 775 public/build
RUN mkdir -p var/cache
RUN chown -R www-data:www-data var
RUN chmod -R 777 var
RUN chown -R www-data:www-data public/uploads/
RUN chmod -R 775 public/uploads/
RUN apt update
RUN apt install supervisor -y
COPY jitsi-admin_messenger_docker.service /etc/supervisor/conf.d/
RUN supervisorctl reread
RUN supervisorctl update
RUN supervisorctl start messenger-consume:*
USER docker