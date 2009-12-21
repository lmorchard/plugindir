<?php
/**
 * Bootstrap for auth_profiles module, sets up autoloader and initial ACL
 *
 * @package    auth_profiles
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */

// Since Zend libraries use require_once instead of an autoloader, set up some 
// more include paths to find things
$base = dirname(dirname(__FILE__));
set_include_path(implode(PATH_SEPARATOR, array(
    $base . '/libraries',
    $base . '/vendor',
    get_include_path()
)));

require_once('BigOrNot/CookieManager.php');
require_once('Zend/Acl/Assert/Interface.php');
require_once('Zend/Acl/Exception.php');
require_once('Zend/Acl/Resource/Interface.php');
require_once('Zend/Acl/Resource.php');
require_once('Zend/Acl/Role/Interface.php');
require_once('Zend/Acl/Role/Registry/Exception.php');
require_once('Zend/Acl/Role/Registry.php');
require_once('Zend/Acl/Role.php');
require_once('Zend/Acl.php');
require_once('Zend/Exception.php');

/**
 * Setup machinery for auth_profiles module.
 */
class AuthProfiles_Setup 
{
    public static function init()
    {
        Event::add('system.ready',
            array(get_class(), 'ready'));
    }

    public static function ready()
    {
        $acl = new Zend_Acl();
        $acl

            ->addRole('admin')
            ->allow('admin') // Admins can do anything to anything.

            ->addRole('guest')
            ->addRole('member', 'guest')

            ->addResource('profile')
            
            ->allow('member', 'profile', 'view', 
                new AuthProfiles_Acl_Assert_Profile_View())
            ->allow('member', 'profile', 'edit',
                new AuthProfiles_Acl_Assert_Profile_Edit())
            ->allow('member', 'profile', array(
                'view_own', 'edit_own'
            ))

            ->addResource('login')

            ->allow('member', 'login', 'view',
                new AuthProfiles_Acl_Assert_Login_View())
            ->allow('member', 'login', 'edit',
                new AuthProfiles_Acl_Assert_Login_Edit())
            ->allow('member', 'login', 'changepassword',
                new AuthProfiles_Acl_Assert_Login_Changepassword())
            ->allow('member', 'login', 'changeemail',
                new AuthProfiles_Acl_Assert_Login_Changeemail())
            ->allow('member', 'login', array(
                'view_own', 'edit_own', 'changepassword_own', 'changeemail_own',
            ))

            ;

        Event::run('auth_profiles.setup_acl', $acl);

        authprofiles::$acls = $acl;
        authprofiles::init();
    }

}

/**
 * Base class for profile ACL logic, accounting for *_any and *_own subordinate 
 * privileges of {view,edit,etc}
 */
class AuthProfiles_Acl_Assert_Profile implements Zend_Acl_Assert_Interface {

    protected $base_priv = 'profile';

    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_own') &&
                !empty($role->id) &&  $resource->id == $role->id) {
            return true;
        }
        return false;
    }
}

/** ACL logic for profile view */
class AuthProfiles_Acl_Assert_Profile_View extends AuthProfiles_Acl_Assert_Profile {
    protected $base_priv = 'view';
}

/** ACL logic for profile edit */
class AuthProfiles_Acl_Assert_Profile_Edit extends AuthProfiles_Acl_Assert_Profile {
    protected $base_priv = 'edit';
}

/**
 * Base class for login ACL logic, accounting for *_any and *_own subordinate 
 * privileges of {view,edit,etc}
 */
class AuthProfiles_Acl_Assert_Login implements Zend_Acl_Assert_Interface {

    protected $base_priv = 'login';

    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_any')) {
            return true;
        }

        // TODO: Do a more simple SQL query to determine ownership?
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_own')) {
            $logins = $role->logins;
            if (empty($logins)) {
                // Role has no logins, so can't possibly own this one.
                return false;
            }
            foreach ($logins as $login) {
                if ($login->id == $resource->id) return true;
            }
        }

        return false;
    }

}

/** ACL logic for login view */
class AuthProfiles_Acl_Assert_Login_View extends AuthProfiles_Acl_Assert_Login {
    protected $base_priv = 'view';
}

/** ACL logic for login edit */
class AuthProfiles_Acl_Assert_Login_Edit extends AuthProfiles_Acl_Assert_Login {
    protected $base_priv = 'edit';
}

/** ACL logic for login change password */
class AuthProfiles_Acl_Assert_Login_Changepassword extends AuthProfiles_Acl_Assert_Login {
    protected $base_priv = 'changepassword';
}

/** ACL logic for login change email */
class AuthProfiles_Acl_Assert_Login_Changeemail extends AuthProfiles_Acl_Assert_Login {
    protected $base_priv = 'changeemail';
}

// Initialize the cookie handler for login.
AuthProfiles_Setup::init();
