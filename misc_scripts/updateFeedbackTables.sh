#!/bin/bash

# This script installs the feedback lists for each page of campsite.

# The output should be pasted into the app/config/parameters.ini replacing older content if necessary.
# These parameters map the feedback links on each page to the correct feedback list in the database.

# PREREQUISITE
# It requires that the site entity has a valid entry set registry id.

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
DB_USER=$( sed -n 's/^ *database_user *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
DB_PASS=$( sed -n 's/^ *database_password *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
DB_HOST=$( sed -n 's/^ *database_host *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
DB_NAME=$( sed -n 's/^ *database_name *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
DB_PORT=$( sed -n 's/^ *database_port *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_HOST" ] || [ -z "$DB_PORT" ] || [ -z "$DB_NAME" ]; then
    echo "Fatal error: One of the following database properties in parameters.ini is empty:"
    echo "   database_user, database_password, database_host, database_name, database_port";
    exit 1
fi

SITE_NAME="Campsite"


values=(\
'accounts_groups' 'Feedback for My Group page' \
'accounts_events' 'Feedback for My Event page' \
'default_index' 'Feedback for Front page' \
'about' 'Feedback for About page' \
'contact' 'Feedback for Contact page' \
'groups' 'Feedback for All Groups page' \
'global_events_index' 'Feedback for All Events page'  \
'profile' 'Feedback for Profile page' \
'accounts_settings' 'Feedback for Profile Settings page'  \
'entry_set_view' 'Feedback for List View page'  \
'profile_edit' 'Feedback for Profile Edit page'  \
'group_event_contact' 'Feedback for Event Contact page'  \
'group_event_attendees' 'Feedback for Event Attendees page'  \
'group_show' 'Feedback for View Group page' \
'group_event_view' 'Feedback for View Event page'  \
'group_new' 'Feedback for New Group page' \
'group_edit' 'Feedback for Edit Group page' \
'entry_set_new' 'Feedback for New List page' \
'idea_admin_event' 'Feedback for New/Edit Event page' \
'idea_admin' 'Feedback for Event Admin page' \
'idea_admin_images' 'Feedback for Add Event Images page' \
'idea_admin_member_approvals' 'Feedback for Approve Event Members page' \
'idea_admin_criteria_all' 'Feedback for Show Event Criteria page' \
'idea_admin_criteria' 'Feedback for New Event Criteria page' \
'idea_admin_criteria_get' 'Feedback for Edit Event Criteria page' \
'idea_summary' 'Feedback for Event Round Summery page' \
'idea_create_form' 'Feedback for New Entry page' \
'idea_show' 'Feedback for Show Entry page' \
'idea_edit_form' 'Feedback for Edit Entry page' \
'idea_upload_form' 'Feedback for Entry Image Upload page' \
'idea_add_link_form' 'Feedback for Entry Add Link page' \
'fos_user_security_login' 'Feedback for Login page' \
'fos_user_resetting_request' 'Feedback for Forgot Password' \
'fos_user_registration_check_email' 'Feedback for Email Check' \
);

size=${#values[@]};

# Get the site's registry id  -  or die trying
esr=`mysql -P$DB_PORT -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names << END_TEXT
SELECT entrySetRegistration_id FROM pd_site WHERE name = "$SITE_NAME";
END_TEXT`
if [[ -z "$esr" ]]; then
    echo "Error: The site '$SITE_NAME' does not have a valid EntrySet Registration id."
    exit;
fi

# Print out the config.yml stanza
for ((i=0; i<size; i+=2))
do

    # Get the id of the new entry set
    es_id=`mysql -P$DB_PORT -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`
    if [[ -n "$es_id" ]]; then
        continue
    fi

done

echo

echo "The following entries have been updated in parameters.ini:"
for ((i=0; i<size; i+=2))
do

    # Get the id of the new entry set
    es_id=`mysql -P$DB_PORT -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`
    if [[ -n "$es_id" ]]; then
        continue
    fi
    
    # Create Feedback list 
    mysql -P$DB_PORT -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
INSERT INTO entry_set (entrySetRegistration_id,name,type,isVotingActive,isSubmissionActive,allowedVoters) 
VALUES ($esr,"${values[$i+1]}",'task',0,1,'');
END_TEXT
    
    # Get the id of the new entry set
    es_id=`mysql -P$DB_PORT -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`

    # Update parameter statement needed to access this entry set
    echo "${values[$i]} = ${es_id}";
    sed -i "s/^ *\(${values[$i]} *=\) */\1 ${es_id}/" $INI_FILE_PATH
done
