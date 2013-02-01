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
echo "|  Alienware Arena Reset Script v1.2              |"
echo "|                                                 |"
echo "---------------------------------------------------"
echo

if [ -z "$AwaResetDirectory" ]; then
    echo "Aborting... Could not find AwaResetDirectory environmental variable...".
    echo
    echo "ENSURE you are not on a production server."
    echo
    exit
fi

cd $AwaResetDirectory > /dev/null

if [ `pwd` != $AwaResetDirectory ]; then
    echo "Aborting... Could not switch directory to the AwaResetDirectory..."
    echo
    exit
fi

echo "Syncing local clock and renewing 'sudo' token..."
echo

sudo ntpdate pool.ntp.org

echo
echo "Updating Vendor files..."
echo

./bin/vendors install > /dev/null

echo
echo "Resetting production database:"
echo "  - Dropping database..."

./app/console doctrine:database:drop --force > /dev/null

echo "  - Creating database..."

./app/console doctrine:database:create > /dev/null

echo "  - Migrating database..."

./app/console doctrine:migrations:migrate --no-interaction > /dev/null

echo "  - Loading fixtures..."

# Note: this needs to be --append, because some database data comes from the migrations

./app/console doctrine:fixtures:load --append > /dev/null

echo "  - Dropping ACL database..."

./app/console doctrine:database:drop --connection="acl" --force > /dev/null

echo "  - Creating ACL database..."

./app/console doctrine:database:create --connection="acl" > /dev/null

echo "  - Initialising ACL structure..."

./app/console init:acl > /dev/null

echo
echo "Resetting test database:"
echo "  - Dropping database..."

./app/console doctrine:database:drop --env=test --force > /dev/null

echo "  - Creating database..."

./app/console doctrine:database:create --env=test > /dev/null

echo "  - Migrating database..."

./app/console doctrine:migrations:migrate --no-interaction --env=test  > /dev/null

echo "  - Loading fixtures..."

# Note: this needs to be --append, because some database data comes from the migrations

./app/console doctrine:fixtures:load --append --env=test > /dev/null

echo "  - Dropping ACL database..."

./app/console doctrine:database:drop --env=test --connection="acl" --force > /dev/null

echo "  - Creating ACL database..."

./app/console doctrine:database:create --env=test --connection="acl" > /dev/null

echo "  - Initialising ACL structure..."

./app/console init:acl --env=test > /dev/null

echo
echo "Changing cache ownership..."

sudo chown -R `whoami`:`whoami` app/cache/ > /dev/null

echo
echo "Clearing caches:"
echo "  - Production..."

./app/console cache:clear --no-debug --env=prod > /dev/null

echo "  - Development..."

./app/console cache:clear --no-debug --env=dev > /dev/null

echo "  - Testing..."

./app/console cache:clear --no-debug --env=test > /dev/null

echo
echo "Installing web assets..."

./app/console assets:install --symlink web > /dev/null

echo
echo "Executing web requests:"
echo "  - Production..."

wget demo.alienwarearena.local/app.php --quiet -O /dev/null  > /dev/null

echo "  - Development..."

wget demo.alienwarearena.local/app_dev.php --quiet -O /dev/null > /dev/null

echo "  - Testing..."

wget demo.alienwarearena.local/app_test.php --quiet -O /dev/null  > /dev/null

echo
echo "Changing cache & logs permissions..."

sudo chmod 777 -R app/cache/ app/logs/

echo
echo "Running Behat tests..."
echo

./behat --format=progress

cd - > /dev/null

echo
