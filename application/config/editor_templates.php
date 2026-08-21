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
| UIDs match Metadata Editor system cores for interoperability.
|
| Optional site overrides: application/templates/editor/custom/{same-filename}
|
*/

/**
 * Base directory for core editor templates (relative to APPPATH).
 */
$config['editor_template_path'] = 'templates/editor';

/**
 * Optional custom override directory (relative to APPPATH).
 * If a file with the same basename exists here, it wins over the core file.
 */
$config['editor_template_custom_path'] = 'templates/editor/custom';

/**
 * Core template registry, keyed by NADA data type.
 * Each entry: uid, name, lang, file (relative to APPPATH).
 */
$config['survey'][] = array(
	'uid'  => 'microdata-system-en',
	'name' => 'Microdata DDI 2.5 EN',
	'lang' => 'en',
	'file' => 'templates/editor/survey_form_template.json',
);

$config['timeseries'][] = array(
	'uid'         => 'timeseries-system-en',
	'name'        => 'Indicator IHSN Schema 1.0 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Core template for indicators and time series (IHSN schema).',
	'file'        => 'templates/editor/timeseries_form_template.json',
);

$config['timeseries-db'][] = array(
	'uid'  => 'timeseries-db-system-en',
	'name' => 'Database IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/timeseries-db_form_template.json',
);

// Alias used by some NADA code paths
$config['timeseriesdb'][] = array(
	'uid'  => 'timeseries-db-system-en',
	'name' => 'Database IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/timeseries-db_form_template.json',
);

$config['script'][] = array(
	'uid'  => 'script-system-en',
	'name' => 'Script IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/script_form_template.json',
);

$config['geospatial'][] = array(
	'uid'  => 'geospatial-system-en',
	'name' => 'Geospatial schema',
	'lang' => 'en',
	'file' => 'templates/editor/geospatial_form_template.json',
);

$config['document'][] = array(
	'uid'  => 'document-system-en',
	'name' => 'Document IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/document_form_template.json',
);

$config['table'][] = array(
	'uid'  => 'table-system-en',
	'name' => 'Table IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/table_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'image-system-en',
	'name' => 'Image IHSN Schema (DCMI and IPTC)',
	'lang' => 'en',
	'file' => 'templates/editor/image_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'image-system-dcmi',
	'name' => 'Image IHSN Schema (DCMI option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/image_dcmi_form_template.json',
);

$config['image'][] = array(
	'uid'  => 'image-system-iptc',
	'name' => 'Image IHSN Schema (IPTC option) 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/image_iptc_form_template.json',
);

$config['video'][] = array(
	'uid'  => 'video-system-en',
	'name' => 'Video IHSN Schema 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/video_form_template.json',
);

$config['resource'][] = array(
	'uid'  => 'resource-system-en',
	'name' => 'External resource schema',
	'lang' => 'en',
	'file' => 'templates/editor/resource_form_template.json',
);

/*
|--------------------------------------------------------------------------
| Default template UID per context and data type
|--------------------------------------------------------------------------
|
| Contexts:
|   catalog — built-in catalog metadata editor
|   deposit — data deposit study form (same cores until deposit subsets exist)
|
| Each UID must exist in the registry above for that data type.
|
*/
$config['editor_template_defaults'] = array(
	'catalog' => array(
		'survey'        => 'microdata-system-en',
		'timeseries'    => 'timeseries-system-en',
		'timeseries-db' => 'timeseries-db-system-en',
		'timeseriesdb'  => 'timeseries-db-system-en',
		'script'        => 'script-system-en',
		'geospatial'    => 'geospatial-system-en',
		'document'      => 'document-system-en',
		'table'         => 'table-system-en',
		'image'         => 'image-system-en',
		'video'         => 'video-system-en',
		'resource'      => 'resource-system-en',
	),
	'deposit' => array(
		'survey'        => 'microdata-system-en',
		'timeseries'    => 'timeseries-system-en',
		'timeseries-db' => 'timeseries-db-system-en',
		'timeseriesdb'  => 'timeseries-db-system-en',
		'script'        => 'script-system-en',
		'geospatial'    => 'geospatial-system-en',
		'document'      => 'document-system-en',
		'table'         => 'table-system-en',
		'image'         => 'image-system-en',
		'video'         => 'video-system-en',
		'resource'      => 'resource-system-en',
	),
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
