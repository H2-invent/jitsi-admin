echo Welcome to the installer:

php bin/console cache:clear
php bin/console doctrine:schema:update --force --no-interaction
php bin/console doctrine:migrations:version --add --all --no-interaction
php bin/console doc:mig:mig --no-interaction
