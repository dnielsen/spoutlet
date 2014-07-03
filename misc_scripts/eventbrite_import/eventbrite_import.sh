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

output_file=import_users.sql

if [ ! -z "${1##*[!0-9]*}" ]; then
    event_id=$1;
else
    echo "$1 is not a number";
    echo "Usage: ./eventbrite_import.sh event_id [-test_run]"
    exit 1;
fi

if [ ! -f $2 ]; then
    echo "File not found! $2"
    echo "Usage: ./eventbrite_import.sh event_id input.csv [-test_run]"
    exit 1;
else 
    input_file=$2; #full_data.csv
fi

rm *.class;
javac -classpath .:opencsv-2.3.jar Main.java;
java -cp .:opencsv-2.3.jar Main $input_file $output_file $event_id > passwords.log;

if [ "$3" != "-test_run" ]; then
    mysql -f -u$mysql_user -p$mysql_pass $mysql_table < $output_file &> err.log

    # Archive results
    folder_name=${input_file%.csv}
    folder_name=`echo ${folder_name##*/}`;
    echo "Creating directory: $folder_name";

    mkdir $folder_name
    if [ $? -ne 0 ]; then
        echo "Operation halted to prevent destruction of existing files"
        exit
    fi
    cp $input_file $folder_name/full_data.csv
    cp *.log $folder_name/
    cp import_users.sql $folder_name
else 
    echo "----- Test Run ----------"
    echo "mysql -f -u$mysql_user -p$mysql_pass $mysql_table < $output_file &> err.log"
fi