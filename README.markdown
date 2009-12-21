# Mozilla Plugin Directory

## Install

* Copy htdocs/htaccess-dist to htdocs/.htaccess
    * Edit htdocs/.htaccess to adjust RewriteBase if site base URL path is not '/'

* Make these directories exist and are writable by Apache:
    * application/logs
    * application/cache
    * application/cache/twig

* Run this to import plugin definitions:
    * `php htdocs/index.php util/import plugins-info/*json`
    * You may need to do this as the Apache user, or another user in same group.

## API Usage
