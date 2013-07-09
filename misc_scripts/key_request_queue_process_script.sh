#!/bin/bash

exec 200>./misc_scripts/flock_files/key_request_queue_process_script
flock -n 200 || exit 1

cd ..

while true
do
    ./app/console pd:keyRequestQueue:process -e prod
done
