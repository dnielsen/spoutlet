echo '============================='
echo 'Clearing cache'
echo 
php app/console cache:clear --env=prod --no-debug
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
echo 


