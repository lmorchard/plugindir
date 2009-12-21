<?php
/**
 * Local base controller for application
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Local_Controller extends TwigRender_Controller {

    /**
     * Local controller constructor, set up some common view global vars.
     */
    public function __construct() 
    {
        parent::__construct();
    }

    public function render() {

        $profile = authprofiles::is_logged_in() ?
            authprofiles::get_profile()->as_array() :
            array();

        View::set_global(array(
            'base_url'          => url::base(),
            'router_controller' => Router::$controller,
            'router_method'     => Router::$method,
            'form_data'         => form::$data,
            'form_errors'       => form::$errors,

            'is_logged_in' => authprofiles::is_logged_in(),
            'authprofile'  => $profile,

            // Dirty, but occasionally useful:
            '_POST' => $_POST,
            '_GET'  => $_GET,
        ));

        parent::render();
    }
    
}
