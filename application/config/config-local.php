<?php
$config['core.needs_installation'] = false;
$config['core.site_domain'] = 'dev.plugindir.mozilla.org';
$config['core.log_threshold'] = 4;
$config['core.internal_cache'] = 60;
$config['core.internal_cache_key'] = '8675309';
$config['core.display_errors'] = FALSE;
$config['core.render_stats'] = FALSE;

$config['core.site_protocol'] = 'http';

$config['cookie.domain'] = $config['core.site_domain'];
$config['csrf_crumbs.secret'] = 'you should really change this';
$config['auth_profiles.secret'] = 'you should really change this too';

$config['model.database'] = 'default';

$config['database.default'] = array
(
    'connection'    => array
    (
        'type'     => 'mysql',
        'user'     => 'plugindir',
        'pass'     => 'plugindir',
        'host'     => 'localhost',
        'port'     => FALSE,
        'socket'   => FALSE,
        'database' => 'plugins_mozilla_org'
    ),
    'table_prefix'  => '',
    'character_set' => 'utf8',
    'benchmark'     => FALSE,
    'persistent'    => FALSE,
    'object'        => TRUE,
    'cache'         => TRUE,
    'escape'        => TRUE
);

$config['cache.default'] = array(
    'driver'   => 'file',
    'params'   => APPPATH.'cache',
    'lifetime' => 1800,
    'requests' => 1000
);

/*
$config['cache.default'] = array(
    'driver' => 'memcache',
    'params' => array(),
    'lifetime' => 1800,
    'requests' => 1000
);

$config['cache_memcache.servers'] = array(
    array(
        'host' => '127.0.0.1',
        'port' => 11211,
        'persistent' => FALSE,
    )
);
$config['cache_memcache.compression'] = FALSE;
*/

$config['email.driver'] = 'native';

/*
$config['email.options'] = array(
    'hostname'   => 'smtp.gmail.com',
    'username'   => 'USER',
    'password'   => 'PASS',
    'port'       => '465',
    'encryption' => 'tls'
);
*/

/*
$config['recaptcha.domain_name'] = 'localhost';
$config['recaptcha.public_key']  = '6Lcb7AYAAAAAAGlcMKBsAQiLlD3Z52UnoK873sjU ';
$config['recaptcha.private_key'] = '6Lcb7AYAAAAAAMZy14GCGWjHzLU5ZAWrxnCwnDZF';
 */
