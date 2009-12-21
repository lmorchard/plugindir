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
        'newer'      => 40,
        'uncertain'  => 50,
    );

    public static $status_choices = array(
        'unknown'    => 'Unknown',
        'latest'     => 'Latest',
        'outdated'   => 'Outdated',
        'vulnerable' => 'Vulnerable',
        'newer'      => 'Newer',
        'uncertain'  => 'Uncertain',
    );

    public $has_and_belongs_to_many = array(
        'mimetypes'
    );

    public $has_many = array(
        'pluginreleases', 'pluginaliases'
    );

    public $belongs_to = array(
        'sandbox_profile' => 'profile'
    );

    /**
     * Assemble a count of releases by plugin.
     */
    public function find_release_counts()
    {
        return $this->db->query("
            SELECT count(plugin_releases.id) AS count, plugins.*
            FROM plugins
            JOIN plugin_releases WHERE plugins.id = plugin_releases.plugin_id
            GROUP BY plugins.id
            ORDER BY plugins.name ASC
        ")->result_array();
    }

    /**
     * Assemble a data structure suitable for later import from plugin records.
     */
    public function export()
    {
        // Build the export scaffolding.
        $out = array(
            'meta' => array(
            ),
            'aliases' => array(
                'literal' => array(),
                'regex'   => array()
            ),
            'mimes' => array(
            ),
            'releases' => array(
            ),
        );

        // Fill up the meta section from plugin columns.
        $plugin_skip_names = array(
            'id', 'os_id', 'modified', 'created'
        );
        foreach ($this->table_columns as $name=>$info) {
            // Skip empty columns and columns named for skip.
            if (empty($this->{$name})) continue;
            if (in_array($name, $plugin_skip_names)) continue;
            // Stash the plugin column in the meta section.
            $out['meta'][$name] = $this->{$name};
        }

        // Assemble all aliases and collate into appropriate section.
        foreach ($this->pluginaliases as $alias) {
            $out['aliases'][$alias->is_regex ? 'regex' : 'literal'][] = 
                $alias->alias;
        }

        // Assemble all mimetypes into the export.
        foreach ($this->mimetypes as $mimetype) {
            $out['mimes'][] = $mimetype->name;
        }

        // Gather up the plugin releases...
        $codes_to_status = array_flip(self::$status_codes);
        $release_skip_names = array(
            'id', 'plugin_id', 'platform_id', 'os_id', 'modified', 'created'
        );
        foreach ($this->pluginreleases as $release) {

            // Munge each release into export form...
            $release_out = array();
            foreach ($release->table_columns as $name=>$def) {

                // Skip empty release columns, columns named for skip, and any 
                // columns whose redundant values duplicate the plugin metadata 
                // of the same name.
                if (empty($release->{$name})) continue;
                if (in_array($name, $release_skip_names)) continue;
                if (isset($out['meta'][$name]) && 
                    $out['meta'][$name] == $release->{$name}) continue;

                $value = $release->{$name};

                // Convert status codes to status names.
                if ('status_code' == $name) {
                    $name = 'status';
                    $value = $codes_to_status[$value];
                }

                // Stash the release column into the export record.
                $release_out[$name] = $value;
            }

            // Stash the release OS name.
            $release_out['os_name'] = $release->os->name;

            // Stash the release platform record, discarding the database ID.
            $release_out['platform'] = $release->platform->as_array();
            unset($release_out['platform']['id']);

            // Finally, add the munced release to the export data.
            $out['releases'][] = $release_out;
        }

        return $out;
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
                PluginRelease_Model::$defaults, $meta, $release_data
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
                'pluginrelease', 
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
                    'pluginalias', $alias_data, $alias_data
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
     * Perform DB lookup based on criteria from parameters
     */
    public function lookup($criteria)
    {
        $criteria = array_merge(array(
            'mimetype' => '',
            'clientOS' => '',
            'appID' => '',
            'appVersion' => '',
            'appRelease' => '',
            'chromeLocale' => ''
        ), $criteria);

        // Check to see if any of the other params are empty, causing a
        // shortcircuit straight to empty results.
        $req_empty = false;
        foreach ($criteria as $name => $value) {
            // All params are required at present
            if (empty($value)) {
                $req_empty = true; break;
            }
        }
        if ($req_empty) {
            // Missing required criteria, so punt.
            // TODO: Respond with an error someday?
            return array();
        }

        if (!is_array($criteria['mimetype'])) {
            $criteria['mimetype'] = explode(' ', $criteria['mimetype']);
        }

        $platform_model = ORM::factory('platform');
        $os_model = ORM::factory('os');
        $plugin_release_model = ORM::factory('pluginrelease');

        $select = array();
        $cols = array();
        foreach (array('plugin', 'pluginrelease', 'os', 'platform') as $model_name) {
            $model = ORM::factory($model_name);
            foreach ($model->table_columns as $name=>$details) {
                $select[] = "{$model->table_name}.{$name} as {$model_name}_{$name}";
                $cols["{$model_name}_{$name}"] = $name;
            }
        }

        $this->db
            ->select($select)
            ->from('plugins')
            ->join('plugin_releases', 'plugin_releases.plugin_id', 'plugins.id')
            ->groupby('plugin_releases.id')

            // HACK: (kind of) Since '*' sorts last in the list, this is roughly a 
            // relevance sort for platforms and OS
            ->orderby(array(
                'plugin_releases.version' => 'DESC',
                'oses.name' => 'DESC', 
                'platforms.locale' => 'DESC', 
                'platforms.app_id' => 'DESC',
                'platforms.app_version' => 'DESC', 
                'platforms.app_release' => 'DESC',
            ))
            ;

        // Add client OS criteria to the SQL
        $criteria['clientOS'] = OS_Model::normalizeClientOS(@$criteria['clientOS']);
        $this->db
            ->join('oses', 'oses.id', 'plugin_releases.os_id')
            ->in('oses.name', $criteria['clientOS'])
            ;

        // Add a search for platform to SQL
        $this->db
            ->join('platforms', 'platforms.id', 'plugin_releases.platform_id')
            ->in('platforms.app_id', array($criteria['appID'], '*'))
            ->in('platforms.app_version', array($criteria['appVersion'], '*'))
            ->in('platforms.app_release', array($criteria['appRelease'], '*'))
            ->in('platforms.locale', array($criteria['chromeLocale'], '*'))
            ;

        /*
         * Add a search for mimetype to SQL
         */
        $mimetypes = $criteria['mimetype'];
        if (!is_array($mimetypes)) {
            $mimetypes = array($mimetypes);
        }

        $this->db
            ->join('mimes_plugins', 'mimes_plugins.plugin_id', 'plugins.id')
            ->join('mimes', 'mimes_plugins.mime_id', 'mimes.id')
            ->in('mimes.name', $mimetypes)
            ;

        /* 
         * Fetch all the rows and assemble the results.  
         *
         * Note that plain column names are used here, rather than 
         * fully-qualified table.column names.  This is so that column values 
         * from the plugins table are overwritten by non-empty columns from the 
         * plugin_releases table, offering a sort of inheritance relationship.
         */
        $codes_to_status = array_flip(self::$status_codes);
        $rows = array();
        foreach ($this->db->get() as $row_in) {
            $row = array();
            foreach ($cols as $select_name => $orig_name) {
                if (empty($row_in->{$select_name})) continue;

                $value = $row_in->{$select_name};
                $name = ('os_name' == $select_name) ? 
                    $select_name : $orig_name;

                if ('status_code' == $name) {
                    $name  = 'status';
                    $value = isset($codes_to_status[$value]) ?
                        $codes_to_status[$value] : 'unknown';
                }

                if (in_array($name, array('created', 'modified'))) {
                    $value = gmdate('c', strtotime($value));
                }

                $row[$name] = $value;
            }

            $rows[] = $row;
        }

        /*
         * Group the releases by pfs_id, and reduce releases down to the
         * single most relevant record per version.  That is, records with 
         * non-wildcard matches are preferred over wildcard matches for OS / 
         * platform / etc.
         *
         * This might be better done in the database, but couldn't quite figure
         * out how to do it all in SQL.
         */
        $data = array();
        foreach ($rows as $row) {

            // Grab the pfs_id and version from the row
            $pfs_id  = $row['pfs_id'];
            $version = isset($row['version']) ? $row['version'] : '0.0.0';

            // Initialize the structure for a plugin, if this is the first 
            // release seen.
            if (!isset($data[$pfs_id]['releases'])) {
                $data[$pfs_id] = array(
                    'aliases'  => array(), 
                    'releases' => array()
                );
            }

            // Calculate relevance for current row
            $row['relevance'] = $this->calcRelevance($row, $criteria);

            // Decide whether to store or ignore this release...
            if (empty($data[$pfs_id]['releases'][$version])) {
                // Don't have a release for this version yet, so store it.
                $data[$pfs_id]['releases'][$version] = $row;
            } else {
                // Replace the release we have, if this new one is more relevant.
                $curr_rel = $data[$pfs_id]['releases'][$version]['relevance'];
                if ($row['relevance'] > $curr_rel) {
                    $data[$pfs_id]['releases'][$version] = $row;
                }
            }
        }

        /*
         * Collect aliases for the pfs_id's found in releases.
         */
        if (!empty($data)) {

            $this->db
                ->select('alias, is_regex, pfs_id')
                ->from('plugin_aliases')
                ->join('plugins', 'plugin_aliases.plugin_id', 'plugins.id')
                ->in('plugins.pfs_id', array_keys($data))
                ;

            // Gather the aliases into the output.
            foreach ($this->db->get() as $row) {
                $data[$row->pfs_id]['aliases'][($row->is_regex) ? 'regex' : 'literal'][] = $row->alias;
            }

        }

        /*
         * Perform some final massaging of the data for easier digestion in a 
         * JS client.  Trade pfs_ids for numeric indices; separate releases into
         * the latest release and a list of others.
         */
        $flat = array();
        foreach ($data as $pfs_id => $plugin) {

            $rs = array(
                'latest' => null,
                'others' => array()
            );

            // Comb through releases to find most relevant latest, collect the 
            // rest under 'others'
            foreach ($plugin['releases'] as $version => $r) {
                if ('latest' == $r['status']) {
                   if (!$rs['latest'] || $r['relevance'] > $rs['latest']['relevance']) {
                        $rs['latest'] = $r;
                    }
                } else {
                    $rs['others'][] = $r;
                }
            }

            $plugin['releases'] = $rs;
            $flat[] = $plugin;

        }

        return $flat;
    }
    
    /**
     * Calculate result relevance based on the criteria values.
     */
    public function calcRelevance($row, $criteria) {
        $rel = 0;
        
        // First, bump relevance where the match was not a wildcard
        $cols = array('os_name', 'app_id', 'app_release', 'app_version', 'locale');
        foreach ($cols as $name) {
            if ('*' !== $row[$name]) $rel++;
        }

        // Next, bump the relevance by how close to the top of the list of 
        // normalized client OS alternatives the row's value matches.
        if (!empty($row['os_name']) && !empty($criteria['clientOS'])) {
            foreach ($criteria['clientOS'] as $idx => $c_name) {
                if ($c_name === $row['os_name']) {
                    $rel += (count($criteria['clientOS']) - $idx);
                    break;
                }
            }
        }

        // TODO: Support lists of locales in the same way as OS

        return $rel;
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
