INSTALLATION
============

* NOTE: Read the last section if you face challenges with the installation.

* Clone the repository

    git clone https://github.com/dnielsen/spoutlet.git

* Move into the project root and install the required packages

    $ cd spoutlet  
    $ ./install_packages.sh 

* Copy the template parameters file to parameters.ini

    $ cp app/config/parameters.ini.dist  app/config/parameters.ini
    
* Update the vendors (this will take several minutes the first time you do it)

    $ php bin/vendors install

    - If this command fails, see the last section
    
* Open the `app/config/parameters.ini` file and customize the database
    information. All the other settings are fine.

* Open up your `/etc/hosts` file for editing:

    $ sudo vim /etc/hosts

* Add the following entry to that file and save

    `127.0.0.1       campsite.local www.campsite.local <community1>.campsite.local <community2>.campsite.local`

* Create a virtual host and point it at the `web/` directory of your
    project. For example, suppose I clone the project to `/home/<user>/sites/campsite`.
    Then I would create this file called campsite under `/etc/apache2/sites-available`:


        <VirtualHost *:80>

            ServerAdmin webmaster@dummy-host.example.com

            ServerName  campsite.local
            ServerAlias <community1>.campsite.local
            ServerAlias <community2>.campsite.local

            DocumentRoot    "/home/<user>/sites/campsite/web"
            ErrorLog        "/home/<user>/sites/campsite/logs/error.log"
            CustomLog       "/home/<user>/sites/campsite/logs/access.log" common

            DirectoryIndex  app_dev.php

            <Directory "/home/<user>/sites/campsite/web">
                AllowOverride All
                Options FollowSymLinks
            </Directory>

        </VirtualHost>


* Enable Campsite and disable the default site

    $ sudo a2ensite campsite  
    $ sudo a2dissite default

* Restart Apache

    $ sudo service apache2 restart

* Set up ACL to handle permissions for the cache and logs directories 

* Edit your `/etc/fstab` file to enable ACL on your sites partition

    $ sudo vim /etc/fstab

* Add the `acl` option to the entry for your partition under the `options` column. Your entry should look something like this:

    `UUID=ba4a563f-4f62-4607-97aa-cd42f68aeb86   /home           ext4    defaults,acl        0       2`

* Run the following commands to set up permissions for apache:

        $ APACHEUSER=`ps aux | grep -E '[a]pache|[h]ttpd' | grep -v root | head -1 | cut -d\  -f1`
        $ sudo setfacl -R -m u:$APACHEUSER:rwX -m u:`whoami`:rwX app/cache app/logs
        $ sudo setfacl -dR -m u:$APACHEUSER:rwX -m u:`whoami`:rwX app/cache app/logs

* Make sure you set the timezone in your php.ini file

    $ sudo vim /etc/php5/apache2/php.ini

* Update date.timezone with your server's timezone:  
    `date.timezone = "America/Los_Angeles"`
    
* Disable <? ?> tags, this can confuse some webservers:   
    `short_open_tag = Off` 

* Check to see if your system is setup by running the following command.
    If you see any issues, you may need to install more things. You can
    choose to ignore any issues, they may or may not affect you.

    $ php -c /etc/php5/apache2/php.ini app/check.php 

* Create the database, migrate up to the current schema
```
    $ php app/console doctrine:database:create  
    $ php app/console doctrine:mig:mig  
    
    $ php app/console doctrine:database:create --connection="acl" --env=prod  
    $ php app/console init:acl --env=prod
```    
    Connect to database and update:  

        pd_site          - Add a site for each community with name, defaultLocale, fullDomain, and theme

        pd_site_config   - Set automatedEmailAddress to your AWS SES account and set emailFromName
                         - Make sure birthdayRequired is set to 0
                         - Set the forward_base_url and forwarded_paths

        pd_site_features - Set has_index, has_about, has_contact to 1, and has_forward_on_404 to 0
        
    You'll need to update app/config/config.yml and add each of your sites and their locales to:  
        available_locales  
        site_host_map  
        platformd_sites  


* Head to the site (in dev mode)!

   http://campsite.local/app_dev.php

UPDATING
--------

* Run the update scripts to grab the latest code

    $ ./updateThemes.sh [dev|prod] - Dumps and installs assets and themes  
    $ ./deploy.sh [dev|prod] - Pulls the latest code from git, runs db migrations, updates vendors, dumps and installs assets and themes, clears cache (takes several minutes to clear cache)  

* Head to the site (in dev mode)!

   http://www.campsite.local/app_dev.php

EMAIL FEATURE
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
    


USING MULTIPLE THEMES
-----------------------

* Create a custom theme and place it inside app/Resources/themes

    Example of theme could be app/Resources/themes/custom_theme

    You can grab an empty one there:
      https://github.com/playitcool/custom_theme

    You have to enable it by adding this to your parameters.ini:
      liip_enabled_themes[] = custom_theme

* You will place all assets and templates inside this unique theme directory

* To install your assets, run the ./updateThemes.sh script:

    $ ./updateThemes.sh [dev|prod]

    This will place your theme assets and assets inclusion template in the proper location and clear the cache

* Be careful to follow the same conventions for your assets file inclusion as the ones in the default theme. Don't use Assetic shorthand for bundles path like @SpoutletBundle.

* Now you can override any template you want in the system. For that, you can read the documentation of LiipThemeBundle to understand how to override templates:

    https://github.com/liip/LiipThemeBundle/blob/master/README.md

* A few things worth noting:

    Always put base templates in a bundle rather than in app/Resources/views or you might get unexpected results
    Never override 'SpoutletBundle::base_assets.html.twig' as Assetic won't be able to dump its content
    Instead, simply use your custom theme assets and run the command app./console themes:install web --symlink
    If you override the default layout: 'SpoutletBundle::layout.html.twig' - don't forget to add all the necessary blocks and to include the base assets
    When you want to override a template and create a new file in your theme directory, make sure to clear the cache or you won't see the new template
    Any time you add a new asset file (css or js) - make sure you clear the cache as well and re-dump the assets
