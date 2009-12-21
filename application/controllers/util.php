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
     * Import one or more JSON files as plugins in the database.
     */
    function import()
    {
        if (!isset($_SERVER['argv'])) {
            return $this->index();
        }
        array_shift($_SERVER['argv']);
        array_shift($_SERVER['argv']);

        foreach ($_SERVER['argv'] as $fn) {
            echo "Importing $fn...\n";
            list($plugin, $releases) =
                Plugin_Model::import(json_decode(file_get_contents($fn), true));
            echo "\t{$plugin->id}: ";
            foreach ($releases as $release) echo "{$release->id} ";
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
