INSTALLATION
============

* NOTE: Read the last section if you face challenges with the installation.


* Prerequisite: 
    You'll need Apache and PHP. At this time, Symphony2 depends on PHP5.3.3+. 
    Mac: You can download and install Apache and PHP5.3.*. Installing PHP on 
      a leopard (10.5.*) m/c can be a challenge.  You might want to use macport 
      to install it.
		Link: https://trac.macports.org/wiki/howto/MAMP
      It may take a couple of hours to install PHP, Apache, and dependencies.


* Clone the repository

    git clone git@github.com:KnpLabs/Platformd.git


* Move into the directory and then update the vendors (this will take several
    minutes the first time you do it)

    cd Platformd
    php bin/vendors install

    - If this command fails, see the last section


* Copy the template parameters file to parameters.ini

    cp app/config/parameters.ini.dist app/config/parameters.ini

* Open the `app/config/parameters.ini` file and customize the database
    information. All the other settings are fine.

* Create a virtual host and point it at the `web/` directory of your
    project. For example, suppose I clone the project to "/Users/ryan/Sites/Platformd".
    Then, my Apache virtualhost would look like this:

    <VirtualHost *:80>
        ServerAdmin webmaster@dummy-host.example.com
        DocumentRoot "/Users/ryan/Sites/Platformd/web"
        ServerName platformd.l
        ServerAlias japan.platformd.l
        ServerAlias china.platformd.l
        ServerAlias demo.platformd.l
        ErrorLog "logs/platformd.com-error_log"
        CustomLog "logs/platformd.com-access_log" common

        <Directory "/Users/ryan/Sites/Platformd/web">
            AllowOverride All
            Options FollowSymLinks
        </Directory>
    </VirtualHost>

* Restart Apache

* Open up your `/etc/hosts` file for editing:

    sudo vim /etc/hosts

* Add the following entry to that file and save

    127.0.0.1       platformd.l japan.platformd.l china.platformd.l demo.platformd.l

* Correct the permissions on a few directories. From your project root:

    sudo chmod -R 777 app/cache app/logs

* Check to see if your system is setup by running the following command.
    If you see any issues, you may need to install more things. You can
    choose to ignore any issues, they may or may not affect you.

    php app/check.php

* Create the database, add the schema, and load some fixtures

    ./app/console doctrine:database:create
    ./app/console doctrine:schema:update --force
    ./app/console doctrine:fixtures:load

* Run the following command and just leave it running while you're working.
    This compiles all of the CSS and JS assets. If you don't run this, you
    may not see any styles, or they may be outdated:

    ./app/console assetic:dump --watch --force

* Head to the site (in dev mode)!

   http://demo.platformd.l/app_dev.php

UPDATING
--------

* First, update your code. Assuming you're using the master branch, you'd
    do the following:

    git fetch origin
    git merge origin/master

* Update the vendors:

    php bin/vendors install

* Update the database schema and reload the fixtures:

    ./app/console doctrine:schema:update --force
    ./app/console doctrine:fixtures:load

* Run your asset compiler if not already:

    ./app/console assetic:dump --watch --force

* Head to the site (in dev mode)!

   http://demo.platformd.l/app_dev.php




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
      https://github.com/newcodeinc/custom-theme

    You have to enable it by adding this to your parameters.ini:
      liip_enabled_themes[] = custom_theme

* You will place all assets and templates inside this unique theme directory

* To install your assets just run the following:

    .app/console cache:clear
    .app/console themes:install web --symlink
    .app/console assetic:dump web

    This will place your theme assets and assets inclusion template in the proper location

* Be careful to follow the same conventions for your assets file inclusion as the ones in the default theme. Don't use Assetic shorthand for bundles path like @SpoutletBundle.

* Now you can override any template you want in the system. For that, you can read the documentation of LiipThemeBundle to understand of to override templates:
      https://github.com/liip/LiipThemeBundle/blob/master/README.md
