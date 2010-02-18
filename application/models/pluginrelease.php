<?php
/**
 * Platform release model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class PluginRelease_Model extends ORM {

    protected $table_name = 'plugin_releases';

    public $has_one = array(
        'os', 'platform'
    );

    public $belongs_to = array(
        'plugin'
    );

    public static $defaults = array(
        'os_name' => '*',
        'detection_type' => 'original',
        'vulnerability_url' => '',
        'vulnerability_description' => ''
    );

    /**
     * Build an array full of the properties of this release - plus the 
     * properties of the associated plugin, platform, and OS.
     */
    public function as_array()
    {
        $arr = parent::as_array();
        $arr['plugin'] = $this->plugin->as_array();
        $arr['platform'] = $this->platform->as_array();
        $arr['os'] = $this->os->as_array();
        return $arr;
    }

}
