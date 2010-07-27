<?php
/**
 * Index / home controller
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Util_Controller extends Local_Controller {

    protected $auto_render = FALSE;

    /**
     * Constructor
     */
    function __construct() 
    {
        parent::__construct();

        if (PHP_SAPI !== 'cli') {
            echo "For command line use only.";
            die;
        }

        // Clear out the htdocs.php and util/foo
        array_shift($_SERVER['argv']);
        array_shift($_SERVER['argv']);

        $this->db = Database::instance(
            Kohana::config('model.database')
        );
    }

    /**
     * Util tool usage instructions
     */
    function index()
    {
        echo "TODO: Usage instructions\n";
    }

    /**
     * Create a user with name, email, and role.
     */
    function createlogin()
    {
        if (!isset($_SERVER['argv']) || 3 != count($_SERVER['argv'])) {
            echo "Usage: createlogin {screen name} {email} {role}\n";
            die;
        }

        list($login_name, $email, $role) = $_SERVER['argv'];

        Database::disable_read_shadow();

        $user = ORM::factory('login', $login_name);
        if ($user->loaded) {
            echo "Login '{$login_name}' already exists.\n";
            die;
        }

        $password = $this->_rand_string(7);

        if (!ORM::factory('profile')->register_with_login(array(
                'screen_name' => $login_name,
                'login_name' => $login_name,
                'email' => $email,
                'password' => $password
            ), true)) {  
            echo "Problem creating new profile!";
            die;
        };

        $new_profile = ORM::factory('profile', $login_name);
        $new_profile->role = $role;
        $new_profile->save();

        echo "Profile ID {$new_profile->id} created for '{$login_name}'\n"; 
        echo "Password: {$password}\n";
    }

    /**
     * Generate a random string.
     * see: http://www.php.net/manual/en/function.mt-rand.php#76658
     */
    function _rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        $string = '';
        for ($i = 0; $i < $len; $i++)
        {
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
    }

    /**
     * Import one or more JSON files as plugins in the database.
     */
    function import()
    {
        if (!isset($_SERVER['argv'])) {
            return $this->index();
        }

        foreach ($_SERVER['argv'] as $fn) {
            echo "Importing $fn...\n";
	    if (file_exists($fn)) {
                if (is_readable($fn)) {
                    Kohana::log('info', "We can read $fn");
		    $pluginJson = json_decode(file_get_contents($fn), true);

                    if (is_null($pluginJson)) {
                        echo "Unable to read JSON formmated data from $fn ... Skipping\n";
                        if (function_exists('json_last_error')) {//PHP 5.3 and up

                            switch(json_last_error()) {
                                case JSON_ERROR_DEPTH:
                                    echo "JSON ERROR: Maximum stack depth exceeded\n";
                                    break;
                                case JSON_ERROR_CTRL_CHAR:
                                    echo "JSON ERROR: Unexpected control character found\n";
                                    break;
                                case JSON_ERROR_SYNTAX:
                                    echo "JSON ERROR: Syntax error, malformed JSON\n";
                                    break;
                                case JSON_ERROR_NONE:
                                    echo "Huh? - No errors\n";
                                    break;
                            }
                        }
                        continue;
                    } else {
                        $plugin = ORM::factory('plugin')->import($pluginJson);
                        echo "\t{$plugin->id}: ";
                        foreach ($plugin->pluginreleases as $release) echo "{$release->id} ";
                        echo "\n";
                    }
                } else {
		    echo "Unable to read ${fn} Skipping\n";
                }
            } else {
                echo "Expected a filename, got ${fn}... Skipping\n";
            }
        }
    }

    /**
     * Delete a plugin by PFS ID.
     */
    function delete_plugin()
    {
        if (!isset($_SERVER['argv']) || 3 != count($_SERVER['argv'])) {
            return $this->index();
        }
        list($script, $path, $pfs_id) = $_SERVER['argv'];
        // Delete the plugin before replacing the data.
        $q = $this->db->query( 
            "DELETE FROM plugins WHERE pfs_id=?", $pfs_id
        );
    }

    /**
     * Compile all Twig templates and copy the resulting PHP source files from 
     * the cache into temporary files named for the original Twig source files.
     *
     * This helps with l10n message extraction by providing appropriate filename
     * context.
     */
    function compiletemplates() 
    {
        $files = array();
        $dirs = array();
        $this->_find_templates(APPPATH . 'views', $files, $dirs);

        // Create the temporary directory structure.
        $tmp_dir = 'tmp/l10n/views/';
        if (!is_dir($tmp_dir)) { mkdir($tmp_dir, 0777, true); }
        foreach ($dirs as $dn) {
            if (!is_dir($tmp_dir.$dn)) { mkdir($tmp_dir.$dn, 0777, true); }
        }

        echo "Compiling templates...\n";
        foreach ($files as $fn) {
            // Load and compile the template
            $tmpl = twigutil::loadTemplate($fn);
            // Find the filename for the compiled template PHP
            $cache_fn  = twigutil::getEnv()->getCacheFilename($fn);
            // Copy the compiled PHP to the temporary directory structure
            copy($cache_fn, "$tmp_dir/$fn");
            echo "\t$fn\n";
        }
    }

    /**
     * Recursively search for HTML templates under views.
     */
    function _find_templates($root, &$files, &$dirs, $prefix='') 
    {
        $dh = opendir($root);
        while ( false !== ( $fn = readdir($dh) ) ) {
            if ('.' === $fn || '..' === $fn) { continue; }
            $full_fn = "$root/$fn";
            $pre_fn = $prefix ? "$prefix/$fn" : $fn;
            if (is_dir($full_fn)) {
                if (!in_array($pre_fn, $dirs)) {
                    $dirs[] = $pre_fn;
                }
                $this->_find_templates($full_fn, $files, $dirs, $pre_fn);
            } else if (preg_match('/\.html$/', $fn)) {
                $files[] = $pre_fn;
            }
        }
        closedir($dh);
    }

}
