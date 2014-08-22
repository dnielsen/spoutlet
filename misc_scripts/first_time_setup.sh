#!/bin/bash

usage()
{
cat << EOF
USAGE: ./first_time_setup.sh [initial_user initial_user_email]

Creates and sets up database using app/config/parameters.ini. 
Adds default sites 'www.campsite.org', 'www.marketingcamp.org' and 'www.campsite.local'.
Also creates the initial user using the database credentials.

Default values:
initial_user = 'admin'
initial_user_email = 'admin@example.com'

EOF

}

site_user=${1:-'admin'}
site_user_email=${2:-'admin@example.com'}

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
if [ ${SCRIPTPATH##*/} != "misc_scripts" ]; then
    echo
    echo "Fatal error: This script must be placed in the misc_scripts directory of campsite";
    echo
    exit 1
fi

CAMPSITE_ROOT="${SCRIPTPATH}/..";
INI_FILE_PATH="${CAMPSITE_ROOT}/app/config/parameters.ini";
if [ ! -e $INI_FILE_PATH ]; then
    echo
    echo "You must first set up the parameters.ini file:"
    echo
    echo "    1. cp app/config/parameters.ini.dist app/config/parameters.ini"
    echo "    2. Edit app/config/parameters.ini and fill in the location and credentials of the database server"
    echo
    exit 1
fi

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

echo "Syncing time server"
sudo ntpdate -u pool.ntp.org
echo

sudo ./install_packages.sh
echo

echo 'Setting ServerName in apache2.conf'
sudo echo 'ServerName localhost' >> /etc/apache2/apache2.conf
echo

echo "Configuring php.ini"
sed -i "s/\(disable_functions = *\).*/\1/" /etc/php5/cli/php.ini
sed -i "s/\(memory_limit = *\).*/\1-1/" /etc/php5/cli/php.ini
sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/cli/php.ini
sed -i "s/.*\(date.timezone *=\).*/\1 America\/Los_Angeles/" /etc/php5/apache2/php.ini
echo

# Get db username, pass, schema from parameters.ini
echo "Fetching DB credentials from parameters.ini"
db_user=$( sed -n 's/^ *database_user *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_pass=$( sed -n 's/^ *database_password *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_host=$( sed -n 's/^ *database_host *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_name=$( sed -n 's/^ *database_name *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_port=$( sed -n 's/^ *database_port *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
echo

if [ -z "$db_user" ] || [ -z "$db_pass" ] || [ -z "$db_host" ] || [ -z "$db_port" ] || [ -z "$db_name" ]; then
    echo "Fatal error: One of the following database properties in parameters.ini is empty:"
    echo "   database_user, database_password, database_host, database_name, database_port";
    exit 1
fi

#--------------------

echo "===================================================="
echo "Application Configuration"
echo

echo "Installing Symfony Vendors"
php ./bin/vendors install
echo

# Create the database and migrate the changes
echo "Creating Database and migrating schema"
php ./app/console doc:data:create
php ./app/console doc:mig:mig --no-interaction
php ./app/console doctrine:database:create --connection="acl" --env=prod
php ./app/console init:acl --env=prod
echo

echo "Creating initial user"
# Create initial user using db password
php ./app/console fos:user:create $site_user $site_user_email $db_pass --super-admin
echo

echo "Insert initial site and community data"
# Add initial data
mysql -h$db_host -u$db_user -p$db_pass -P$db_port $db_name <<!!

INSERT INTO entry_set_registry VALUES 
(1,'SpoutletBundle:Site',1),
(2,'SpoutletBundle:Site',2),
(3,'SpoutletBundle:Site',3),
(4, 'GroupBundle:Group',1);

INSERT INTO pd_groups (
id,name,category,isPublic,owner_id,description,slug,relativeSlug,entrySetRegistration_id) VALUES 
(1, 'MarketingCamp', 'topic', 1, 1, '<h1>About MarketingCamp</h1><p>MarketingCamp is a gathering of marketing thought-leaders who get together to dialogue and brainstorm about marketing topics, tools, trends, and technology they share as passions.</p>','marketingcamp', 'marketingcamp', 4);

INSERT INTO pd_site (
id, name, defaultLocale, fullDomain, theme, entrySetRegistration_id, communityGroup_id) VALUES 
(1, 'Campsite','en_campsite','www.campsite.org','default', 1, NULL),
(2, 'MarketingCamp','en_campsite','www.marketingcamp.org','default', 2, 1),
(3, 'CampsiteDev','en_campsite','www.campsite.local','default', 3, NULL);

INSERT INTO pd_site_config (
id,site_id,supportEmailAddress,automatedEmailAddress,emailFromName,birthdateRequired,forward_base_url,min_age_requirement) VALUES 
(1,1,'support@campsite.org','noreply@campsite.org','Campsite.org',0,'www.campsite.org',0),
(2,2,'support@campsite.org','noreply@campsite.org','MarketingCamp.org',0,'www.marketingcamp.org',0),
(3,3,'support@campsite.org','noreply@campsite.org','Campsite.org',0,'www.campsite.local',0);

INSERT INTO pd_site_features (
id, site_id,has_video,has_steam_xfire_communities,has_sweepstakes,has_forums,has_arp,has_news,has_deals,has_games,has_games_nav_drop_down,has_messages,has_groups,has_wallpapers,has_microsoft,has_photos,has_contests,has_comments,has_events,has_giveaways,has_html_widgets,has_facebook,has_google_analytics,has_profile,has_tournaments,has_match_client,has_forward_on_404,has_index,has_about,has_contact,has_search,has_polls,has_static_photo_widget,has_multi_site_groups) VALUES
('1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0'), 
('2', '2', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0'), 
('3', '3', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0');

INSERT INTO pd_group_site VALUES (1, 2);

quit
!!
echo

./updateAssets.sh prod

# Flush memcached
echo "Flushing memcached"
echo "flush_all" | nc -q 2 localhost 11211
echo

echo "Configuring Apache Virtual Hosts"
sed -i "s/\(CAMPSITE_ROOT\)/$CAMPSITE_ROOT/" ./apache_vhosts/*-campsite*
sudo cp ./apache_vhosts/* /etc/apache2/sites-available/
sudo a2dissite default
sudo a2ensite 040-campsite 
sudo a2enmod rewrite headers proxy proxy_http ssl
sudo service apache2 restart
echo
echo "===================================================="
echo "Campsite Installation complete!" 
echo

