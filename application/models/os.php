<?php
/**
 * OS model
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class OS_Model extends ORM {
    
    protected $table_name = "oses";

    /**
     * Look up all the OSes by name, associated with plugin counts for each.
     *
     * @return array List of name / count arrays.
     */
    public function find_release_counts()
    {
        return $this->db->query("
            SELECT count(plugin_releases.id) AS count, oses.id AS id, oses.name AS name
            FROM oses
            JOIN plugin_releases WHERE oses.id = plugin_releases.os_id
            GROUP BY oses.name
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

    /**
     * Normalize a client OS name into a list of names for fuzzy
     * matching in the DB.
     *
     * @param   string Raw name of client OS
     * @returns array  List of normalized matches
     */
    public static function normalizeClientOS($client_os)
    {
        $list = array();

        $client_os = trim(strtolower($client_os));
        if (!empty($client_os)) 
            $list[] = $client_os;

        if (preg_match('/^windows nt 6\.0/', $client_os)) {
            $list[] = 'windows vista';
        }
        if (preg_match('/^win/', $client_os)) {
            $list[] = 'win';
        }
        if (preg_match('/^ppc mac os x/', $client_os)) {
            $list[] = 'ppc mac os x';
        }
        if (preg_match('/^intel mac os x/', $client_os)) {
            $list[] = 'intel mac os x';
        }
        if (preg_match('/^macintel/', $client_os)) {
            $list[] = 'intel mac os x';
            $list[] = 'mac os x';
            $list[] = 'mac';
        }
        if (preg_match('/^(ppc|intel) mac os x/', $client_os)) {
            $list[] = 'mac os x';
            $list[] = 'mac';
        }
        if (preg_match('/^linux/', $client_os)) {
            $list[] = 'linux';
        }
        if (preg_match('/^linux.+i\d86/i', $client_os)) {
            $list[] = 'linux x86';
        }
        
        if (preg_match('/^sunos/i', $client_os)) {
            $list[] = 'sunos';
            if (preg_match('/sun4u/i', $client_os)) {
                $list[] = 'sunos sun4u';
            }
        }

        $list[] = '*';

        return $list;
    }

}
