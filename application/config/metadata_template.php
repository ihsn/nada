<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Metadata template configurations
|--------------------------------------------------------------------------
|
| This file should have the template configurations for each type of document
| such as survey, geospatial, time series, dublin core, etc.
|
|
| @template - path to the view file - all metadata is passed to the view as an
| array and is upto the template to format
|
| Field-label language files: config/metadata_field_languages.php
| (language_translations is filled from that map).
|
| Catalog display preprocess: config/metadata_field_languages.php
|
*/


//config format to support multiple formats for each type of data
$config['survey']=array(
        'template' => 'metadata_templates/survey-template',
); 

$config['timeseries']=array(
    'template' => 'metadata_templates/timeseries-template',
); 

$config['timeseriesdb']=array(
    'template' => 'metadata_templates/timeseriesdb-template',
); 

$config['timeseries-db']=array(
    'template' => 'metadata_templates/timeseriesdb-template',
);


$config['script']=array(
    'template' => 'metadata_templates/script-template',
); 


//geospatial template/view
$config['geospatial']=array(
        'template' => 'metadata_templates/geospatial-iso19139',
);

//document
$config['document']=array(
    'template' => 'metadata_templates/document-template',
); 

//table
$config['table']=array(
    'template' => 'metadata_templates/table-template',
); 

//image
$config['image']=array(
    'template' => 'metadata_templates/image-template',
); 


//video
$config['video']=array(
    'template' => 'metadata_templates/video-template',
);

include APPPATH . 'config/metadata_field_languages.php';
if (isset($config['metadata_field_languages']) && is_array($config['metadata_field_languages'])) {
	foreach ($config['metadata_field_languages'] as $type => $lang_file) {
		if (isset($config[$type]) && is_array($config[$type]) && !isset($config[$type][0])) {
			$config[$type]['language_translations'] = $lang_file;
		}
	}
}