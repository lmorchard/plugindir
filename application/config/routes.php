<?php defined('SYSPATH') OR die('No direct access allowed.');

$config['pfs/v2'] = 'search/pfs_v2';

$config['([^/]+)/plugins/?;create'] = 'plugins/create';

$config['([^/]+)/plugins/detail/([^\.]+).json'] = 'plugins/detail/$2/json';
$config['([^/]+)/plugins/detail/([^\.]+).html'] = 'plugins/detail/$2/html';
$config['([^/]+)/plugins/detail/([^;]+);(.*)']  = 'plugins/$3/$2';
$config['([^/]+)/plugins/detail/(.*)']          = 'plugins/detail/$2/html';

$config['([^/]+)/profiles/([^/]+)/']             = 'plugins/sandbox/$2';
$config['([^/]+)/profiles/([^/]+)/plugins/']     = 'plugins/sandbox/$2';
$config['([^/]+)/profiles/([^/]+)/plugins.json'] = 'plugins/sandbox/$2/json';

$config['([^/]+)/profiles/([^/]+)/plugins/?;create'] = 
    'plugins/create/$2';
$config['([^/]+)/profiles/([^/]+)/plugins/detail/([^\.]+).json'] = 
    'plugins/detail/$3/json/$2';
$config['([^/]+)/profiles/([^/]+)/plugins/detail/([^\.]+).html'] = 
    'plugins/detail/$3/html/$2';
$config['([^/]+)/profiles/([^/]+)/plugins/detail/([^;]+);(.*)'] = 
    'plugins/$4/$3/$2';
$config['([^/]+)/profiles/([^/]+)/plugins/detail/(.*)'] = 
    'plugins/detail/$3/html/$2';

$config['([^/]+)/plugins/submissions/(.*)'] = 'plugins/submission_detail/$2';

$config['([^/]+)/plugins/(.*)'] = 'plugins/$2';

$config['([^/]+)/pfs/v2'] = 'search/pfs_v2';

$config['([^/]+)/home'] =     'auth_profiles/home';
$config['([^/]+)/register'] = 'auth_profiles/register';
$config['([^/]+)/login'] =    'auth_profiles/login';
$config['([^/]+)/logout'] =   'auth_profiles/logout';

$config['([^/]+)/verifyemail'] = 
    'auth_profiles/verifyemail';
$config['([^/]+)/reverifyemail/(.*)'] = 
    'auth_profiles/reverifyemail/login_name/$2/';

$config['([^/]+)/changepassword'] = 'auth_profiles/changepassword';
$config['([^/]+)/forgotpassword'] = 'auth_profiles/forgotpassword';

$config['([^/]+)/settings'] = 'auth_profiles/settings';

$config['([^/]+)/profiles/([^/]+)/settings'] = 
    'auth_profiles/settings/screen_name/$2/';

$config['([^/]+)/profiles/([^/]+)/settings/basics/changepassword'] = 
    'auth_profiles/changepassword/screen_name/$2/';

$config['([^/]+)/profiles/([^/]+)/settings/basics/changeemail'] = 
    'auth_profiles/changeemail/screen_name/$2/';

$config['([^/]+)/profiles/([^/]+)/settings/basics/details'] = 
    'auth_profiles/editprofile/screen_name/$2/';


$config['([^/]+)/(.*)/(.*)'] = '$2/$3';

$config['([^/]+)'] = 'index';

$config['_default'] = 'index/locale_redirect';
