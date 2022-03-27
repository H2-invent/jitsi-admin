CREATE USER 'jitsiadmin'@'%' IDENTIFIED BY 'test';
CREATE DATABASE jitsiadmin;
GRANT ALL PRIVILEGES ON jitsiadmin.* TO 'datenschutzcenter'@'%';
CREATE USER 'keycloak'@'%' IDENTIFIED BY 'test';
CREATE DATABASE keycloak;
GRANT ALL PRIVILEGES ON keycloak.* TO 'keycloak'@'%';
FLUSH PRIVILEGES;