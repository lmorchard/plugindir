<?php
/**
 * Mimetype model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Mimetype_Model extends ORM {

    protected $table_name = "mimes";

    /**
     * Look up all the MIME types by name, associated with plugin counts for each.
     *
     * @return array List of name / count arrays.
     */
    public function find_release_counts()
    {
        return $this->db->query("
            SELECT count(plugin_releases.id) AS count, mimes.id AS id, mimes.name AS name
            FROM plugin_releases
            JOIN plugins ON plugins.id = plugin_releases.plugin_id
            JOIN mimes_plugins ON mimes_plugins.plugin_id = plugins.id
            JOIN mimes ON mimes.id = mimes_plugins.id
            WHERE plugins.sandbox_profile_id IS NULL
            GROUP BY mimes.name
            ORDER BY count DESC
        ")->result_array();
    }

    /**
     * Allow mime-types to be referred to by name.
     */
    public function unique_key($id = NULL)
    {
        if (!empty($id) AND is_string($id) AND !ctype_digit($id) ) {
            return 'name';
        }
        return parent::unique_key($id);
    }

}
