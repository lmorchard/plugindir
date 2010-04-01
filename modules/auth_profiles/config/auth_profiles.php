<?php
/**
 * Configuration for auth profiles
 */
$config['secret']      = '8675309jenny';
$config['home_url']    = 'profiles/%1$s';
$config['cookie_name'] = 'auth_profiles';
$config['cookie_path'] = '/';

$config['base_anonymous_role'] = 'guest';
$config['base_profile_role']   = 'member';

$config['salt_length'] = 16;
