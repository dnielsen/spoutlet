#!/bin/bash

exec 200>./shared/misc_scripts/flock_files/chunked_mass_email_queue_process_script
flock -n 200 || exit 1

./current/app/console pd:massEmails:sendChunks --spawn-more -e prod
