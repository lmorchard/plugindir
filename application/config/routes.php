<?php defined('SYSPATH') OR die('No direct access allowed.');

$config['pfs/v2'] = 'search/pfs_v2';

$config['index.json'] = 'plugins/index_json';

$config['plugins/?;create'] = 'plugins/create';

$config['plugins/detail/([^\.]+).json'] = 'plugins/detail/$1/json';
$config['plugins/detail/([^\.]+).html'] = 'plugins/detail/$1/html';
$config['plugins/detail/([^;]+);(.*)']  = 'plugins/$2/$1';
$config['plugins/detail/(.*)']          = 'plugins/detail/$1/html';

$config['profiles/([^/]+)/']             = 'plugins/sandbox/$1';
$config['profiles/([^/]+)/plugins/']     = 'plugins/sandbox/$1';
$config['profiles/([^/]+)/plugins.json'] = 'plugins/sandbox/$1/json';

$config['profiles/([^/]+)/plugins/?;create'] = 
    'plugins/create/$1';
$config['profiles/([^/]+)/plugins/detail/([^\.]+).json'] = 
    'plugins/detail/$2/json/$1';
$config['profiles/([^/]+)/plugins/detail/([^\.]+).html'] = 
    'plugins/detail/$2/html/$1';
$config['profiles/([^/]+)/plugins/detail/([^;]+);(.*)'] = 
    'plugins/$3/$2/$1';
$config['profiles/([^/]+)/plugins/detail/(.*)'] = 
    'plugins/detail/$2/html/$1';

$config['plugins/submissions/(.*)'] = 'plugins/submission_detail/$1';

$config['plugins/(.*)'] = 'plugins/$1';

$config['pfs/v2'] = 'search/pfs_v2';

$config['home'] =     'auth_profiles/home';
$config['register'] = 'auth_profiles/register';
$config['login'] =    'auth_profiles/login';
$config['logout'] =   'auth_profiles/logout';

$config['verifyemail'] = 
    'auth_profiles/verifyemail';
$config['reverifyemail/(.*)'] = 
    'auth_profiles/reverifyemail/login_name/$1/';

$config['changepassword'] = 'auth_profiles/changepassword';
$config['forgotpassword'] = 'auth_profiles/forgotpassword';

$config['settings'] = 'auth_profiles/settings';

$config['profiles/([^/]+)/settings'] = 
    'auth_profiles/settings/screen_name/$1/';

$config['profiles/([^/]+)/settings/basics/changepassword'] = 
    'auth_profiles/changepassword/screen_name/$1/';

$config['profiles/([^/]+)/settings/basics/changeemail'] = 
    'auth_profiles/changeemail/screen_name/$1/';

$config['profiles/([^/]+)/settings/basics/details'] = 
    'auth_profiles/editprofile/screen_name/$1/';

$config['captcha/?(.*)'] = 'index/captcha/$1';

$config['(.*)/(.*)'] = '$1/$2';

$config['_default'] = 'index';
