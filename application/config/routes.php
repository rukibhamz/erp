<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'Dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth routes
$route['login'] = 'Auth/login';
$route['logout'] = 'Auth/logout';
$route['forgot-password'] = 'Auth/forgotPassword';
$route['reset-password'] = 'Auth/resetPassword';

// Dashboard
$route['dashboard'] = 'Dashboard/index';

// Companies
$route['companies'] = 'Companies/index';
$route['companies/create'] = 'Companies/create';
$route['companies/edit/(:num)'] = 'Companies/edit/$1';

// Users
$route['users'] = 'Users/index';
$route['users/create'] = 'Users/create';
$route['users/edit/(:num)'] = 'Users/edit/$1';
$route['users/permissions/(:num)'] = 'Users/permissions/$1';
$route['users/delete/(:num)'] = 'Users/delete/$1';

// Profile
$route['profile'] = 'Profile/index';
$route['profile/terminate-session/(:any)'] = 'Profile/terminateSession/$1';

// Settings
$route['settings'] = 'Settings/index';
$route['settings/modules'] = 'Settings/modules';

// Activity Log
$route['activity'] = 'Activity/index';

