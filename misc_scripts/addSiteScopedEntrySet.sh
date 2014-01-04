
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
'accounts_groups' 'My Group''s Feedback' \
'accounts_events' 'My Event''s Feedback' \
'default_index' 'Front Page Feedback' \
'about' 'About Page Feedback' \
'contact' 'Contact Page Feedback' \
'groups' 'All Groups Feedback' \
'global_events_index' 'All Events Feedback'  \
'profile' 'Profile Feedback' \
'accounts_settings' 'Profile Settings Feedback'  \
'entry_set_view' 'List View Feedback'  \
'profile_edit' 'Profile Edit Feedback'  \
'group_event_contact' 'Event Contact Feedback'  \
'group_event_attendees' 'Event Attendees Feedback'  \
'group_show' 'View Group Feedback' \
'group_event_view' 'View Event Feedback'  \
'group_new' 'New Group Feedback' \
'group_edit' 'Edit Group Feedback' \
'entry_set_new' 'New List Feedback' \
'idea_admin_event' 'New Event Feedback' \
'idea_admin' 'Event Admin Feedback' \
'idea_admin_event' 'Edit Event Feedback' \
'idea_admin_images' 'Add Event Images Feedback' \
'idea_admin_member_approvals' 'Approve Event Members Feedback' \
'idea_admin_criteria_all' 'Show Event Criteria Feedback' \
'idea_admin_criteria' 'New Event Criteria Feedback' \
'idea_admin_criteria_get' 'Edit Event Criteria Feedback' \
'idea_summary' 'Event Round Summery Feedback' \
'idea_create_form' 'New Entry Feedback' \
'idea_show' 'Show Entry Feedback' \
'idea_edit_form' 'Edit Entry Feedback' \
'idea_upload_form' 'Entry Image Upload Feedback' \
'idea_add_link_form' 'Entry Add Link Feedback' \
);

esr=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names << END_TEXT
SELECT entrySetRegistration_id FROM pd_site WHERE name = "$SITE_NAME";
END_TEXT`

if [[ -z "$esr" ]]; then
    echo "Error: The site '$SITE_NAME' does not have a valid EntrySet Registration id."
    exit;
fi

echo "Add the following lines to your parameters.ini (replace if neccessary) in the app/config directory."

size=${#values[@]};
for ((i=0; i<size; i+=2))
do
    mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
INSERT INTO entry_set (entrySetRegistration_id,name,type,isVotingActive,isSubmissionActive,allowedVoters) 
VALUES ($esr,"${values[$i+1]}",'idea',0,1,'');
END_TEXT
    
    es_id=`mysql -u$DB_USER -p$DB_PASS $DB_NAME -h$DB_HOST --skip-column-names <<END_TEXT
SELECT id FROM entry_set WHERE name = "${values[$i+1]}";
END_TEXT`

    echo "${values[$i]} = ${es_id}";
done
