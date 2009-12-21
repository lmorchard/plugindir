<?php
/**
 * Index / home controller
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Index_Controller extends Local_Controller {

    /**
     * Home page action
     */
    function index()
    {
        $by_cat = $this->input->get('by');

        $this->view->by_cat = $by_cat;

        switch ($by_cat) {
            case 'claimed':
                // TODO
                break;

            case 'name':
                $names = array();
                $plugins = ORM::factory('plugin')->find_all();
                foreach ($plugins as $plugin) {
                    $names[] = array(
                        'pfs_id' => $plugin->pfs_id,
                        'name'   => $plugin->name
                    );
                }
                $this->view->plugin_names = $names;
                break;

            case 'application':
                $this->view->platform_counts = 
                    ORM::factory('platform')->find_plugin_counts();
                break;

            case 'os':
                $this->view->os_counts = 
                    ORM::factory('os')->find_plugin_counts();
                break;

            case 'installed':
            default:
                $this->view->by_cat = 'installed';
                break;
        }
    }

}
