<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Admin search metadata extract API — documents for external search indexes.
 */

$config['search_metadata_extract_schema_version'] = '1.0';

/** Default page size for GET …/studies batch. */
$config['search_metadata_extract_default_limit'] = 15;

/** Maximum page size for batch study export. */
$config['search_metadata_extract_max_limit'] = 100;

/**
 * Page size for GET …/variables batch and per-study export. Variables are small flat rows (a page of 1000 is about half
 * a megabyte of JSON), unlike the study documents the limit above is sized for, so they get their own, larger cap.
 * Used as the default too: a caller that does not ask for a size gets a full page.
 */
$config['search_metadata_extract_variables_max_limit'] = 1000;

/**
 * Page size for GET …/citations batch. A citation document carries its abstract and notes, so a page is heavier than a
 * page of variables and lighter than a page of study documents. Used as the default too.
 */
$config['search_metadata_extract_citations_max_limit'] = 500;
