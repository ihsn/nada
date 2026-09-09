<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Metadata editor core templates (ME-compatible form templates)
|--------------------------------------------------------------------------
|
| Core JSON templates live under application/templates/editor/.
| Paths in `file` are relative to APPPATH (application/).
|
| Registry keys use NADA catalog data types (survey, timeseries, …).
| UIDs are edit-* so they never collide with display-* or deposit-* UIDs.
|
| Data-deposit forms are a separate registry: config/deposit_templates.php.
|
| Site overrides (same basename wins over the shipped file):
|   {userdata_path}/templates/editor/{filename}
|
| To use a different override directory, set editor_template_custom_path.
| Empty = {userdata_path}/templates/editor. Switching a type's form is done
| by changing editor_template_defaults below (no admin UI).
|
*/

/**
 * Base directory for core editor templates (relative to APPPATH).
 */
$config['editor_template_path'] = 'templates/editor';

/**
 * Optional override directory (relative or absolute).
 * Empty: {userdata_path}/templates/editor.
 * Same basename as the registered `file` wins over the shipped core.
 */
$config['editor_template_custom_path'] = '';

/**
 * Core template registry, keyed by NADA data type.
 * Each entry: uid, name, lang, file (relative to APPPATH).
 */
$config['survey'][] = array(
	'uid'  => 'edit-microdata-system-en',
	'name' => 'Microdata DDI 2.5 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_survey_form_template.json',
);

$config['timeseries'][] = array(
	'uid'         => 'edit-timeseries-system-en',
	'name'        => 'Indicator IHSN Schema 1.0 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Core template for indicators and time series (IHSN schema).',
	'file'        => 'templates/editor/edit_timeseries_form_template.json',
);

$config['timeseries-db'][] = array(
	'uid'  => 'edit-timeseries-db-system-en',
	'name' => 'Database IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_timeseries-db_form_template.json',
);

// Alias used by some NADA code paths
$config['timeseriesdb'][] = array(
	'uid'  => 'edit-timeseries-db-system-en',
	'name' => 'Database IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_timeseries-db_form_template.json',
);

$config['script'][] = array(
	'uid'  => 'edit-script-system-en',
	'name' => 'Script IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_script_form_template.json',
);

$config['geospatial'][] = array(
	'uid'  => 'edit-geospatial-system-en',
	'name' => 'Geospatial schema',
	'lang' => 'en',
	'file' => 'templates/editor/edit_geospatial_form_template.json',
);

$config['document'][] = array(
	'uid'  => 'edit-document-system-en',
	'name' => 'Document IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_document_form_template.json',
);

$config['table'][] = array(
	'uid'  => 'edit-table-system-en',
	'name' => 'Table IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_table_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'edit-image-system-en',
	'name' => 'Image IHSN Schema (DCMI and IPTC)',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'edit-image-system-dcmi',
	'name' => 'Image IHSN Schema (DCMI option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_dcmi_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'edit-image-system-iptc',
	'name' => 'Image IHSN Schema (IPTC option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_image_iptc_form_template.json',
);

$config['video'][] = array(
	'uid'  => 'edit-video-system-en',
	'name' => 'Video IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/edit_video_form_template.json',
);

$config['resource'][] = array(
	'uid'  => 'edit-resource-system-en',
	'name' => 'External resource schema',
	'lang' => 'en',
	'file' => 'templates/editor/edit_resource_form_template.json',
);

/*
|--------------------------------------------------------------------------
| Default template UID per catalog data type
|--------------------------------------------------------------------------
|
| Each UID must exist in the registry above for that data type.
| Deposit defaults live in config/deposit_templates.php.
|
*/
$config['editor_template_defaults'] = array(
	'survey'        => 'edit-microdata-system-en',
	'timeseries'    => 'edit-timeseries-system-en',
	'timeseries-db' => 'edit-timeseries-db-system-en',
	'timeseriesdb'  => 'edit-timeseries-db-system-en',
	'script'        => 'edit-script-system-en',
	'geospatial'    => 'edit-geospatial-system-en',
	'document'      => 'edit-document-system-en',
	'table'         => 'edit-table-system-en',
	'image'         => 'edit-image-system-en',
	'video'         => 'edit-video-system-en',
	'resource'      => 'edit-resource-system-en',
);

/**
 * Config keys that are not data-type template lists (skipped when building the registry).
 */
$config['editor_template_meta_keys'] = array(
	'editor_template_path',
	'editor_template_custom_path',
	'editor_template_defaults',
	'editor_template_meta_keys',
);
