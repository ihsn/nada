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
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'page';
$route['404_override'] = 'page';
$route['translate_uri_dashes'] = FALSE;



///////////////////////// API routes ////////////////////////////////////////

//data deposit project - resources
$route['api/datadeposits/(:num)/resources'] = "api/datadeposits/resources/$1";
$route['api/datadeposits/(:num)/resources/(:num)'] = "api/datadeposits/resources/$1/$2";

//data deposit project - citations
$route['api/datadeposits/(:num)/citations'] = "api/datadeposits/citations/$1";
$route['api/datadeposits/(:num)/citations/(:num)'] = "api/datadeposits/citations/$1/$2";

//submit project
$route['api/datadeposits/(:num)/submit'] = "api/datadeposits/submit/$1";

//projct access policy
$route['api/datadeposits/(:num)/access_policy'] = "api/datadeposits/access_policy/$1";

//dataset datafiles
$route['api/datasets/(:any)/datafiles'] = "api/datasets/datafiles/$1";

//timeseries series [variable]
$route['api/datasets/(:any)/series/(:any)'] = "api/datasets/series/$1/$2";

//variables
$route['api/datasets/(:any)/variables/(:any)'] = "api/datasets/variables/$1/$2";


//dataset resources
$route['api/datasets/(:any)/resources'] = "api/resources/$1";
$route['api/datasets/(:any)/resources/(:num)'] = "api/resources/$1/$2";
$route['api/datasets/(:any)/resources/delete/(:num)'] = "api/resources/delete/$1/$2";
$route['api/datasets/(:any)/resources/delete_all'] = "api/resources/delete_all/$1";
$route['api/datasets/(:any)/resources/import_rdf'] = "api/resources/import_rdf/$1";
$route['api/datasets/(:any)/resources/download/(:num)'] = "api/resources/download/$1/$2";

//dataset files
$route['api/datasets/(:any)/files'] = "api/files/$1";
$route['api/datasets/(:any)/files/(.*)'] = "api/files/$1/$2";
$route['api/datasets/(:any)/download/(.*)'] = "api/files/download/$1/$2";

$route['api/datasets/(:any)/thumbnail'] = "api/datasets/thumbnail/$1";

//public api
$route['api/catalog/(:any)/data_files/(.*)/variables'] = "api/catalog/data_file_variables/$1/$2";
$route['api/catalog/(:any)/data_files/(.*)'] = "api/catalog/data_files/$1/$2";
$route['api/catalog/(:any)/data_files'] = "api/catalog/data_files/$1";

$route['api/catalog/(:any)/variables/(.*)'] = "api/catalog/variables/$1/$2";
$route['api/catalog/(:any)/variables'] = "api/catalog/variables/$1";

$route['api/catalog/(:any)/variable'] = "api/catalog/variable/$1";
$route['api/catalog/(:any)/variable/(.*)'] = "api/catalog/variable/$1/$2";


//Collections
$route['api/collections/(:any)/datasets'] = "api/collections/datasets/$1";

//Tables API - fields endpoints (new format: /api/tables/fields/{db_id}/{table_id})
$route['api/tables/fields/(:any)/(:any)/sync'] = "api/tables/fields_sync/$1/$2";
$route['api/tables/fields/(:any)/(:any)/populate'] = "api/tables/fields_populate/$1/$2";
$route['api/tables/fields/(:any)/(:any)/reorder'] = "api/tables/fields_reorder/$1/$2";
$route['api/tables/fields/(:any)/(:any)/(:any)/delete'] = "api/tables/fields_delete/$1/$2/$3";
$route['api/tables/fields/(:any)/(:any)/(:any)'] = "api/tables/field/$1/$2/$3";
$route['api/tables/fields/(:any)/(:any)'] = "api/tables/fields/$1/$2";

//Tables API - export definition endpoint
$route['api/tables/export_definition/(:any)/(:any)'] = "api/tables/export_definition/$1/$2";

//Tables API - indexes endpoints
$route['api/tables/indexes/(:any)/(:any)/all'] = "api/tables/indexes_delete_all/$1/$2";

//Tables API - studies endpoints
$route['api/tables/(:any)/(:any)/studies'] = "api/tables/studies/$1/$2";

/*
$route['api/datasets/(:num)/resources/delete_all'] = "api/datadeposits/resources/delete_all/$1";
$route['api/datasets/(:num)/resources/upload_file'] = "api/datadeposits/resources/upload_file/$1";
$route['api/datasets/(:num)/resources(:num)/upload'] = "api/datadeposits/resources/upload/$1/$2";
*/


///////////////////////// END API routes /////////////////////////////////////

/*
$route['api/datadeposits/(:any)'] = "api/datadeposits/projects/$1";
$route['api/datadeposits'] = "api/datadeposits/projects";
*/


//collections
$route['collections/(.*)'] = "collections/index/$1";

//for new study page
$route['catalog/(:num)/study-description'] = "study/metadata/$1";
$route['catalog/(:num)/metadata'] = "study/metadata/$1";
$route['catalog/(:num)/data-dictionary'] = "study/data_dictionary/$1";
$route['catalog/(:num)/data_dictionary'] = "study/data_dictionary/$1";
$route['catalog/(:num)/variable-groups'] = "study/variable_groups/$1";
$route['catalog/(:num)/variable_groups'] = "study/variable_groups/$1";
$route['catalog/(:num)/vargrp'] = "study/variable_groups/$1";

//timeseries db info page
$route['catalog/(:num)/timeseries-db'] = "study/timeseries_db/$1";

$route['catalog/(:num)/variable-groups/(.*)'] = "study/variable_groups/$1/$2";
$route['catalog/(:num)/variable_groups/(.*)'] = "study/variable_groups/$1/$2";
$route['catalog/(:num)/vargrp/(.*)'] = "study/variable_groups/$1/$2";

//data file page
$route['catalog/(:num)/data-dictionary/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/data_dictionary/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/datafile/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/data-file/(.*)'] = "study/data_file/$1/$2";

//download
$route['catalog/(:num)/download/(.*)'] = "study/download/$1/$2";

//variable info page
$route['catalog/(:num)/variable/(.*)'] = "study/variable/$1/$2/$3"; //sid/fid/vid

//variable search
$route['catalog/(:num)/search'] = "study/search/$1";

$route['catalog/(:num)'] = "study/metadata/$1";
$route['catalog/(:num)/related-publications'] = "study/related_publications/$1";
$route['catalog/(:num)/related_citations'] = "study/related_publications/$1";
$route['catalog/(:num)/get-microdata'] = "study/get_microdata/$1";
$route['catalog/(:num)/get_microdata'] = "study/get_microdata/$1";
$route['catalog/(:num)/related_materials'] = "study/related_materials/$1";
$route['catalog/(:num)/related-materials'] = "study/related_materials/$1";
$route['catalog/(:num)/downloads'] = "study/downloads/$1";
$route['catalog/(:num)/request-access'] = "study/request_access/$1";
$route['catalog/(:num)/request-access/(.*)'] = "study/request_access/$1";
$route['catalog/(:num)/related-datasets'] = "study/related_datasets/$1";
$route['catalog/(:num)/pdf-documentation'] = "study/pdf_documentation/$1";
$route['catalog/(:num)/data-api'] = "study/data_api/$1";


//$route['catalog/(:num)'] = "ddibrowser/$1";
$route['catalog/(:num)/rdf'] = "catalog/rdf/$1";
$route['catalog/(:num)/citations'] = "catalog/citations/$1";
//$route['catalog/(:num)/(.*)'] = "ddibrowser/$1/$2";
$route['catalog/(:num)/(.*)'] = "study/$1/$2";

$route['catalog/(.*)'] = "catalog/$1";//this should always be the last route for the data-catalog routes

$route['switch_language/(.*)'] = "page/switch_language/$1";
//$route['home'] = "catalog/repositories";
/*$route['catalog/central/about'] = "catalog/repositories";*/

//forms {pubic,direct, etc}
$route['forms/(.*)'] = "forms";

//$route['embed/(.*)'] = "embed/index/$1";
//$route['embed/(.*)'] = "embed/index/$1";

//admin paths
$route['admin'] = "admin/admin";
$route['admin/logs/cleanup'] = "admin/logs/cleanup";

// DB Logs API
$route['api/db_logs/cleanup/chunk'] = "api/db_logs/cleanup_chunk";

// API Logs Admin Page
$route['admin/api_logs'] = "admin/logs/api_logs";

// Filestore Admin Page
$route['admin/filestore'] = "admin/filestore";
$route['admin/filestore/upload'] = "admin/filestore";

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

//data deposit
$route['admin/datadeposit/tasks/info/(.*)'] = "admin/datadeposittasks/info/$1";
$route['admin/datadeposit/tasks/update/(:num)/(:num)'] = "admin/datadeposittasks/update/$1/$2";
$route['admin/datadeposit/tasks/delete/(:num)'] = "admin/datadeposittasks/delete/$1";
$route['admin/datadeposit/tasks/my_tasks'] = "admin/datadeposittasks/my_tasks";
$route['admin/datadeposit/tasks'] = "admin/datadeposittasks";



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

//public use files by collection
$route['access_public_collection/(:num)'] = "access_public_collection/index/$1";

//licensed
$route['access_licensed/(:num)'] = "access_licensed/index/$1";
$route['access_licensed/(:num)/download/(:any)'] = "access_licensed/download/$1/$2";

//direct downloads
$route['access_direct/(:num)'] = "access_direct/index/$1";
$route['access_direct/(:num)/download/(:any)'] = "access_direct/download/$1/$2";

//data enclave
$route['access_enclave/(:num)'] = "access_enclave/index/$1";
$route['access_enclave/(:num)/download/(:any)'] = "access_enclave/download/$1/$2";

$route['admin/permissions/(:num)'] = "admin/permissions/index/$1";

$route['admin/catalog/attach_related_data/(:num)'] = "admin/attach_related_data/index/$1";


//Downloads API
$route['api/downloads/(:any)/files'] = "api/downloads/files/$1";
$route['api/downloads/(:any)/info/(:any)'] = "api/downloads/info/$1/$2";

//Analytics API
$route['api/analytics/pageview'] = "api/analytics/pageview";
$route['api/analytics/download'] = "api/analytics/download";
$route['api/analytics/recent/pageviews'] = "api/analytics/recent_pageviews";
$route['api/analytics/recent/downloads'] = "api/analytics/recent_downloads";
$route['api/analytics/stats/study/(:any)'] = "api/analytics/stats_study_get/$1";
$route['api/analytics/stats/file/(:any)'] = "api/analytics/stats_file_get/$1";
$route['api/analytics/totals/study/(:any)'] = "api/analytics/totals_study_get/$1";
$route['api/analytics/totals/file/(:any)'] = "api/analytics/totals_file_get/$1";
$route['api/analytics/aggregate/daily'] = "api/analytics/aggregate_daily";
$route['api/analytics/aggregate/monthly'] = "api/analytics/aggregate_monthly";
$route['api/analytics/aggregate/totals'] = "api/analytics/aggregate_totals";
$route['api/analytics/aggregate/run'] = "api/analytics/aggregate_run";
$route['api/analytics/aggregate/status'] = "api/analytics/aggregate_status";
$route['api/analytics/aggregate/stop'] = "api/analytics/aggregate_stop";
$route['api/analytics/raw/pageviews'] = "api/analytics/raw_pageviews";
$route['api/analytics/raw/downloads'] = "api/analytics/raw_downloads";
$route['api/analytics/monthly/studies'] = "api/analytics/monthly_studies";
$route['api/analytics/monthly/files'] = "api/analytics/monthly_files";
$route['api/analytics/monthly/totals'] = "api/analytics/monthly_totals";
$route['api/analytics/studies'] = "api/analytics/studies";
$route['api/analytics/aggregate/run_all'] = "api/analytics/aggregate_run_all";


/* End of file routes.php */
/* Location: ./system/application/config/routes.php */

/* Location: ./system/application/config/routes.php */
