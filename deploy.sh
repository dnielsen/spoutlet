echo
echo 'This will update the code, alter the database, and restart Apache. It may take several minutes to complete.'
read -p "Are you sure you wish to proceed? (y/n) " -r
echo 
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi
echo '============================='
echo 'Pulling latest code from git'
echo
git pull 
echo '============================='
echo 'Installing vendors'
echo
php bin/vendors install
echo '============================='
echo 'Dumping assets'
echo
php app/console assetic:dump --env=prod --no-debug
echo '============================='
echo 'Installing assets'
echo
php app/console assets:install web --symlink
echo '============================='
echo 'Installing themes'
echo
php app/console themes:install web --symlink
echo '============================='
echo 'Migrating Doctrine schema'
echo 
php app/console doc:mig:mig --no-interaction
echo '============================='
echo 'Clearing Symfony cache'
echo 
php app/console cache:clear --env=prod --no-debug
echo '============================='
echo 'Restarting Apache (gracefully)'
echo 
sudo apache2ctl graceful
echo '============================='
echo

