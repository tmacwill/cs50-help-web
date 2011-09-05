<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$CURRENT_VERSION = 1;

$route['default_controller'] = "questions_v1";
$route['404_override'] = '';

/**
 * CS50 ID return_to, cannot have course as 1st URI segment because of openid restrictions
 *
 */
$route['auth/return_to'] = "auth_v{$CURRENT_VERSION}/return_to";

/**
 * Shorter per-course URL to enter the queue
 *
 */
$route['(\w+)'] = "questions_v{$CURRENT_VERSION}/q/$1";

/**
 * API call 
 * route   course/api/v#/controller/method/parameters 
 * becomes controller_v#/action/method/course/parameters
 *
 */
$route['(\w+)/api/v(\d+)/(\w+)/(\w+)/?(.*)'] = "$3_v$2/$4/$1/$5";

/**
 * Application call always uses current version of API
 * route   course/controller/method/parameters
 * becomes controller_vn/action/method/course/parameters
 *
 */
$route['(\w+)/(\w+)/(\w+)/?(.*)'] = "$2_v{$CURRENT_VERSION}/$3/$1/$4";

/* End of file routes.php */
/* Location: ./application/config/routes.php */
