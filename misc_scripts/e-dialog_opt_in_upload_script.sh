#!/bin/bash


exec 200>./shared/misc_scripts/flock_files/e-dialog_opt_in_upload_script
flock -n 200 || exit 1

./current/app/console pd:optin:eDialogUpload -e prod
