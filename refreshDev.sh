echo '============================='
echo 'Dumping assets'
echo
php app/console assetic:dump --env=dev 
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
