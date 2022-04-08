echo Welcome to the installer:
FILE=docker.conf
if [ -f "$FILE" ]; then
  source $FILE
else
  touch $FILE
    KEYCLOAK_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    JITSI_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    MERCURE_JWT_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    KEYCLOAK_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    echo "KEYCLOAK_PW=$KEYCLOAK_PW" >> $FILE
    echo "MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET" >> $FILE
    echo "KEYCLOAK_ADMIN_PW=$KEYCLOAK_ADMIN_PW" >> $FILE
    NEW_UUID=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    echo "NEW_UUID=$NEW_UUID" >> $FILE
    echo "JITSI_ADMIN_PW=$JITSI_ADMIN_PW" >> $FILE

    HTTP_METHOD='http'
    PUBLIC_URL='jadevelop2'
  source $FILE
fi
  ENVIRONMENT=${ENVIRONMENT:=dev}
  read -p "Enter the environment dev/prod[$ENVIRONMENT]: " input
  ENVIRONMENT=${input:=$ENVIRONMENT}
  echo "ENVIRONMENT=$ENVIRONMENT" >> $FILE

  HTTP_METHOD=${HTTP_METHOD:=http}
  read -p "Enter http/https for testing on local environment ALWAYS use http [$HTTP_METHOD]: " input
  HTTP_METHOD=${input:=$HTTP_METHOD}
  echo "HTTP_METHOD=$HTTP_METHOD" >> $FILE

  PUBLIC_URL=${PUBLIC_URL:=dev}
  read -p "Enter the url you want to enter the jitsi-admin [$PUBLIC_URL]: " input
  PUBLIC_URL=${input:=$PUBLIC_URL}
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
  echo "smtpHost=$smtpHost" >> $FILE

  smtpPort=${smtpPort:=587}
  read -p "Enter smtp port [$smtpPort]: " input
  smtpPort=${input:=$smtpPort}
  echo "smtpPort=$smtpPort" >> $FILE

  smtpUsername=${smtpUsername:=username}
  read -p "Enter smtp username [$smtpUsername]: " input
  smtpUsername=${input:=$smtpUsername}
  echo "smtpUsername=$smtpUsername" >> $FILE


  smtpPassword=${smtpPassword:=password}
  read -p "Enter smtp password [$smtpPassword]: " input
  smtpPassword=${input:=$smtpPassword}
  echo "smtpPassword=$smtpPassword" >> $FILE


  smtpEncryption=${smtpEncryption:=none}
  read -p "Enter SMTP encrytion tls/ssl/none: [$smtpEncryption]" input
  smtpEncryption=${input:=$smtpEncryption}
  echo "smtpEncryption=$smtpEncryption" >> $FILE

  smtpFrom=${smtpFrom:=test@local.de}
  read -p "Enter smtp FROM mail:[$smtpFrom] " input
  smtpFrom=${input:=$smtpFrom}
  echo "smtpFrom=$smtpFrom" >> $FILE


  echo -------------------------------------------------------------
  echo -----------------we build the KEycloak-----------------------
  echo -------------------------------------------------------------
sed -i "s|<clientsecret>|$NEW_UUID|g" keycloak/realm-export.json
sed -i "s|<clientUrl>|$HTTP_METHOD://$PUBLIC_URL|g" keycloak/realm-export.json

sed -i "s|<smtpPassword>|$smtpPassword|g" keycloak/realm-export.json
sed -i "s|<smtpPort>|$smtpPort|g" keycloak/realm-export.json
sed -i "s|<smtpHost>|$smtpHost|g" keycloak/realm-export.json
sed -i "s|<smtpFrom>|$smtpFrom|g" keycloak/realm-export.json
sed -i "s|<smtpUser>|$smtpUsername|g" keycloak/realm-export.json

if [ "$smtpEncryption" == 'tls' ]; then
   sed -i "s|<smtpEncyption>|\"starttls\": \"true\",|g" keycloak/realm-export.json
elif [ "$smtpEncryption" == 'ssl' ]; then
   sed -i "s|<smtpEncyption>| \"ssl\": \"true\",|g" keycloak/realm-export.json
   else
     sed -i "s|<smtpEncyption>| '',|g" keycloak/realm-export.json
fi

  echo -------------------------------------------------------------
  echo -----------------we build the Database-----------------------
  echo -------------------------------------------------------------
sed -i "s|<jitsi-admin-pw>|$JITSI_ADMIN_PW|g" docker-entrypoint-initdb.d/init-userdb.sql
sed -i "s|<keycloak-pw>|$KEYCLOAK_PW|g" docker-entrypoint-initdb.d/init-userdb.sql


export MAILER_DSN=smtp://$smtpUsername:$smtpPassword@$smtpHost:$smtpPort
export laF_baseUrl=$HTTP_METHOD://$PUBLIC_URL

export MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET

export PUBLIC_URL=$PUBLIC_URL
export OAUTH_KEYCLOAK_CLIENT_SECRET=$NEW_UUID
export HTTP_METHOD=$HTTP_METHOD
export KEYCLOAK_PW=$KEYCLOAK_PW
export JITSI_ADMIN_PW=$JITSI_ADMIN_PW
export KEYCLOAK_ADMIN_PW=$KEYCLOAK_ADMIN_PW
export registerEmailAdress=$smtpFrom



if [ "$ENVIRONMENT" == 'dev' ]; then
  docker-compose -f docker-compose.test.yml build
  docker-compose -f docker-compose.test.yml up -d
  docker exec -d jitsi-admin_app-ja_1 /bin/bash /var/www/html/dockerupdate.sh
else
  #todo hier das letsencrypt file rein
   docker-compose -f docker-compose.yml build
  docker-compose -f docker-compose.yml up -d
  docker exec -d jitsi-admin_app-ja_1 /bin/bash /var/www/html/dockerupdate.sh
fi
