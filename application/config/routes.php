<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Core
 *
 * Sets the default route to "welcome"
 */
$config['plugins/submit'] = 'plugins/submit';

$config['plugins/detail/([^\.]+).json'] = 'plugins/detail/$1/json';
$config['plugins/detail/([^\.]+).html'] = 'plugins/detail/$1/html';
$config['plugins/detail/([^;]+);(.*)']  = 'plugins/$2/$1';
$config['plugins/detail/(.*)']          = 'plugins/detail/$1/html';

$config['api/v1/lookup'] = 'search/lookup_apiv1';

$config['_default'] = 'index';
