<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Admin search metadata extract API — documents for external search indexes.
 */

$config['search_metadata_extract_schema_version'] = '1.0';

/** Default page size for GET …/studies batch. */
$config['search_metadata_extract_default_limit'] = 15;

/** Maximum page size for batch study export. */
$config['search_metadata_extract_max_limit'] = 100;
