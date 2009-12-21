# Mozilla Plugin Directory

## Install

* Copy htdocs/htaccess-dist to htdocs/.htaccess
    * Edit htdocs/.htaccess to adjust RewriteBase if site base URL path is not '/'

* Copy application/config/config.php-dist to application/config/config.php
    * Change $config['site_protocol'] to 'http' or 'https', accordingly.
    * Change $config['site_domain'] to match installation domain and base path.
    * For production / staging make these changes:
        * internal_cache = TRUE
        * internal_cache_key = (pick some random key)
        * log_threshold = 0
        * display_errors = FALSE
        * render_stats = FALSE

* Create a MySQL database using application/config/sql/current.sql as schema

* Copy application/config/database.php-dist to application/config/database.php
    * Change the host / port / user / pass / database to point to MySQL database

* Make these directories exist and are writable by Apache:
    * application/logs
    * application/cache
    * application/cache/twig

* Run this to import plugin definitions:
    * `php htdocs/index.php util/import plugins-info/*json`
        * You may need to do this as the Apache user, or another user in same group.
        * This will attempt to write to the same logs, caches, etc as the web app.

## API Usage
