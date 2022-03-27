echo Welcome to the installer:

php bin/console doctrine:schema:create
php bin/console cache:clear
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:version --add --all
php bin/console doc:mig:mig --no-interaction
