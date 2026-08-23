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
| (*-system-en display seeds) when a data type has no site default yet.
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
| Display default UIDs are {data-type}-system-en (survey-system-en, …).
| Editor cores keep their own UIDs (microdata-system-en for the DDI form).
|
| Custom overrides: {same-directory}/custom/{same-filename}
|   e.g. application/templates/display/custom/survey_display_template.json
|
| Optional overlay titles: {same-basename}.translations.json
|   e.g. application/templates/display/survey_display_template.translations.json
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
 * Editor-based cores still resolve overrides from templates/editor/custom/.
 */
$config['display_template_custom_path'] = 'templates/display/custom';

/**
 * Core template registry, keyed by NADA data type.
 * Each entry: uid, name, lang, file (relative to APPPATH).
 */
$config['survey'][] = array(
	'uid'         => 'survey-system-en',
	'name'        => 'Survey display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for microdata.',
	'file'        => 'templates/display/survey_display_template.json',
);

$config['survey'][] = array(
	'uid'         => '6740f5f920502baf3f6cbcaa5c113deeen',
	'name'        => 'IHSN DDI 2.5 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'A DDI 2.5 (DDI Codebook) template recommended by the International Household Survey Network (IHSN) for the documentation of survey and census datasets.',
	'file'        => 'templates/editor/survey_template_ihsn_2.5_v1.json',
);

$config['survey'][] = array(
	'uid'  => '232ea3aaece0cdf1db157f797f6b92e5fr',
	'name' => 'IHSN DDI 2.5 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/survey_template_ihsn_2.5_v1_fr.json',
);

$config['survey'][] = array(
	'uid'  => 'microdata-system-es',
	'name' => 'Microdatos DDI 2.5 ES',
	'lang' => 'es',
	'file' => 'templates/editor/survey_template_es.json',
);

$config['survey'][] = array(
	'uid'  => '6740f5f920502baf3f6cbcaa5c113uzbek',
	'name' => 'DDI 2.5 Template v01 UZ',
	'lang' => 'uz',
	'file' => 'templates/editor/microdata_template_uzbek.json',
);

$config['timeseries'][] = array(
	'uid'         => 'timeseries-system-en',
	'name'        => 'Timeseries display template',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Catalog study description layout for indicators and time series.',
	'file'        => 'templates/display/timeseries_display_template.json',
);

$config['timeseries'][] = array(
	'uid'         => '8603d94e27bccc2bdad1e00dbbf0fe32en',
	'name'        => 'IHSN INDICATOR 1.0 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'A template for the documentation of indicators (or time series).',
	'file'        => 'templates/editor/timeseries_template_ihsn.json',
);

$config['timeseries'][] = array(
	'uid'  => 'b576eb7519fe8e761a239f9d36f032c3fr',
	'name' => 'IHSN INDICATOR 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/timeseries_template_ihsn_fr.json',
);

$config['timeseries-db'][] = array(
	'uid'         => 'timeseries-db-system-en',
	'name'        => 'Timeseries database display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for indicator databases.',
	'file'        => 'templates/display/timeseriesdb_display_template.json',
);

$config['timeseries-db'][] = array(
	'uid'         => 'f3e2d1c8c494be27bc229463a265a33d',
	'name'        => 'IHSN DATABASE 1.0 Template v01 EN',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Database metadata schema for indicator time series containers.',
	'file'        => 'templates/editor/timeseries-db_template_ihsn.json',
);

$config['timeseries-db'][] = array(
	'uid'  => '776d77524fe8130f520035fb9b077d82',
	'name' => 'IHSN BASE DE DONNEES 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/timeseries-db_template_ihsn_fr.json',
);

// Alias used by some NADA code paths
$config['timeseriesdb'][] = array(
	'uid'         => 'timeseries-db-system-en',
	'name'        => 'Timeseries database display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for indicator databases.',
	'file'        => 'templates/display/timeseriesdb_display_template.json',
);

$config['timeseriesdb'][] = array(
	'uid'  => 'f3e2d1c8c494be27bc229463a265a33d',
	'name' => 'IHSN DATABASE 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/timeseries-db_template_ihsn.json',
);

$config['script'][] = array(
	'uid'         => 'script-system-en',
	'name'        => 'Script display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for scripts.',
	'file'        => 'templates/display/script_display_template.json',
);

$config['script'][] = array(
	'uid'  => 'd0e54377885c64c360259b65398e319den',
	'name' => 'IHSN SCRIPT 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/script_template_ihsn.json',
);

$config['script'][] = array(
	'uid'  => 'd31789f2d6dcdf3c7dc07a5729d09ac9fr',
	'name' => 'IHSN SCRIPT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/script_template_ihsn_fr.json',
);

$config['geospatial'][] = array(
	'uid'         => 'geospatial-system-en',
	'name'        => 'Geospatial display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for geospatial datasets (ISO 19139).',
	'file'        => 'templates/display/geospatial_display_template.json',
);

$config['geospatial'][] = array(
	'uid'         => 'geospatial-gemini-inspire-en',
	'name'        => 'Inspire/Gemini with additional elements',
	'lang'        => 'en',
	'version'     => '1.0',
	'description' => 'Geospatial template based on Inspire and Gemini 2.3.',
	'file'        => 'templates/editor/geospatial_form_template_gemini.json',
);

$config['document'][] = array(
	'uid'         => 'document-system-en',
	'name'        => 'Document display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for documents (IHSN document schema).',
	'file'        => 'templates/display/document_display_template.json',
);

$config['document'][] = array(
	'uid'  => 'document-system-es',
	'name' => 'Documento IHSN Esquema 1.0 ES',
	'lang' => 'es',
	'file' => 'templates/editor/document_form_template_es.json',
);

$config['document'][] = array(
	'uid'  => '2f62a6b2716ab55b4426005abdbe1600en',
	'name' => 'IHSN DOCUMENT 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/document_template_ihsn.json',
);

$config['document'][] = array(
	'uid'  => '4915f93564fbde26945dd9022b9d7fc1fr',
	'name' => 'IHSN DOCUMENT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/document_template_ihsn_fr.json',
);

$config['table'][] = array(
	'uid'         => 'table-system-en',
	'name'        => 'Table display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for tables (IHSN table schema).',
	'file'        => 'templates/display/table_display_template.json',
);

$config['table'][] = array(
	'uid'  => '4b56b6c4ec82324c7c2865ad61c4f2c0en',
	'name' => 'IHSN TABLE 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/table_template_ihsn.json',
);

$config['table'][] = array(
	'uid'  => '9454eee369c79c65cdc0b4ee23aed4e8fr',
	'name' => 'IHSN TABLEAU 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/table_template_ihsn_fr.json',
);

$config['image'][] = array(
	'uid'         => 'image-system-en',
	'name'        => 'Image display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for images (combined DCMI and IPTC).',
	'file'        => 'templates/display/image_display_template.json',
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

$config['image'][] = array(
	'uid'  => '0d8e25111cee667a4b4636088cdb33e3',
	'name' => 'IHSN IMAGE DCMI Template 1.0 EN',
	'lang' => 'en',
	'file' => 'templates/editor/image_dcmi_template_ihsn.json',
);

$config['image'][] = array(
	'uid'  => '6192765f2dbddb6bd93f3f92a29129d3fr',
	'name' => 'IHSN IMAGE DCMI Modèle 1.0 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/image_dcmi_template_ihsn_fr.json',
);

$config['image'][] = array(
	'uid'  => '2bfd3fb47e291331a43e949e9e38675een',
	'name' => 'IHSN IMAGE IPTC 2022.1 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/image_iptc_template_ihsn.json',
);

$config['image'][] = array(
	'uid'  => '21769091754bb7c998a1caf0fa75800cfr',
	'name' => 'IHSN IMAGE IPTC 2022.1 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/image_iptc_template_ihsn_fr.json',
);

$config['video'][] = array(
	'uid'         => 'video-system-en',
	'name'        => 'Video display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for videos (IHSN video schema).',
	'file'        => 'templates/display/video_display_template.json',
);

$config['video'][] = array(
	'uid'  => '1f899824931c133f162f584c928d4256',
	'name' => 'IHSN VIDEO 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/video_template_ihsn.json',
);

$config['video'][] = array(
	'uid'  => 'e3e5a1d8bd0338c1b3a5d6bfff8e5517',
	'name' => 'IHSN VIDEO 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/video_template_ihsn_fr.json',
);

$config['resource'][] = array(
	'uid'         => 'resource-system-en',
	'name'        => 'Resource display template',
	'lang'        => 'en',
	'description' => 'Catalog study description layout for external resources.',
	'file'        => 'templates/display/resource_display_template.json',
);

$config['resource'][] = array(
	'uid'  => 'e6670ad469892a3871ee0e5d47cf243den',
	'name' => 'IHSN EXT RESOURCES 1.0 Template v01 EN',
	'lang' => 'en',
	'file' => 'templates/editor/resource_template_ihsn.json',
);

$config['resource'][] = array(
	'uid'  => '453a8bf9f800465f3cb8dc37699cb574',
	'name' => 'IHSN RESSOURCES EXT 1.0 Modèle v01 FR',
	'lang' => 'fr',
	'file' => 'templates/editor/resource_template_ihsn_fr.json',
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
	'survey'        => 'survey-system-en',
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
