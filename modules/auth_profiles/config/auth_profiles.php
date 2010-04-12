<?php
/**
 * Configuration for auth profiles
 */

// URL template for redirect from /home
$config['home_url']    = 'profiles/%1$s';

// Name of the login cookie
$config['cookie_name'] = 'auth_profiles';

// Path of the login cookie
$config['cookie_path'] = '/';

// Secret used in encrypting login cookies
$config['secret'] = '8675309jenny';

// Default role given to non-authenticated users.
$config['base_anonymous_role'] = 'guest';

// Default role given to authenticated users.
$config['base_profile_role'] = 'member';

// Length of the salt used in {SHA-256} password hashes
$config['salt_length'] = 16;

// Maximum number of failed logins for an account before login lockout
$config['max_failed_logins'] = 5; 

// Amount of seconds for which login will be disabled on lockout trigger
$config['account_lockout_period'] = 3600;
