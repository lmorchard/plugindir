<?php
/**
 * Plugin controller
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Plugins_Controller extends Local_Controller {

    /**
     * Home page action
     */
    function detail($pfs_id)
    {
        $plugin = ORM::factory('plugin', $pfs_id);

        if (!$plugin->loaded) {
            header('HTTP/1.1 404 Not Found');
            Event::run('system.404');
            return;
        }

        $this->view->plugin = $plugin->as_array();

        $releases = array();
        foreach ($plugin->pluginreleases as $release) {
            if (!isset($releases[$release->version])) {
                $releases[$release->version] = array();
            }
            $releases[$release->version][] = $release->as_array();
        }
        $this->view->releases = $releases;

    }

}
