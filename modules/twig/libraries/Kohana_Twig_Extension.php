<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Kohana extensions to Twig, makes certain helpers and suchlike available from 
 * Twig templates.
 *
 * @package    Twig_Module
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Kohana_Twig_Extension extends Twig_Extension
{

    /**
     * Return the name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'kohana';
    }

    /**
     * Define the set of filters made available by this extension.
     */
    public function getFilters()
    {
        $filters = array(
            'phpeval'  => new Twig_Filter_Method($this, 'filter_phpeval'),
            'explode'  => new Twig_Filter_Method($this, 'filter_explode'),
            'json'     => new Twig_Filter_Method($this, 'filter_json'),
            'fromjson' => new Twig_Filter_Method($this, 'filter_fromjson'),
        );

        return $filters;
    }

    /**
     * This is a nasty escape hatch for PHP one-liners in Twig templates.  When 
     * used sparingly in macros, it's helpful for accessing Kohana helpers.
     */
    public function filter_phpeval($string)
    {
        return eval($string);
    }

    /**
     * Explode a string into a list.
     */
    public function filter_explode($string, $delim=' ')
    {
        return explode($delim, $string);
    }

    /**
     * Convert a data structure into a JSON string.
     */
    public function filter_json($data)
    {
        return json_encode($data);
    }

    /**
     * Decode a data structure expressed as JSON
     */
    public function filter_fromjson($string)
    {
        return json_decode($string, true);
    }

}
