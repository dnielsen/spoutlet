#!/bin/bash


exec 200>./shared/misc_scripts/flock_files/code_assignment_email_process_script
flock -n 200 || exit 1

./current/app/console pd:codeAssignment:sendEmails -e prod
