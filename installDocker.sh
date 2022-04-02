echo Welcome to the installer:
FILE=docker.conf
if test -f "$FILE"; then
  source $FILE
else
  NEW_UUID=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  HTTP_METHOD=$1
  PUBLIC_URL=$2
  KEYCLOAK_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  JITSI_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  echo "NEW_UUID=$NEW_UUID" >> $FILE
  echo "HTTP_METHOD=$HTTP_METHOD" >> $FILE
  echo "PUBLIC_URL=$PUBLIC_URL" >> $FILE
  echo "JITSI_ADMIN_PW=$JITSI_ADMIN_PW" >> FILE
  echo "KEYCLOAK_PW=$KEYCLOAK_PW" >> FILE

fi

sed -i "s|**********|$NEW_UUID|g" keycloak/realm-export.json
sed -i "s|clientUrl|$1://$2|g" keycloak/realm-export.json
sed -i "s|<jitsi-admin-pw>|$JITSI_ADMIN_PW|g" docker-entrypoint-initdb.d/init-userdb.sql
sed -i "s|<keycloak-pw>|$KEYCLOAK_PW|g" docker-entrypoint-initdb.d/init-userdb.sql


export PUBLIC_URL=$2
export OAUTH_KEYCLOAK_CLIENT_SECRET=NEW_UUID
export HTTP_METHOD=$1
docker-compose -f docker-compose.test.yml build
docker-compose -f docker-compose.test.yml up -d
docker exec -d app-ja bash /var/www/dockerupdate.sh