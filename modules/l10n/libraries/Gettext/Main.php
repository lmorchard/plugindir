<?php
/**
 * Main static package for gettext module.
 *
 * TODO: Include something like http://fligtar.com/faketext/faketext.phps ?
 */
class Gettext_Main {

    static $default_language = 'en_US';
    static $default_domain   = 'messages';

    static $domain;
    static $domain_path;

    static $current_language = 'en_US';

    /**
     * Initialize the module.
     */
    public static function init()
    {
        if (extension_loaded('gettext')) {
            Event::add('system.post_routing', 
                array(get_class(), 'handle_post_routing'));
        }
    }

    /**
     * After routing is ready, pick up the current language.
     */
    public static function handle_post_routing()
    {
        self::set_domain();
        self::set_language();
    }
    
    /**
     * Set the path for a domain and set the domain.
     *
     * @param string Name of the domain
     * @param string Path of the domain
     */
    public static function set_domain($domain=null, $path=null)
    {
        if (null===$domain)
            $domain = self::$default_domain;
        if (null===$path)
            $path = APPPATH . 'locale';

        self::$domain = $domain;
        self::$domain_path = $path;

        bindtextdomain(self::$domain, self::$domain_path);
        bind_textdomain_codeset(self::$domain, 'UTF-8');
        textdomain(self::$domain);
    }

    /**
     * Set the locale for the application.
     *
     * @param array List of locales
     */
    public static function set_language($langs=null)
    {
        // Build priority list of parameter, ?lang, Accept-Language, and 
        // default language.
        if (null===$langs)
            $langs = array();
        if (!empty(Router::$segments))
            $langs[] = Router::$segments[0];
        if (!empty($_GET['lang']))
            $langs[] = $_GET['lang'];

        $langs = array_merge($langs, Kohana::user_agent('languages'));
        $langs[] = self::$default_language;

        // Grab case-insensitive mappings for valid languages.
        $valid_languages = Kohana::config('locale.valid_languages');
        $u_valid = array();
        if ($valid_languages) foreach ($valid_languages as $valid=>$name) {
            $u_valid[strtolower($valid)] = $valid;
        }

        // Look for the first valid language from the list.
        foreach ($langs as $lang) {

            // Convert candidate locale to lower case, normalize hyphens to 
            // underscores, look for the result in case-insensitive valid 
            // locale matches.
            $lang = str_replace('-','_',strtolower($lang));
            if (empty($u_valid[$lang])) continue;
            $lang = $u_valid[$lang];

            // The candidate matches a valid locale, so use it and stop the 
            // search.
            self::$current_language = Kohana::$locale = $lang;
            setlocale(LC_COLLATE, $lang);
            setlocale(LC_MONETARY, $lang);
            setlocale(LC_NUMERIC, $lang);
            setlocale(LC_TIME, $lang);
            if (defined('LC_MESSAGES')) {
                setlocale(LC_MESSAGES, $lang);
            }
            putenv("LANG=" . $lang);
            return true;

        }
        return false;
    }

}

if (!extension_loaded('gettext')) {
    // TODO: Include something like http://fligtar.com/faketext/faketext.phps ?

    function _($msgid) {
        return $msgid;
    }

} else {

    if (!function_exists('pgettext')) {
        /**
         * See: http://us2.php.net/manual/en/book.gettext.php#89975
         */
        function pgettext($context, $msgid)
        {
            $context_string = "{$context}\004{$msgid}";
            $translation = dcgettext('messages', $context_string, LC_MESSAGES);
            if ($translation == $context_string)  return $msgid;
            else  return $translation;
        }     

        function npgettext($context, $msgid, $msgid_plural, $num) {
            $contextString = "{$context}\004{$msgid}";
            $contextStringp = "{$context}\004{$msgid_plural}";
            $translation = ngettext($contextString, $contextStringp, $num);
            if ($translation == $contextString ||
                $translation == $contextStringp)  return $msgid;
            else  return $translation;
        }
    }

}
