<?php 
/**
 * Controller handling all auth activities, including registration and 
 * login / logout
 *
 * @package    auth_profiles
 * @subpackage controllers
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Auth_Profiles_Controller extends Local_Controller
{ 
    protected $auto_render = TRUE;

    /** 
     * Basic overall controller preamble
     */
    public function __construct()
    {
        parent::__construct();

        $protected_methods = array(
            'home', 'changeemail', 'logout'
        );
        if (!authprofiles::is_logged_in()) {
            if (in_array(Router::$method, $protected_methods)) {
                return authprofiles::redirect_login();
            }
        }

        $this->login_model   = new Login_Model();
        $this->profile_model = new Profile_Model();
    }

    /**
     * Convenience action to redirect to logged in user's default profile.
     */
    public function home()
    {
        $this->auto_render = false;
        if (!authprofiles::is_logged_in()) {
            return url::redirect('login');
        } else {
            $auth_data = authprofiles::get_user_data();
            return url::redirect(sprintf(
                Kohana::config('auth_profiles.home_url'),
                authprofiles::get_profile('screen_name')
            ));
        }
    }

    /**
     * Combination login / registration action.
     */
    public function index()
    {
        return url::redirect('home');
    }


    /**
     * New user registration.
     */
    public function register()
    {
        $form_data = $this->_validate(
            $this->profile_model, 'validate_registration', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $new_profile = $this->profile_model
            ->register_with_login($form_data);

        if (!empty($new_profile->email_verification_token)) {
            email::send_view(
                $new_profile->new_email,
                'auth_profiles/register_email',
                array(
                    'email_verification_token' => 
                        $new_profile->email_verification_token,
                    'login_name' => 
                        $new_profile->login_name
                )
            );
        }
        $this->view->login_name = $new_profile->login_name;
        $this->view->set_filename('auth_profiles/register_success');
    }

    /**
     * User login action.
     */
    public function login()
    {
        $form_data = $this->_validate(
            $this->login_model, 'validate_login', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $login = ORM::factory('login', $form_data['login_name']);
        if (!$login->active) {
            $this->view->login_inactive = TRUE;
            return;
        } elseif (empty($login->email)) {
            $this->view->no_verified_email = TRUE;
            return;
        }

        // TODO: Allow profile selection here if multiple.
        $profile = $login->find_default_profile_for_login();

        $login->login($form_data);
        authprofiles::login($login->login_name, $login, $profile);

        if (isset($form_data['jump']) && substr($form_data['jump'], 0, 1) == '/') {
            // Allow post-login redirect only if the param starts with '/', 
            // interpreted as relatve to root of site.
            return url::redirect($form_data['jump']);
        } else {
            return url::redirect('/home');
        }
    }

    /**
     * User logout action.
     */
    public function logout()
    {
        authprofiles::logout();
    }


    /**
     * Manage profile details.
     */
    public function editprofile()
    {
        list($profile, $login) = $this->_find_profile_and_login();
        if (!authprofiles::is_allowed($profile, 'edit'))
            return Event::run('system.403');

        if (authprofiles::is_allowed($profile, 'edit_role')) {
            $all_roles = authprofiles::$acls->getRegisteredRoles();
            $role_choices = array();
            foreach ($all_roles as $name=>$obj) {
                $role_choices[$name] = $name;
            }
            $this->view->set(array(
                'role' => $profile->role,
                'role_choices' => $role_choices
            ));
        }

        $form_data = $this->_validate(
            $profile, 'validate_update', 'form_errors_auth'
        );
        if (null===$form_data) return;

        if (!authprofiles::is_allowed($profile, 'edit_role')) {
            // Ignore attempts to change role if not allowed.
            unset($form_data['role']);
        }

        $profile->set($form_data)->save();

        Session::instance()->set_flash(
            'message', 'Profile updated'
        );
        return url::redirect(url::current());
    }

    /**
     * Start email address change process.
     */
    public function changeemail()
    {
        list($profile, $login) = $this->_find_profile_and_login();
        if (!authprofiles::is_allowed($login, 'changeemail'))
            return Event::run('system.403');

        $form_data = $this->_validate(
            $this->login_model, 
            'validate_change_email', 'form_errors_auth'
        );
        if (null===$form_data) return;

        $token = $login->set_email_verification_token($form_data['new_email']);

        $this->view->email_verification_token_set = true;

        email::send_view(
            $form_data['new_email'],
            'auth_profiles/changeemail_email',
            array(
                'email_verification_token' => $token,
                'login_name' => authprofiles::get_login('login_name')
            )
        );
    }

    /**
     * Re-send an email verification
     */
    public function reverifyemail()
    {
        $params = $this->_get_params(array(
            'login_name' => null,
        ));

        $login = ORM::factory('login', $params['login_name']);
        if (!$login->loaded) 
            return Event::run('system.404');

        $verify = $login->get_email_verification();
        if (empty($verify))
            return Event::run('system.404');

        if ('post' == request::method()) {

            email::send_view(
                $verify->value,
                'auth_profiles/register_email',
                array(
                    'email_verification_token' => $verify->token,
                    'login_name' => $login->login_name
                )
            );
            $this->view->email_sent = TRUE;
            $this->view->login_name = $params['login_name'];
            $this->view->set_filename('auth_profiles/register_success');
        }

        $this->view->login = $login;
    }

    /**
     * Complete verification of a new email address.
     */
    public function verifyemail()
    {
        if (false !== $this->input->get('success', false)) {
            $this->view->valid_token = true;
            return;
        }
        
        $token = ('post' == request::method()) ?
            $this->input->post('email_verification_token') :
            $this->input->get('email_verification_token');

        list($login, $new_email) = ORM::factory('login')
            ->find_by_email_verification_token($token);
        if (!$login) {
            $this->view->invalid_token = true;
            return;
        }
        $login->change_email($new_email);

        // TODO: Make auto-login on email verification configurable?
        $profile = $login->find_default_profile_for_login();
        $login->login();
        authprofiles::login($login->login_name, $login, $profile);
        url::redirect(url::current().'?success=true');
    }


    /**
     * Change password for a login
     */
    public function changepassword()
    {
        // Try accepting a reset token from either GET or POST.
        $reset_token = ('post' == request::method()) ?
            $this->input->post('password_reset_token') :
            $this->input->get('password_reset_token');

        if (empty($reset_token) && !authprofiles::is_logged_in()) {
            // If no token and not logged in, jump to login.
            return authprofiles::redirect_login();
        }
        
        if (empty($reset_token)) {
            // No reset token, so try figuring out the profile and login.
            list($profile, $login) = $this->_find_profile_and_login();
        } else {
            // Look up the login by token, and abort if not found.
            $login = ORM::factory('login')
                ->find_by_password_reset_token($reset_token);
            if (!$login->loaded) {
                $this->view->invalid_reset_token = true;
                return;
            }
            $profile = $login->find_default_profile_for_login();

            // Use the found login ID and toss name into view.
            $this->view->forgot_password_login_name = $login->login_name;
            
            // Pre-emptively force logout in case current login and login 
            // associated with token differ.
            authprofiles::logout();
        }

        $_POST['login_name'] = $login->login_name;

        // Now that we know who's trying to change a password, validate the 
        // form appropriately
        $form_data = $this->_validate(
            $this->login_model, 
            empty($reset_token) ? 
                ( (authprofiles::is_allowed($login, 'changepassword_any')) ?
                    'validate_force_change_password' : 
                    'validate_change_password'
                ) :
                'validate_change_password_with_token', 
            'form_errors_auth'
        );
        if (null===$form_data) return;
        
        // Finally, perform the password change.
        $changed = $login->change_password($form_data['new_password']);
        if (!$changed) {
            // Something unexpected happened.
            $this->view->password_change_failed = true;
        } else {
            $this->view->password_changed = true;
        }
    }

    /**
     * Handle request to recover from a forgotten password.
     */
    public function forgotpassword() 
    {
        $form_data = $this->_validate(
            $this->login_model, 'validate_forgot_password', 'form_errors_auth'
        );
        if (null===$form_data) return;

        if (!empty($form_data['login_name'])) {
            $login = ORM::factory('login', $form_data['login_name']);
        } elseif (!empty($form_data['email'])) {
            $login = ORM::factory('login', array(
                'email' => $form_data['email']
            ));
        }

        $reset_token = $login->set_password_reset_token();
        $this->view->password_reset_token_set = true;

        email::send_view(
            $login->email,
            'auth_profiles/forgotpassword_email',
            array(
                'password_reset_token' => $reset_token,
                'login_name' => $login->login_name
            )
        );
    }


    /**
     * Profiles settings.
     */
    public function settings()
    {
        list($profile, $login) = $this->_find_profile_and_login();
        if (!authprofiles::is_allowed($profile, 'edit')) 
            return Event::run('system.403');

        $u_name = rawurlencode($profile->screen_name);

        // Set up initial whiteboard, fire off event to gather content from 
        // interested listeners.
        $data = array(
            'controller' => $this, 
            'sections'   => array(
                array(
                    'title' => _('Basics'),
                    'priority' => 999,
                    'items' => array(
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/changepassword",
                            'title' => _('Change login password'),
                            'description' => _('change current login password')
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/changeemail",
                            'title' => _('Change login email'),
                            'description' => _('change current login email')
                        ),
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/details",
                            'title' => _('Edit profile details'),
                            'description' => _('change screen name, bio, etc.')
                        ),
                        /*
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/logins",
                            'title' => 'Manage profile logins',
                            'description' => 'create and remove logins for this profile'
                        ),
                         */
                        /*
                        array(
                            'url' => "profiles/{$u_name}/settings/basics/delete",
                            'title' => 'Delete profile',
                            'description' => 'delete this profile altogether'
                        ),
                         */
                    )
                )
            )
        );
        
        Event::run('auth_profiles.before_settings_menu', $data);

        usort($data['sections'], create_function(
            '$b,$a', '
                $a = @$a["priority"];
                $b = @$b["priority"];
                return ($a==$b)?0:(($a<$b)?-1:1);
            '
        ));

        $this->view->set(array(
            'sections' => $data['sections']
        ));
    }

    /**
     * Perform model-based form POST data validation.
     *
     * @param  Model       model instance
     * @param  string      name of model validation method
     * @param  string      name of the error messages file
     * @return Validation  validation object with data, or null on failure
     */
    public function _validate($model, $callback, $errors, $require_post=true) {

        $this->view->form_data = array_merge(
            $model->loaded ?
                $model->as_array() : array(),
            ('post' == request::method()) ? 
                $this->input->post() : $this->input->get()
        );
        
        if ($require_post && 'post' != request::method()) {
            return;
        }
        
        $is_valid = call_user_func_array(
            array($model, $callback), 
            array(&$this->view->form_data)
        );

        if (!$is_valid) {
            $this->view->form_errors = 
                $this->view->form_data->errors($errors);
            return null;
        }

        return $this->view->form_data;

    }

    /**
     * Convert the arguments in the route to name/value parameters.
     *
     * @return array Parameters based on current route.
     */
    public function _get_params($defaults=null, $wildcard='path')
    {
        $args = Router::$arguments;
        $params = empty($defaults) ? array() : $defaults;
        while (!empty($args)) {
            $name = array_shift($args);
            if ($wildcard == $name) {
                $params[$name] = join('/', $args);
                break;
            } else {
                $params[$name] = array_shift($args);
            }
        }
        return $params;
    }

    /**
     * Utility method to find profile and login objects from screen name in 
     * URL, or failing that use the current user's profile / login
     */
    public function _find_profile_and_login()
    {
        $params = $this->_get_params(array(
            'screen_name' => null,
        ));
        if (empty($params['screen_name'])) {
            // Use current in profile and login.
            $profile = authprofiles::get_profile();
            $login = authprofiles::get_login(); 
        } else {
            // Look up profile from URL route param, find login.
            $profile = ORM::factory('profile')->find($params['screen_name']);
            if (authprofiles::get_profile('id') == $profile->id) {
                // Use current login if profile lookup matches current.
                $login = authprofiles::get_login();
            } else {
                // Otherwise, use the default.
                // TODO: Login selection UI
                $login = $profile->find_default_login_for_profile();
            }
        }
        $this->view->set(array(
            'profile' => $profile, 'login' => $login
        ));
        return array($profile, $login);
    }

} 
