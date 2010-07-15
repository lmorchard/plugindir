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

        switch ($by_cat) {
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
            case 'name':
            default:
                $by_cat = 'name';
                $this->view->name_counts = 
                    ORM::factory('plugin')->find_release_counts();
                break;
        }

        $this->view->events = ORM::factory('auditLogEvent')
            ->orderby(array('created'=>'DESC'))
            ->limit(15)
            ->find_all_for_view();

        $this->view->by_cat = $by_cat;
        $this->view->set_filename('index/index_by' . $this->view->by_cat);
    }

    /**
     * Display a captcha image, stolen from system/controllers/captcha.php 
     * since it has a hardcoded reference into the URI segments.  Language
     * handling seems to break that.
     */
    public function captcha ($group='default')
    {
        $this->auto_render = false;
        // Output the Captcha challenge resource (no html)
        // Pull the config group name from the URL
        Captcha::factory($group)->render(FALSE);
    }

}
