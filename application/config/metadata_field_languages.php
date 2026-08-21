<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Metadata field language files
|--------------------------------------------------------------------------
|
| Catalog data type → CodeIgniter language file (no _lang.php suffix).
| Loaded by Display_template for study-description labels (web and PDF).
|
| Files live under application/language/{language}/.
|
*/

$config['metadata_field_languages'] = array(
	'survey'         => 'ddi_fields',
	'timeseries'     => 'fields_timeseries',
	'timeseriesdb'   => 'fields_timeseriesdb',
	'timeseries-db'  => 'fields_timeseriesdb',
	'script'         => 'fields_scripts',
	'geospatial'     => 'iso19139_fields',
	'document'       => 'fields_document',
	'table'          => 'fields_table',
	'image'          => 'fields_image',
	'video'          => 'fields_video',
);

/**
 * Optional preprocess before display-template HTML is rendered.
 *
 * $config['metadata_template_preprocess_metadata'] = array(
 *     'function' => 'preprocess_metadata',
 *     'file'     => 'application/hooks/metadata_template_preprocess_metadata.php',
 * );
 */
