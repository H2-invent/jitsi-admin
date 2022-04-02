echo Welcome to the installer:
FILE=docker.conf
if test -f "$FILE"; then
  source $FILE
else
  NEW_UUID=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  HTTP_METHOD=$1
  PUBLIC_URL=$2
  echo "NEW_UUID=$NEW_UUID\nHTTP_METHOD=$HTTP_METHOD\nPUBLIC_URL=$PUBLIC_URL" > $FILE
fi


sed -i "s|**********|$NEW_UUID|g" keycloak/realm-export.json
sed -i "s|clientUrl|$1://$2|g" keycloak/realm-export.json

export PUBLIC_URL=$2
export OAUTH_KEYCLOAK_CLIENT_SECRET=NEW_UUID
export HTTP_METHOD=$1
docker-compose -f docker-compose.test.yml build
docker-compose -f docker-compose.test.yml up -d