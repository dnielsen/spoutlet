INSTALLATION
============

* Clone the repository

    git clone git@github.com:KnpLabs/Platformd.git

* Move into the directory and then update the vendors (this will take several
    minutes the first time you do it)

    cd Platformd
    php bin/vendors install

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