#!/bin/bash
#TODO: Get db host, username, pass, schema from parameters.ini

DB_HOST="localhost"
PROD_DB_NAME="campsite"
ACL_DB_NAME="campsite_acl"
DB_USER="root"
DB_PASS="sqladmin"

mysqldump -h$DB_HOST -u$DB_USER -p$DB_PASS $PROD_DB_NAME > prodDbBackup.sql
mysqldump -h$DB_HOST -u$DB_USER -p$DB_PASS $ACL_DB_NAME > aclDbBackup.sql 

tar -cvzf dbBackups.tar.gz prodDbBackup.sql aclDbBackup.sql
rm prodDbBackup.sql aclDbBackup.sql

echo
echo 'Backup sql scripts have been generated and zipped into dbBackups.tar.gz'
echo

