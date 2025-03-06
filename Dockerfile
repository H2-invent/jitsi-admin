FROM thecodingmachine/php:8.2-v4-apache-node20
ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV PHP_EXTENSION_BCMATH=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV STARTUP_COMMAND_1="php bin/console cache:clear"
ENV STARTUP_COMMAND_2="php bin/console doctrine:mig:mig --no-interaction"
ENV STARTUP_COMMAND_3="php bin/console app:system:repair"
ENV STARTUP_COMMAND_4="php bin/console cache:clear"


ENV TZ=Europe/Berlin
USER root
RUN usermod -a -G www-data docker
#Do npm install

COPY . /var/www/html
RUN npm install
USER docker
RUN composer install --no-scripts
USER root
#do npm build
RUN mkdir var
RUN chmod -R 777 var/
RUN chown -R docker:docker var/
RUN chown -R docker:docker public/uploads/
RUN chown -R docker:docker public/theme/
RUN chown -R docker:docker theme/
RUN chown -R  docker:docker data/
RUN mkdir -m 777 -p public/build
RUN php bin/console cache:clear
RUN php bin/console cache:warmup
RUN npm run build
RUN rm -rf node_modules/
#copy all the rest of the app


USER docker