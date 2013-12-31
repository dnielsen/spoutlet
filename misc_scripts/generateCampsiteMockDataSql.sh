# Get db username, pass, schema from parameters.ini

DB_NAME="campsite"
DB_USER="root"
DB_PASS="sqladmin"

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME pd_site pd_site_config pd_site_features fos_user pd_groups group_event entry_set_registry document judge_idea_map tag_idea_map vote vote_criteria comments entry_set followMappings group_event_rsvp_actions group_events_attendees group_events_sites idea links pd_group_membership_actions pd_group_site pd_groups_members pd_locations pd_tags tags > campsiteMockData.sql
