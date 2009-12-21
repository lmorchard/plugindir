<?php
/**
 * ORM model with Zend ACL hooks.
 *
 * @package    auth_profiles
 * @subpackage models
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class ORM_Resource extends ORM implements Zend_Acl_Resource_Interface {

    /**
     * Identify this model as a resource for Zend_ACL
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'resource';
    }

    /**
     * Check if the given role has the given privilege for this resource.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @param  string                             $privilege
     * @return boolean
     */
    public function is_allowed($role, $priv)
    {
        if (empty($role)) {
            $role = Kohana::config('auth_profiles.base_anonymous_role');
        }
        return authprofiles::$acls->isAllowed($role, $this, $priv);
    }
    
}
