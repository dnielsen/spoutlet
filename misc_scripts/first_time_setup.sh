#!/bin/bash

usage()
{
cat << EOF
USAGE: misc_scripts/first_time_setup.sh [environment] [initial_username] [initial_user_email]

Creates and sets up database using app/config/parameters.yml.
Adds default sites based on environment. Valid environment arguments are 'dev' and 'prod'
Also creates the initial user using the database credentials.

Default values:
environment: 'dev'
initial_username: 'admin'
initial_user_email: 'admin@example.com'

EOF

}

site_env=${1:-'dev'}
site_user=${2:-'admin'}
site_user_email=${3:-'admin@example.com'}

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
if [ ${SCRIPTPATH##*/} != "misc_scripts" ]; then
    echo
    echo "Fatal error: This script must be placed in the misc_scripts directory of campsite";
    echo
    exit 1
fi

YML_FILE_PATH="${SCRIPTPATH}/../app/config/parameters.yml";
if [ ! -e $YML_FILE_PATH ]; then
    echo
    echo "You must first set up the parameters.yml file:"
    echo
    echo "    1. cp app/config/parameters.yml.dist app/config/parameters.yml"
    echo "    2. Edit app/config/parameters.ini and fill in the location and credentials of the database server"
    echo
    exit 1
fi

if [ "$site_env" != "dev" ] && [ "$site_env" != "prod" ]; then
    echo
    echo "Valid environment arguments are 'dev' and 'prod'"
    echo
    exit 1;
fi

echo
echo "Fetching DB credentials from parameters.yml"
db_user=$( sed -n 's/^ *database_user: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
db_pass=$( sed -n 's/^ *database_password: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
db_host=$( sed -n 's/^ *database_host: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
db_name=$( sed -n 's/^ *database_name: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
db_port=$( sed -n 's/^ *database_port: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
echo

if [ -z "$db_user" ] || [ -z "$db_pass" ] || [ -z "$db_host" ] || [ -z "$db_port" ] || [ -z "$db_name" ]; then
    echo
    echo "Fatal error: One of the following database properties in parameters.yml is empty:"
    echo "   database_user, database_password, database_host, database_name, database_port";
    echo
    exit 1
fi

CAMPSITE_ROOT=`pwd`;

if [ "$site_env" = "prod" ]; then
    memory_limit="1024M";
    data_file="initial_prod_data.sql";
else
    memory_limit="-1";
    data_file="initial_dev_data.sql";
fi

echo
echo "===================================================="
echo "Server Configuration"
echo

echo "Setting locale and timezone"
sudo tee /etc/default/locale <<EOF > /dev/null
LANG="en_US.UTF-8"
LANGUAGE="en_US:en"
EOF
echo \"America/Los_Angeles\" | sudo tee /etc/timezone && dpkg-reconfigure --frontend noninteractive tzdata
echo

sudo ./install_packages.sh
echo

echo "Configuration MySQL"
sudo sed -i -e '$a\sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"' /etc/mysql/mysql.conf.d/mysqld.cnf
echo

echo "Configuring php.ini"
sudo sed -i "s/\(disable_functions = *\).*/\1/" /etc/php/7.0/cli/php.ini
sudo sed -i "s/\(memory_limit = *\).*/\1-1/" /etc/php/7.0/cli/php.ini
sudo sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php/7.0/cli/php.ini
sudo sed -i "s/\(memory_limit = *\).*/\1${memory_limit}/" /etc/php/7.0/apache2/php.ini
sudo sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php/7.0/apache2/php.ini
echo

echo 'Setting ServerName in apache2.conf'
sudo echo 'ServerName localhost' >> /etc/apache2/apache2.conf
echo

echo "Configuring Apache Virtual Hosts ... "
sudo cp ./apache_vhosts/*-campsite* /etc/apache2/sites-available/
sudo sed -i "s|\(CAMPSITE_ROOT\)|${CAMPSITE_ROOT}|" /etc/apache2/sites-available/???-campsite*
echo

echo "Enabling site and restarting Apache"
sudo a2dissite default
sudo a2ensite 040-campsite 
sudo a2enmod rewrite headers proxy proxy_http ssl
sudo service apache2 restart
echo

echo
echo "===================================================="
echo "Application Configuration"
echo

echo "Installing Symfony Vendors ..."
composer install --prefer-dist >/dev/null
echo

echo "Creating Database and migrating schema ..."
php app/console doc:data:create
php app/console doc:mig:mig --no-interaction >/dev/null
php app/console doctrine:database:create --connection="acl" --env=$site_env
php app/console init:acl --env=$site_env
echo

echo "Inserting initial data ..."
php ./app/console fos:user:create $site_user $site_user_email $db_pass --super-admin
mysql -h$db_host -u$db_user -p$db_pass -P$db_port $db_name < misc_scripts/$data_file
echo

echo "Preparing assets and warming up cache ..."
./updateAssets.sh $site_env >/dev/null
echo

echo "Flushing Database Cache ..."
echo "flush_all" | nc -q 2 localhost 11211
echo

echo
echo "===================================================="
echo "Campsite Installation complete!" 
echo
