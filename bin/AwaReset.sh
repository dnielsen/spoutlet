#!/bin/bash

# This script requires that you set an environmental variable that points to your symfony root directory (this allows
# each developer to have their site in a different location, as well as ensuring that this script never gets
# accidentally executed on a production server).
#
# To set this variable, edit your ~/.bashrc file and at the bottom add the following (obviously with your path, mine is
# here as an example):
#
#   export AwaResetDirectory=/home/ubuntu/sites/alienwarearena.com
#
# Save your ~/.bashrc file and close your editor, then type:
#
#   source ~/.bashrc
#
# Now you should be able to use this script.

echo
echo "---------------------------------------------------"
echo "|                                                 |"
echo "|  Alienware Arena Reset Script v1.8              |"
echo "|                                                 |"
echo "---------------------------------------------------"
echo
echo "NOTE: All output from the commands executed by this script are available in 'AwaReset.log'."
echo

if [ -z "$AwaResetDirectory" ]; then
    echo "Aborting... Could not find AwaResetDirectory environmental variable...".
    echo
    echo "ENSURE you are not on a production server."
    echo
    exit
fi

cd $AwaResetDirectory > /dev/null

rm bin/AwaReset.log 2> /dev/null

if [ `pwd` != $AwaResetDirectory ]; then
    echo "Aborting... Could not switch directory to the AwaResetDirectory..."
    echo
    exit
fi

echo "Syncing local clock and renewing 'sudo' token..."
echo

sudo ntpdate -u pool.ntp.org

echo
echo "Nuking cache from orbit..."

sudo rm -rf app/cache/* >> bin/AwaReset.log

echo
echo "Updating Vendor files..."
echo

./bin/vendors install 2>&1 >> bin/AwaReset.log

sudo ls > /dev/null

echo
echo "Updating all themes..."
echo

./app/console themes:update 2>&1 >> bin/AwaReset.log

echo
echo "Creating necessary symlinks for themes..."

./app/console themes:install web --symlink >> bin/AwaReset.log

echo
echo "Clearing developer specific indexed search documents..."

./app/console pd:search:deleteAll --confirm-delete >> bin/AwaReset.log

echo
echo "Resetting development database:"
echo "  - Dropping database..."

./app/console doctrine:database:drop --env=dev --force >> bin/AwaReset.log

echo "  - Creating database..."

./app/console doctrine:database:create --env=dev >> bin/AwaReset.log

echo "  - Migrating database..."

./app/console doctrine:migrations:migrate --no-interaction --env=dev >> bin/AwaReset.log

echo "  - Loading fixtures..."

./app/console doctrine:fixtures:load --env=dev >> bin/AwaReset.log

echo "  - Dropping ACL database..."

./app/console doctrine:database:drop --connection="acl" --force --env=dev >> bin/AwaReset.log

echo "  - Creating ACL database..."

./app/console doctrine:database:create --connection="acl" --env=dev >> bin/AwaReset.log

echo "  - Initialising ACL structure..."

./app/console init:acl --env=dev >> bin/AwaReset.log

sudo ls > /dev/null

echo
echo "Clearing development cache:"

./app/console cache:clear --no-debug --env=dev >> bin/AwaReset.log

echo
echo "Installing web assets..."

./app/console assets:install --symlink web >> bin/AwaReset.log
./app/console assetic:dump >> bin/AwaReset.log

echo
echo "Executing development web request:"

wget demo.alienwarearena.local/app_dev.php --quiet -O /dev/null >> bin/AwaReset.log

sudo ls > /dev/null

echo
echo "Resetting test database:"
echo "  - Dropping database..."

./app/console doctrine:database:drop --env=test --force >> bin/AwaReset.log

echo "  - Creating database..."

./app/console doctrine:database:create --env=test >> bin/AwaReset.log

echo "  - Migrating database..."

./app/console doctrine:migrations:migrate --no-interaction --env=test  >> bin/AwaReset.log

echo "  - Loading fixtures..."

./app/console doctrine:fixtures:load --env=test >> bin/AwaReset.log

echo "  - Dropping ACL database..."

./app/console doctrine:database:drop --env=test --connection="acl" --force >> bin/AwaReset.log

echo "  - Creating ACL database..."

./app/console doctrine:database:create --env=test --connection="acl" >> bin/AwaReset.log

echo "  - Initialising ACL structure..."

./app/console init:acl --env=test >> bin/AwaReset.log

echo
echo "Clearing test cache:"

./app/console cache:clear --no-debug --env=test >> bin/AwaReset.log

echo
echo "Executing test web request:"

wget demo.alienwarearena.local/app_test.php --quiet -O /dev/null  >> bin/AwaReset.log

echo
echo "Changing cache & logs permissions..."

sudo chmod -R 777 app/cache/ app/logs/

echo
echo "Running Behat tests..."
echo

./behat --format=progress

cd - > /dev/null

echo
