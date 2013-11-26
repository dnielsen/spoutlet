#!/bin/bash
HOME="/home/vagrant"
PROJ="$HOME/campsite"
HOST="campsite"

DB_NAME="campsite"
DB_USER="root"
DB_PASS="platformd"
INITIAL_DIR=`pwd`;

echo "---------------------------------------"
if [ ! -d $PROJ ]; then 
    FIRST_TIME=true
    echo "Running Initial Configuration"
else
    FIRST_TIME=false
    echo "Skipping first-time configuration"
fi
echo "---------------------------------------"

if $FIRST_TIME ; then
    echo
    echo "Copying Custom Configs"
    cp -R /vagrant/user_data/. /home/vagrant/
    sudo chown vagrant:vagrant /home/vagrant/.ssh/*
    sudo chmod 600 /home/vagrant/.ssh/id_rsa
    echo "---------------------------------------"

    echo
    echo "Setting Locale & timezone"
    sudo tee /etc/default/locale <<EOF > /dev/null
    LANG="en_US.UTF-8"
    LANGUAGE="en_US:en"
EOF
    echo \"America/Los_Angeles\" | sudo tee /etc/timezone && dpkg-reconfigure --frontend noninteractive tzdata
    echo "---------------------------------------"

    echo
    echo "Updating System Clock"
    echo
    sudo ntpdate -u pool.ntp.org
    echo "---------------------------------------"

    echo
    echo "Install Applications"
    sudo apt-get update
    sudo apt-get -q -y install varnish htop screen vim acl apache2-doc git-core libapache2-mod-php5 php5-intl php-apc php5-curl php5-gd php5-mysql php5-mcrypt memcached php5-memcache php5-memcached php5-sqlite ftp-upload ncurses-term php5-xdebug mysql-client php-pear
    echo "---------------------------------------"
    
    echo
    echo "Fix php.ini"
    sed -i "s/\(disable_functions = *\).*/\1/" /etc/php5/cli/php.ini
    sed -i "s/\(memory_limit = *\).*/\1-1/" /etc/php5/cli/php.ini
    sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/cli/php.ini
    sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/apache2/php.ini
    echo "---------------------------------------"

    echo
    echo "Download the campsite source from github"
    sudo -u vagrant -g vagrant git clone git@github.com:dnielsen/spoutlet.git $PROJ
    sudo -u vagrant -g vagrant cp -r $PROJ/app/Resources/* $HOME/app_resources_backup 
    echo "---------------------------------------"
    
    echo
    echo "Configuring Apache2 VirtualHost"
    sudo tee /etc/apache2/sites-available/$HOST <<EOF > /dev/null
    <VirtualHost *:80>
      ServerAdmin webmaster@dummy-host.example.com

      ServerName      $HOST
      DocumentRoot    ${PROJ}/web
      ErrorLog        ${PROJ}/logs/error.log
      CustomLog       ${PROJ}/logs/access.log common

      DirectoryIndex app_dev.php

      <Directory ${PROJ}/web>
        Options FollowSymLinks
        AllowOverride All
      </Directory>
    </VirtualHost>
EOF
    echo "---------------------------------------"

    echo
    a2dissite default
    a2enmod rewrite
    a2ensite ${HOST}
    service apache2 restart
    echo "---------------------------------------"
    
    echo
    echo "Adding parameters.ini from user_data"
    sudo -u vagrant -g vagrant cp /vagrant/user_data/parameters.ini $PROJ/app/config/parameters.ini
    sudo -u vagrant -g vagrant sed -i "s/\(^\s*database_name\s*=\s*\).*/\1$DB_NAME/" $PROJ/app/config/parameters.ini
    sudo -u vagrant -g vagrant sed -i "s/\(^\s*database_user\s*=\s*\).*/\1$DB_USER/" $PROJ/app/config/parameters.ini
    sudo -u vagrant -g vagrant sed -i "s/\(^\s*database_password\s*=\s*\).*/\1$DB_PASS/" $PROJ/app/config/parameters.ini
    echo "---------------------------------------"

    echo
    echo "Install Symfony vendors"
    sudo -u vagrant -g vagrant php $PROJ/bin/vendors install
    echo "---------------------------------------"
    
    echo
    echo "---------------------------------------"
    echo "Create database and run migrations"
    cd $PROJ
    php app/console doc:data:create
    php app/console doc:mig:mig --no-interaction
    mysql -u$DB_USER -p$DB_PASS $DB_NAME < /vagrant/user_data/campsite_sites.sql
    cd $INITIAL_DIR
    echo "---------------------------------------"
    echo
fi

if [ "$( ls $HOME/app_resources )" ]; then 
    echo "Copying in themes changes from synced directory "
    sudo -u vagrant -g vagrant cp -r $HOME/app_resources/* $PROJ/app/Resources
else
    echo "No themes changes from synced directory, refreshing content..."
    sudo -u vagrant -g vagrant cp -r $PROJ/app/Resources/* $HOME/app_resources
fi

echo "---------------------------------------"
echo
cd $PROJ
sudo -u vagrant -g vagrant $PROJ/updateThemes.sh
cd $INITIAL_DIR
echo "---------------------------------------"

echo
echo "Refresh the cache"
rm -rf $PROJ/app/cache/*
sudo -u vagrant -g vagrant php $PROJ/app/console cache:clear 
echo "---------------------------------------"

echo 
echo "Restarting Apache"
service apache2 restart
echo "---------------------------------------"
echo

if $FIRST_TIME ; then
    echo "First time set up complete, please run 'vagrant provision' to complete configuration"
else
    echo "Update complete! Access the site at:"
    echo
    echo "http://192.168.56.3/app_dev.php"
    echo
    echo "---------------------------------------"
fi
echo
