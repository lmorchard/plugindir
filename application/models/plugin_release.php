<?php
/**
 * Platform release model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Plugin_Release_Model extends ORM {

    protected $table_name = 'plugin_releases';

    public static $defaults = array(
        'os_name' => '*',
        'detection_type' => 'original',
    );

}
