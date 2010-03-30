<?php
/**
 * Plugin model and ACL assertions
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */

/**
 * Plugin model class.
 */
class Plugin_Model extends ORM_Resource {

    // {{{ Relations

    public $has_and_belongs_to_many = array(
        'mimetypes',
    );

    public $has_many = array(
        'pluginreleases', 'pluginaliases'
    );

    public $belongs_to = array(
        'sandbox_profile' => 'profile'
    );

    // }}}
    // {{{ Class constants

    public static $status_codes = array(
        'unknown'    => 0,
        'latest'     => 10,
        'outdated'   => 20,
        'vulnerable' => 30,
        'newer'      => 40,
        'uncertain'  => 50,
    );

    /**
     * Default properties for plugins and releases.
     */
    public static $defaults = array(
        'os_name' => '*',
        'app_id' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}', 
        'app_release' => '*', 
        'app_version' => '*', 
        'locale' => '*', 
        'name' => '', 
        'vendor' => '', 
        'description' => '', 
        'version' => '0.0.0', 
        'detected_version' => '0.0.0',
        'detection_type' => 'original',
        'guid' => '', 
        'vulnerability_description' => '', 
        'vulnerability_url' => '', 
        'filename' => '', 
        'url' => '', 
        'icon_url' => '', 
        'license_url' => '', 
        'manual_installation_url' => '', 
        'xpi_location' => '', 
        'installer_location' => '', 
        'installer_hash' => '', 
        'installer_shows_ui' => '', 
        'needs_restart' => '', 
        'xpcomabi' => '', 
        'min' => '',
        'max' => '',
    );

    // HACK: The contents of these two arrays are defined at the end of the file.
    public static $status_choices = array();
    public static $properties = array();

    // }}}
    
    /**
     * Assemble a count of releases by plugin.
     */
    public function find_release_counts()
    {
        return $this->db->query("
            SELECT count(plugin_releases.id) AS count, plugins.*
            FROM plugins
            JOIN plugin_releases ON plugins.id = plugin_releases.plugin_id
            WHERE sandbox_profile_id IS NULL
            GROUP BY plugins.id
            ORDER BY plugins.name ASC
        ")->result_array();
    }

    /**
     * Assemble plugins for a given profile's sandbox.
     */
    public function find_for_sandbox($profile_id)
    {
        return $this
            ->where('sandbox_profile_id', $profile_id)
            ->orderby('modified', 'DESC')
            ->find_all();
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
    public function import($plugin_data, $delete_first=FALSE)
    {
        Database::disable_read_shadow();
        $db = Database::instance(Kohana::config('model.database'));

        // Grab the overall metadata for the plugin.
        $meta = $plugin_data['meta'];

        // Delete the plugin before replacing the data.
        if ($delete_first) {
            $this->db->where('pfs_id', $meta['pfs_id']);
            if (empty($meta['sandbox_profile_id'])) {
                $this->db->where('plugins.sandbox_profile_id IS NULL');
            } else {
                $this->db->where('sandbox_profile_id', $meta['sandbox_profile_id']);
            }
            $this->db->delete('plugins');
        }

        // Find or update the main plugin record.
        if (empty($meta['sandbox_profile_id'])) {
            $plugin = ORM::find_or_insert(
                'plugin', $meta['pfs_id'], $meta, true
            );
        } else {
            $plugin = ORM::find_or_insert(
                'plugin', array(
                    'pfs_id' => $meta['pfs_id'],
                    'sandbox_profile_id' => $meta['sandbox_profile_id']
                ), $meta, true
            );
        }

        // Get all the mimetypes handled by the plugin, adding to the DB first 
        // if necessary.
        $mime_ids = array();
        if (isset($plugin_data['mimes'])) {
            foreach ($plugin_data['mimes'] as $mime_def) {
                if (is_string($mime_def)) {
                    $mime_def = array( 'name' => $mime_def );
                }
                if (!$mime_def['name']) continue;
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
                Plugin_Model::$defaults, PluginRelease_Model::$defaults, 
                $meta, $release_data
            );

            if (empty($release_data['detection_type'])) {
                $release_data['detection_type'] = 'original';
            }

            if (!isset($release_data['detected_version']) ||
                    '0.0.0' == $release_data['detected_version']) {
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
        $alias_ids = array();
        foreach (array('literal', 'regex') as $kind) {
            $is_regex = ('regex' == $kind) ? 1 : 0;
            $a = array_unique($aliases[$kind]);
            foreach ($a as $alias) {
                if (!$alias) continue;
                $alias_data = array(
                    'plugin_id' => $plugin->id,
                    'alias'     => $alias,
                    'is_regex'  => $is_regex
                );
                $alias = ORM::find_or_insert(
                    'pluginalias', $alias_data, $alias_data
                );
                $alias_ids[] = $alias->id;
            }
        }

        // Delete plugin aliases and releases not included in this import, 
        // assuming deletion by omission.
        if (!$delete_first) {
            if (!empty($alias_ids)) {
                $db->query(
                    "DELETE FROM plugin_aliases ".
                    "WHERE plugin_id=? AND ".
                    "id NOT IN (". join(',', $alias_ids).")",
                    $plugin->id
                );
            }
            if (!empty($release_ids)) {
                $db->query(
                    "DELETE FROM plugin_releases ".
                    "WHERE plugin_id=? AND ".
                    "id NOT IN (". join(',', $release_ids).")",
                    $plugin->id
                );
            }
        }

        Database::enable_read_shadow();
        return $plugin;
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
            'chromeLocale' => '',
            'filename' => false,
            'name' => false,
            'vendor' => false,
            'detection' => false,
            'sandboxScreenName' => false
        ), $criteria);

        // Check to see if any of the other params are empty, causing a
        // shortcircuit straight to empty results.
        $req_empty = false;
        foreach ($criteria as $name => $value) {

            // False values indicate empty is okay, not required.
            if (false === $value) continue;

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

        // Consult the cache first before hitting the DB, but only for 
        // non-sandbox lookups.
        // TODO: Cache invalidation timestamp based on mimetypes & what else?
        // TODO: Cache invalidation after the fact using a PFS ID timestamp in cache? (ie. stale-while-revalidate-ish)
        ksort($criteria);
        $cache_key = 'plugin_lookup_' . sha1(json_encode($criteria));
        if (empty($criteria['sandboxScreenName']) && 
                ($cache_data = @$this->cache->get($cache_key))) {
            return $cache_data;
        }

        // Turn mimetype into an array, if it's not one yet.
        if (!is_array($criteria['mimetype'])) {
            $criteria['mimetype'] = explode(' ', $criteria['mimetype']);
        }

        // HACK: If using a sandbox screen name, get the set of plugin IDs for
        // that profile, allowing their sandbox plugins to supercede live data.
        // This (ab)uses some MySQL GROUP BY trickery to ensure sandbox plugins
        // come out on top, but non-sandboxed plugins are still included.
        //
        // Might be better done as a sub-select, but the expression is awkward
        // with Kohana DB
        $profile_plugin_ids = array();
        if (!empty($criteria['sandboxScreenName'])) {
            // HACK: Using sprintf() here because, for some reason, query param
            // binding isn't working for this query.
            $rows = $this->db->query(sprintf("
                SELECT
                    substring_index(group_concat(
                        `plugins`.`id` ORDER BY `plugins`.`sandbox_profile_id` DESC
                    ), ',', 1) AS `id`
                FROM `plugins`
                LEFT JOIN `profiles` ON `plugins`.`sandbox_profile_id`=`profiles`.`id`
                WHERE `sandbox_profile_id` IS NULL OR `profiles`.`screen_name` = %s
                GROUP BY `pfs_id`
            ", $this->db->escape( $criteria['sandboxScreenName'] )));
            foreach ($rows as $row) {
                $profile_plugin_ids[] = $row->id;
            }
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

        // Add sandbox criteria, if any.  Require non-sandboxed if none.
        if (empty($criteria['sandboxScreenName'])) {
            $this->db->where('plugins.sandbox_profile_id IS NULL');
        } else {
            // Toss in the sandbox_profile_screen_name column
            $select[] = 'profiles.screen_name as plugin_sandbox_profile_screen_name';
            $cols["plugin_sandbox_profile_screen_name"] = 'sandbox_profile_screen_name';

            // Constrain plugins matched to the set constructed with overriding
            // sandbox plugins.
            $this->db
                ->in('plugins.id', $profile_plugin_ids)
                ->join('profiles', 'profiles.id', 'plugins.sandbox_profile_id', 'LEFT')
                ;
        }

        if (!empty($criteria['detection'])) {
            // Add detection type to the SQL, if supplied
            $this->db->in('detection_type', array($criteria['detection'], '*'));
        }

        foreach (array('filename', 'name', 'vendor') as $field_name) {
            if (!empty($criteria[$field_name])) {
                $this->db->where('plugin_releases.'.$field_name, $criteria[$field_name]);
            }
        }

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

        // Finally, roll in all the columns needed.
        $this->db->select($select);

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

            $row['fetched'] = date('c');
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
                $key = ($row->is_regex) ? 'regex' : 'literal';
                $data[$row->pfs_id]['aliases'][$key][] = $row->alias;
            }

        }

        /*
         * Perform some final massaging of the data for easier digestion in a 
         * JS client.  Trade pfs_ids for numeric indices; separate releases into
         * the latest release and a list of others.
         */
        $flat = array();
        foreach ($data as $pfs_id => $plugin) {

            $status_versions = array(
                'vulnerable' => array(),
                'outdated' => array()
            );

            $rs = array(
                'latest' => null,
                'others' => array()
            );

            // Comb through releases to find most relevant latest, collect the 
            // rest under 'others'
            foreach ($plugin['releases'] as $version => $r) {

                // Collect detected versions by status
                if (array_key_exists($r['status'], $status_versions)) {
                    $status_versions[$r['status']][] = $r['detected_version'];
                }

                if ('latest' != $r['status']) {
                    // Not a latest candidate, so stick it with the others.
                    $rs['others'][] = $r;
                } else {
                    // This release is the new latest if:
                    $is_new_latest =
                        // there's no latest chosen yet
                        !$rs['latest'] ||
                        // or this one has better relevance than the existing
                        $r['relevance'] > $rs['latest']['relevance'] ||
                        // or this one has the same relevance, but a higher version
                        (
                            $r['relevance'] == $rs['latest']['relevance'] &&
                            $this->compareVersions($r['version'], 
                                $rs['latest']['version']) > 0
                        );
                    if ($is_new_latest) {
                        $rs['latest'] = $r;
                    }
                }
            }

            $plugin['releases'] = $rs;

            // Special case: if the latest release also describes a
            // vulnerability, mark it with a should_disable status.
            if (!empty($plugin['releases']['latest']['vulnerability_url']) ||
                !empty($plugin['releases']['latest']['vulnerability_description'])) {
                $plugin['releases']['latest']['status'] = 'should_disable';
            }

            // Special case: if the latest shares a detected version with
            // another release having a non-latest status, mark it with a dubious
            // status (i.e. in need of manual checking to account for imprecise 
            // version detection mechanism)
            if (!empty($plugin['releases']['latest']['detected_version'])) {
                $ldv = $plugin['releases']['latest']['detected_version'];
                foreach ($status_versions as $status => $versions) {
                    if (in_array($ldv, $versions)) {
                        $plugin['releases']['latest']['status'] = 'maybe_' . $status;
                    }
                }
            }

            $flat[] = $plugin;

        }

        // Cache the data, return it.
        if (empty($criteria['sandboxScreenName'])) {
            $this->cache->set($cache_key, $flat);
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
            $l_os_name = strtolower($row['os_name']);
            foreach ($criteria['clientOS'] as $idx => $c_name) {
                if ($c_name === $l_os_name) {
                    $rel += (count($criteria['clientOS']) - $idx);
                    break;
                }
            }
        }

        // Sandboxed plugins are more relevant, if present.
        if (!empty($row['sandbox_profile_id'])) $rel++;

        // TODO: Support lists of locales in the same way as OS

        return $rel;
    }


    /**
     * Make a fuzzy search using mimetypes, filename, name, and/or vendor
     * to try digging up a PFS ID for a plugin.  Last ditch effort will
     * attempt to derive an ID from name or filename.
     */
    public function suggestPfsId($criteria, $derive=TRUE, $requery=TRUE) {

        $criteria = array_merge(array(
            'mimetype' => '',
            'filename' => false,
            'name' => false,
            'vendor' => false,
        ), $criteria);

        // Start building the basic DB query to look for PFS ID.
        $this->db
            ->select('pfs_id')
            ->from('plugins')
            ->join('plugin_releases', 'plugin_releases.plugin_id', 'plugins.id')
            ->where('plugins.sandbox_profile_id IS NULL')
            ->groupby('plugin_releases.id')
            ;

        // Add in the search for mimetypes, if any supplied.
        if (!empty($criteria['mimetype'])) {
            $mimetypes = $criteria['mimetype'];
            if (!is_array($mimetypes)) {
                $mimetypes = explode(' ', $mimetypes);
            }
            $this->db
                ->join('mimes_plugins', 'mimes_plugins.plugin_id', 'plugins.id')
                ->join('mimes', 'mimes_plugins.mime_id', 'mimes.id')
                ->in('mimes.name', $mimetypes)
                ;
        }

        // Build up a set of WHERE ... OR criteria
        $orwhere = array();
        foreach (array('filename','name','vendor') as $name) {
            if (!empty($criteria[$name])) {
                $orwhere["plugin_releases.{$name}"] = $criteria[$name];
            }
        }
        if (!empty($orwhere)) $this->db->orwhere($orwhere);

        // Execute the query and try collecting the PFS IDs
        $pfs_ids = array();
        foreach ($this->db->get() as $row) {
            $pfs_ids[] = $row->pfs_id;
        }

        // If there were no IDs found, try the query again with just mimetypes.
        if (empty($pfs_ids) && !empty($mimetypes) && $requery) {
            $sub_criteria = array( 'mimetype' => $criteria['mimetype'] );
            $pfs_ids = $this->suggestPfsId($sub_criteria, false, false);
        }

        // If there were no PFS IDs found, it's time to try deriving one.
        if (empty($pfs_ids) && $derive) {

            // Use name, filename, or nothing.
            if (!empty($criteria['name'])) {
                $source = $criteria['name'];
            } else if (!empty($criteria['filename'])) {
                $source = $criteria['filename'];
            } else {
                $source = null;
            }

            // If we've got a source to start deriving from...
            if ($source) {
                $source = strtolower($source);
                $source = preg_replace('/_/',         '-', $source);
                $source = preg_replace('/ /',         '-', $source);
                $source = preg_replace('/\.plugin$/', '', $source);
                $source = preg_replace('/\.dll$/',    '', $source);
                $source = preg_replace('/\.so$/',     '', $source);
                $source = preg_replace('/\d/',        '', $source);
                $source = preg_replace('/\./',        '', $source);
                $source = preg_replace('/-+$/',       '', $source);
                $pfs_ids[] = $source;
            }

        }

        return array_unique($pfs_ids);
    }

    /**
     * Allow plugins to be referred to by name.
     */
    public function unique_key($id = NULL)
    {
        if (!empty($id) AND is_string($id) AND !ctype_digit($id) ) {
            return 'pfs_id';
        }
        return parent::unique_key($id);
    }


    /**
     * Determine whether this plugin is in a sandbox.
     *
     * @returns bool
     */
    public function is_sandboxed() 
    {
        return !empty($this->sandbox_profile_id);
    }


    /**
     * Add the profile as trusted to this plugin by PFS ID.
     * Note that this covers sandboxed copies, as well.
     *
     * @param Profile $profile Profile to be trusted
     */
    public function add_trusted($profile)
    {
        $rv = $this->db->query("
            INSERT INTO plugins_trustedprofiles
            (pfs_id, profile_id) VALUES ( ?, ? )
            ON DUPLICATE KEY UPDATE pfs_id=pfs_id, profile_id=profile_id
        ", $this->pfs_id, $profile->id);
        $this->db->clear_cache();
        return $rv;
    }

    /**
     * Remove the profile as trusted from this plugin by PFS ID.
     * Note that this covers sandboxed copies, as well.
     *
     * @param Profile $profile Profile to be trusted
     */
    public function remove_trusted($profile)
    {
        $rv = $this->db->query("
            DELETE FROM plugins_trustedprofiles
            WHERE pfs_id=? AND profile_id=?
        ", $this->pfs_id, $profile->id);
        $this->db->clear_cache();
        return $rv;
    }

    /**
     * List the profiles that are trusted by this plugin.
     *
     * @returns array
     */
    public function list_trusted()
    {
        return ORM::factory('profile')
            ->join(
                'plugins_trustedprofiles', 
                'plugins_trustedprofiles.profile_id', 
                'profiles.id'
            )
            ->where('plugins_trustedprofiles.pfs_id', $this->pfs_id)
            ->find_all();
    }

    /**
     * Determine whether this plugin trusts the given profile.
     *
     * @returns bool
     */
    public function trusts($profile)
    {
        if (is_string($profile)) return false;
        $count = $this->db
            ->where(array(
                'pfs_id'=>$this->pfs_id, 
                'profile_id'=>$profile->id
            ))
            ->count_records('plugins_trustedprofiles');
        return $count > 0;
    }


    /**
     * Identify this model as a resource for Zend_ACL
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'plugin';
    }

    /**
     * Pad a number to the left with zeroes
     */
    public function num_pad($num, $pad_len = 6) {
        return str_pad($num, $pad_len, '0', STR_PAD_LEFT);
    }

    /**
     * Quick and dirty version comparison using zero-padding and lexical sort.
     *
     * 1.2.3 -> 000001.000002.000003
     * 1.1.2 -> 000001.000001.000002
     */
    public function compareVersions($av, $bv) {
        $pav = implode('.', array_map(array($this, 'num_pad'), explode('.', $av)));
        $pbv = implode('.', array_map(array($this, 'num_pad'), explode('.', $bv)));
        return strcmp($pav, $pbv);
    }

}

// {{{ Plugin and release properties

/** 
 * HACK: Defined here because PHP doesn't like using _() function calls in the 
 * class definition.
 */
Plugin_Model::$status_choices = array(
    "unknown"        => _("Unknown"),
    "latest"         => _("Latest"),
    "outdated"       => _("Outdated"),
    "vulnerable"     => _("Vulnerable"),
    "newer"          => _("Newer than directory"),
    "uncertain"      => _("Uncertain"),
    "should_disable" => _("Should be disabled")
);

Plugin_Model::$properties = array(
    'pfs_id' => array( 
        'type' => 'text', 
        'description' => _("pfs_id of the plugin within PFS2")
    ),
    'status' => array( 
        'type' => 'status', 
        'description' => _("Current status of the release, eg. latest, outdated, vulnerable")
    ),
    'name' => array( 
        'type' => 'text', 
        'description' => _("Name of the plugin")
    ),
    'version' => array( 
        'type' => 'text', 
        'description' => _("A dot-separated normalized version for the plugin, may differ from official vendor versioning scheme in order to maintain internal consistency in PFS2")
    ),
    'detected_version' => array(
        'type' => 'text',
        'description' => _("Version detected in the client, can differ from vendor-intended version depending on capabilities of detection_type")
    ),
    'detection_type' => array(
        'type' => 'text',
        'description' => _("Detection scheme used in the client to derive the detected_version value")
    ),
    'description' => array( 
        'type' => 'textarea', 
        'description' => _("More verbose description of the plugin")
    ),
    'vendor' => array( 
        'type' => 'text', 
        'description' => _("Name of the vendor providing the plugin")
    ),
    'guid' => array( 
        'type' => 'text', 
        'description' => _("A GUID for the plugin release, may differ between releases and platforms (unlike pfs_id)")
    ),
    'vulnerability_description' => array( 
        'type' => 'text', 
        'description' => _("For status vulnerable, a short description of security vulnerabilities for the plugin release")
    ),
    'vulnerability_url' => array( 
        'type' => 'text', 
        'description' => _("For status vulnerable, a URL detailing security vulnerabilities for the plugin release")
    ),
    'filename' => array( 
        'type' => 'text', 
        'description' => _("Filename of the plugin as installed")
    ),
    'url' => array( 
        'type' => 'text', 
        'description' => _("URL with details describing the plugin")
    ),
    'icon_url' => array( 
        'type' => 'text', 
        'description' => _("URL to an image icon for the plugin")
    ),
    'license_url' => array( 
        'type' => 'text', 
        'description' => _("URL where the license for using the plugin may be found")
    ),
    'manual_installation_url' => array( 
        'type' => 'text', 
        'description' => _("URL for a manually-launched executable installer for the plugin")
    ),
    'xpi_location' => array( 
        'type' => 'text', 
        'description' => _("URL for an XPI-based installer for the plugin")
    ),
    'installer_location' => array( 
        'type' => 'text', 
        'description' => _("URL for an executable installer for the plugin (mainly for Windows)")
    ),
    'installer_hash' => array( 
        'type' => 'text', 
        'description' => _("A hash of the installer contents for verifying its integrity")
    ),
    'installer_shows_ui' => array( 
        'type' => 'text', 
        'description' => _("(0/1) whether or not the installer displays a user interface")
    ),
    'needs_restart' => array( 
        'type' => 'text', 
        'description' => _("(0/1) whether or not the OS needs to restart after plugin installation")
    ),
    'xpcomabi' => array( 
        'type' => 'text', 
        'description' => _("(Not sure, inherited from PFS1, need a description)")
    ),
    'min' => array( 
        'type' => 'text', 
        'description' => _("(Not sure, inherited from PFS1, need a description)")
    ),
    'max' => array( 
        'type' => 'text', 
        'description' => _("(Not sure, inherited from PFS1, need a description)")
    ),
    'app_id' => array( 
        'type' => 'text', 
        'description' => _("Application ID for client app"),
        'parent' => 'platform'
    ),
    'app_release' => array( 
        'type' => 'text', 
        'description' => _("Client app release for which the plugin is intended (* is wildcard)"),
        'parent' => 'platform'
    ),
    'app_version' => array( 
        'type' => 'text', 
        'description' => _("Client app version for which the plugin is intended (* is wildcard)"),
        'parent' => 'platform'
    ),
    'locale' => array( 
        'type' => 'text', 
        'description' => _("Client app locale for which the plugin is intended (* is wildcard)"),
        'parent' => 'platform'
    ),
    'os_name' => array( 
        'type' => 'text', 
        'description' => _("Client app OS for which the plugin is intended (* is wildcard)")
    ),
    'modified' => array( 
        'type' => 'text', 
        'description' => _("Timestamp when last the release record was modified") 
    )
);

// }}}
