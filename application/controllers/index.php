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
     * Bounce visitors to the site root over to their current language path.
     */
    function locale_redirect()
    {
        return url::redirect(Gettext_Main::$current_language, 301);
    }

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
            case 'mimetype':
                $this->view->mimetype_counts = 
                    ORM::factory('mimetype')->find_release_counts();
                break;
            case 'installed':
            default:
                $this->view->by_cat = 'installed';
                if (authprofiles::is_logged_in()) {
                    $this->view->sandbox_plugins = ORM::factory('plugin')
                        ->find_for_sandbox(authprofiles::get_profile('id'));
                }
                break;
        }

        $this->view->set_filename('index/index_by' . $this->view->by_cat);
    }

}
