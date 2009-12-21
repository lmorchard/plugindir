<?php
/**
 * Twig auto-render controller
 *
 * @package    Twig_Module
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class TwigRender_Controller extends Controller {

    // Wrapped view for current method
    public $view = NULL;

    // Automatically render the layout?
    protected $auto_render = TRUE;

    /**
     * Constructor, sets up the layout and core views, as well as registering 
     * the display handler
     */
    public function __construct()
    {
        parent::__construct();

        $this->view = Twig_View::factory();

        // Register the final display handler.
        Event::add('system.post_controller', array($this, 'render'));
    }

    /**
     * Render a template wrapped in the global layout.
     */
    public function render()
    {
        if (TRUE === $this->auto_render) {

            Event::run('layout.before_auto_render', $this);

            if ($this->view && !$this->view->get_filename()) {
                // If no view filename set, use controller/method by default.
                $this->view->set_filename(
                    Router::$controller . '/' . Router::$method
                );
            }

            if (!empty($this->view)) {
                // Only render the core view, since the layout emptied.
                $this->view->render(true);
            }

            Event::run('layout.auto_rendered', $this);

        }
    }

}
