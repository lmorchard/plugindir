<?php
/**
 * Simple JSON output rendering helper
 *
 * @package    LMO_Utils
 * @subpackage helpers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class json_Core
{
    
    /**
     * Render the given data structure as JSON, optionally wrapped in the given 
     * callback function.
     *
     * @param mixed  Data structure for JSON rendering
     * @param string Callback function string in which to wrap JSON
     */
    public static function render($out, $callback=null)
    {
        if ($callback) {
            header('Content-Type: text/javascript');
            // Whitelist the callback to alphanumeric and a few mostly harmless
            // characters, none of which can be used to form HTML or escape a JSONP call
            // wrapper.
            $callback = preg_replace(
                '/[^0-9a-zA-Z\(\)\[\]\,\.\_\-\+\=\/\|\\\~\?\!\#\$\^\*\: \'\"]/', '', 
                $callback
            );
            echo "$callback(";
        } else {
            header('Content-Type: application/json');
        }

        echo json_encode($out);

        if ($callback) echo ')';
    }

}
