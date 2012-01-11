<?php
require_once Kohana::find_file('libraries','Gettext/Main');
/**
 * L10N enabled URL helper
 *
 * Ensures current language is prepended to all site() URLs, unless the 
 * initial path segment is found in the list of path exceptions.
 *
 * @package    l10n
 * @subpackage helpers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class url extends url_Core {

    /**
     * Return the current URL with the language path snipped off the front, if
     * present.
     *
     * @param   boolean  include the query string
     * @return  string
     */
    public static function current($qs = false)
    {
        $current = parent::current($qs);
        $lang_path = Gettext_Main::$current_language . '/';
        if (0 !== strpos($current, $lang_path)) {
            return $current;
        } else {
            return substr($current, strlen($lang_path));
        }
    }

    /**
     * Auto-prepend the language to all site URLs.
     */
    public static function site($uri = '', $protocol = FALSE)
    {
        $segs = explode('/', $uri);
        $path_exceptions = Kohana::config('locale.path_exceptions');
        if (!in_array($segs[0], $path_exceptions)) {
            $uri = Gettext_Main::$current_language . '/' . $uri;
        }
        return parent::site($uri, $protocol);
    }

}
