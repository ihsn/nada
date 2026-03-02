<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Database Logs Configuration
|--------------------------------------------------------------------------
|
| Configuration for database log cleanup and retention policies
|
*/

// Retention period for logs in database (days)
// Applies to both sitelogs and api_logs tables
$config['db_logs_retention_days'] = 180;


// Chunk size for processing logs
// Number of rows to process per chunk (memory efficiency)
// Recommended: 5000-20000 depending on server memory
$config['db_logs_chunk_size'] = 10000;

// CSV export directory
// Path where exported CSV files will be stored.
// IMPORTANT: This directory must be OUTSIDE the web root to prevent public HTTP access to log archives.
// Example safe path (one level above the web root):
//   $config['db_logs_csv_dir'] = dirname(FCPATH) . '/nada_logs/db_logs/';
// The cleanup page will show a security warning and block backups if this path is inside FCPATH.
$config['db_logs_csv_dir'] =  '../nada_files/logs/db_logs/';

// Row count warning threshold
// If either sitelogs or api_logs exceeds this number of rows, a warning is shown on the
// respective admin pages prompting the admin to run cleanup.
$config['db_logs_row_count_warning'] = 50000;

// Row count cache TTL (seconds)
// Estimated row counts are cached to avoid repeated information_schema queries.
// Default: 300 (5 minutes)
$config['db_logs_row_count_cache_ttl'] = 300;
