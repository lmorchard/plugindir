<?php
/**
 * Plugin model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Plugin_Model extends ORM {
    
    public static $status_codes = array(
        'unknown'    => 0,
        'latest'     => 10,
        'outdated'   => 20,
        'vulnerable' => 30,
    );

    public $has_and_belongs_to_many = array(
        'mimetypes'
    );

    /**
     * Assemble a data structure suitable for later import from plugin records.
     */
    public function export()
    {

    }

    /**
     * Import a plugin into the database from data structure.
     */
    public static function import($plugin_data, $delete_first=FALSE)
    {
        $db = Database::instance(Kohana::config('model.database'));

        // Grab the overall metadata for the plugin.
        $meta = $plugin_data['meta'];

        // Delete the plugin before replacing the data.
        if ($delete_first) {
            $q = $db->query( 
                "DELETE FROM plugins WHERE pfs_id=?", $meta['pfs_id']
            );
        }

        // Find or update the main plugin record.
        $plugin = ORM::find_or_insert(
            'plugin', $meta['pfs_id'], $meta, true
        );

        // Get all the mimetypes handled by the plugin, adding to the DB first 
        // if necessary.
        $mime_ids = array();
        if (isset($plugin_data['mimes'])) {
            foreach ($plugin_data['mimes'] as $mime_def) {
                if (is_string($mime_def)) {
                    $mime_def = array( 'name' => $mime_def );
                }
                $mime = ORM::find_or_insert(
                    'mimetype', $mime_def['name'], $mime_def
                );
                $mime_ids[] = $mime->id;
            }
        }

        // Update the plugin with the list of mimetypes.
        $plugin->mimetypes = array_unique($mime_ids);
        $plugin->save();

        // Set up aliases accumulator
        $aliases = array(
            'literal' => isset($plugin_data['aliases']['literal']) ? 
                $plugin_data['aliases']['literal'] : array(),
            'regex' => isset($plugin_data['aliases']['regex']) ? 
                $plugin_data['aliases']['regex'] : array(),
        );

        // Iterate through each of the known releases and update/create 
        // records for each.
        $releases = array();
        $release_ids = array();
        foreach ($plugin_data['releases'] as $release_data) {

            // Assemble the release data with defaults from plugin data.
            $release_data = array_merge(
                Plugin_Release_Model::$defaults, $meta, $release_data
            );

            if (!isset($release_data['detected_version'])) {
                $release_data['detected_version'] = $release_data['version'];
            }

            // Assign the release to the current plugin.
            $release_data['plugin_id'] = $plugin->id;

            // Find the designated OS and point the release to it.
            $os = ORM::find_or_insert(
                'os', $release_data['os_name'], 
                array('name'=>$release_data['os_name'])
            );
            $release_data['os_id'] = $os->id;

            // Find the designated platform and point the release to it.
            $platform_data = array_merge(
                Platform_Model::$defaults, $release_data['platform']
            );
            $platform = ORM::find_or_insert(
                'platform', $platform_data, $platform_data
            );
            $release_data['platform_id'] = $platform->id;

            // Convert status name to status code from data.
            if (!empty($release_data['status']) && 
                    !empty(self::$status_codes[$release_data['status']])) {
                $release_data['status_code'] = 
                    self::$status_codes[$release_data['status']];
            }

            // Force a vulnerable status if a vulnerability is described.
            if (!empty($release_data['vulnerability_description']) ||
                    !empty($release_data['vulnerability_url'])) {
                $release_data['status_code'] = 
                    self::$status_codes['vulnerable'];
            }

            // Find and update or create the appropriate release.
            $release = ORM::find_or_insert(
                'plugin_release', 
                array(
                    'plugin_id'        => $plugin->id,
                    'os_id'            => $os->id,
                    'platform_id'      => $platform->id,
                    'version'          => $release_data['version'],
                    'detected_version' => $release_data['detected_version'],
                    'detection_type'   => $release_data['detection_type'],
                ),
                $release_data,
                true
            );

            // Stash the ID for this plugin release.
            $releases[] = $release;
            $release_ids[] = $release->id;

            // Add another name to the literal pile.
            $aliases['literal'][] = $release_data['name'];
        }

        // Finally, create appropriate records to give the plugin aliases 
        // based on specified literal and regex names, as well as literal 
        // names accumulated from releases.
        foreach (array('literal', 'regex') as $kind) {
            $is_regex = ('regex' == $kind) ? 1 : 0;
            $a = array_unique($aliases[$kind]);
            foreach ($a as $alias) {
                $alias_data = array(
                    'plugin_id' => $plugin->id,
                    'alias'     => $alias,
                    'is_regex'  => $is_regex
                );
                $alias = ORM::find_or_insert(
                    'plugin_alias', $alias_data, $alias_data
                );
            }
        }

        // Delete plugin releases not included in this import, assuming 
        // deletion by omission.
        if (!$delete_first) {
            $db->query(
                "DELETE FROM plugin_releases ".
                "WHERE plugin_id=? AND ".
                "id NOT IN (". join(',', $release_ids).")",
                $plugin->id
            );
        }

        return array($plugin, $releases);

    }


    /**
     * Allow mime-types to be referred to by name.
     */
    public function unique_key($id = NULL)
    {
        if (!empty($id) AND is_string($id) AND !ctype_digit($id) ) {
            return 'pfs_id';
        }
        return parent::unique_key($id);
    }

}
