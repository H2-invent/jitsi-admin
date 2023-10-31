FROM thecodingmachine/php:8.2-v4-apache-node18
ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV STARTUP_COMMAND_1="php bin/console cache:clear"
ENV STARTUP_COMMAND_2="php bin/console doctrine:mig:mig --no-interaction"
ENV STARTUP_COMMAND_3="php bin/console app:system:repair"
ENV STARTUP_COMMAND_4="php bin/console cache:clear"
ENV STARTUP_COMMAND_5="php bin/console cache:warmup"

ENV TZ=Europe/Berlin
USER root
RUN usermod -a -G www-data docker
#Do npm install
COPY package.json /var/www/html
COPY package-lock.json /var/www/html
COPY webpack.config.js /var/www/html
RUN npm install
#do npm build
COPY assets /var/www/html/assets
COPY public /var/www/html/public
RUN mkdir -m 777 -p public/build
RUN npm run build
RUN rm -rf node_modules/
#copy all the rest of the app
COPY . /var/www/html
#install all php dependencies
USER docker
RUN composer install
USER root
#do all the directory stuff
RUN chown -R docker:docker var
RUN chown -R docker:docker public/uploads/
RUN chown -R docker:docker public/theme/
RUN chown -R docker:docker theme/
USER docker