Campsite Install Instructions:
==

Must use UBUNTU 12.04 LTS

1. Download and extract Campsite zip OR clone repo from GitHub

    a. unzip campsite-master.zip -d ~/sites/; mv ~/sites/campsite-master ~/sites/campsite

    OR

    b. git clone git@github.com:dnielsen/spoutlet ~/sites/campsite

2. If using a local DB, install MySQL Server:

    a. sudo apt-get install mysql-server

3. Configure application parameters file:

    a. cd ~/sites/campsite; cp app/config/parameters.ini.dist app/config/parameters.ini

    b. Edit app/config/parameters.ini and fill in DB details

4. Run first time setup script as root:

    a. $ sudo misc_scripts/first_time_setup.sh

5. If viewing on local machine, set your hosts file:

    a. Edit /etc/hosts and add entries for any sites you want to access:

    > 127.0.0.1 www.campsite.local www.marketingcamp.org

6. Go check out the site at either the addresses added to the hosts file on a local machine, or the domain name for a live server

UPDATING
--------

* Run the update scripts to grab the latest code

    $ ./updateAssets.sh [dev|prod] - Dumps and installs assets, clears relevant cache if environment argument is provided
    
    $ ./deploy.sh [dev|prod] - Pulls the latest code from git, runs db migrations, updates vendors, dumps and installs assets, clears cache (takes several minutes to clear cache)  

* Head to the site (in dev mode)!

   http://www.campsite.local/app_dev.php

Configuring Mass Emails
-------------
1. From [AWS SQS console](https://console.aws.amazon.com/sqs/home) create two queues: `PD_TESTING_CHUNKED_MASS_EMAIL`, `PD_TESTING_MASS_EMAIL`  

2. In `app/config/parameters.ini` set `queue_prefix` to the url of the queues you just created minus everything after `PD_TESTING`

3. Setup cron task to execute these two commands on a 1 minute interval:  
    > app/console pd:massEmails:process --env=prod  
    > app/console pd:massEmails:sendChunks --env=prod
  1. Modify both `misc_scripts/mass_email_queue_process_script.sh` and `misc_scripts/chunked_mass_email_queue_process_script.sh` such that all paths are relative to user home directory.  For example:  
  
    ```sh
    #!/bin/bash
    # Create a file handle if it doesn't already exist, 200 is arbitrary file handle id
    exec 200>./misc_scripts/flock_files/mass_email_queue_process_script
    # Lock the newly created file handle (whose id is 200) or exit with error code '1'
    flock -n 200 || exit 1
    # If we are still executing this script then we have exclusive access to the file handle
    # Run the process mass email command
    ./app/console pd:massEmails:process -e prod
    ```
  2. Fix permissions on the scripts so the cron user can run them:
  
     ```
     sudo chmod 755 misc_scripts/mass_email_queue_process_script.sh
     sudo chmod 755 misc_scripts/chunked_mass_email_queue_process_script.sh
     ```
  4. Edit crontab (`crontab -e`) to add the following two lines (adjusting file paths as needed):
  
    ```
    * * * * * ./sites/campsite/misc_scripts/mass_email_queue_process_script.sh >> /dev/null 2>&1
    * * * * * ./sites/campsite/misc_scripts/chunked_mass_email_queue_process_script.sh >> /dev/null 2>&1
    ```
    
Eventbrite event import
--
http://www.campsite.local/app_dev.php/{campsite group slug}/eb_event_import/{eventbrite event id}
