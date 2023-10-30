echo Welcome to the installer:
FILE=docker.conf
if [ -f "$FILE" ]; then
  source $FILE
else
  touch $FILE
    KEYCLOAK_PW=$(date +%s | sha256sum | base64 | head -c 32)
    JITSI_ADMIN_PW=$(date +%s | sha256sum | base64 | head -c 32)
    MERCURE_JWT_SECRET=$(date +%s | sha256sum | base64 | head -c 32)
    KEYCLOAK_ADMIN_PW=$(date +%s | sha256sum | base64 | head -c 32)
    NEW_UUID=$(date +%s | sha256sum | base64 | head -c 32)
    echo "KEYCLOAK_PW=$KEYCLOAK_PW" >> $FILE
    echo "MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET" >> $FILE
    echo "KEYCLOAK_ADMIN_PW=$KEYCLOAK_ADMIN_PW" >> $FILE
    echo "NEW_UUID=$NEW_UUID" >> $FILE
    echo "JITSI_ADMIN_PW=$JITSI_ADMIN_PW" >> $FILE
  source $FILE
fi
  ENVIRONMENT=${ENVIRONMENT:=prod}
  read -p "Enter the environment dev/prod[$ENVIRONMENT]: " input
  ENVIRONMENT=${input:=$ENVIRONMENT}
  sed -i '/ENVIRONMENT/d' $FILE
  echo "ENVIRONMENT=$ENVIRONMENT" >> $FILE

  HTTP_METHOD=${HTTP_METHOD:=https}
  read -p "Enter http/https for testing on local environment ALWAYS use http [$HTTP_METHOD]: " input
  HTTP_METHOD=${input:=$HTTP_METHOD}
  sed -i '/HTTP_METHOD/d' $FILE
  echo "HTTP_METHOD=$HTTP_METHOD" >> $FILE

  PUBLIC_URL=${PUBLIC_URL:=dev.domain.de}
  read -p "Enter the url you want to enter the jitsi-admin with no protocoll (no https/http) [$PUBLIC_URL]: " input
  PUBLIC_URL=${input:=$PUBLIC_URL}
  sed -i '/PUBLIC_URL/d' $FILE
  echo "PUBLIC_URL=$PUBLIC_URL" >> $FILE

  echo --------------------------------------------------------------------------
  echo -----------------We looking for all the other parameters-------------------
  echo --------------------------------------------------------------------------
  echo -------------------------------------------------------------
  echo -----------------Mailer--------------------------------------
  echo -------------------------------------------------------------
  smtpHost=${smtpHost:=localhost}
  read -p "Enter smtp host: [$smtpHost]" input
  smtpHost=${input:=$smtpHost}
  sed -i '/smtpHost/d' $FILE
  echo "smtpHost=$smtpHost" >> $FILE

  smtpPort=${smtpPort:=587}
  read -p "Enter smtp port [$smtpPort]: " input
  smtpPort=${input:=$smtpPort}
  sed -i '/smtpPort/d' $FILE
  echo "smtpPort=$smtpPort" >> $FILE

  smtpUsername=${smtpUsername:=username}
  read -p "Enter smtp username [$smtpUsername]: " input
  smtpUsername=${input:=$smtpUsername}
  sed -i '/smtpUsername/d' $FILE
  echo "smtpUsername=$smtpUsername" >> $FILE


  smtpPassword=${smtpPassword:=password}
  read -p "Enter smtp password [$smtpPassword]: " input
  smtpPassword=${input:=$smtpPassword}
  sed -i '/smtpPassword/d' $FILE
  echo "smtpPassword='$smtpPassword'" >> $FILE


  smtpEncryption=${smtpEncryption:=none}
  read -p "Enter SMTP encrytion tls/ssl/none: [$smtpEncryption]" input
  smtpEncryption=${input:=$smtpEncryption}
  sed -i '/smtpEncryption/d' $FILE
  echo "smtpEncryption=$smtpEncryption" >> $FILE

  smtpFrom=${smtpFrom:=test@local.de}
  read -p "Enter smtp FROM mail:[$smtpFrom] " input
  smtpFrom=${input:=$smtpFrom}
  sed -i '/smtpFrom/d' $FILE
  echo "smtpFrom=$smtpFrom" >> $FILE


  echo -------------------------------------------------------------
  echo -----------------we build the KEycloak-----------------------
  echo -------------------------------------------------------------
sed -i "s|<clientsecret>|$NEW_UUID|g" docker/keycloak/realm-export.json
sed -i "s|<clientUrl>|$HTTP_METHOD://$PUBLIC_URL|g" docker/keycloak/realm-export.json

sed -i "s|<smtpPassword>|$smtpPassword|g" docker/keycloak/realm-export.json
sed -i "s|<smtpPort>|$smtpPort|g" docker/keycloak/realm-export.json
sed -i "s|<smtpHost>|$smtpHost|g" docker/keycloak/realm-export.json
sed -i "s|<smtpFrom>|$smtpFrom|g" docker/keycloak/realm-export.json
sed -i "s|<smtpUser>|$smtpUsername|g" docker/keycloak/realm-export.json


if [ "$smtpEncryption" == 'tls' ]; then
   sed -i "s|<smtpEncyption>|\"starttls\": \"true\",|g" docker/keycloak/realm-export.json
elif [ "$smtpEncryption" == 'ssl' ]; then
   sed -i "s|<smtpEncyption>| \"ssl\": \"true\",|g" docker/keycloak/realm-export.json
   else
     sed -i "s|<smtpEncyption>| \"ssl\": \"false\",\n\"starttls\": \"false\",|g" docker/keycloak/realm-export.json
fi

  echo -------------------------------------------------------------
  echo -----------------we build the Database-----------------------
  echo -------------------------------------------------------------
sed -i "s|<jitsi-admin-pw>|$JITSI_ADMIN_PW|g" docker/docker-entrypoint-initdb.d/init-userdb.sql
sed -i "s|<keycloak-pw>|$KEYCLOAK_PW|g" docker/docker-entrypoint-initdb.d/init-userdb.sql


export MAILER_DSN=smtp://$smtpUsername:$smtpPassword@$smtpHost:$smtpPort
export laF_baseUrl=$HTTP_METHOD://$PUBLIC_URL
export VICH_BASE=$HTTP_METHOD://$PUBLIC_URL
export MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET
export GIT_VERSION=$(git rev-parse --short=5 HEAD)
export PUBLIC_URL=$PUBLIC_URL
export OAUTH_KEYCLOAK_CLIENT_SECRET=$NEW_UUID
export HTTP_METHOD=$HTTP_METHOD
export KEYCLOAK_PW=$KEYCLOAK_PW
export JITSI_ADMIN_PW=$JITSI_ADMIN_PW
export KEYCLOAK_ADMIN_PW=$KEYCLOAK_ADMIN_PW
export registerEmailAdress=$smtpFrom
RANDOMTAG=$(date +%s | sha256sum | base64 | head -c 10);
export RANDOMTAG

chmod +x dockerupdate.sh

if [ "$ENVIRONMENT" == 'dev' ]; then
  docker-compose -f docker-compose.test.yml build
  docker-compose -f docker-compose.test.yml up -d --remove-orphans 
elif [ "$ENVIRONMENT" == 'cluster' ]; then
  docker-compose -f docker-compose.test.yml build
  docker-compose -f docker-compose.cluster.yml up -d --remove-orphans 
else
   docker-compose -f docker-compose.yml build
  docker-compose -f docker-compose.yml up -d --remove-orphans 
fi
RED='\033[0;31m'
NC='\033[0m' # No Color
printf "Browse to ${RED}%s://%s${NC} and visit your own jitsi-admin\n" $HTTP_METHOD $PUBLIC_URL
printf "To change any keycloak setting browse to${RED} %s://%s${NC}/keycloak and there the username is: admin and the password: %s\n" $HTTP_METHOD $PUBLIC_URL $KEYCLOAK_ADMIN_PW
printf "Any settings and password can be found in the ${RED}docker.conf${NC} file\n"
printf "To find your loadbalancer go to ${RED}%s://%s${NC}/traefik and enter the user: test and the password: test\n" $HTTP_METHOD $PUBLIC_URL
printf "Have fun with your jitsi-admin and give us a star on github. https://github.com/H2-invent/jitsi-admin\n"
