#!/bin/bash

# Default behavior runs assetic dump for dev environment without cache clear
# First parameter 'dev' or 'prod' overrides it and also clears cache for the corresponding environment

if [ $# -eq 0 ]; then
    ENV='dev'
    CACHE=false
elif [ $1 == 'dev' ] || [ $1 == 'prod' ]; then
    ENV=$1
    CACHE=true
else
    printf '\n\tUsage:\n\n\tupdateThemes.sh [dev|prod]\n\tdefault: dev\n\n'
    exit -1
fi

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
if [ $ENV = 'dev' ]; then
    php app/console assetic:dump
    if [ $CACHE = true ]; then
        echo '============================='
        sudo rm -rf app/cache/*
        php app/console cache:clear
    fi
elif [ $ENV = 'prod' ]; then 
    php app/console assetic:dump -e prod --no-debug
    echo '============================='
    sudo rm -rf app/cache/*
    php app/console cache:clear -e prod --no-debug
fi
echo '============================='
echo 
