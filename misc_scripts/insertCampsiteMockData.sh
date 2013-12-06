
# Get db username, pass, schema from parameters.ini

DB_NAME="campsite"
DB_USER="root"
DB_PASS="sqladmin"

# Inserting mock data (sites, groups, users, events, ideas, etc.) 
mysql -u$DB_USER -p$DB_PASS $DB_NAME < configCampsiteDb.sql

# default users are (user:pass) :
#   admin:admin
#   bill:william
#   john:johnson

