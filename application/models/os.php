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
