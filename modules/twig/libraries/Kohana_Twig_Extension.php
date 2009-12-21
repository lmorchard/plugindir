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
     */
    public function getFilters()
    {
        $filters = array();

        $filters['url_site'] = array('url::site', false );
        $filters['url_base'] = array('Kohana_Twig_Extension::filter_urlBase', false);

        return $filters;
    }

    public static function filter_urlBase($string)
    {
        return url::base() . $string;
    }

}
