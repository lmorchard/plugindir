<?php
/**
 * Basic utility helper to manage the Twig environment with Kohana 
 * configuration
 *
 * @package    Twig_Module
 * @subpackage helpers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class twigutil_Core {

    public static $loader = null;
    public static $env = null;

    /**
     * Get a Twig_Loader instance based on Kohana config.
     */
    public function getLoader() {
        if (!self::$loader) {
            self::$loader = new Twig_Loader_Filesystem(
                Kohana::config('twig.template_path')
            );
        }
        return self::$loader;
    }

    /**
     * Get a Twig_Environment instance based on Kohana config.
     */
    public function getEnv() {
        if (!self::$env) {
            self::$env = new Twig_Environment(
                self::getLoader(), 
                array(
                    'cache' => Kohana::config('twig.cache'),
                    'auto_reload' => Kohana::config('twig.auto_reload'),
                    'trim_blocks' => Kohana::config('twig.trim_blocks'),
                )
            );
            self::$env->addExtension(new Twig_Extension_L10N());
            self::$env->addExtension(new Kohana_Twig_Extension());
        }
        return self::$env;
    }

    /**
     * Load a template based on Kohana config.
     *
     * @param string Filename for the template.
     */
    public function loadTemplate($filename) {
        return self::getEnv()->loadTemplate($filename);
    }

}
