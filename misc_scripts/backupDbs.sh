#!/bin/bash

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
if [ ${SCRIPTPATH##*/} != "misc_scripts" ]; then
    echo "Fatal error: This script must be placed in the misc_scripts directory of campsite";
    exit 1
fi

YML_FILE_PATH="${SCRIPTPATH}/../app/config/parameters.yml";
if [ ! -e $YML_FILE_PATH ]; then
    echo "Fatal error: app/config/parameters.ini file not found."
    exit 1
fi

DB_BACKUP=${SCRIPTPATH}/../db_backups/

# Get db username, pass, schema from parameters.ini
DB_USER=$( sed -n 's/^ *database_user: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
DB_PASS=$( sed -n 's/^ *database_password: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
DB_HOST=$( sed -n 's/^ *database_host: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
PROD_DB_NAME=$( sed -n 's/^ *database_name: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
ACL_DB_NAME=$( sed -n 's/^ *acl_database_name: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )
DB_PORT=$( sed -n 's/^ *database_port: *\([^ ]*.*\)/\1/p' < ${YML_FILE_PATH} )

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_HOST" ] || [ -z "$DB_PORT" ] || [ -z "$PROD_DB_NAME" ] || [ -z "$ACL_DB_NAME" ]; then
    echo "Fatal error: One of the following database properties in parameters.ini is empty:"
    echo "   database_user, database_password, database_host, database_name, acl_database_name, database_port";
    exit 1
fi

mysqldump -P$DB_PORT -h$DB_HOST -u$DB_USER -p$DB_PASS $PROD_DB_NAME > ${DB_BACKUP}/prodDbBackup.sql
mysqldump -P$DB_PORT -h$DB_HOST -u$DB_USER -p$DB_PASS $ACL_DB_NAME > ${DB_BACKUP}/aclDbBackup.sql 

tar -cvzf dbBackups.tar.gz ${DB_BACKUP}/prodDbBackup.sql ${DB_BACKUP}

echo
echo 'Backup sql scripts have been generated and zipped into dbBackups.tar.gz'
echo

