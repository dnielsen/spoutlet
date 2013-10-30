echo
echo "---------------------------------------"
echo "Installing Additional Packages"
echo "---------------------------------------"
echo

sudo apt-get install curl

curl http://repo.varnish-cache.org/debian/GPG-key.txt | sudo apt-key add -

if ! grep -q 'deb http://repo.varnish-cache.org/ubuntu/ precise varnish-3.0' /etc/apt/sources.list ;
then
echo "deb http://repo.varnish-cache.org/ubuntu/ precise varnish-3.0" | sudo tee -a /etc/apt/sources.list
fi

sudo apt-get update

sudo apt-get -q -y install varnish htop screen vim acl apache2-doc git-core libapache2-mod-php5 php5-intl php-apc php5-curl php5-gd php5-mysql php5-mcrypt memcached php5-memcache php5-memcached php5-sqlite ftp-upload ncurses-term php5-xdebug mysql-server mysql-client php-pear

echo
echo "---------------------------------------"
echo "[custom] Adding PEAR channels"
echo
sudo pear channel-discover pear.symfony.com
sudo pear channel-discover pear.behat.org
sudo pear channel-discover pear.phpunit.de
sudo pear channel-discover pear.symfony-project.com
sudo pear install behat/behat
sudo pear install behat/mink
sudo pear install phpunit/PHPUnit

echo "---------------------------------------"
echo "Package installation complete"
echo "---------------------------------------"

echo

