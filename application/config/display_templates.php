<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Shipped display template cores (seed only)
|--------------------------------------------------------------------------
|
| This file is not a runtime catalog. After migrate, cores are rows in
| display_templates with source=file and file_path pointing at JSON on disk.
| Display_template_model::sync_shipped_cores() reads this list on upgrade.
|
| Dedicated catalog display cores live under application/templates/display/
| for survey, script, timeseries, timeseries-db, geospatial, document, table,
| image, video, and resource. Catalog fallback uses display_template_defaults
| (display-*-system-en seeds) when a data type has no site default yet.
|
| Additional registry rows are Metadata Editor / IHSN variants under
| application/templates/editor/. They appear in the display template manager;
| they are not the catalog site default.
|
| Editor section_containers with is_custom are omitted when editor files
| are loaded as display cores.
|
| Paths in `file` are relative to APPPATH (application/).
| Registry keys use NADA catalog data types (survey, timeseries, …).
| Display default UIDs are display-{data-type}-system-en.
| Editor form UIDs are edit-* (see editor_templates.php). IHSN extras use display-{legacy-uid}.
|
| Custom overrides: {same-directory}/custom/{same-filename}
|   e.g. application/templates/display/custom/display_survey_template.json
|
| Optional overlay titles: {same-basename}.translations.json
|   e.g. application/templates/display/display_survey_template.translations.json
|   Seeded into display_template_translations on migrate/sync. Custom sidecar
|   in {same-directory}/custom/ wins when present.
|
| legacy_study_templates is still read at runtime by display_template_helper.
|
*/

/**
 * Base directory for dedicated display cores (relative to APPPATH).
 */
$config['display_template_path'] = 'templates/display';

/**
 * Optional custom override directory for display seeds (relative to APPPATH).
 * Display cores whose `file` lives under templates/editor/ still resolve
 * overrides from that file's sibling custom/ folder (display manager only).
 * Catalog metadata-editor overrides use userdata — see editor_templates.php.
 */
$config['display_template_custom_path'] = 'templates/display/custom';

/**
 * Core template registry, keyed by NADA data type.
 * Each entry: uid, name, lang, file (relative to APPPATH).
 */
$config['survey'][] = array(
	'uid'         => 'display-survey-system-en',
	'name'        => 'Survey display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for microdata.',
	'file'        => 'templates/display/display_survey_template.json',
);

$config['survey'][] = array(
	'uid'         => 'display-6740f5f920502baf3f6cbcaa5c113deeen',
	'name'        => 'IHSN DDI 2.5 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'A DDI 2.5 (DDI Codebook) template recommended by the International Household Survey Network (IHSN) for the documentation of survey and census datasets.',
	'file'        => 'templates/editor/edit_survey_template_ihsn_2.5_v1.json',
);

$config['survey'][] = array(
	'uid'  => 'display-232ea3aaece0cdf1db157f797f6b92e5fr',
	'name' => 'IHSN DDI 2.5 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_survey_template_ihsn_2.5_v1_fr.json',
);

$config['survey'][] = array(
	'uid'  => 'display-microdata-system-es',
	'name' => 'Microdatos DDI 2.5 ES',
	'lang' => 'es',
	'file' => 'templates/editor/edit_survey_template_es.json',
);

$config['survey'][] = array(
	'uid'  => 'display-6740f5f920502baf3f6cbcaa5c113uzbek',
	'name' => 'DDI 2.5 Template v01 UZ',
	'lang' => 'uz',
	'file' => 'templates/editor/edit_microdata_template_uzbek.json',
);

$config['timeseries'][] = array(
	'uid'         => 'display-timeseries-system-en',
	'name'        => 'Timeseries display template',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Catalog study description layout for indicators and time series.',
	'file'        => 'templates/display/display_timeseries_template.json',
);

$config['timeseries'][] = array(
	'uid'         => 'display-8603d94e27bccc2bdad1e00dbbf0fe32en',
	'name'        => 'IHSN INDICATOR 1.0 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'A template for the documentation of indicators (or time series).',
	'file'        => 'templates/editor/edit_timeseries_template_ihsn.json',
);

$config['timeseries'][] = array(
	'uid'  => 'display-b576eb7519fe8e761a239f9d36f032c3fr',
	'name' => 'IHSN INDICATOR 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_timeseries_template_ihsn_fr.json',
);

$config['timeseries-db'][] = array(
	'uid'         => 'display-timeseries-db-system-en',
	'name'        => 'Timeseries database display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for indicator databases.',
	'file'        => 'templates/display/display_timeseries-db_template.json',
);

$config['timeseries-db'][] = array(
	'uid'         => 'display-f3e2d1c8c494be27bc229463a265a33d',
	'name'        => 'IHSN DATABASE 1.0 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Database metadata schema for indicator time series containers.',
	'file'        => 'templates/editor/edit_timeseries-db_template_ihsn.json',
);

$config['timeseries-db'][] = array(
	'uid'  => 'display-776d77524fe8130f520035fb9b077d82',
	'name' => 'IHSN BASE DE DONNEES 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_timeseries-db_template_ihsn_fr.json',
);

// Alias used by some NADA code paths
$config['timeseriesdb'][] = array(
	'uid'         => 'display-timeseries-db-system-en',
	'name'        => 'Timeseries database display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for indicator databases.',
	'file'        => 'templates/display/display_timeseries-db_template.json',
);

$config['timeseriesdb'][] = array(
	'uid'  => 'display-f3e2d1c8c494be27bc229463a265a33d',
	'name' => 'IHSN DATABASE 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_timeseries-db_template_ihsn.json',
);

$config['script'][] = array(
	'uid'         => 'display-script-system-en',
	'name'        => 'Script display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for scripts.',
	'file'        => 'templates/display/display_script_template.json',
);

$config['script'][] = array(
	'uid'  => 'display-d0e54377885c64c360259b65398e319den',
	'name' => 'IHSN SCRIPT 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_script_template_ihsn.json',
);

$config['script'][] = array(
	'uid'  => 'display-d31789f2d6dcdf3c7dc07a5729d09ac9fr',
	'name' => 'IHSN SCRIPT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_script_template_ihsn_fr.json',
);

$config['geospatial'][] = array(
	'uid'         => 'display-geospatial-system-en',
	'name'        => 'Geospatial display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for geospatial datasets (ISO 19139).',
	'file'        => 'templates/display/display_geospatial_template.json',
);

$config['geospatial'][] = array(
	'uid'         => 'display-geospatial-gemini-inspire-en',
	'name'        => 'Inspire/Gemini with additional elements',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Geospatial template based on Inspire and Gemini 2.3.',
	'file'        => 'templates/editor/edit_geospatial_form_template_gemini.json',
);

$config['document'][] = array(
	'uid'         => 'display-document-system-en',
	'name'        => 'Document display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for documents (IHSN document schema).',
	'file'        => 'templates/display/display_document_template.json',
);

$config['document'][] = array(
	'uid'  => 'display-document-system-es',
	'name' => 'Documento IHSN Esquema 1.0 ES',
	'lang' => 'es',
	'file' => 'templates/editor/edit_document_form_template_es.json',
);

$config['document'][] = array(
	'uid'  => 'display-2f62a6b2716ab55b4426005abdbe1600en',
	'name' => 'IHSN DOCUMENT 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_document_template_ihsn.json',
);

$config['document'][] = array(
	'uid'  => 'display-4915f93564fbde26945dd9022b9d7fc1fr',
	'name' => 'IHSN DOCUMENT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_document_template_ihsn_fr.json',
);

$config['table'][] = array(
	'uid'         => 'display-table-system-en',
	'name'        => 'Table display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for tables (IHSN table schema).',
	'file'        => 'templates/display/display_table_template.json',
);

$config['table'][] = array(
	'uid'  => 'display-4b56b6c4ec82324c7c2865ad61c4f2c0en',
	'name' => 'IHSN TABLE 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_table_template_ihsn.json',
);

$config['table'][] = array(
	'uid'  => 'display-9454eee369c79c65cdc0b4ee23aed4e8fr',
	'name' => 'IHSN TABLEAU 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_table_template_ihsn_fr.json',
);

$config['image'][] = array(
	'uid'         => 'display-image-system-en',
	'name'        => 'Image display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for images (combined DCMI and IPTC).',
	'file'        => 'templates/display/display_image_template.json',
);

$config['image'][] = array(
	'uid'  => 'display-image-system-dcmi',
	'name' => 'Image IHSN Schema (DCMI option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_dcmi_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'display-image-system-iptc',
	'name' => 'Image IHSN Schema (IPTC option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_iptc_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'display-0d8e25111cee667a4b4636088cdb33e3',
	'name' => 'IHSN IMAGE DCMI Template 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_dcmi_template_ihsn.json',
);

$config['image'][] = array(
	'uid'  => 'display-6192765f2dbddb6bd93f3f92a29129d3fr',
	'name' => 'IHSN IMAGE DCMI Modèle 1.0 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_image_dcmi_template_ihsn_fr.json',
);

$config['image'][] = array(
	'uid'  => 'display-2bfd3fb47e291331a43e949e9e38675een',
	'name' => 'IHSN IMAGE IPTC 2022.1 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_iptc_template_ihsn.json',
);

$config['image'][] = array(
	'uid'  => 'display-21769091754bb7c998a1caf0fa75800cfr',
	'name' => 'IHSN IMAGE IPTC 2022.1 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_image_iptc_template_ihsn_fr.json',
);

$config['video'][] = array(
	'uid'         => 'display-video-system-en',
	'name'        => 'Video display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for videos (IHSN video schema).',
	'file'        => 'templates/display/display_video_template.json',
);

$config['video'][] = array(
	'uid'  => 'display-1f899824931c133f162f584c928d4256',
	'name' => 'IHSN VIDEO 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_video_template_ihsn.json',
);

$config['video'][] = array(
	'uid'  => 'display-e3e5a1d8bd0338c1b3a5d6bfff8e5517',
	'name' => 'IHSN VIDEO 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_video_template_ihsn_fr.json',
);

$config['resource'][] = array(
	'uid'         => 'display-resource-system-en',
	'name'        => 'Resource display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for external resources.',
	'file'        => 'templates/display/display_resource_template.json',
);

$config['resource'][] = array(
	'uid'  => 'display-e6670ad469892a3871ee0e5d47cf243den',
	'name' => 'IHSN EXT RESOURCES 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_resource_template_ihsn.json',
);

$config['resource'][] = array(
	'uid'  => 'display-453a8bf9f800465f3cb8dc37699cb574',
	'name' => 'IHSN RESSOURCES EXT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/edit_resource_template_ihsn_fr.json',
);

/*
|--------------------------------------------------------------------------
| Default core template UID per data type
|--------------------------------------------------------------------------
|
| Each UID must exist in the registry above for that data type.
| Unused-field browsing and catalog fallback use this default.
|
*/
$config['display_template_defaults'] = array(
	'survey'        => 'display-survey-system-en',
	'timeseries'    => 'display-timeseries-system-en',
	'timeseries-db' => 'display-timeseries-db-system-en',
	'timeseriesdb'  => 'display-timeseries-db-system-en',
	'script'        => 'display-script-system-en',
	'geospatial'    => 'display-geospatial-system-en',
	'document'      => 'display-document-system-en',
	'table'         => 'display-table-system-en',
	'image'         => 'display-image-system-en',
	'video'         => 'display-video-system-en',
	'resource'      => 'display-resource-system-en',
);

/*
|--------------------------------------------------------------------------
| Legacy PHP study templates (optional)
|--------------------------------------------------------------------------
|
| Fallback only. Site Configurations → Display templates stores
| legacy_study_templates in the configurations table and wins when that key is present.
|
| Catalog study-description pages use JSON display templates by default.
| List data types here to use the older PHP views under
| application/views/metadata_templates/ instead.
|
| Example:
|   $config['legacy_study_templates'] = array('survey');
|   $config['legacy_study_templates'] = array('survey' => true, 'geospatial' => true);
|
| PDF overview always uses display templates. Variables / compare are unchanged.
|
*/
$config['legacy_study_templates'] = array();

/**
 * Config keys that are not data-type template lists (skipped when building the registry).
 */
$config['display_template_meta_keys'] = array(
	'display_template_path',
	'display_template_custom_path',
	'display_template_defaults',
	'display_template_meta_keys',
	'legacy_study_templates',
);
