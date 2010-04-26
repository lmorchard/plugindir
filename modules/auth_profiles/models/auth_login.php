<?php
/**
 * Login model
 *
 * @package    auth_profiles
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Auth_Login_Model extends ORM implements Zend_Acl_Resource_Interface
{
    // {{{ Model attributes

    // Titles for named columns
    public $table_column_titles = array(
        'id'             => 'ID',
        'login_name'     => 'Login name',     
        'active'         => 'Active',
        'email'          => 'Email',
        'password'       => 'Password',
        'last_login'     => 'Last login',
        'modified'       => 'Modified',
        'created'        => 'Created',
    );
    
    public $has_and_belongs_to_many = array('profiles');

    protected $_table_name_password_reset_token =
        'login_password_reset_tokens';
    protected $_table_name_email_verification_token =
        'login_email_verification_tokens';

    // }}}

    /**
     * One-way hash a plaintext password, both for storage and comparison 
     * purposes.
     *
     * @param  string cleartext password
     * @return string encrypted password
     */
    public function hash_password($password, $salt=null, $algo='SHA-256')
    {
        if ('SHA-256' == $algo) {
            if (null === $salt) {
                // Generate a new random salt, if none provided.
                $salt = substr(md5(uniqid(mt_rand(), true)), 0, 
                    Kohana::config('auth_profiles.salt_length'));
            }
            return '{SHA-256}'.$salt.'-'.hash('sha256', $salt.$password);
        } else {
            return md5($password);
        }
    }

    /**
     * Accept a database password hash and attempt to parse it into algo,
     * salt, and hash values.
     *
     * @param  string $str full DB hash
     * @return array  algo, salt, hash
     */
    public function parse_password_hash($str) 
    {
        $m = array();
        if (1 === preg_match('/^\{([\w-]+)\}(\w+)-(\w+)$/', $str, $m)) {
            // This is a hash in {ALGO}SALT-HASH form.
            return array( $m[1], $m[2], $m[3] );
        } else {
            // Assume this is a legacy MD5 password hash.
            return array( 'MD5', null, $str );
        }
    }

    /**
     * Check a given plaintext password against a full DB hash.
     *
     * @param  string  $password     plaintext password
     * @param  string  $db_full_hash full hash string from DB
     * @return boolean 
     */
    public function check_password($password, $db_full_hash)
    {
        list($algo, $salt, $hash) = $this->parse_password_hash($db_full_hash);
        $password_full_hash = $this->hash_password($password, $salt, $algo);
        return ($password_full_hash === $db_full_hash);
    }

    /**
     * Returns the unique key for a specific value. This method is expected
     * to be overloaded in models if the model has other unique columns.
     *
     * If the key used in a find is a non-numeric string, search 'login_name' column.
     *
     * @param   mixed   unique value
     * @return  string
     */
    public function unique_key($id)
    {
        if (!empty($id) && is_string($id) && !ctype_digit($id)) {
            return 'login_name';
        }
        return parent::unique_key($id);
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
        // Never allow password changes without going through change_password()
        unset($this->password);

        return parent::save();
    }

    /**
     * Perform anything necessary for login on the model side.
     *
     * @param  array|Validation Form data (if any) used in login
     * @return boolean
     */
    public function login($data=null)
    {
        $this->failed_login_count = 0;
        $this->last_login = gmdate('c');
        $this->save();
        return true;
    }

    /**
     * Record a failed login, for eventual purposes of account lockout.
     *
     * @chainable
     * @return Auth_Login_Model
     */
    public function record_failed_login()
    {
        $was_locked_out = $this->is_locked_out();
        $this->failed_login_count = $this->failed_login_count + 1;
        $this->last_failed_login  = gmdate('c');
        $this->save();

        if (!$was_locked_out && $this->is_locked_out()) {
            $profile = $this->find_default_profile_for_login();
            if ($profile->loaded && 'admin' == $profile->role) {
                cef_logging::log(
                    cef_logging::ADMIN_ACCOUNT_LOCKED, 
                    'Admin Account Locked', 9, 
                    array( 'suser' => $this->login_name )
                );
            } else {
                cef_logging::log(
                    cef_logging::ACCOUNT_LOCKED, 
                    'Account Locked', 5, 
                    array( 'suser' => $this->login_name )
                );
            }
        }

        return $this;
    }

    /**
     * Determine whether the login is locked out per the configured
     * threshold of failed logins and the lockout period with respect
     * to the last failed login.
     *
     * @return boolean
     */
    public function is_locked_out()
    {
        // HACK: Switch to UTC for date parsing since all MySQL times 
        // should be in UTC
        $old_tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $time_now = 
            time();
        $last_failed_login = 
            strtotime($this->last_failed_login, $time_now);
        $lockout_threshold = 
            Kohana::config('auth_profiles.max_failed_logins');
        $lockout_period =
            Kohana::config('auth_profiles.account_lockout_period');

        $is_locked_out =  
            $this->failed_login_count >= $lockout_threshold &&
            $time_now < ( $last_failed_login + $lockout_period );

        // HACK: Restore original time zone default.
        date_default_timezone_set($old_tz);

        return $is_locked_out;
    }


    /**
     * Find the default profile for this login, usually the first registered.
     * @TODO: Change point for future multiple profiles per login
     */
    public function find_default_profile_for_login()
    {
        if (!$this->loaded) return null;
        $profiles = $this->profiles;
        return $profiles[0];
    }


    /**
     * Set the password reset token for a given login and return the value 
     * used.
     *
     * @param  string login ID
     * @return string password reset string
     */
    public function set_password_reset_token()
    {
        if (!$this->loaded) return;

        $token = md5(uniqid(mt_rand(), true));

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $this->id )
        );
        $rv = $this->db->insert(
            $this->_table_name_password_reset_token,
            array(
                'login_id' => $this->id,
                'token'    => $token
            )
        );
        
        return $token;
    }

    /**
     * Change password for a login.
     * The password reset token, if any, is cleared as well.
     *
     * @param  string  login id
     * @param  string  new password value
     * @return boolean whether or not a password was changed
     */
    public function change_password($new_password)
    {
        if (!$this->loaded) return;

        $full_hash = $this->hash_password($new_password);

        $this->db->delete(
            $this->_table_name_password_reset_token,
            array( 'login_id' => $this->id )
        );
        $rows = $this->db->update(
            'logins', 
            array('password'=>$full_hash), 
            array('id'=>$this->id)
        );

        return !empty($rows);
    }


    /**
     * Set the password reset token for a given login and return the value 
     * used.
     *
     * @param  string login ID
     * @return string password reset string
     */
    public function generate_email_verification_token($email=null)
    {
        if (!$this->loaded) return;
        if (empty($email)) $email = $this->email;

        $token = md5(uniqid(mt_rand(), true));

        $rv = $this->db->insert(
            $this->_table_name_email_verification_token,
            array(
                'login_id'   => $this->id,
                'token'      => $token,
                'value'      => $email,
            )
        );
        
        return $token;
    }

    /**
     * Return the value and token for a pending email verification, if any.
     *
     * @return object Object with properties value and token.
     */
    public function get_email_verification($email=null)
    {
        $q = $this->db
            ->select('value, token')
            ->from($this->_table_name_email_verification_token)
            ->where('login_id', $this->id);
        if (null === $email) {
            $q->where('value <>', $this->email);
        }
        $row = $q->get()->current();
        return (empty($row)) ? null : $row;
    }

    /**
     * Change email for a login, using a verification token.
     *
     * @param  string  login id
     * @param  string  new email value
     * @return boolean whether or not a email was changed
     */
    public function change_email_with_verification_token($token)
    {
        list($login, $new_email, $token_id) = 
            $this->find_by_email_verification_token($token);

        if (empty($login) || empty($new_email)) { 
            return array(null, null, null); 
        }
        
        $old_email = $login->email;
        $login->email = $new_email;
        $login->save();

        // Delete this token, and all tokens created after this one.
        // HACK: Relies on auto-increment ID column.
        $this->db
            ->where(array(
                'login_id' => $login->id,
                'id >='    => $token_id 
            ))
            ->delete($this->_table_name_email_verification_token);

        cef_logging::log('000', 'Email changed', 5, array( 
            'suser'     => $login->login_name,
            'email_old' => $old_email,
            'email_new' => $new_email
        ));

        return array($login, $new_email, $token_id);
    }


    /**
     * Find by password reset token
     *
     * @param  string token value
     * @return Login_Model
     */
    public function find_by_password_reset_token($token)
    {
        return ORM::factory('login')
            ->join(
                $this->_table_name_password_reset_token,
                "{$this->_table_name_password_reset_token}.login_id",
                "{$this->table_name}.id"
            )
            ->where(
                "{$this->_table_name_password_reset_token}.token",
                $token
            )
            ->find();
    }

    /**
     * Find by email verification token
     *
     * @param  string token value
     * @return Login_Model
     */
    public function find_by_email_verification_token($token)
    {
        $row = $this->db
            ->select('value, login_id, id')
            ->from($this->_table_name_email_verification_token)
            ->where('token', $token)
            ->get()->current();

        if (!$row) {
            return array(null, null, null);
        } else {
            return array(
                ORM::factory('login', $row->login_id),
                $row->value,
                $row->id
            );
        }
    }


    /**
     * Replace incoming data with login validator and return whether 
     * validation was successful.
     *
     * Build and return a validator for the login form
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_login(&$data)
    {
        // Validate the login form data itself.
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('crumb', 'csrf_crumbs::validate')
            ->add_rules('login_name', 'required', 'length[3,64]', 
                'valid::alpha_dash', array($this, 'is_login_name_registered'))
            ->add_rules('password', 'required')
            ->add_callbacks('password', array($this, 'is_password_correct'))
            ;
        $is_valid = $data->validate();

        // Try loading the login object if possible
        $login = ORM::factory('login', $data['login_name']);
        if ($login->loaded) {
            if (!$login->active) {
                // Flag a disabled account.
                $data->add_error('inactive', 'inactive');
                $is_valid = false;
            }
            if (empty($login->email)) {
                // Flag unverified login.
                $data->add_error('email_unverified', 'email_unverified');
                $is_valid = false;
            }
            if ($data->errors('password')) {
                // If the form data wasn't valid, record a failed login.
                $login->record_failed_login();
            }
            if ($login->is_locked_out()) {
                // If the login has been locked out after failed logins, flag 
                // as invalid no matter what.
                $data->add_error('locked_out', 'locked_out');
                $is_valid = false;
            }
            if (!$is_valid) {
                $profile = $login->find_default_profile_for_login();
                if ($profile->loaded && 'admin' == $profile->role) {
                    cef_logging::log(
                        cef_logging::ACCESS_CONTROL_VIOLATION, 
                        'Admin Invalid Login', 5, 
                        array( 'suser' => $login->login_name )
                    );
                } else {
                    cef_logging::log(
                        cef_logging::ACCESS_CONTROL_VIOLATION, 
                        'Invalid Login', 7, 
                        array( 'suser' => $login->login_name )
                    );
                }
            }
        }

        
        return $is_valid;
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using old password.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_email(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('crumb', 'csrf_crumbs::validate')
            ->add_callbacks('password',
                array($this, 'is_password_correct'))
            ->add_rules('new_email', 
                'required', 'length[3,255]', 'valid::email',
                array($this, 'is_email_available'))
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using old password.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_password(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('crumb', 'csrf_crumbs::validate')
            ->add_rules('old_password', 'required')
            ->add_callbacks('old_password',
                array($this, 'is_password_correct'))
            ->add_rules('new_password',
                'required', 'length[8,255]',
                array($this, 'is_password_acceptable'))
            ->add_rules('new_password_confirm', 
                'required', 'matches[new_password]')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using old password.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_force_change_password(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('crumb', 'csrf_crumbs::validate')
            ->add_rules('new_password',
                'required', 'length[8,255]',
                array($this, 'is_password_acceptable'))
            ->add_rules('new_password_confirm', 
                'required', 'matches[new_password]')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with change password validator and return whether 
     * validation was successful, using forgot password token.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_change_password_with_token(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('password_reset_token', 
                array($this, 'is_password_reset_token_valid'))
            ->add_rules('new_password',
                'required', 'length[8,255]',
                array($this, 'is_password_acceptable'))
            ->add_rules('new_password_confirm', 
                'required', 'matches[new_password]')
            ;
        return $data->validate();
    }

    /**
     * Replace incoming data with forgot password validator and return whether 
     * validation was successful.
     *
     * @param  array   Form data to validate
     * @return boolean Validation success
     */
    public function validate_forgot_password(&$data)
    {
        $data = Validation::factory($data)
            ->pre_filter('trim')
            ->add_rules('crumb', 'csrf_crumbs::validate')
            ->add_rules('login_name', 'length[3,64]', 'valid::alpha_dash')
            ->add_rules('email', 'valid::email')
            ->add_callbacks('login_name', array($this, 'need_login_name_or_email'))
            ->add_callbacks('email', array($this, 'need_login_name_or_email'))
            ;
        return $data->validate();
    }


    /**
     * Check to see whether a login name is available, for use in form 
     * validator.
     */
    public function is_login_name_available($login_name)
    {
        if ($this->loaded && $login_name == $this->login_name) {
            return true;
        }
        $count = $this->db
            ->where('login_name', $login_name)
            ->count_records($this->table_name);
        return (0==$count);
    }

    /**
     * Check to see whether a login name has been registered, for use in form 
     * validator.
     */
    public function is_login_name_registered($name)
    {
        return !($this->is_login_name_available($name));
    }

    /**
     * Check to see whether a given email address has been registered to a 
     * login, for use in form validation.
     */
    public function is_email_available($email) {
        if ($this->loaded && $email == $this->email) {
            return true;
        }
        $count = $this->db
            ->where('email', $email)
            ->count_records($this->table_name);
        return (0==$count);
    }

    /**
     * Check to see whether a given email address has been registered to a 
     * login, for use in form validation.
     */
    public function is_email_registered($email) {
        return !($this->is_email_available($email));
    }

    /**
     * Check to see whether password is acceptably strong enough for us.
     */
    public function is_password_acceptable($passwd) 
    {
        if (strlen($passwd) < 8) {
            // greater than 8 characters
            return false;
        }
        if (preg_match('/\d/', $passwd) == 0) {
            // includes at least 1 number
            return false;
        }
        if (preg_match('/\W/', $passwd) == 0) {
            // require at least one character not in [0-9a-zA-Z_]
            return false;
        }
        return true;
    }

    /**
     * Check to see whether a password is correct, for use in form 
     * validator.
     */
    public function is_password_correct($valid, $field)
    {
        $login_name = (isset($valid['login_name'])) ?
            $valid['login_name'] : authprofiles::get_login('login_name');

        $row = $this->db
            ->select('password')
            ->from($this->table_name)
            ->where('login_name', $login_name)
            ->get()->current();

        if (empty($row->password)) {
            // No password found for this login name
            $valid->add_error($field, 'invalid');
        } else if (!$this->check_password($valid[$field], $row->password)) {
            // Password for this login name is invalid.
            $valid->add_error($field, 'invalid');
        } else {
            // Password is correct, but does it need to be migrated?
            // TODO: Disable this with a config setting?
            list($algo, $salt, $hash) = 
                $this->parse_password_hash($row->password);
            if ('MD5' == $algo) {
                // Migrate the legacy MD5 password to new-style salted hash.
                ORM::factory('login', $login_name)
                    ->change_password($valid[$field]);
            }
        }

    }

    /**
     * Check whether the given password token is valid.
     *
     * @param  string  password reset token
     * @return boolean 
     */
    public function is_password_reset_token_valid($token)
    {
        $count = $this->db
            ->where('token', $token)
            ->count_records($this->_table_name_password_reset_token);
        return !(0==$count);
    }

    /**
     * Enforce that either an existing login name or email address is supplied 
     * in forgot password validation.
     */
    public function need_login_name_or_email($valid, $field)
    {
        $login_name = (isset($valid['login_name'])) ? 
            $valid['login_name'] : null;
        $email = (isset($valid['email'])) ? 
            $valid['email'] : null;

        if (empty($login_name) && empty($email)) {
            return $valid->add_error($field, 'either');
        }

        if ('login_name' == $field && !empty($login_name)) {
            if (!$this->is_login_name_registered($login_name)) {
                $valid->add_error($field, 'default');
            }
        }

        if ('email' == $field && !empty($email)) {
            if (!$this->is_email_registered($email)) {
                $valid->add_error($field, 'default');
            }
        }
    }


    /**
     * Identify this model as a resource for Zend_ACL
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'login';
    }

}
