#!/bin/bash


exec 200>./sites/campsite/misc_scripts/flock_files/mass_email_queue_process_script
flock -n 200 || exit 1

./sites/campsite/app/console pd:massEmails:process -e prod
