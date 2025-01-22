ARG PHP_IMAGE_VERSION=3.20.6
FROM thecodingmachine/php:8.3-v4-fpm-node22 AS builder
ARG VERSION

ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV PHP_EXTENSION_BCMATH=1
ENV COMPOSER_MEMORY_LIMIT=-1

COPY . /var/www/html

USER root

RUN npm install \
    && npm run build

RUN composer install --no-scripts

RUN sed -i "s/^laF_version=.*/laF_version=${VERSION}/" .env

RUN tar \
    --exclude='./.github' \
    --exclude='./.git' \
    --exclude='./docs' \
    --exclude='./documentation' \
    --exclude='./installer' \
    --exclude='./docker' \
    --exclude='./nodejs' \
    --exclude='./debian_installer' \
    --exclude='./jstest' \
    --exclude='./testjwt' \
    --exclude='./traefik' \
    --exclude='./node_modules' \
    --exclude='./var/cache' \
    --exclude='./var/log' \
    -zcvf /artifact.tgz .


FROM git.h2-invent.com/public-system-design/alpine-php8-webserver:${PHP_IMAGE_VERSION}
ARG VERSION
ARG SUPERCRONIC_VERSION=0.2.33

LABEL version="${VERSION}" \
    Maintainer="H2 invent GmbH" \
    Description="Docker Image der Anwendung Jitsi Admin" \
    org.opencontainers.version="${VERSION}" \
    org.opencontainers.image.title="Jitsi Admin" \
    org.opencontainers.image.license="AGPLv3" \
    org.opencontainers.image.vendor="H2 invent GmbH" \
    org.opencontainers.image.authors="Emanuel Holzmann <support@h2-invent.com>" \
    org.opencontainers.image.source="https://github.com/h2-invent/jitsi-admin" \
    org.opencontainers.image.documentation="https://meetling.de" \
    org.opencontainers.image.url="https://jitsi-admin.de"

USER root
RUN apk --no-cache add \
    php83-ldap \
    php83-bcmath \
    && rm -rf /var/cache/apk/*

RUN echo "Europe/Berlin" > /etc/timezone

RUN wget https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/supercronic-linux-amd64 -O /supercronic \
    && chmod +x /supercronic

RUN wget https://git.h2-invent.com/Public-System-Design/Public-Helperscripts/raw/branch/main/distributed_cron.sh -O /distributed_cron.sh \
    && chmod +x /distributed_cron.sh

RUN mkdir /etc/service/cron \
    && echo "#!/bin/sh" > /etc/service/cron/run \
    && echo "exec 2>&1 /supercronic /var/crontab" >> /etc/service/cron/run \
    && chown -R nobody:nobody /etc/service/cron \
    && chmod -R +x /etc/service/cron

RUN mkdir /etc/service/symfony_messenger \
    && echo "#!/bin/sh -e" > /etc/service/symfony_messenger/run \
    && echo "exec 2>&1 php -d memory_limit=-1 /var/www/html/bin/console messenger:consume async --memory-limit=512m --env=prod" >> /etc/service/symfony_messenger/run \
    && chown -R nobody:nobody /etc/service/symfony_messenger \
    && chmod -R +x /etc/service/symfony_messenger 

RUN echo "# Docker Cron Jobs" > /var/crontab \
    && echo "SHELL=/bin/sh" >> /var/crontab \
    && echo "TZ=Europe/Berlin" >> /var/crontab \
    && echo "*/10 * * * * /bin/sh /distributed_cron.sh '/var/www/html/data/cron_lock' 'php /var/www/html/bin/console cron:run'" >> /var/crontab \
    && echo "" >> /var/crontab \
    && chown nobody:nobody /var/crontab

RUN echo "#!/bin/sh" > /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console cache:clear" >> /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console doc:mig:mig --no-interaction" >> /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console cache:clear" >> /docker-entrypoint-init.d/03-symfony.sh \
    && chmod +x /docker-entrypoint-init.d/03-symfony.sh

USER nobody

COPY --from=builder /artifact.tgz artifact.tgz

RUN tar -zxvf artifact.tgz \
    && rm artifact.tgz \
    && mkdir -p var/cache \
    && mkdir -p var/log

ENV nginx_root_directory=/var/www/html/public \
    client_max_body_size=20M \
    memory_limit=1024M \
    post_max_size=20M \
    upload_max_filesize=20M \
    date_timezone=Europe/Berlin
