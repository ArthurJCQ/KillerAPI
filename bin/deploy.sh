#!/bin/bash
# To use on production env only

read -p 'What version do you want to release (eg. 1.0.0, ...) ? => ' versionNumber

ARCHIVE=KillerAPI-$versionNumber.zip

if [ ! -f "$ARCHIVE" ]; then
    echo "$ARCHIVE archive does not exists."
    exit 0
fi

echo -e '\nDeploying... ⏳'

unzip $ARCHIVE
RELEASE=KillerAPI-$versionNumber

APP_PATH=/var/www/api-killerparty

rsync -av --exclude={'config/secrets','config/jwt','tests','deploy.yaml','codeception.yml','docker-compose.yml','phpcs.xml','phpstan.neon','phpunit.xml.dist'} $RELEASE/ $APP_PATH/

rm -rf $RELEASE

cd $APP_PATH

composer install --no-dev --optimize-autoloader
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
php bin/console/clear_apcu_cache.php

bin/console do:mi:mi --no-interaction

echo -e "\nDeploy success ✔️"
