#!/bin/bash

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
PROD_DB_NAME=$( sed -n 's/^ *database_name *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
ACL_DB_NAME=$( sed -n 's/^ *acl_database_name *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )
DB_PORT=$( sed -n 's/^ *database_port *= *\([^ ]*.*\)/\1/p' < ${INI_FILE_PATH} )

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_HOST" ] || [ -z "$DB_PORT" ] || [ -z "$PROD_DB_NAME" ] || [ -z "$ACL_DB_NAME" ]; then 
    echo "Fatal error: One of the following database properties in parameters.ini is empty:"
    echo "   database_user, database_password, database_host, database_name, acl_database_name, database_port";
    exit 1
fi

echo
echo "Grabbing data from production server database ... "
echo
ssh -t campsite '~/sites/campsite/misc_scripts/backupDbs.sh' 
scp campsite:~/dbBackups.tar.gz ./
tar -xzvf ./dbBackups.tar.gz

echo
echo "Updating local database ... "

mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASS $PROD_DB_NAME < ./db_backups/prodDbBackup.sql
mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASS $ACL_DB_NAME < ./db_backups/aclDbBackup.sql

echo
echo "Updating site domain to www.campsite.local ... "

mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASS $PROD_DB_NAME --execute="UPDATE pd_site SET fullDomain='www.campsite.local' WHERE fullDomain='www.campsite.org'"

echo
echo "Flushing memcached cache ... "
echo

echo "flush_all" | nc -q 2 localhost 11211

echo
echo "Cleaning up temporary files ... "

rm dbBackups.tar.gz

echo
echo "Database up to date: http://www.campsite.local/app_dev.php"
echo

