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

        View::set_global(array(
            'base_url'          => url::base(),
            'router_controller' => Router::$controller,
            'router_method'     => Router::$method,
        ));
    }

    /**
     * Perform some last minute stuff before rendering the template.
     */
    public function render()
    {
        parent::render();
    }
    
}
