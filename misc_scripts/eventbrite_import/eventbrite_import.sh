#!/bin/bash

# Usage: ./eventbrite_import.sh event_id 
# Requires: 
# - input file full_data.csv
# - opencsv-2.3.jar (csv parsing library)
# Output: import_users.sql

# Execute MySQL script with:
# mysql -uroot -p -f < import_users.sql

rm *.class;
javac -classpath .:opencsv-2.3.jar Main.java;
java -cp .:opencsv-2.3.jar Main $1
