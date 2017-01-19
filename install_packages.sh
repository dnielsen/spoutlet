#!/bin/bash

echo
echo "---------------------------------------"
echo "Installing Additional Packages"
echo "---------------------------------------"
echo

sudo apt-get update

sudo apt-get install curl

sudo apt-get -q -y install varnish htop screen vim acl git-core apache2 libapache2-mod-php7.0 php7.0-intl php-apcu php7.0-curl php7.0-gd php7.0-mysql php7.0-mcrypt memcached php-memcached php-memcache php7.0-sqlite3 php7.0-bcmath php7.0-mbstring php7.0-zip php-amqplib ftp-upload ncurses-term mysql-server mysql-client php-pear python-software-properties nodejs

echo
echo "---------------------------------------"
echo "[custom] Composer installation"
echo
wget https://getcomposer.org/composer.phar
chmod +x composer.phar
sudo mv composer.phar /usr/local/bin/composer
echo "---------------------------------------"
echo "Composer installation complete"
echo "---------------------------------------"

echo
