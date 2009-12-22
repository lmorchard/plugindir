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

        if (!isset($_SERVER['argc'])) {
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
            $plugin = ORM::factory('plugin')->import(
                json_decode(file_get_contents($fn), true)
            );
            echo "\t{$plugin->id}: ";
            foreach ($plugin->pluginreleases as $release) echo "{$release->id} ";
            echo "\n";
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

}
