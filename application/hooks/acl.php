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
                new PluginDir_Acl_Assert_Plugin_View())
            ->allow('guest', 'plugin', array(
                'view_public', 'submit_plugin'
            ))

            ->allow('member', 'plugin', 'edit', 
                new PluginDir_Acl_Assert_Plugin_Edit())
            ->allow('member', 'plugin', 'delete',
                new PluginDir_Acl_Assert_Plugin_Delete())
            ->allow('member', 'plugin', 'managetrust',
                new PluginDir_Acl_Assert_Plugin_ManageTrust())
            ->allow('member', 'plugin', 'requestpush',
                new PluginDir_Acl_Assert_Plugin_RequestPush())
            ->allow('member', 'plugin', 'deploy',
                new PluginDir_Acl_Assert_Plugin_Deploy())
            ->allow('member', 'plugin', array(
                'copy', 'request_deploy', 
                'view_own', 'edit_own', 'delete_own',
                'create', 'create_own', 'requestpush_own',
                'view_trusted', 'edit_trusted', 'deploy_trusted',
            ))

            ->allow('editor', 'plugin', array(
                'view_sandbox', 'edit_sandbox', 'delete_sandbox', 
                'deploy_any', 'create_sandbox', 'requestpush_sandbox',
                'managetrust_sandbox',
                'view_submissions', 
            ))

            ->allow('guest', 'profile', 'view_sandbox', 
                new PluginDir_Acl_Assert_Sandbox_View())
            ->allow('guest', 'profile', array(
                'view_sandbox_any' 
            ))

            ->allow('member', 'profile', 'create_in_sandbox', 
                new PluginDir_Acl_Assert_Sandbox_CreateIn())
            ->allow('member', 'profile', array( 
                'create_in_sandbox_own'
            ))

            ->allow('editor', 'profile', array(
                'create_in_sandbox_any'
            ))
            ;
    }
}

/**
 * Overall ACL logic for sandbox operations
 */
class PluginDir_Acl_Assert_Sandbox implements Zend_Acl_Assert_Interface {
    protected $base_priv = 'sandbox';

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

class PluginDir_Acl_Assert_Sandbox_View extends PluginDir_Acl_Assert_Sandbox {
    protected $base_priv = 'view_sandbox';
}

class PluginDir_Acl_Assert_Sandbox_CreateIn extends PluginDir_Acl_Assert_Sandbox {
    protected $base_priv = 'create_in_sandbox';

    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if (empty($resource->id) &&
                $acl->isAllowed($role, $resource, 'create_in_public')) {
            // Special case: If the resource is not a profile instance, assume 
            // this is an attempt to create a new plugin in public.
            return true;
        }
        return parent::assert($acl, $role, $resource, $privilege);
    }
}
 
/**
 * Overall ACL logic for plugin operations
 */
class PluginDir_Acl_Assert_Plugin implements Zend_Acl_Assert_Interface {
    protected $base_priv = 'plugin';

    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, 
        Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_any')) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_own') &&
                !empty($role->id) &&  
                $resource->sandbox_profile_id == $role->id) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_trusted') &&
                $resource->trusts($role)) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_public') &&
                !$resource->is_sandboxed()) {
            return true;
        }
        if ($acl->isAllowed($role, $resource, $this->base_priv . '_sandbox') &&
                $resource->is_sandboxed()) {
            return true;
        }
        return false;
    }
}

class PluginDir_Acl_Assert_Plugin_View extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'view';
}
class PluginDir_Acl_Assert_Plugin_Edit extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'edit';
}
class PluginDir_Acl_Assert_Plugin_Delete extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'delete';
}
class PluginDir_Acl_Assert_Plugin_ManageTrust extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'managetrust';
}
class PluginDir_Acl_Assert_Plugin_RequestPush extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'requestpush';
}
class PluginDir_Acl_Assert_Plugin_Deploy extends PluginDir_Acl_Assert_Plugin {
    protected $base_priv = 'deploy';
}

// Fire up the initialization on load.
PluginDir_ACL_Setup::init();
