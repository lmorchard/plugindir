<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Core
 *
 * Sets the default route to "welcome"
 */
$config['plugins/(.*)'] = 'plugins/detail/$1';
$config['_default'] = 'index';
