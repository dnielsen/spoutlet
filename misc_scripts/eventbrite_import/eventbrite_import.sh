#!/bin/bash

# Usage: ./eventbrite_import.sh event_id [-test_run]
# Requires: 
# - input file full_data.csv
# - opencsv-2.3.jar (csv parsing library)
# Output: import_users.sql

# Execute MySQL script with:
# mysql -uroot -p -f < import_users.sql

mysql_user=root
mysql_pass=sqladmin
mysql_table=campsite

input_file=full_data.csv
output_file=import_users.sql

if [ ! -z "${1##*[!0-9]*}" ]; then
    event_id=$1;
else
     echo "Usage: ./eventbrite_import.sh event_id [-test_run]"
     echo "$1 is not a number";
     exit 1;
fi

rm *.class;
javac -classpath .:opencsv-2.3.jar Main.java;
java -cp .:opencsv-2.3.jar Main $input_file $output_file $event_id > passwords.log;

if [ "$2" != "-test_run" ]; then
    mysql -f -u$mysql_user -p$mysql_pass $mysql_table < $output_file &> err.log
else 
    echo "----- Test Run ----------"
    echo "mysql -f -u$mysql_user -p$mysql_pass $mysql_table < $output_file &> err.log"
fi