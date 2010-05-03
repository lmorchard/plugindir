<?php
/**
 * CEF logging helper
 *
 * See also: https://intranet.mozilla.org/Security/Users_and_Logs
 *
 * @package    cef_logging
 * @subpackage helpers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class cef_logging_Core 
{
    public static $cef = null;
    public static $vendor;
    public static $product;
    public static $product_version;
    public static $cef_version = 15;

    const ACCESS_CONTROL_VIOLATION = 'ACE0';
    const ACCOUNT_LOCKED           = 'AE2';
    const ADMIN_ACCOUNT_LOCKED     = 'AE2';
    const NEW_PRIVILEGED_ACCOUNT   = 'AE0';

    /**
     * Helper initialization, called in init hook.
     */
    public static function init()
    {
        self::$vendor          = Kohana::config('cef_logging.vendor');
        self::$product         = Kohana::config('cef_logging.product');
        self::$product_version = Kohana::config('cef_logging.product_version');
        self::$cef_version     = Kohana::config('cef_logging.cef_version');
        self::$syslog_facility = Kohana::config('cef_logging.syslog_facility');

        openlog(self::$product, LOG_ODELAY, self::$syslog_facility);
    }

    /**
     * Emit a CEF log message, with some educated guesses at defaults.
     */
    public static function log($sig, $name, $severity, $extension=null)
    {
        // Try to automagically assemble some defaults for the log line from 
        // request and environmental details.
        $defaults = array(
            'rt' => date('m d Y H:i:s'),
            'dst' => $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'],
            'requestMethod' => strtoupper(request::method()),

            'cs1Label' => 'requestClientApplication',
            'cs1' => Kohana::user_agent(),

            'request' => url::site(url::current(true)),
            'msg' => Router::$controller . '::' . Router::$method,
        );

        // Get the address of the incoming request, using XFF header if we're 
        // behind a proxy.
        $defaults['src'] = 
            array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ?
                $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	
        // If there's an authprofiles module in play, try getting a login name 
        // for the current user.
        if (is_callable(array('authprofiles','get_login'))) {
            $suser = authprofiles::get_login('login_name');
            if (!empty($suser)) {
                $defaults['suser'] = $suser;
                $defaults['suid'] = 0;
            }
        }

        // Merge the extended logging data passed in atop the defaults.
        $extension = empty($extension) ?
            $defaults : array_merge($defaults, $extension);

        // Now, take all the extended logging data and assemble escaped 
        // key/value pairs for string building.
        // https://intranet.mozilla.org/Security/Users_and_Logs#Formatting
        $extension_parts = array();
        foreach ($extension as $key=>$value) {
            foreach (array("|", "\\", "=", "\n", "\r") as $esc_char) {
                $value = str_replace($esc_char, "\\$esc_char", $value);
                $key   = str_replace($esc_char, "\\$esc_char", $key);
            }
            $extension_parts[] = "$key=$value";
        }

        // Build the parts of the log message.
        $message_parts = array(
            "CEF:".self::$cef_version,
            self::$vendor,
            self::$product,
            self::$product_version,
            $sig,
            $name,
            $severity,
            implode(' ', $extension_parts),
        );

        // Finally, emit the log message.
        $priority = LOG_NOTICE;
        syslog($priority, implode("|", $message_parts));
    } 

}
