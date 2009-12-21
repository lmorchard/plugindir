# Mozilla Plugin Directory

## Prerequisites

* Apache
* PHP 5.2+
* MySQL 5+
* Memcached (optional)

## Install

* Create a MySQL database using application/config/sql/current.sql as schema

* In general, files ending in "-dist" are meant to be copied to a local version
without the "-dist" and modified for your installation.

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
    * Modify $config['database.default'] to reflect your database details
    * Change $config['core.site_protocol'] to 'http' or 'https', accordingly.
    * Change $config['core.site_domain'] to match installation domain and base path.
    * For production / staging make these changes:
        * core.internal_cache = TRUE
        * core.internal_cache_key = (pick some random key)
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

* To run tests (requires PHP CLI):
    * Create another MySQL database just for tests
    * Copy config-testing.php-dist to config-testing.php
    * Modify config-testing.php as above, substituting test database details
    * To list available test groups:
        * `php htdocs/index.php phpunit/group`
    * Try running the plugin model tests:
        * `php htdocs/index.php phpunit/group/models.plugindir.plugin`

## Search API Usage

* See: https://wiki.mozilla.org/PFS2#Request_Parameters
* Example query:
    * `curl -sD - 'http://dev.plugindir.mozilla.org/api/v1/lookup?mimetype=application%2Fx-shockwave-flash+application%2Ffuturesplash&appID=%7Bec8030f7-c20a-464f-9b0e-13a3a9e97384%7D&appRelease=3.5.5&appVersion=20091102134505&clientOS=Intel+Mac+OS+X+10.6&chromeLocale=en-US'`

