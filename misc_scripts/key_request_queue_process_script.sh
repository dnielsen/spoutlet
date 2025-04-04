#!/bin/bash

EXIT=0

end_script()
{
  EXIT=1
}

trap end_script SIGTERM

exec 200>./shared/misc_scripts/flock_files/key_request_queue_process_script
flock -n 200 || exit 1

while [ $EXIT -eq 0 ]; do
    ./current/app/console pd:keyRequestQueue:process -e prod
    sleep 2
done
