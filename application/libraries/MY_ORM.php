<?php
/**
 * ORM class that consults config for which database to use.
 *
 * @package    LMO_Utils
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class ORM extends ORM_Core {

    /**
     * Initialize the object with a configured database.
     */
	public function __initialize()
	{
		if (!is_object($this->db)) {
            $this->db = Database::instance(
                Kohana::config('model.database')
            );
        }
        parent::__initialize();
	}

    /**
     * Attempt to fetch the record for the given model and ID, creating a new 
     * one using the supplied data if none found.
     *
	 * @chainable
	 * @param   string  model name
	 * @param   mixed   parameter for find()
     * @param   array   model data for insert
	 * @return  ORM
     */
    public static function find_or_insert($model, $id_or_terms, $data, $save=false) {
        
        if (is_string($id_or_terms)) {
            $obj = ORM::factory($model, $id_or_terms);
        } else {
            $obj = ORM::factory($model)->where($id_or_terms)->find();
        }

        if ($save || !$obj->loaded) {
            foreach ($data as $name=>$val) {
                if (!isset($obj->table_columns[$name])) continue;
                $obj->{$name} = $val;
            }
            $obj->save();
        }
        return $obj;
    }

    /**
	 * Sets object values from an array.
	 *
	 * @chainable
	 * @return  ORM
     */
    public function set($arr=null)
    {
        if (empty($arr)) return;
        foreach ($arr as $name=>$value) {
            if (isset($this->table_columns[$name]))
                $this->{$name} = $value;
        }
        return $this;
    }

    /**
     * Before saving, update created/modified timestamps and generate a UUID if 
     * necessary.
     *
	 * @chainable
	 * @return  ORM
     */
    public function save()
    {
        if (isset($this->table_columns['created']) && empty($this->created)) {
            $this->created = gmdate('c');
        }
        if (isset($this->table_columns['modified'])) {
            $this->modified = gmdate('c');
        }
        if (isset($this->table_columns['uuid']) && empty($this->uuid)) {
            $this->uuid = uuid::uuid();
        }
        return parent::save();
    }

}
