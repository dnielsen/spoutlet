INSTALLATION
============

* NOTE: Read the last section if you face challenges with the installation.

* Clone the repository

    git clone https://github.com/dnielsen/spoutlet.git

* Move into the project root and install the required packages

    $ cd spoutlet  
    $ ./install_packages.sh 

* Update the vendors (this will take several minutes the first time you do it)

    $ php bin/vendors install

    - If this command fails, see the last section

* Copy the template parameters file to parameters.ini

    $ cp app/config/parameters.ini.dist  app/config/parameters.ini
    

* Open the `app/config/parameters.ini` file and customize the database
    information. All the other settings are fine.

* Create a virtual host and point it at the `web/` directory of your
    project. For example, suppose I clone the project to "/home/user/sites/campsite".
    Then, my Apache virtualhost would look like this:


        <VirtualHost *:80>
            ServerAdmin webmaster@dummy-host.example.com
            ServerName campsite.local
            DirectoryIndex app_dev.php
            DocumentRoot "/home/user/sites/campsite/web"
            ErrorLog "/home/user/sites/campsite/logs/error.log"
            CustomLog "/home/user/sites/campsite/logs/access.log" common

            <Directory "/home/user/sites/campsite/web">
                AllowOverride All
                Options FollowSymLinks
            </Directory>
        </VirtualHost>


* Restart Apache

* Open up your `/etc/hosts` file for editing:

    $ sudo vim /etc/hosts

* Add the following entry to that file and save

    127.0.0.1       campsite.local

* Correct the permissions on a few directories. From your project root:

    $ sudo chmod -R 777 app/cache app/logs

* Check to see if your system is setup by running the following command.
    If you see any issues, you may need to install more things. You can
    choose to ignore any issues, they may or may not affect you.

    $ php app/check.php

* Create the database, add the schema

    $ php app/console doctrine:database:create  
    $ php app/console doctrine:mig:mig  
    
    Connect to database and update:  
        pd_site  
        pd_site_config  
        pd_site_features   
        
    You'll need to update app/config/config.yml and add your sites to:  
        available_locales  
        site_host_map  
        platformd_sites  

* Run the following command and just leave it running while you're working.
    This compiles all of the CSS and JS assets. If you don't run this, you
    may not see any styles, or they may be outdated:

     $ php app/console assetic:dump --watch --force


* Head to the site (in dev mode)!

   http://campsite.local

UPDATING
--------

* First, update your code. Assuming you're using the master branch, you'd
    do the following:

    git fetch origin  
    git merge origin/master  

* Update the vendors:

    php bin/vendors install

* Update the database schema

    php app/console doc:mig:mig
    
* Run the refresh scripts to dump assets, install assets, and install themes

    $ ./refreshDev.sh - Dumps assets, installs assets, installs themes  
    $ ./refresh.sh - Does all the above and clears cache for production (takes several minutes to clear cache)  

* Run your asset compiler if not already:

    ./app/console assetic:dump --watch --force

* Head to the site (in dev mode)!

   http://campsite.local



INSTALLATION CHALLENGES
-----------------------

* php bin/vendors install : fails 
    The following error is because of certificate verification:

      error: SSL certificate problem, verify that the CA cert is OK. Details:
      error:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed while accessing https://github.com/Behat/BehatBundle.git/info/refs

    Replace https:// with git:// in <deps> file. Here is a quick fix: 

       $ cd spoutlet
       $ vi deps

	  Execute vi command
	  :.,$s@https://@git://@


USING MULTIPLE THEMES
-----------------------

* Create a custom theme and place it inside app/Resources/themes

    Example of theme could be app/Resources/themes/custom_theme

    You can grab an empty one there:
      https://github.com/playitcool/custom_theme

    You have to enable it by adding this to your parameters.ini:
      liip_enabled_themes[] = custom_theme

* You will place all assets and templates inside this unique theme directory

* To install your assets just run the following:

    .app/console cache:clear
    .app/console themes:install web --symlink
    .app/console assetic:dump web

    This will place your theme assets and assets inclusion template in the proper location

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
