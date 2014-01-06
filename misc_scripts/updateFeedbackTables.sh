
# This script installs the feedback lists for each page of campsite.

# The output sould be pasted into the app/config/parameters.ini replacing older content if neccessary.
# These parameters map the feedback links on each page to the correct feedback list in the database.

# PREREQUISITE
#It requires that the site entity has a valid entry set registry id.

# Get db username, pass, schema from parameters.ini

DB_NAME="campsite"
DB_USER="root"
DB_PASS="sqladmin"
DB_HOST="localhost"
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
'idea_admin_event' 'Feedback for New Event page' \
'idea_admin' 'Feedback for Event Admin page' \
'idea_admin_event' 'Feedback for Edit Event page' \
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
);

size=${#values[@]};

# Get the site's registry id  -  or die trying
esr=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names << END_TEXT
SELECT entrySetRegistration_id FROM pd_site WHERE name = "$SITE_NAME";
END_TEXT`
if [[ -z "$esr" ]]; then
    echo "Error: The site '$SITE_NAME' does not have a valid EntrySet Registration id."
    exit;
fi

# Print out the config.yml stanza
echo "Add/Update config.yml under section parameters:feedback_ids with the following:"
for ((i=0; i<size; i+=2))
do

    # Get the id of the new entry set
    es_id=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`
    if [[ -n "$es_id" ]]; then
        continue
    fi

    echo "    ${values[$i]}: %${values[$i]}%"
done

echo

echo "Add/Update the following entries in parameters.ini:"
for ((i=0; i<size; i+=2))
do

    # Get the id of the new entry set
    es_id=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`
    if [[ -n "$es_id" ]]; then
        continue
    fi
    
    # Create Feedback list 
    mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
INSERT INTO entry_set (entrySetRegistration_id,name,type,isVotingActive,isSubmissionActive,allowedVoters) 
VALUES ($esr,"${values[$i+1]}",'idea',0,1,'');
END_TEXT
    
    # Get the id of the new entry set
    es_id=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`

    # Print parameter statement needed to access this entry set
    echo "    ${values[$i]} = ${es_id}";
done
