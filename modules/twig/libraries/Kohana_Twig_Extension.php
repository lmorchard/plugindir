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
        $filters = array();

        $filters['phpeval']  = array('Kohana_Twig_Extension::filter_phpeval', false);
        $filters['url_site'] = array('url::site', false );
        $filters['url_base'] = array('Kohana_Twig_Extension::filter_urlBase', false);

        return $filters;
    }

    /**
     * This is a nasty escape hatch for PHP one-liners in Twig templates.  When 
     * used sparingly in macros, it's helpful for accessing Kohana helpers.
     */
    public static function filter_phpeval($string)
    {
        return eval($string);
    }

    public static function filter_urlBase($string)
    {
        return url::base() . $string;
    }

}
