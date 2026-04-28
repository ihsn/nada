<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Indicator / SDMX-style timeseries observations (MongoDB)
|--------------------------------------------------------------------------
|
| Collections: {collection_prefix}{data_structure_id}, e.g. indicator_ts_42
|
*/
$config['indicator_timeseries_collection_prefix'] = 'indicator_ts_';
/** Rows buffered before Mongo flush during CSV import (and rehash); caps memory per batch. */
$config['indicator_timeseries_bulk_batch_size']     = 500;
/** Max documents per insertMany; larger API/CSV batches are split automatically. */
$config['indicator_timeseries_max_bulk_insert']     = 5000;
$config['indicator_timeseries_key_hash_algorithm']  = 'sha256';
/** Prefix stored before hex digest, e.g. "sha256:" — empty string for raw hex */
$config['indicator_timeseries_key_hash_prefix']     = 'sha256:';

/** Default / max page size for GET data */
$config['indicator_timeseries_default_page_size'] = 100;
$config['indicator_timeseries_max_page_size']       = 500;

/** Max Mongo observation rows scanned for GET …/chart (server-side chart aggregation). */
$config['indicator_timeseries_chart_max_raw_rows'] = 50000;

/*
|--------------------------------------------------------------------------
| CSV import → study resource (catalogue downloads / microdata)
|--------------------------------------------------------------------------
|
| After each successful CSV import, the file is copied into the study folder
| as {resource_idno}.csv and a resources row is created or updated.
| dctype code must map to resource_type dat or dat/micro to appear under
| GET /api/downloads/{idno}/files?type=data
|
*/
$config['timeseries_csv_resource_idno']   = 'ts_csv_latest';
/** Bare dctype code: "dat/micro" (microdata) or "dat" (database). */
$config['timeseries_csv_resource_dctype'] = 'dat/micro';
