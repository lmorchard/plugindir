<?php
/**
 * Setup static ACLs for the application
 *
 * @package    PluginDir
 * @subpackage hooks
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */

/**
 * Hook to set up ACL
 */
class PluginDir_ACL_Setup
{
    public static function init()
    {
        authprofiles::$acls = new Zend_Acl();
        authprofiles::$acls

            ->addRole('guest')
            ->addRole('member', 'guest')
            ->addRole('editor', 'member')
            ->addRole('admin', 'editor')

            ->allow('admin') // Admins can do anything to anything.

            ->addResource('plugin')

            ->allow('guest', 'plugin', 'view', 
                new Plugin_View_Assertion())
            ->allow('guest', 'plugin', 'view_public')
            ->allow('guest', 'plugin', 'submit_plugin')

            ->allow('member', 'plugin', 'edit', 
                new Plugin_Edit_Assertion())
            ->allow('member', 'plugin', 'delete', 
                new Plugin_Delete_Assertion())
            ->allow('member', 'plugin', array(
                'copy', 'request_deploy', 'view_own', 'edit_own', 'delete_own'
            ))

            ->allow('editor', 'plugin', array(
                'view_any', 'edit_sandbox', 'delete_sandbox', 'deploy'
            ))

            ->addResource('profile')
            
            ->allow('member', 'profile', 'view_sandbox', 
                new Profile_Sandbox_View_Assertion())
            ->allow('member', 'profile', 'view') // Profile_Model_ViewAssertion
            ->allow('member', 'profile', 'edit') // Profile_Model_EditAssertion
            ->allow('member', 'profile', array( 
                'view_own', 'edit_own', 'view_sandbox_own' 
            ))

            ->allow('editor', 'profile', 'view_sandbox_any')

            ->allow('admin', 'profile', 'edit_any')
            ->allow('admin', 'profile', 'delete_any')

            ->addResource('login')

            ->allow('member', 'login', 'view') // Login_Model_ViewAssertion
            ->allow('member', 'login', 'edit') // Login_Model_EditAssertion
            ->allow('member', 'login', array('view_own', 'edit_own'))

            ->allow('admin', 'login', 'edit_any')
            ->allow('admin', 'login', 'delete_any')

            ->add(new Zend_Acl_Resource('profiles'))
            ->allow('member', 'profiles', array('view_own', 'edit_own',))

            ->add(new Zend_Acl_Resource('logins'))
            ->allow('member', 'logins', array('view_own', 'edit_own',))

            ;

    }
}

/**
 * ACL assertion logic for profile view_sandbox privilege and subordinates.
 */
class Profile_Sandbox_View_Assertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, 'view_sandbox_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'view_sandbox_own') &&
                !empty($role->id) &&  $resource->id == $role->id) {
            return true;
        }
        return false;
    }
}

/**
 * ACL logic for plugin view
 */
class Plugin_View_Assertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, 'view_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'view_own') &&
                !empty($role->id) &&  
                $resource->sandbox_profile_id == $role->id) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'view_public') &&
                !$resource->is_sandboxed()) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'view_sandbox') &&
                $resource->is_sandboxed()) {
            return true;
        }
        return false;
    }
}

/**
 * ACL logic for plugin edit
 */
class Plugin_Edit_Assertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, 'edit_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'edit_own') &&
                !empty($role->id) &&  
                $resource->sandbox_profile_id == $role->id) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'edit_sandbox') &&
                $resource->is_sandboxed()) {
            return true;
        }
        return false;
    }
}

/**
 * ACL logic for plugin delete
 */
class Plugin_Delete_Assertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, 'delete_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'delete_own') &&
                !empty($role->id) &&  
                $resource->sandbox_profile_id == $role->id) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, 'delete_sandbox') &&
                $resource->is_sandboxed()) {
            return true;
        }
        return false;
    }
}

// Fire up the initialization on load.
PluginDir_ACL_Setup::init();
