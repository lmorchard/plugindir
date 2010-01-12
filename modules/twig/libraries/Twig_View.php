<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Loads and displays Twig view files.
 *
 * @package    Twig_Module
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Twig_View_Core extends View {

	/**
	 * Creates a new Twig_View using the given parameters.
	 *
	 * @param   string  view name
	 * @param   array   pre-load data
	 * @param   string  type of file: html, css, js, etc.
	 * @return  object
	 */
	public static function factory($name = NULL, $data = NULL, $type = NULL)
	{
		return new Twig_View($name, $data, $type);
	}

    /**
     * Get the current filename set for the view.
     *
     * @return string
     */
    public function get_filename()
    {
        return $this->kohana_filename;
    }

	/**
	 * Sets the view filename.
	 *
	 * @chainable
	 * @param   string  view filename
	 * @return  object
	 */
	public function set_filename($name)
	{
        $this->kohana_filename = $name . '.' . Kohana::config('twig.extension');
		return $this;
	}

	/**
	 * Renders a view.
	 *
	 * @param   boolean   set to TRUE to echo the output instead of returning it
	 * @param   callback  special renderer to pass the output through
	 * @return  string    if print is FALSE
	 * @return  void      if print is TRUE
	 */
	public function render($print = FALSE, $renderer = FALSE)
	{
		if (empty($this->kohana_filename))
			throw new Kohana_Exception('core.view_set_filename');

        // Merge global and local data, local overrides global with the same name
        $data = array_merge(Twig_View::$kohana_global_data, $this->kohana_local_data);

        $template = twigutil::loadTemplate($this->kohana_filename);
        $output   = $template->render($data); 

        if ($renderer !== FALSE AND is_callable($renderer, TRUE)) {
            // Pass the output through the user defined renderer
            $output = call_user_func($renderer, $output);
        }

        if ($print === TRUE) {
            // Display the output
            echo $output;
            return;
        }

		return $output;
	}

} // End View
