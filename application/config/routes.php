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

$route['default_controller'] = "page";
$route['404_override'] = 'page'; 

$route['microdata-home-x'] = "page/static_page";
$route['terms-of-use'] = "page/static_page";
$route['using-our-catalog'] = "page/static_page";
$route['practices-and-tools'] = "page/static_page";
$route['faqs'] = "page/static_page";


//ddi browser
$route['ddibrowser/(:num)'] = "ddibrowser/index/$1";

//data-catalog page
$route['catalog/(:num)/ddibrowser'] = "ddibrowser/$1";

$route['catalog/(:num)'] = "ddibrowser/$1";
$route['catalog/(:num)/rdf'] = "catalog/rdf/$1";
$route['catalog/(:num)/citations'] = "catalog/citations/$1";
$route['catalog/(:num)/(.*)'] = "ddibrowser/$1/$2";
$route['catalog/(.*)'] = "catalog/$1";//this should always be the last route for the data-catalog routes

$route['switch_language/(.*)'] = "page/switch_language/$1";
<<<<<<< HEAD
//$route['home'] = "catalog/repositories";
=======
$route['microdata-catalogs'] = "catalog/repositories";
>>>>>>> 0df80238506a3fa904ffbc982da373dfec446f9c
$route['catalog/central/about'] = "catalog/repositories";

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

/* End of file routes.php */
/* Location: ./system/application/config/routes.php */