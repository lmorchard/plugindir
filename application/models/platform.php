<?php
/**
 * Platform model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Platform_Model extends ORM {

    public static $defaults = array(
        'app_id' => '*',
        'app_release' => '*',
        'app_version' => '*',
        'locale' => '*'
    );
    
}
