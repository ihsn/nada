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
$route['api/admin/collections/(:any)/datasets'] = "api/admin/collections/datasets/$1";
// Legacy alias: api/collections/* -> api/admin/collections/* (controller moved under admin)
$route['api/collections/(:any)/datasets'] = "api/admin/collections/datasets/$1";
$route['api/collections'] = 'api/admin/collections';
$route['api/collections/(.*)'] = 'api/admin/collections/$1';

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
$route['catalog/(:num)/indicator-chart'] = "study/indicator_chart/$1";
$route['catalog/(:num)/indicator-data-api'] = "study/indicator_observations/$1";
// Legacy slug (301-style redirect in controller).
$route['catalog/(:num)/indicator-observations'] = "study/redirect_indicator_observations/$1";
$route['catalog/(:num)/indicator-structure'] = "study/indicator_structure/$1";
// Legacy URL: redirects to indicator-chart (or observations/structure) with query preserved.
$route['catalog/(:num)/indicator-data'] = "study/indicator_data/$1";
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
$route['api/db_logs/row_counts']              = "api/db_logs/row_counts";
$route['api/db_logs/cleanup/chunk']           = "api/db_logs/cleanup_chunk";
$route['api/db_logs/api_logs/stats']         = "api/db_logs/api_logs_stats";
$route['api/db_logs/api_logs/cleanup/chunk'] = "api/db_logs/api_logs_cleanup_chunk";
$route['api/db_logs/api_logs/files']         = "api/db_logs/api_logs_files";

// Dashboard API
$route['api/dashboard/stats'] = "api/dashboard/stats";

// Admin Display Templates API
$route['api/admin/templates/import']                          = 'api/admin/templates/import';
$route['api/admin/templates/validate']                        = 'api/admin/templates/validate';
$route['api/admin/templates/default/(:any)/(:any)']           = 'api/admin/templates/default/$1/$2';
$route['api/admin/templates/(:any)/export']                   = 'api/admin/templates/export/$1';
$route['api/admin/templates/(:any)/duplicate']                = 'api/admin/templates/duplicate/$1';
$route['api/admin/templates/(:any)/delete']                   = 'api/admin/templates/delete/$1';
$route['api/admin/templates/renderers/(:any)']                = 'api/admin/templates/renderers_by_type/$1';
$route['api/admin/templates/renderers']                       = 'api/admin/templates/renderers';
$route['api/admin/templates/(:any)']                          = 'api/admin/templates/item/$1';

// Admin Codelists API (nested routes first)
// REST_Controller::_remap: map to versions/$1 (not versions_get/$1) so versions_get is invoked.
$route['api/admin/codelists/versions/(:any)']             = 'api/admin/codelists/versions/$1';
$route['api/admin/codelists/item/(:num)/restore']         = 'api/admin/codelists/item_restore/$1';
$route['api/admin/codelists/item/(:num)/items']           = 'api/admin/codelists/item_items/$1';
$route['api/admin/codelists/item/(:num)/groups']          = 'api/admin/codelists/item_groups/$1';
$route['api/admin/codelists/items/(:num)/translations/(:any)'] = 'api/admin/codelists/items_translation/$1/$2';
$route['api/admin/codelists/items/(:num)/translations']   = 'api/admin/codelists/items_translations/$1';
$route['api/admin/codelists/groups/(:num)/items/(:num)']   = 'api/admin/codelists/groups_items_remove/$1/$2';
$route['api/admin/codelists/groups/(:num)/items']         = 'api/admin/codelists/groups_items/$1';
$route['api/admin/codelists/groups/(:num)/translations/(:any)'] = 'api/admin/codelists/groups_translation/$1/$2';
$route['api/admin/codelists/groups/(:num)/translations'] = 'api/admin/codelists/groups_translations/$1';
$route['api/admin/codelists/import_json']               = 'api/admin/codelists/import_json';
$route['api/admin/codelists/item/(:num)']                = 'api/admin/codelists/item/$1';
$route['api/admin/codelists/items/(:num)']                = 'api/admin/codelists/items/$1';
$route['api/admin/codelists/groups/(:num)']               = 'api/admin/codelists/groups/$1';

// Admin Data Structures API (DSD catalogue; nested routes first)
$route['api/admin/data_structures/import_json']                  = 'api/admin/data_structures/import_json';
$route['api/admin/data_structures/import']                       = 'api/admin/data_structures/import';
$route['api/admin/data_structures/create']                       = 'api/admin/data_structures/create';
$route['api/admin/data_structures/update/(:any)']                = 'api/admin/data_structures/update/$1';
$route['api/admin/data_structures/status/(:any)']                = 'api/admin/data_structures/status/$1';
// versions/{id-or-idno}: second segment maps to versions_get($1); must precede catch-all …/(:any).
$route['api/admin/data_structures/versions/(:any)']             = 'api/admin/data_structures/versions/$1';
$route['api/admin/data_structures/projects/(:any)']             = 'api/admin/data_structures/projects/$1';
$route['api/admin/data_structures/by_identity']                  = 'api/admin/data_structures/by_identity';
$route['api/admin/data_structures/validate']                    = 'api/admin/data_structures/validate';
$route['api/admin/data_structures/export/(:any)']               = 'api/admin/data_structures/export/$1';
$route['api/admin/data_structures/batch_delete']                = 'api/admin/data_structures/batch_delete';
$route['api/admin/data_structures/delete/(:any)']              = 'api/admin/data_structures/delete/$1';
$route['api/admin/data_structures/components_update/(:num)']   = 'api/admin/data_structures/components_update/$1';
$route['api/admin/data_structures/components_delete/(:num)']   = 'api/admin/data_structures/components_delete/$1';
$route['api/admin/data_structures/components/(:any)']          = 'api/admin/data_structures/components/$1';
// Single structure: GET/DELETE …/{id_or_idno}; register after all named paths above
$route['api/admin/data_structures/(:any)']                      = 'api/admin/data_structures/structure_lookup/$1';

// Admin Timeseries API (indicator observations / Mongo; nested paths first)
$route['api/admin/timeseries/data/(:any)/import']                = 'api/admin/timeseries/data_import/$1';
$route['api/admin/timeseries/data/(:any)/count']                 = 'api/admin/timeseries/data_count/$1';
$route['api/admin/timeseries/data/(:any)']                       = 'api/admin/timeseries/data/$1';
$route['api/admin/timeseries/data/(:any)/sync-counts']          = 'api/admin/timeseries/data_sync_counts/$1';
$route['api/admin/timeseries/data/(:any)/value-counts-summary'] = 'api/admin/timeseries/data_value_counts_summary/$1';
$route['api/admin/timeseries/data/(:any)/attach-dsd']          = 'api/admin/timeseries/data_attach_dsd/$1';
$route['api/admin/timeseries/data/(:any)/rehash']              = 'api/admin/timeseries/data_rehash/$1';
$route['api/admin/timeseries/data/(:any)/duplicates']          = 'api/admin/timeseries/data_duplicates/$1';
$route['api/admin/timeseries/data/(:any)/schema']              = 'api/admin/timeseries/data_schema/$1';
$route['api/admin/timeseries/data-structures/(:num)/indexes']  = 'api/admin/timeseries/structure_indexes/$1';
$route['api/admin/timeseries/data-structures/(:num)/rehash']   = 'api/admin/timeseries/structure_rehash/$1';
$route['api/admin/timeseries/data-structures/(:num)/duplicates'] = 'api/admin/timeseries/structure_duplicates/$1';
$route['api/admin/timeseries/data-structures/(:num)/stats']    = 'api/admin/timeseries/structure_stats/$1';

// Public Timeseries API (read-only; nested paths first)
$route['api/timeseries/data/(:any)/count']                      = 'api/timeseries_public/data_count/$1';
$route['api/timeseries/data/(:any)/export']                     = 'api/timeseries_public/data_export/$1';
$route['api/timeseries/data/(:any)']                            = 'api/timeseries_public/data/$1';
$route['api/timeseries/data/(:any)/filter-options']             = 'api/timeseries_public/data_filter_options/$1';
$route['api/timeseries/data/(:any)/chart']                      = 'api/timeseries_public/data_chart/$1';
$route['api/timeseries/data/(:any)/schema']                     = 'api/timeseries_public/data_schema/$1';
$route['api/timeseries/data-structures/by_idno/(:any)']        = 'api/timeseries_public/structure_by_idno/$1';
$route['api/timeseries/data-structures/by_identity/(:any)']    = 'api/timeseries_public/structure_by_identity/$1';
$route['api/timeseries/data-structures/versions/(:any)']       = 'api/timeseries_public/structure_versions/$1';
$route['api/timeseries/data-structures/item/(:num)']         = 'api/timeseries_public/structure_item/$1';
$route['api/timeseries/codelists/by_idno/(:any)']              = 'api/timeseries_public/codelist_by_idno/$1';
$route['api/timeseries/codelists/by_name/(:any)']             = 'api/timeseries_public/codelist_by_name/$1';
$route['api/timeseries/codelists/item/(:num)/items']          = 'api/timeseries_public/codelist_item_items/$1';
$route['api/timeseries/codelists/item/(:num)']                = 'api/timeseries_public/codelist_item/$1';

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
$route['api/analytics/daily/studies/export'] = "api/analytics/daily_studies_export";
$route['api/analytics/daily/files/export'] = "api/analytics/daily_files_export";
$route['api/analytics/monthly/studies/export'] = "api/analytics/monthly_studies_export";
$route['api/analytics/monthly/files/export'] = "api/analytics/monthly_files_export";
$route['api/analytics/daily/studies'] = "api/analytics/daily_studies";
$route['api/analytics/daily/files'] = "api/analytics/daily_files";
$route['api/analytics/monthly/studies'] = "api/analytics/monthly_studies";
$route['api/analytics/monthly/files'] = "api/analytics/monthly_files";
$route['api/analytics/monthly/totals'] = "api/analytics/monthly_totals";
$route['api/analytics/studies'] = "api/analytics/studies";
$route['api/analytics/aggregate/run_all'] = "api/analytics/aggregate_run_all";
$route['api/analytics/legacy/studies'] = "api/analytics/legacy_studies";
$route['api/analytics/legacy/files'] = "api/analytics/legacy_files";


/* End of file routes.php */
/* Location: ./system/application/config/routes.php */

/* Location: ./system/application/config/routes.php */
