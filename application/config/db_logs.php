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
$config['db_logs_retention_days'] = 180;


// Chunk size for processing logs
// Number of rows to process per chunk (memory efficiency)
// Recommended: 5000-20000 depending on server memory
$config['db_logs_chunk_size'] = 10000;

// CSV export directory
// Path where exported CSV files will be stored
$config['db_logs_csv_dir'] = FCPATH . 'logs/db_logs/';
