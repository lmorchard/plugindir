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
