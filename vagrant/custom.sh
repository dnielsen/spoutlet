#!/bin/bash
# DB_NAME=campsite
# DB_USER=root
# DB_PASS=platformd
HOME="/home/vagrant"
PROJ="$HOME/campsite"

echo
echo "---------------------------------------"
echo "[custom] Copying Custom Configs"
#sudo echo > /home/vagrant/.ssh/authorized_keys
cp -R /vagrant/user_data/. /home/vagrant/
sudo chown vagrant:vagrant /home/vagrant/.ssh/*
sudo chmod 600 /home/vagrant/.ssh/id_rsa
#sudo curl -k https://raw.github.com/mitchellh/vagrant/master/keys/vagrant.pub >> /home/vagrant/.ssh/authorized_keys
echo "---------------------------------------"

if [ -f /home/vagrant/user_specific.sh ]; then
    echo
    echo "---------------------------------------"
    echo "Running User Specific Script"
    /home/vagrant/user_specific.sh
    echo "---------------------------------------"
fi

echo
echo "---------------------------------------"
echo "Setting Locale"
sudo tee /etc/default/locale <<EOF > /dev/null
LANG="en_US.UTF-8"
LANGUAGE="en_US:en"
EOF
echo "---------------------------------------"
echo
echo
echo "---------------------------------------"
echo "Updating System Clock"
echo
sudo ntpdate -u pool.ntp.org
echo "---------------------------------------"

echo
echo "---------------------------------------"
echo "Install Applications"
sudo apt-get update
sudo apt-get -q -y install varnish curl htop screen vim apache2-doc git-core libapache2-mod-php5 php5-intl php-apc php5-curl php5-gd php5-mysql php5-mcrypt memcached php5-sqlite php5-memcache ftp-upload ncurses-term php5-memcached php5-xdebug
echo "---------------------------------------"
echo

echo "---------------------------------------"
echo "Fix php.ini"
sed -i "s/\(disable_functions = *\).*/\1/" /etc/php5/cli/php.ini
sed -i "s/\(memory_limit = *\).*/\1-1/" /etc/php5/cli/php.ini
sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/cli/php.ini
sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/apache2/php.ini
echo "---------------------------------------"


if [ ! -d $PROJ ]; then
  echo "---------------------------------------"
  echo "Download the campsite source from github"
  sudo -u vagrant -g vagrant git clone git@github.com:dnielsen/spoutlet.git $PROJ
  sudo -u vagrant -g vagrant cp -r $PROJ/app/Resources/* $HOME/app_resources_backup 
  echo "---------------------------------------"
  echo
else 
  echo "---------------------------------------"
  echo "Skipping campsite source download"
  echo "---------------------------------------"
fi

if [ ! "$( ls -A $HOME/app_resources )" ]; then
     echo "app_resources appears to be empy, refreshing content..."
     sudo -u vagrant -g vagrant cp -r $PROJ/app/Resources/* $HOME/app_resources 
fi

echo
echo "---------------------------------------"
echo "Configure parameters.ini"
sudo -u vagrant -g vagrant cp parameters.ini $PROJ/app/config/parameters.ini
# sudo -u vagrant -g vagrant cp app/config/parameters.ini.dist app/config/parameters.ini
# sudo -u vagrant -g vagrant sed -i "s/\(database_name *= *\).*/\1$DB_NAME/" app/config/parameters.ini
# sudo -u vagrant -g vagrant sed -i "s/\(database_user *= *\).*/\1$DB_USER/" app/config/parameters.ini
# sudo -u vagrant -g vagrant sed -i "s/\(database_password*= *\).*/\1$DB_PASSWORD/" app/config/parameters.ini
echo "---------------------------------------"
echo

echo
echo "---------------------------------------"
echo "Install Symfony vendors"
sudo -u vagrant -g vagrant php $PROJ/bin/vendors install
echo "---------------------------------------"
echo

if [ "$( ls $HOME/app_resources )" ]; then 
  echo "---------------------------------------"
  echo "Copy themes changes from synced directory"
  cp -r $HOME/app_resources/* $PROJ/app/Resources; 
  echo "---------------------------------------"
else
  echo "---------------------------------------"
  echo "Themes changes from synced directory skipped -- no files"
  echo "---------------------------------------"
fi;

cd $PROJ && source $PROJ/updateThemes.sh

# php app/console doc:data:create
# php app/console doc:mig:mig --no-interaction

# php app/console themes:install web --symlink

# cp /home/vagrant/app_resources ./app/Resources

# php app/console assetic:dump --env=prod --no-debug
# php app/console assets:install web --symlink

# php app/console cache:clear --env=prod --no-debug

#cp /vagrant/setup_environment.sh /home/vagrant/
#sudo chown -R vagrant:vagrant /home/vagrant/

