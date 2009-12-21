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
    function index($by_cat=null)
    {
        if (!$by_cat) {
            $by_cat = $this->input->get('by');
        }

        $this->view->by_cat = $by_cat;

        switch ($by_cat) {
            case 'name':
                $this->view->name_counts = 
                    ORM::factory('plugin')->find_release_counts();
                break;
            case 'application':
                $this->view->platform_counts = 
                    ORM::factory('platform')->find_release_counts();
                break;
            case 'os':
                $this->view->os_counts = 
                    ORM::factory('os')->find_release_counts();
                break;
            case 'installed':
            default:
                $this->view->by_cat = 'installed';
                break;
        }

        $this->view->set_filename('index/index_by' . $this->view->by_cat);
    }

}
