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

    /**
     * Init by hooking up the ACL setup event handler.
     */
    public static function init()
    {
        Event::add('auth_profiles.setup_acl', 
            array(get_class(), 'setup_acl'));
    }

    /**
     * Perform ACL setup after the module hook has completed.
     */
    public static function setup_acl()
    {
        Event::$data

            ->addRole('editor', 'member')

            ->addResource('plugin')

            ->allow('guest', 'plugin', 'view', 
                new PluginDir_Plugin_View_Assertion())
            ->allow('guest', 'plugin', array(
                'view_public', 'submit_plugin'
            ))

            ->allow('member', 'plugin', 'edit', 
                new PluginDir_Plugin_Edit_Assertion())
            ->allow('member', 'plugin', 'delete', 
                new PluginDir_Plugin_Delete_Assertion())
            ->allow('member', 'plugin', array(
                'copy', 'request_deploy', 'view_own', 'edit_own', 'delete_own'
            ))

            ->allow('editor', 'plugin', array(
                'view_any', 'edit_sandbox', 'delete_sandbox', 'deploy'
            ))

            ->allow('member', 'profile', 'view_sandbox', 
                new PluginDir_Sandbox_View_Assertion())
            ->allow('member', 'profile', array( 
                'view_sandbox_own' 
            ))

            ->allow('editor', 'profile', 'view_sandbox_any')
            ;
    }
}

/**
 * ACL assertion logic for profile view_sandbox privilege and subordinates.
 */
class PluginDir_Sandbox_View_Assertion implements Zend_Acl_Assert_Interface {
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
class PluginDir_Plugin_View_Assertion implements Zend_Acl_Assert_Interface {
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
class PluginDir_Plugin_Edit_Assertion implements Zend_Acl_Assert_Interface {
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
class PluginDir_Plugin_Delete_Assertion implements Zend_Acl_Assert_Interface {
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
