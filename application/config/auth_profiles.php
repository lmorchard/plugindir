<?php
/**
 * Configuration for auth profiles
 */
$config['secret']      = '8675309jenny';
$config['home_url']    = '/';
$config['cookie_name'] = 'auth_profiles';
$config['cookie_path'] = '/';

$config['base_anonymous_role'] = 'guest';
$config['base_profile_role']   = 'member';

// See also: hooks/acls.php for ACL setup, since they're uncacheable (so far) 
// via Kohana
