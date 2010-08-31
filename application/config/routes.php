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
| 	example.com/class/method/id/
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
| There are two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['scaffolding_trigger'] = 'scaffolding';
|
| This route lets you set a "secret" word that will trigger the
| scaffolding feature for added security. Note: Scaffolding must be
| enabled in the controller in which you intend to use it.   The reserved 
| routes must come before any wildcard or regular expression routes.
|
*/

//$route[':any'] = "page";
$route['default_controller'] = "page";
$route['scaffolding_trigger'] = "";
//$route['admin/user/(:num)'] = "admin/user/$1";

//data-catalog page
//$route['data-catalog'] = "catalog";
//$route['data-catalog/(:num)/accesspolicy'] = "catalog/accesspolicy/$1";
//$route['data-catalog/(:num)/download'] = "catalog/download/$1";
//$route['data-catalog/(:num)/ddi'] = "catalog/ddi/$1";
//$route['data-catalog/rss'] = "catalog/rss";
$route['catalog/(:num)'] = "catalog/survey/$1";
$route['catalog/(.*)'] = "catalog/$1";//this should always be teh last route for the data-catalog routes

//user
//$route['admin/users'] = "admin/user";
//$route['admin/users/:any'] = "admin/user";
#$route['register'] = "admin/user/register";

$route['switch_language/(.*)'] = "page/switch_language/$1";

//forms {pubic,direct, etc}
$route['forms/(.*)'] = "forms";

//admin paths
$route['admin'] = "admin/admin";

//catalog/resources
$route['admin/clear_cache'] = "admin/admin/clear_cache";
$route['admin/catalog/(:num)/resources'] = "admin/resources";
$route['admin/catalog/(:num)/resources/(:num)'] = "admin/resources/view/$2";
$route['admin/catalog/(:num)/resources/add'] = "admin/resources/add";
$route['admin/catalog/(:num)/resources/edit/(:num)'] = "admin/resources/edit/$2";
$route['admin/catalog/(:num)/resources/delete/(:num)'] = "admin/resources/delete/$2";
$route['admin/catalog/(:num)/resources/fixlinks'] = "admin/resources/fixlinks/$1";
$route['admin/catalog/(:num)/edit'] = "admin/catalog/edit/$1";
$route['admin/catalog/(:num)/resources/import'] = "admin/resources/import";

//licensed files
$route['admin/licensed_files/files/(:num)/add'] = "admin/licensed_files/add/$1";

//data files [public/direct/licensed/enclave]
$route['admin/catalog/(:num)/datafiles'] = "admin/datafiles/index/$1";//index page
$route['admin/datafiles/(:num)'] = "admin/datafiles/index/$1";
//$route['admin/catalog/(:num)/datafiles/edit/(:num)'] = "admin/datafiles/edit/$1";//edit page
$route['admin/catalog/(:num)/datafiles/add'] = "admin/datafiles/add/$1";//add page
$route['admin/datafiles/(:num)/edit/(:num)'] = "admin/datafiles/edit/$1/$2";//edit page
$route['admin/datafiles/(:num)/delete/(:num)'] = "admin/datafiles/delete/$1/$2";//edit page

//$route['admin/catalog/(:num)/data_files'] = "admin/data_files/$2"; 


//catalog/edit
//$route['admin/catalog/(:num)/edit'] = "admin/catalog/edit/$2";
//$route['admin/catalog/(:num)/delete'] = "admin/catalog/delete/$2";

//$route['admin/(.*)'] = "admin/admin";
//$route['admin/(.*)'] = "catalog/data_catalog";

//$route['admin/catalog2/(:num)/resources'] = "admin/resources";
//$route['admin/catalog2/(:num)/resources/add'] = "admin/resources/add";


//access request forms
$route['catalog/(:num)/request'] = "catalog/access_request_form/$1";
$route['survey/(:num)/request'] = "catalog/access_request_form/$1";

//public use files
$route['access_public/(:num)'] = "access_public/index/$1";
$route['access_public/(:num)/download/(:any)'] = "access_public/download/$1/$2";

//licensed
$route['access_licensed/(:num)'] = "access_licensed/index/$1";
$route['access_licensed/(:num)/download/(:any)'] = "access_licensed/download/$1/$2";

//direct downloads
$route['access_direct/(:num)'] = "access_direct/index/$1";
$route['access_direct/(:num)/download/(:any)'] = "access_direct/download/$1/$2";

//data enclave
$route['access_enclave/(:num)'] = "access_enclave/index/$1";
$route['access_enclave/(:num)/download/(:any)'] = "access_enclave/download/$1/$2";

//ddi browser
$route['ddibrowser/(:num)'] = "ddibrowser/index/$1";
$route['ddibrowser/(:num)/overview'] = "ddibrowser/overview/$1";


/*
//catalog routes
$route['admin/catalog'] = "catalog/catalog/admin";
$route['admin/catalog/(.*)'] = "catalog/catalog/$1";
 
$route['admin/users'] = "users/user";
$route['admin/user'] = "users/user";
$route['admin/user/(.*)'] = "users/user/$1";
*/

//$this->load->database();
//html pages
//$route['pages'] = "page";
//$route['page/(.*)'] = "page";
//$route['pages/(.*)'] = "page";

//$route['home'] = "page";
//$route['home-page'] = "page";
//$route['contact-us'] = "page";
//$route['(:any)'] = 'page';
/* End of file routes.php */
/* Location: ./system/application/config/routes.php */