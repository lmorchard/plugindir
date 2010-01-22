<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Core
 *
 * Default language locale name(s).
 * First item must be a valid i18n directory name, subsequent items are alternative locales
 * for OS's that don't support the first (e.g. Windows). The first valid locale in the array will be used.
 * @see http://php.net/setlocale
 */
$config['language'] = array('en_US', 'English_United States');

$config['valid_languages'] = array(

    'en_US' => 'English/United States', 

    // More locales to come...
    //'en_CA' => 'English/Canada',
    //'zh_CN' => 'Chinese/China',

    // Silly talks, see bin/silly-po.sh
    'xx_b1ff'       => 'Silly/B1FF', 
    'xx_chef'       => 'Silly/Chef', 
    'xx_pirate'     => 'Silly/Pirate', 
    'xx_warez'      => 'Silly/Warez',
);

/**
 * Locale timezone. Defaults to use the server timezone.
 * @see http://php.net/timezones
 */
$config['timezone'] = '';
