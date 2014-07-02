#!/bin/bash

# Usage: ./eventbrite_import.sh event_id 
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
event_id=411

rm *.class;
javac -classpath .:opencsv-2.3.jar Main.java;
java -cp .:opencsv-2.3.jar Main $input_file $output_file $event_id;

mysql -f -u$mysql_user -p$mysql_pass $mysql_table < $output_file
