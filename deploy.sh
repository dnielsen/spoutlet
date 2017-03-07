#!/bin/bash

# Default behavior runs for production
# First parameter 'dev' or 'prod' overrides it

if [ $# -eq 0 ]; then
    ENV='prod'
elif [ $1 == 'dev' ] || [ $1 == 'prod' ]; then
    ENV=$1
else
    printf '\n\tUsage:\n\n\tdeploy.sh [dev|prod]\n\tdefault: prod\n\n'
    exit -1
fi

echo
echo '===================================================='
echo 'DEPLOY CAMPSITE -- '$ENV
echo '===================================================='
echo

echo 'This will update the code, alter the database, and restart Apache. It may take several minutes to complete.'
echo
read -p "Are you sure you wish to proceed? (y/n) " -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

echo '============================='
echo 'Nuking cache'
echo
sudo rm -rf app/cache/*

echo '============================='
echo 'Pulling latest code from git'
echo
git pull

echo '============================='
echo 'Installing vendors'
echo
composer install --prefer-dist

echo '============================='
echo 'Installing assets'
echo
php app/console assets:install web --symlink

# echo '============================='
# echo 'Installing themes'
# echo
# php app/console themes:install web --symlink

echo '============================='
echo 'Assetic Dump'
echo
if [ $ENV == 'dev' ]; then
    php app/console assetic:dump
elif [ $ENV == 'prod' ]; then
    php app/console assetic:dump --env=prod --no-debug
fi

echo '============================='
echo 'Migrating Doctrine schema'
echo
php app/console doc:mig:mig --no-interaction

echo '============================='
echo 'Clearing Symfony cache'
if [ $ENV == 'dev' ]; then
    php app/console cache:clear
elif [ $ENV == 'prod' ]; then
    php app/console cache:clear --env=prod --no-debug
fi

if [ $ENV == 'prod' ]; then
    echo '============================='
    echo 'Clearing Controllers'
    echo
    rm web/app_*.php
fi

# echo '============================='
# echo 'Updating Node dependencies'
# cd node-api
# npm install
# cd ..
# echo
#
# echo '============================='
# echo 'Restarting Node API Service'
# forever restartall
# echo

echo '============================='
echo 'Restarting Apache (gracefully)'
echo
sudo systemctl restart apache2.service

echo '============================='
echo
