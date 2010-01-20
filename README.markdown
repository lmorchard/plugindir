# Mozilla Plugin Directory

## Prerequisites

* Apache
* PHP 5.2+
** mcrypt
* MySQL 5+
* Memcached (optional)

## Install

* Note: In general, files ending in "-dist" are meant to be copied to a local version
without "-dist" and modified for your installation.

* Create a MySQL database using application/config/sql/current.sql as schema

* Copy htdocs/htaccess-dist to htdocs/.htaccess
    * Edit htdocs/.htaccess to adjust RewriteBase if site base URL path is not '/'
        * e.g. RewriteBase /2009/11/plugindir/htdocs/
    * Optionally, you can do away with the .htaccess and move the rules to your
        main Apache config.

* Set up Apache config something like the following for a virtual host:

        <VirtualHost *:80>
            ServerName dev.plugindir.mozilla.org
            DocumentRoot {plugindir path}/htdocs/
            ErrorLog {plugindir path}/logs/error_log
            TransferLog {plugindir path}/logs/access_log
            <Directory {plugindir path}/htdocs/>
                AllowOverride all
                Order allow,deny
                Allow from all
            </Directory>
        </VirtualHost>

* Copy application/config/config-local.php-dist to application/config/config-local.php
    * Change $config['core.site_protocol'] to 'http' or 'https', accordingly.
    * Change $config['core.site_domain'] to match installation domain and base path.
        * eg. if the site lives at http://dev.plugindir.mozilla.org/~lorchard/plugindir, change core.site_domain to dev.plugindir.mozilla.org/~lorchard/plugindir
    * Modify $config['database.default'] to reflect your primary database connection
    * Modify $config['database.shadow'] to reflect your read shadow database connection
        * If there's no read shadow, delete the 'read_shadow' setting from the primary database config. 
    * If memcached is available:
        * Delete the existing $config['cache.default']
        * Uncomment the following $config['cache.default'] settings and associated memcache configurations settings.
        * Modify the memcache settings accordingly
    * For production / staging make these changes:
        * core.internal_cache = 60
        * core.internal_cache_key = (pick some random key, eg 'abs%^&27Abh11@')
        * core.log_threshold = 0
        * core.display_errors = FALSE
        * core.render_stats = FALSE

* Make these directories exist and are writable by Apache:
    * application/logs
    * application/cache
    * application/cache/twig

* Run this to import initial plugin definitions:
    * `php htdocs/index.php util/import plugins-info/*json`
        * You may need to do this as the Apache user, or another user in same group.
        * This will attempt to write to the same logs, caches, etc as the web app.

* Creating the initial admin user:
    * `php htdocs/index.php util/createlogin admin lorchard@mozilla.com admin`
        * First argument is the login name (ie. 'admin')
        * Second argument is a valid email address (ie. 'lorchard@mozilla.com')
        * Third argument is the access role for the user (ie. admin, editor, member)
    * Note the password generated and displayed on a successful new login creation:

        Profile ID 10 created for 'admin4'
        Password: wxg3qav

* (optional) To run tests (requires PHP CLI):
    * Create another MySQL database just for tests
    * Copy config-testing.php-dist to config-testing.php
    * Modify config-testing.php as above, substituting test database details
    * To list available test groups:
        * `php htdocs/index.php phpunit/group`
    * Try running the plugin model tests:
        * `php htdocs/index.php phpunit/group/models.plugindir.plugin`

## Developer Notes

### Localization

* To update locales after code / template changes:
    * `php htdocs/index.php util/compiletemplates`
    * `./bin/extract-po.sh`

* To compile messages after localization work:
    * `./bin/compile-mo.sh`

### Search API Usage

* See: https://wiki.mozilla.org/PFS2#Request_Parameters
* Example query:
    * `curl -sD - 'http://dev.plugindir.mozilla.org/pfs/v2?mimetype=application%2Fx-shockwave-flash+application%2Ffuturesplash&appID=%7Bec8030f7-c20a-464f-9b0e-13a3a9e97384%7D&appRelease=3.5.5&appVersion=20091102134505&clientOS=Intel+Mac+OS+X+10.6&chromeLocale=en-US'`

