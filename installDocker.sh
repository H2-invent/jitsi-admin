echo Welcome to the installer:
FILE=docker.conf
if [ -f "$FILE" ]; then
  source $FILE
else
  NEW_UUID=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  read -p "Enter the environment dev/prod[dev]: " ENVIRONMENT
  ENVIRONMENT=${ENVIRONMENT:dev}
  read -p "Enter http/https for testing on local environment ALWAYS use http [http]: " HTTP_METHOD
  HTTP_METHOD=${HTTP_METHOD:http}
  read -p "Enter the url you want to enter the jitsi-admin [jadevelop2]: " PUBLIC_URL
  PUBLIC_URL=${PUBLIC_URL:jadevelop2}

  KEYCLOAK_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  JITSI_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  MERCURE_JWT_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  KEYCLOAK_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  echo "NEW_UUID=$NEW_UUID" >> $FILE
  echo "HTTP_METHOD=$HTTP_METHOD" >> $FILE
  echo "PUBLIC_URL=$PUBLIC_URL" >> $FILE
  echo "JITSI_ADMIN_PW=$JITSI_ADMIN_PW" >> $FILE
  echo "KEYCLOAK_PW=$KEYCLOAK_PW" >> $FILE
  echo "MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET" >> $FILE
  echo "KEYCLOAK_ADMIN_PW=$KEYCLOAK_ADMIN_PW" >> $FILE
  echo "ENVIRONMENT=$ENVIRONMENT" >> $FILE
  echo --------------------------------------------------------------------------
  echo -----------------We looking for all the other parameters-------------------
  echo --------------------------------------------------------------------------
  echo -------------------------------------------------------------
  echo -----------------Mailer--------------------------------------
  echo -------------------------------------------------------------
  read -p "Enter smtp host: " smtpHost
  read -p "Enter smtp port: " smtpPort
  read -p "Enter smtp username: " smtpUsername
  read -p "Enter smtp password: " smtpPassword
  read -p "Enter SMTP encrytion tls/ssl/none: " smtpEncryption
  read -p "Enter smtp FROM mail: " smtpFrom
  echo "smtpHost=$smtpHost" >> $FILE
  echo "smtpPort=$smtpPort" >> $FILE
  echo "smtpUsername=$smtpUsername" >> $FILE
  echo "smtpPassword=$smtpPassword" >> $FILE
  echo "smtpEncryption=$smtpEncryption" >> $FILE
  echo "smtpFrom=$smtpFrom" >> $FILE
fi

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

export MAILER_HOST=$smtpHost
export MAILER_PORT=$smtpPort
export MAILER_PASSWORD=$smtpPassword
export MAILER_USERNAME=$smtpUsername
export MAILER_ENCRYPTION=$smtpEncryption
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

chmod +x dockerupdate.sh

if [ "$ENVIRONMENT" == 'dev' ]; then
  docker-compose -f docker-compose.test.yml build
  docker-compose -f docker-compose.test.yml up -d

else
  docker-compose -f docker-compose.yml build
  docker-compose -f docker-compose.yml up -d

fi
RED='\033[0;31m'
NC='\033[0m' # No Color
printf "Browse to ${RED}%s://%s${NC} and visit your own jitsi-admin\n" $HTTP_METHOD $PUBLIC_URL
printf "To change any keycloak setting browse to${RED} %s://keycloak.%s${NC} and there the username is:admin and the password %s\n" $HTTP_METHOD $PUBLIC_URL $KEYCLOAK_ADMIN_PW
printf "Any settings and password can be found in the ${RED}docker.conf${NC} file\n"
printf "To find your loadbalancer go to ${RED}%s://traefik.%s${NC} and enter the user:test and the password:test\n" $HTTP_METHOD $PUBLIC_URL
printf "Have fun with your jitsi-admin and give us a star on github. https://github.com/H2-invent/jitsi-admin\n"
