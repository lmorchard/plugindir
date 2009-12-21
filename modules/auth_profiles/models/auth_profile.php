<?php
/**
 * Profiles model
 *
 * @package    auth_profiles
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Auth_Profile_Model extends ORM implements Zend_Acl_Role_Interface, Zend_Acl_Resource_Interface
{
    // {{{ Model attributes

    // Titles for named columns
    public $table_column_titles = array(
        'id'             => 'ID',
        'uuid'           => 'UUID',
        'screen_name'    => 'Screen name',     
        'full_name'      => 'Full name',
        'bio'            => 'Bio',
        'created'        => 'Created',
        'role'           => 'Role',
        'last_login'     => 'Last login',
    );

    public $has_and_belongs_to_many = array('logins');

    // }}}

    /**
     * Find the default login for this profile, usually the first registered.
     * @TODO: Change point for future multiple logins per profile
     */
    public function find_default_login_for_profile()
    {
        if (!$this->loaded) return null;
        $logins = $this->logins;
        return $logins[0];
    }

    /**
     * Find profiles by role name.
     *
     * @param  string|array Role name or names
     * @return ORM_Iterator
     */
    public function find_all_by_role($role_name)
    {
        if (!is_array($role_name)) $role_name = array($role_name);
        return $this
            ->in('role', $role_name)
            ->find_all();
    }


    /**
     * Create a new login and associated profile.
     */
    public function register_with_login($data, $force_email_verified=false)
    {
        $profile_data = array(
            'login_name' => $data['login_name'],
            'email'      => ($force_email_verified) ? $data['email'] : '',
            'created'    => gmdate('c', time())
        );

        $new_login = ORM::factory('login')->set($profile_data)->save();

        $new_login->change_password($data['password']);

        $profile_data['id'] = $new_login->id;

        if (!$force_email_verified) {
            $profile_data['new_email'] = $data['email'];
            $profile_data['email_verification_token'] = 
                $new_login->set_email_verification_token($data['email']);
        }

        $new_profile = ORM::factory('profile')->set($data)->save();

        $new_login->add($new_profile);
        $new_login->save();

        $data = array(
            'login'   => $new_login->as_array(),
            'profile' => $new_profile->as_array()
        );
        Event::run('auth_profiles.registered', $data);

        return arr::to_object($profile_data);
    }


    /**
     * Replace incoming data with registration validator and return whether 
     * validation was successful.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_registration(&$data)
    {
        $login_model = new Login_Model();

        if (empty($data['screen_name']) && !empty($data['login_name'])) {
            $data['screen_name'] = $data['login_name'];
        }

        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('login_name',       
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($login_model, 'is_login_name_available'))
            ->add_rules('email', 
                'required', 'length[3,255]', 'valid::email',
                array($login_model, 'is_email_available'))
            ->add_rules('email_confirm', 
                'required', 'valid::email', 'matches[email]')
            ->add_rules('password', 'required')
            ->add_rules('password_confirm', 'required', 'matches[password]')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'is_screen_name_available'))
            ->add_rules('full_name', 'required', 'valid::standard_text')
            ->add_rules('captcha', 'required', 'Captcha::valid')
            ;
        return $data->validate();
    }

    /**
     * Validate form data for profile creation, optionally saving it if valid.
     */
    public function validate_create(&$data, $save = FALSE)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'is_screen_name_available'))
            ->add_rules('full_name', 'required', 'valid::standard_text')
            ;
        return $this->validate($data, $save);
    }

    /**
     * Validate form data for profile modification, optionally saving if valid.
     */
    public function validate_update(&$data, $save = FALSE)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('screen_name',      
                'required', 'length[3,64]', 'valid::alpha_dash', 
                array($this, 'is_screen_name_available'))
            ->add_rules('full_name', 'required', 'valid::standard_text')
            ;
        return $this->validate($data, $save);
    }

    /**
     * Determine whether a given screen name has been taken.
     *
     * @param  string   screen name
     * @return boolean
     */
    public function is_screen_name_available($name)
    {
        if ($this->loaded && $name == $this->screen_name) {
            return true;
        }
        $count = $this->db
            ->where('screen_name', $name)
            ->count_records($this->table_name);
        return (0==$count);
    }


    /**
     * Returns the unique key for a specific value. This method is expected
     * to be overloaded in models if the model has other unique columns.
     *
     * If the key used in a find is a non-numeric string, search 'screen_name' column.
     *
     * @param   mixed   unique value
     * @return  string
     */
    public function unique_key($id)
    {
        if (!empty($id) && is_string($id) && !ctype_digit($id)) {
            return 'screen_name';
        }
        return parent::unique_key($id);
    }


    /**
     * Set a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @param string Profile attribute value
     */
    public function set_attribute($name, $value)
    {
        if (!$this->loaded) return null;
        $profile_id = $this->id;

        $row = $this->db
            ->select()->from('profile_attributes')
            ->where('profile_id', $profile_id)
            ->where('name', $name)
            ->get()->current();

        if (null == $row) {
            $data = array(
                'profile_id' => $profile_id,
                'name'       => $name,
                'value'      => $value
            );
            $data['id'] = $this->db
                ->insert('profile_attributes', $data)
                ->insert_id();
        } else {
            $this->db->update(
                'profile_attributes', 
                array('value' => $value),
                array('profile_id'=>$profile_id, 'name'=>$name)
            );
        }
    }

    /**
     * Set profile attributes
     *
     * @param string Profile ID
     * @param array list of profile attributes
     */
    public function set_attributes($attributes)
    {
        foreach ($attributes as $name=>$value) {
            $this->set_attribute($name, $value);
        }
    }

    /**
     * Get a profile attribute
     *
     * @param string Profile ID
     * @param string Profile attribute name
     * @return string Attribute value 
     */
    public function get_attribute($name)
    {
        if (!$this->loaded) return null;
        $profile_id = $this->id;

        $select = $this->db
            ->select('value')
            ->from('profile_attributes')
            ->where('profile_id', $profile_id)
            ->where('name', $name);
        $row = $select->get()->current();
        if (null == $row) return false;
        return $row->value;
    }

    /**
     * Get all profile attributes
     *
     * @param string Profile ID
     * @return array Profile attributes
     */
    public function get_attributes($names=null)
    {
        if (!$this->loaded) return null;
        $profile_id = $this->id;

        $select = $this->db->select()
            ->from('profile_attributes')
            ->where('profile_id', $profile_id);
        if (null != $names) {
            $select->in('name', $names);
        }
        $rows = $select->get();
        $attribs = array();
        foreach ($rows as $row) {
            $attribs[$row->name] = $row->value;
        }
        return $attribs;
    }


    /**
     * Check if this profile has the given privilege for the given resource.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @param  string                             $privilege
     * @return boolean
     */
    public function is_allowed($resource, $priv)
    {
        return authprofiles::$acls->isAllowed($this, $resource, $priv);
    }

    /**
     * Get the role identifier for ACLs
     *
     * @return string
     */
    public function getRoleId() {
        return empty($this->role) ? 
            Kohana::config('auth_profiles.base_profile_role') :
            $this->role;
    }

    /**
     * Identify this model as a resource for Zend_ACL
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'profile';
    }

}
