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



// Schema files served from application/schemas/ (web-inaccessible directory)
$route['schemas/openapi/(:any)'] = 'schemas/openapi/$1';
$route['schemas/(:any)'] = 'schemas/serve/$1';

///////////////////////// API routes ////////////////////////////////////////

// Depositor Vue API (collaborator auth). Not api/datadeposits (legacy staff read)
// and not api/admin/datadeposit (staff review).
$route['api/datadeposit/(:num)/metadata'] = 'api/datadeposit/metadata/$1';
$route['api/datadeposit/(:num)/submission'] = 'api/datadeposit/submission/$1';
$route['api/datadeposit/(:num)/submit'] = 'api/datadeposit/submit/$1';
$route['api/datadeposit/(:num)/files/delete'] = 'api/datadeposit/files_delete/$1';
$route['api/datadeposit/(:num)/files/(:num)/download'] = 'api/datadeposit/files_download/$1/$2';
$route['api/datadeposit/(:num)/files/(:num)'] = 'api/datadeposit/files_item/$1/$2';
$route['api/datadeposit/(:num)/files'] = 'api/datadeposit/files/$1';
$route['api/datadeposit/(:num)/delete'] = 'api/datadeposit/delete_item/$1';
$route['api/datadeposit/(:num)/reopen'] = 'api/datadeposit/reopen/$1';
$route['api/datadeposit/(:num)/email'] = 'api/datadeposit/email/$1';
$route['api/datadeposit/(:num)/export/(:any)'] = 'api/datadeposit/export/$1/$2';
$route['api/datadeposit/(:num)/validate'] = 'api/datadeposit/validate/$1';
$route['api/datadeposit/(:num)/import'] = 'api/datadeposit/import/$1';
$route['api/datadeposit/(:num)'] = 'api/datadeposit/item/$1';
$route['api/datadeposit'] = 'api/datadeposit';

// Staff data-deposit admin (ACL datadeposit). Not the depositor API above.
$route['api/admin/datadeposit/projects/(:num)/files/(:num)/download'] = 'api/admin/datadeposit/projects_files_download/$1/$2';
$route['api/admin/datadeposit/projects/(:num)/export/(:any)'] = 'api/admin/datadeposit/projects_export/$1/$2';
$route['api/admin/datadeposit/projects/(:num)/process'] = 'api/admin/datadeposit/projects_process/$1';
$route['api/admin/datadeposit/projects/(:num)/communicate'] = 'api/admin/datadeposit/projects_communicate/$1';
$route['api/admin/datadeposit/projects/(:num)/history'] = 'api/admin/datadeposit/projects_history/$1';
$route['api/admin/datadeposit/projects/(:num)/delete'] = 'api/admin/datadeposit/projects_delete/$1';
$route['api/admin/datadeposit/projects/(:num)/assign'] = 'api/admin/datadeposit/projects_assign/$1';
$route['api/admin/datadeposit/delete'] = 'api/admin/datadeposit/delete';
$route['api/admin/datadeposit/tasks/my'] = 'api/admin/datadeposit/tasks_my';
$route['api/admin/datadeposit/tasks/(:num)/update'] = 'api/admin/datadeposit/tasks_update/$1';
$route['api/admin/datadeposit/tasks/(:num)/delete'] = 'api/admin/datadeposit/tasks_delete/$1';
$route['api/admin/datadeposit/tasks/(:num)'] = 'api/admin/datadeposit/tasks_item/$1';
$route['api/admin/datadeposit/tasks'] = 'api/admin/datadeposit/tasks';
$route['api/admin/datadeposit/projects/(:num)'] = 'api/admin/datadeposit/projects/$1';
$route['api/admin/datadeposit'] = 'api/admin/datadeposit';
$route['api/admin/datadeposit/(:any)'] = 'api/admin/datadeposit/$1';

// Legacy staff read API. Export/download use v2 (same as /api/admin/datadeposit).
$route['api/datadeposits/(:num)/export/(:any)'] = 'api/datadeposits/export/$2/$1';
$route['api/datadeposits/export/(:any)/(:num)'] = 'api/datadeposits/export/$1/$2';
$route['api/datadeposits/(:num)/download/(:num)'] = 'api/datadeposits/download/$1/$2';
$route['api/datadeposits/download/(:num)/(:num)'] = 'api/datadeposits/download/$1/$2';

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
$route['api/datasets/(:any)/resources/(:num)/fix_links'] = 'api/resources/fix_links/$1';
$route['api/datasets/(:any)/resources/fix_links'] = 'api/resources/fix_links/$1';

// Admin external resources API — mirror of api/resources (same handlers); study ref + /resources/...
$route['api/admin/resources/(:any)/resources'] = 'api/admin/resources/$1';
$route['api/admin/resources/(:any)/resources/(:num)'] = 'api/admin/resources/$1/$2';
$route['api/admin/resources/(:any)/resources/delete/(:num)'] = 'api/admin/resources/delete/$1/$2';
$route['api/admin/resources/(:any)/resources/delete_all'] = 'api/admin/resources/delete_all/$1';
$route['api/admin/resources/(:any)/resources/import_rdf'] = 'api/admin/resources/import_rdf/$1';
$route['api/admin/resources/(:any)/resources/export_rdf'] = 'api/admin/resources/export_rdf/$1';
$route['api/admin/resources/(:any)/resources/download/(:num)'] = 'api/admin/resources/download/$1/$2';
$route['api/admin/resources/(:any)/resources/(:num)/fix_links'] = 'api/admin/resources/fix_links/$1';
$route['api/admin/resources/(:any)/resources/fix_links'] = 'api/admin/resources/fix_links/$1';
$route['api/admin/resources/download_links'] = 'api/admin/resources/download_links';

//dataset files
$route['api/datasets/(:any)/files'] = "api/files/$1";
$route['api/datasets/(:any)/files/(.*)'] = "api/files/$1/$2";
$route['api/datasets/(:any)/download/(.*)'] = "api/files/download/$1/$2";

$route['api/datasets/(:any)/thumbnail'] = "api/datasets/thumbnail/$1";
$route['api/admin/search-metadata-extract/status']              = 'api/admin/search_metadata_extract/status';
$route['api/admin/search-metadata-extract/studies/(:any)']      = 'api/admin/search_metadata_extract/studies/$1';
$route['api/admin/search-metadata-extract/studies']             = 'api/admin/search_metadata_extract/studies';
$route['api/admin/search-metadata-extract/citations/(:num)']    = 'api/admin/search_metadata_extract/citations/$1';
$route['api/admin/search-metadata-extract/variables/(:any)']     = 'api/admin/search_metadata_extract/variables/$1';

$route['api/admin/search-index/status']                         = 'api/admin/search_index/status';
$route['api/admin/search-index/queue/(:num)/ack']               = 'api/admin/search_index/ack/$1';
$route['api/admin/search-index/queue']                          = 'api/admin/search_index/queue';
$route['api/admin/search-index/requeue']                        = 'api/admin/search_index/requeue';

$route['api/admin/catalog/data-access-codelist']         = 'api/admin/catalog/data_access_codelist';
$route['api/admin/catalog/data-access-options']          = 'api/admin/catalog/data_access_options';
$route['api/admin/catalog/data-classifications']         = 'api/admin/data_classifications';
$route['api/admin/catalog/data-classifications/(.*)']   = 'api/admin/data_classifications/$1';
$route['api/admin/catalog/check_idno/(:any)']           = 'api/admin/catalog/check_idno/$1';
$route['api/admin/catalog/replace_idno']                = 'api/admin/catalog/replace_idno';
$route['api/admin/catalog/import_package/status']       = 'api/admin/catalog/import_package_status';
$route['api/admin/catalog/import_package/unzip']        = 'api/admin/catalog/import_package_unzip';
$route['api/admin/catalog/import_package/create']       = 'api/admin/catalog/import_package_create';
$route['api/admin/catalog/import_package/datafile']     = 'api/admin/catalog/import_package_datafile';
$route['api/admin/catalog/import_package/finalize']     = 'api/admin/catalog/import_package_finalize';
$route['api/admin/catalog/list_collections']            = 'api/admin/catalog/list_collections';
$route['api/admin/catalog/(:any)/warnings']            = 'api/admin/catalog/warnings/$1';
$route['api/admin/catalog/(:any)/summary']             = 'api/admin/catalog/summary/$1';
$route['api/admin/catalog/(:any)/folder-status']      = 'api/admin/catalog/folder_status/$1';
$route['api/admin/catalog/(:any)/aliases/delete']       = 'api/admin/catalog/aliases_delete/$1';
$route['api/admin/catalog/(:any)/aliases_delete']       = 'api/admin/catalog/aliases_delete/$1';
$route['api/admin/catalog/(:any)/aliases']              = 'api/admin/catalog/aliases/$1';
$route['api/admin/catalog/(:any)/collections'] = 'api/admin/catalog/collections/$1';
$route['api/admin/catalog/(:any)/doi']         = 'api/admin/catalog/doi/$1';
$route['api/admin/catalog/(:any)/package']     = 'api/admin/catalog/package/$1';
$route['api/admin/catalog/(:any)/thumbnail'] = 'api/admin/catalog/thumbnail/$1';
$route['api/admin/catalog/(:any)/tags/delete'] = 'api/admin/catalog/tags_delete/$1';
$route['api/admin/catalog/(:any)/tags_delete'] = 'api/admin/catalog/tags_delete/$1';
$route['api/admin/catalog/(:any)/tags']       = 'api/admin/catalog/tags/$1';
// Microdata (DDI data files + variables) — `api/admin/microdata` controller; literal `microdata` before generic catalog/(:any)/… routes
$route['api/admin/catalog/(:any)/microdata/variable_delete/(:any)/(:any)'] = 'api/admin/microdata/variable_delete/$1/$2/$3';
$route['api/admin/catalog/(:any)/microdata/variables_delete/(:any)'] = 'api/admin/microdata/variables_delete/$1/$2';
$route['api/admin/catalog/(:any)/microdata/datafiles_delete/(:any)'] = 'api/admin/microdata/datafiles_delete/$1/$2';
$route['api/admin/catalog/(:any)/microdata/variable/(:any)/(:any)'] = 'api/admin/microdata/variable/$1/$2/$3';
$route['api/admin/catalog/(:any)/microdata/variable/(:any)']     = 'api/admin/microdata/variable/$1/$2';
$route['api/admin/catalog/(:any)/microdata/variables/(:any)']    = 'api/admin/microdata/variables/$1/$2';
$route['api/admin/catalog/(:any)/microdata/variables']           = 'api/admin/microdata/variables/$1';
$route['api/admin/catalog/(:any)/microdata/datafiles/(:any)']    = 'api/admin/microdata/datafiles/$1/$2';
$route['api/admin/catalog/(:any)/microdata/datafiles']           = 'api/admin/microdata/datafiles/$1';
$route['api/admin/catalog/(:any)/generate_pdf'] = 'api/admin/catalog/generate_pdf/$1';
$route['api/admin/catalog/delete_pdf/(:any)'] = 'api/admin/catalog/delete_pdf/$1';
$route['api/admin/catalog/(:any)/replace_ddi'] = 'api/admin/catalog/replace_ddi/$1';
$route['api/admin/catalog/(:any)/export_ddi'] = 'api/admin/catalog/export_ddi/$1';
$route['api/admin/catalog/(:any)/refresh_ddi'] = 'api/admin/catalog/refresh_ddi/$1';
$route['api/admin/catalog/(:any)/generate_ddi'] = 'api/admin/catalog/generate_ddi/$1';
$route['api/admin/catalog/(:any)/validate_ddi'] = 'api/admin/catalog/validate_ddi/$1';
$route['api/admin/catalog/(:any)/transfer_ownership'] = 'api/admin/catalog/transfer_ownership/$1';
// Study folder files — explicit segments before generic files/(.*)
$route['api/admin/catalog/(:any)/files/download']       = 'api/admin/catalog_files/download/$1';
$route['api/admin/catalog/(:any)/files/download/(.*)'] = 'api/admin/catalog_files/download/$1/$2';
$route['api/admin/catalog/(:any)/files/commit']       = 'api/admin/catalog_files/commit_resumable/$1';
$route['api/admin/catalog/(:any)/files/upload']       = 'api/admin/catalog_files/process_batch_uploads/$1';
$route['api/admin/catalog/(:any)/files/(.*)/delete']  = 'api/admin/catalog_files/files_delete/$1/$2';
$route['api/admin/catalog/(:any)/files/(.*)']        = 'api/admin/catalog_files/$1/$2';
$route['api/admin/catalog/(:any)/files']              = 'api/admin/catalog_files/$1';
$route['api/admin/catalog/(:any)/citations/search']   = 'api/admin/study_citations/search/$1';
$route['api/admin/catalog/(:any)/citations/(:num)']   = 'api/admin/study_citations/$1/$2';
$route['api/admin/catalog/(:any)/citations']          = 'api/admin/study_citations/$1';
$route['api/admin/catalog/(:any)/admin-metadata/delete']  = 'api/admin/study_admin_metadata/delete_post/$1';
$route['api/admin/catalog/(:any)/admin-metadata/update']  = 'api/admin/study_admin_metadata/update_post/$1';
$route['api/admin/catalog/(:any)/admin-metadata']         = 'api/admin/study_admin_metadata/$1';
$route['api/admin/catalog/(:any)/notes/(.*)']         = 'api/admin/study_notes/$1/$2';
$route['api/admin/catalog/(:any)/notes']              = 'api/admin/study_notes/$1';
$route['api/admin/catalog/(:any)/related-studies/search']   = 'api/admin/study_related_studies/search/$1';
$route['api/admin/catalog/(:any)/related-studies/(:num)']   = 'api/admin/study_related_studies/$1/$2';
$route['api/admin/catalog/(:any)/related-studies']          = 'api/admin/study_related_studies/$1';
$route['api/admin/data-classifications']              = 'api/admin/data_classifications';
$route['api/admin/data-classifications/(.*)']        = 'api/admin/data_classifications/$1';

// Admin catalog study folder files — Catalog_files controller (not api/admin/files)
$route['api/admin/catalog-files/(:any)/download']       = 'api/admin/catalog_files/download/$1';
$route['api/admin/catalog-files/(:any)/download/(.*)'] = 'api/admin/catalog_files/download/$1/$2';
$route['api/admin/catalog-files/(:any)/commit']       = 'api/admin/catalog_files/commit_resumable/$1';
$route['api/admin/catalog-files/(:any)/upload']       = 'api/admin/catalog_files/process_batch_uploads/$1';
$route['api/admin/catalog-files/(:any)/(.*)/delete']  = 'api/admin/catalog_files/files_delete/$1/$2';
$route['api/admin/catalog-files/(:any)/(.*)']        = 'api/admin/catalog_files/$1/$2';
$route['api/admin/catalog-files/(:any)']              = 'api/admin/catalog_files/$1';

//public api
$route['api/catalog/(:any)/data_files/(.*)/variables'] = "api/catalog/data_file_variables/$1/$2";
$route['api/catalog/(:any)/data_files/(.*)'] = "api/catalog/data_files/$1/$2";
$route['api/catalog/(:any)/data_files'] = "api/catalog/data_files/$1";

$route['api/catalog/(:any)/variables/(.*)'] = "api/catalog/variables/$1/$2";
$route['api/catalog/(:any)/variables'] = "api/catalog/variables/$1";

$route['api/catalog/(:any)/variable'] = "api/catalog/variable/$1";
$route['api/catalog/(:any)/variable/(.*)'] = "api/catalog/variable/$1/$2";

$route['api/catalog/pdf_documentation/(:any)'] = 'api/catalog/pdf_documentation/$1';
$route['api/catalog/(:any)/resources/(:num)/pdf-stream'] = 'api/catalog/pdf_stream/$1/$2';
$route['api/catalog/(:any)/resources'] = 'api/catalog/resources/$1';


//Collections
$route['api/admin/collections/(:any)/datasets'] = "api/admin/collections/datasets/$1";
// Legacy alias: api/collections/* -> api/admin/collections/* (controller moved under admin)
$route['api/collections/(:any)/datasets'] = "api/admin/collections/datasets/$1";
$route['api/collections'] = 'api/admin/collections';
$route['api/collections/(.*)'] = 'api/admin/collections/$1';

$route['api/tables/import_errors/(:any)/(:any)'] = "api/tables/import_errors/$1/$2";

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

// Legacy Repositories admin URLs → Collections (controller removed in NADA 5.6)
$route['admin/repositories/active/(:num)'] = 'admin/collections/active/$1';
$route['admin/repositories/select'] = 'admin/collections';
$route['admin/repositories'] = 'admin/collections';
$route['admin/repositories/(.*)'] = 'admin/collections';

//for new study page
$route['catalog/(:num)/study-description'] = "study/metadata/$1";
$route['catalog/(:num)/metadata'] = "study/metadata/$1";
$route['catalog/(:num)/data-dictionary'] = "study/data_dictionary/$1";
$route['catalog/(:num)/data_dictionary'] = "study/data_dictionary/$1";
$route['catalog/(:num)/variable-groups'] = "study/variable_groups/$1";
$route['catalog/(:num)/variable_groups'] = "study/variable_groups/$1";
$route['catalog/(:num)/vargrp'] = "study/variable_groups/$1";

// Indicator / timeseries study pages
$route['catalog/(:num)/indicator-chart'] = "study/indicator_chart/$1";
$route['catalog/(:num)/indicator-table'] = "study/indicator_table/$1";
$route['catalog/(:num)/indicator-table-export'] = "study/indicator_table_export/$1";
$route['catalog/(:num)/indicator-data-api'] = "study/indicator_observations/$1";
// Legacy slug (301-style redirect in controller).
$route['catalog/(:num)/indicator-observations'] = "study/redirect_indicator_observations/$1";
$route['catalog/(:num)/indicator-structure'] = "study/indicator_structure/$1";
// Legacy URL: redirects to indicator-chart (or observations/structure) with query preserved.
$route['catalog/(:num)/indicator-data'] = "study/indicator_data/$1";
$route['catalog/(:num)/related-series'] = "study/related_series/$1";

$route['catalog/(:num)/variable-groups/(.*)'] = "study/variable_groups/$1/$2";
$route['catalog/(:num)/variable_groups/(.*)'] = "study/variable_groups/$1/$2";
$route['catalog/(:num)/vargrp/(.*)'] = "study/variable_groups/$1/$2";

//data file page
$route['catalog/(:num)/data-dictionary/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/data_dictionary/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/datafile/(.*)'] = "study/data_file/$1/$2";
$route['catalog/(:num)/data-file/(.*)'] = "study/data_file/$1/$2";

//download
$route['viewer/pdf'] = 'pdfviewer/index';
$route['catalog/(:num)/pdf-stream/(:num)'] = "study/pdf_stream/$1/$2";
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

$route['embed/catalog/(:num)/chart'] = 'embed/catalog_chart/$1';
$route['embed/catalog/(:num)/table'] = 'embed/catalog_table/$1';

//admin paths
$route['admin'] = "admin/admin";
$route['admin/ui-kit'] = "admin/admin/ui_kit";
$route['admin/logs/cleanup'] = "admin/logs/cleanup";

// DB Logs API
$route['api/db_logs/row_counts']              = "api/db_logs/row_counts";
$route['api/db_logs/cleanup/chunk']           = "api/db_logs/cleanup_chunk";
$route['api/db_logs/api_logs/stats']         = "api/db_logs/api_logs_stats";
$route['api/db_logs/api_logs/cleanup/chunk'] = "api/db_logs/api_logs_cleanup_chunk";
$route['api/db_logs/api_logs/files']         = "api/db_logs/api_logs_files";

// Dashboard API
$route['api/dashboard/stats'] = "api/dashboard/stats";

// Admin Site Configurations API — REST_Controller::_remap handles GET/PUT/PATCH/DELETE on
// api/admin/configurations and POST aliases api/admin/configurations/save|patch|remove.

// Admin Display Template Manager API
$route['api/admin/display_templates/import']                          = 'api/admin/display_templates/import';
$route['api/admin/display_templates/validate']                        = 'api/admin/display_templates/validate';
$route['api/admin/display_templates/default/(:any)/(:any)']           = 'api/admin/display_templates/default/$1/$2';
$route['api/admin/display_templates/cores']                           = 'api/admin/display_templates/cores';
$route['api/admin/display_templates/core/(:any)']                     = 'api/admin/display_templates/core/$1';
$route['api/admin/display_templates/(:any)/translations/(:any)/remove'] = 'api/admin/display_templates/translation_remove/$1/$2';
$route['api/admin/display_templates/(:any)/translations/(:any)']        = 'api/admin/display_templates/translation_item/$1/$2';
$route['api/admin/display_templates/(:any)/translations']               = 'api/admin/display_templates/translations/$1';
$route['api/admin/display_templates/(:any)/export']                   = 'api/admin/display_templates/export/$1';
$route['api/admin/display_templates/(:any)/duplicate']                = 'api/admin/display_templates/duplicate/$1';
$route['api/admin/display_templates/(:any)/delete']                   = 'api/admin/display_templates/delete/$1';
$route['api/admin/display_templates/renderers/(:any)']                = 'api/admin/display_templates/renderers_by_type/$1';
$route['api/admin/display_templates/renderers']                       = 'api/admin/display_templates/renderers';
$route['api/admin/display_templates/(:any)']                          = 'api/admin/display_templates/item/$1';

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
$route['api/admin/codelists/batch_delete']              = 'api/admin/codelists/batch_delete';
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
$route['api/admin/timeseries/data/import']                         = 'api/admin/timeseries/data_import';
$route['api/admin/timeseries/data/(:any)/import']                = 'api/admin/timeseries/data_import/$1';
$route['api/admin/timeseries/data/(:any)/import-csv']             = 'api/admin/timeseries/data_import_csv/$1';
$route['api/admin/timeseries/data/(:any)/clear-data']            = 'api/admin/timeseries/data_clear_data/$1';
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
$route['api/timeseries/data/(:any)/export-xml']                 = 'api/timeseries_public/data_export_xml/$1';
$route['api/timeseries/data/(:any)/export']                     = 'api/timeseries_public/data_export/$1';
$route['api/timeseries/data/(:any)']                            = 'api/timeseries_public/data/$1';
$route['api/timeseries/data/(:any)/filter-options']             = 'api/timeseries_public/data_filter_options/$1';
$route['api/timeseries/data/(:any)/chart']                      = 'api/timeseries_public/data_chart/$1';
$route['api/timeseries/data/(:any)/table-export']               = 'api/timeseries_public/data_table_export/$1';
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

$route['admin/clear_cache'] = "admin/admin/clear_cache";
$route['admin/catalog/batch-import'] = 'admin/catalog/batch_import_page';
$route['admin/catalog/batch-refresh'] = 'admin/catalog/batch_refresh_page';
$route['admin/catalog/batch-generate'] = 'admin/catalog/batch_generate_page';

//data deposit
$route['admin/datadeposit/projects/(:num)/(:any)'] = 'admin/datadeposit/projects/$1/$2';
$route['admin/datadeposit/projects/(:num)'] = 'admin/datadeposit/projects/$1';
$route['admin/datadeposit/summary/(:num)'] = 'admin/datadeposit/summary/$1';
$route['admin/datadeposit/tasks/info/(:num)'] = 'admin/datadeposit/task_info/$1';
$route['admin/datadeposit/tasks/update/(:num)/(:num)'] = "admin/datadeposittasks/update/$1/$2";
$route['admin/datadeposit/tasks/delete/(:num)'] = "admin/datadeposittasks/delete/$1";
$route['admin/datadeposit/tasks/my_tasks'] = 'admin/datadeposit/my_tasks';
$route['admin/datadeposit/tasks'] = 'admin/datadeposit/tasks';



//licensed files
$route['admin/licensed_files/files/(:num)/add'] = "admin/licensed_files/add/$1";

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

//Downloads API
$route['api/downloads/(:any)/files'] = "api/downloads/files/$1";
$route['api/downloads/(:any)/info/(:any)'] = "api/downloads/info/$1/$2";
$route['api/downloads/download/(:any)/(:num)'] = "api/downloads/download/$1/$2";

// Public analytics tracking API
$route['api/analytics/pageview'] = "api/analytics/pageview";
$route['api/analytics/download'] = "api/analytics/download";

// Admin analytics API
$route['api/admin/analytics/recent/pageviews'] = "api/admin/analytics/recent_pageviews";
$route['api/admin/analytics/recent/downloads'] = "api/admin/analytics/recent_downloads";
$route['api/admin/analytics/stats/study/(:any)'] = "api/admin/analytics/stats_study_get/$1";
$route['api/admin/analytics/stats/file/(:any)'] = "api/admin/analytics/stats_file_get/$1";
$route['api/admin/analytics/totals/study/(:any)'] = "api/admin/analytics/totals_study_get/$1";
$route['api/admin/analytics/totals/file/(:any)'] = "api/admin/analytics/totals_file_get/$1";
$route['api/admin/analytics/aggregate/daily'] = "api/admin/analytics/aggregate_daily";
$route['api/admin/analytics/aggregate/monthly'] = "api/admin/analytics/aggregate_monthly";
$route['api/admin/analytics/aggregate/totals'] = "api/admin/analytics/aggregate_totals";
$route['api/admin/analytics/aggregate/run'] = "api/admin/analytics/aggregate_run";
$route['api/admin/analytics/aggregate/status'] = "api/admin/analytics/aggregate_status";
$route['api/admin/analytics/aggregate/stop'] = "api/admin/analytics/aggregate_stop";
$route['api/admin/analytics/raw/pageviews'] = "api/admin/analytics/raw_pageviews";
$route['api/admin/analytics/raw/downloads'] = "api/admin/analytics/raw_downloads";
$route['api/admin/analytics/daily/studies/export'] = "api/admin/analytics/daily_studies_export";
$route['api/admin/analytics/daily/files/export'] = "api/admin/analytics/daily_files_export";
$route['api/admin/analytics/monthly/studies/export'] = "api/admin/analytics/monthly_studies_export";
$route['api/admin/analytics/monthly/files/export'] = "api/admin/analytics/monthly_files_export";
$route['api/admin/analytics/daily/studies'] = "api/admin/analytics/daily_studies";
$route['api/admin/analytics/daily/files'] = "api/admin/analytics/daily_files";
$route['api/admin/analytics/monthly/studies'] = "api/admin/analytics/monthly_studies";
$route['api/admin/analytics/monthly/files'] = "api/admin/analytics/monthly_files";
$route['api/admin/analytics/monthly/totals'] = "api/admin/analytics/monthly_totals";
$route['api/admin/analytics/studies'] = "api/admin/analytics/studies";
$route['api/admin/analytics/aggregate/run_all'] = "api/admin/analytics/aggregate_run_all";
$route['api/admin/analytics/legacy/studies'] = "api/admin/analytics/legacy_studies";
$route['api/admin/analytics/legacy/files'] = "api/admin/analytics/legacy_files";

// Admin licensed requests API (hyphen alias → controller)
$route['api/admin/bulk-data-access'] = 'api/admin/bulk_da';
$route['api/admin/bulk-data-access/(.*)'] = 'api/admin/bulk_da/$1';

$route['api/admin/licensed-requests'] = 'api/admin/licensed_requests';
$route['api/admin/licensed-requests/(.*)'] = 'api/admin/licensed_requests/$1';
$route['api/admin/licensed_requests/item/(:num)'] = 'api/admin/licensed_requests/item/$1';
$route['api/admin/licensed_requests/send_mail/(:num)'] = 'api/admin/licensed_requests/send_mail/$1';
$route['api/admin/licensed_requests/forward/(:num)'] = 'api/admin/licensed_requests/forward/$1';


/* End of file routes.php */
/* Location: ./system/application/config/routes.php */

/* Location: ./system/application/config/routes.php */
