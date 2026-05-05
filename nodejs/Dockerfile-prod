FROM node:23-alpine
ARG VERSION=development

LABEL version="${VERSION}" \
    Maintainer="H2 invent GmbH" \
    Description="Docker Image Jitsi Admin Websocket" \
    org.opencontainers.version="${VERSION}" \
    org.opencontainers.image.title="Jitsi Admin" \
    org.opencontainers.image.license="AGPLv3" \
    org.opencontainers.image.vendor="H2 invent GmbH" \
    org.opencontainers.image.authors="Emanuel Holzmann <support@h2-invent.com>" \
    org.opencontainers.image.source="https://github.com/h2-invent/jitsi-admin" \
    org.opencontainers.image.documentation="https://meetling.de" \
    org.opencontainers.image.url="https://jitsi-admin.de"

WORKDIR /usr/src/app

COPY . .

RUN apk --no-cache add \
    curl \
    && rm -rf /var/cache/apk/*

RUN npm install

EXPOSE 3000

USER node

ENV WEBSOCKET_SECRET=CHANGEME

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:3000/healthz || exit 1

CMD [ "node", "." ] 
