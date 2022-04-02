CREATE USER 'jitsiadmin'@'%' IDENTIFIED BY '<keycloak-pw>';
CREATE DATABASE jitsiadmin;
GRANT ALL PRIVILEGES ON jitsiadmin.* TO 'jitsiadmin'@'%';
CREATE USER 'keycloak'@'%' IDENTIFIED BY '<jitsi-admin-pw>';
CREATE DATABASE keycloak;
GRANT ALL PRIVILEGES ON keycloak.* TO 'keycloak'@'%';
FLUSH PRIVILEGES;