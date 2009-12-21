<?php defined('SYSPATH') OR die('No direct access allowed.');

$config['plugins/submit'] = 'plugins/submit';

$config['plugins/detail/([^\.]+).json'] = 'plugins/detail/$1/json';
$config['plugins/detail/([^\.]+).html'] = 'plugins/detail/$1/html';
$config['plugins/detail/([^;]+);(.*)']  = 'plugins/$2/$1';
$config['plugins/detail/(.*)']          = 'plugins/detail/$1/html';

$config['profiles/([^/]+)/']             = 'plugins/sandbox/$1';
$config['profiles/([^/]+)/plugins/']     = 'plugins/sandbox/$1';
$config['profiles/([^/]+)/plugins.json'] = 'plugins/sandbox/$1/json';

$config['profiles/([^/]+)/plugins/detail/([^\.]+).json'] = 
    'plugins/detail/$2/json/$1';
$config['profiles/([^/]+)/plugins/detail/([^\.]+).html'] = 
    'plugins/detail/$2/html/$1';
$config['profiles/([^/]+)/plugins/detail/([^;]+);(.*)'] = 
    'plugins/$3/$2/$1';
$config['profiles/([^/]+)/plugins/detail/(.*)'] = 
    'plugins/detail/$2/html/$1';

$config['api/v1/lookup'] = 'search/lookup_apiv1';

$config['_default'] = 'index';
