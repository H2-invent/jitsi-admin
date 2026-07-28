#!/usr/bin/env bash

cp -sR /mnt/ddev_config/keycloak/import /opt/keycloak/data/
/opt/keycloak/bin/kc.sh start-dev --proxy-headers xforwarded --import-realm --log-console-level info
