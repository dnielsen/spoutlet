#!/bin/bash

usage()
{
cat << EOF
USAGE: ./first_time_setup.sh [initial_user initial_user_email]

Creates and setups up database using app/config/parameters.ini. 
Adds default sites 'www.campsite.org' and 'www.campsite.local'.
Also creates the initial user using the database credentials.

Default values:
initial_user = 'admin'
initial_user_email = 'admin@examle.com'

EOF
}

# #-----------------------------
# # Check and set db parameters|
# #-----------------------------
# if [ "$#" -ne 2 ]; then
#     echo "Illegal number of parameters: $#"
#     usage
#     exit 1
# fi

site_user=${1:-'admin'}
site_user_email=${2:-'admin@example.com'}

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
if [ ${SCRIPTPATH##*/} != "misc_scripts" ]; then
    echo "Fatal error: This script must be placed in the misc_scripts directory of campsite";
    exit 1
fi

INI_FILE_PATH="${SCRIPTPATH}/../app/config/parameters.ini";
if [ ! -e $INI_FILE_PATH ]; then
    echo "Fatal error: app/config/parameters.ini file not found."
    exit 1
fi

# Get db username, pass, schema from parameters.ini
db_user=$( sed -n 's/^ *database_user *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_pass=$( sed -n 's/^ *database_password *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_host=$( sed -n 's/^ *database_host *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_name=$( sed -n 's/^ *database_name *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
db_port=$( sed -n 's/^ *database_port *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )

if [ -z "$db_user" ] || [ -z "$db_pass" ] || [ -z "$db_host" ] || [ -z "$db_port" ] || [ -z "$db_name" ]; then
    echo "Fatal error: One of the following database properties in parameters.ini is empty:"
    echo "   database_user, database_password, database_host, database_name, database_port";
    exit 1
fi

#--------------------

# Create the database and migrate the changes
php app/console doc:data:create
yes | php app/console doc:mig:mig

# Create initial user using db password
php app/console fos:user:create $site_user $site_user_email $db_pass --super-admin

# Create initial sites (www.campsite.org, www.campsite.local)
mysql -h$db_host -u$db_user -p$db_pass -P$db_port $db_name <<!!
INSERT INTO pd_site (id, name, defaultLocale, fullDomain, theme) VALUES (1, 'Campsite','en_campsite','www.campsite.org','ideacontest'),(2, 'Campsite_dev','en_campsite','www.campsite.local','ideacontest');
INSERT INTO pd_site_config (id,site_id,supportEmailAddress,automatedEmailAddress,emailFromName,birthdateRequired,forward_base_url,min_age_requirement) VALUES (1,1,'support@campsite.org','noreply@campsite.org','Campsite.org',0,'www.campsite.org',0),(2,2,'support@campsite.org','noreply@campsite.org','Campsite.org',0,'www.campsite.local',0);
INSERT INTO pd_site_features (id, site_id,has_video,has_steam_xfire_communities,has_sweepstakes,has_forums,has_arp,has_news,has_deals,has_games,has_games_nav_drop_down,has_messages,has_groups,has_wallpapers,has_microsoft,has_photos,has_contests,has_comments,has_events,has_giveaways,has_html_widgets,has_facebook,has_google_analytics,has_profile,has_tournaments,has_match_client,has_forward_on_404,has_index,has_about,has_contact,has_search,has_polls,has_static_photo_widget,has_multi_site_groups) VALUES ('1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0'), ('2', '2', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '0', '0', '1', '1', '0', '1', '0', '0', '0', '0', '0', '0', '1', '1', '1', '0', '0', '0', '0');

INSERT INTO entry_set_registry VALUES (1,'SpoutletBundle:Site',1),(2,'SpoutletBundle:Site',2);
UPDATE pd_site SET entrySetRegistration_id = 1 WHERE id=1;
UPDATE pd_site SET entrySetRegistration_id = 2 WHERE id=2;

quit
!!

# Flush memcached
echo "flush_all" | nc -q 2 localhost 11211