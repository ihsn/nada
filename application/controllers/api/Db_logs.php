<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Database Logs API Controller
 * 
 * export database logs to CSV and cleaning up old logs
 * 
 * 
 */
class Db_logs extends MY_REST_Controller {

    private $chunk_size;
    private $csv_dir;
    private $retention_days;
    private $csv_dir_in_webroot;

    public function __construct() {
        parent::__construct();
        
        $this->load->helper('file');
        $this->load->config('db_logs');
        $this->load->model('Sitelog_model');
        
        $this->chunk_size = $this->config->item('db_logs_chunk_size') ?: 10000;
        $this->csv_dir = $this->config->item('db_logs_csv_dir') ?: FCPATH . 'logs/db_logs/';
        $this->retention_days = $this->config->item('db_logs_retention_days') ?: 180;

        // Detect whether the archive directory is inside the web root
        $webroot  = rtrim(str_replace('\\', '/', realpath(FCPATH) ?: FCPATH), '/') . '/';
        $csv_real = rtrim(str_replace('\\', '/', realpath($this->csv_dir) ?: $this->csv_dir), '/') . '/';
        $this->csv_dir_in_webroot = (strpos($csv_real, $webroot) === 0);
                
        $this->is_admin_or_die();
    }

    /**
     * Returns true if csv_dir is safely outside the web root.
     * Throws an exception (used by write endpoints) if it is inside.
     */
    private function check_csv_dir_safe() {
        if ($this->csv_dir_in_webroot) {
            throw new Exception(
                'The log archive directory is located inside the web root and is publicly accessible. ' .
                'Please configure db_logs_csv_dir in application/config/db_logs.php to a path outside the web root before running cleanup.'
            );
        }
    }

    /**
     * Returns estimated row counts for sitelogs and api_logs tables.
     *
     * Uses information_schema (MySQL/MariaDB) or sys.partitions (SQL Server) for
     * a non-locking, near-instant estimate. Results are cached in the filesystem
     * to avoid repeated metadata queries on busy servers.
     *
     * Response:
     *  {
     *    "status": "success",
     *    "data": {
     *      "sitelogs": 320000,
     *      "api_logs": 0,
     *      "warning_threshold": 50000,
     *      "sitelogs_exceeds_threshold": true,
     *      "api_logs_exceeds_threshold": false,
     *      "cached": true,
     *      "cache_age_seconds": 42
     *    }
     *  }
     */
    public function row_counts_get()
    {
        try {
            $warning_threshold = $this->config->item('db_logs_row_count_warning') ?: 50000;
            $cache_ttl         = $this->config->item('db_logs_row_count_cache_ttl') ?: 300;
            $cache_file        = FCPATH . 'cache/db_logs_row_counts.json';

            // Serve from cache if fresh
            if (file_exists($cache_file)) {
                $cache_age = time() - filemtime($cache_file);
                if ($cache_age < $cache_ttl) {
                    $cached = json_decode(file_get_contents($cache_file), true);
                    if ($cached) {
                        $cached['cached']            = true;
                        $cached['cache_age_seconds'] = $cache_age;
                        $this->set_response(['status' => 'success', 'data' => $cached], REST_Controller::HTTP_OK);
                        return;
                    }
                }
            }

            $counts = ['sitelogs' => 0, 'api_logs' => 0];
            $dbdriver = $this->db->dbdriver;
            $is_mysql = in_array($dbdriver, ['mysqli', 'mysql']) ||
                        ($dbdriver === 'pdo' && isset($this->db->subdriver) && $this->db->subdriver === 'mysql');
            $is_sqlsrv = in_array($dbdriver, ['sqlsrv', 'mssql']) ||
                         ($dbdriver === 'pdo' && isset($this->db->subdriver) && $this->db->subdriver === 'sqlsrv');

            if ($is_mysql) {
                // information_schema.TABLE_ROWS: metadata-only read, no table scan, no locks
                $query = $this->db->query("
                    SELECT table_name, table_rows
                    FROM information_schema.TABLES
                    WHERE table_schema = DATABASE()
                    AND table_name IN ('sitelogs', 'api_logs')
                ");
                foreach ($query->result_array() as $row) {
                    $counts[$row['table_name']] = (int)$row['table_rows'];
                }
            } elseif ($is_sqlsrv) {
                // sys.partitions: index_id 0 = heap, 1 = clustered index — covers all rows, no scan
                $query = $this->db->query("
                    SELECT OBJECT_NAME(i.object_id) AS table_name, SUM(p.rows) AS table_rows
                    FROM sys.indexes i
                    INNER JOIN sys.partitions p
                        ON i.object_id = p.object_id AND i.index_id = p.index_id
                    WHERE i.index_id IN (0, 1)
                    AND OBJECT_NAME(i.object_id) IN ('sitelogs', 'api_logs')
                    GROUP BY i.object_id
                ");
                foreach ($query->result_array() as $row) {
                    $counts[$row['table_name']] = (int)$row['table_rows'];
                }
            } else {
                throw new Exception('Unsupported database driver: ' . $dbdriver . '. Only MySQL/MariaDB and SQL Server are supported.');
            }

            $data = [
                'sitelogs'                   => $counts['sitelogs'],
                'api_logs'                   => $counts['api_logs'],
                'warning_threshold'          => (int)$warning_threshold,
                'sitelogs_exceeds_threshold' => $counts['sitelogs'] >= $warning_threshold,
                'api_logs_exceeds_threshold' => $counts['api_logs'] >= $warning_threshold,
                'cached'                     => false,
                'cache_age_seconds'          => 0
            ];

            // Write cache (exclude live cached/age fields from stored data)
            $store = $data;
            unset($store['cached'], $store['cache_age_seconds']);
            @file_put_contents($cache_file, json_encode($store));

            $this->set_response(['status' => 'success', 'data' => $data], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->set_response(['status' => 'failed', 'message' => $e->getMessage()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * Search, browse, filter, and paginate logs
     * 
     * Query parameters:
     *  - limit: Records per page (default: 50, max: 200)
     *  - offset: Pagination offset (default: 0)
     *  - keywords: Search term
     *  - field: Field to search in (logtype, section, keyword, username, ip, url)
     *  - sort_by: Column to sort by (default: logtime)
     *  - sort_order: Sort direction (asc/desc, default: desc)
     * 
     * Returns paginated log records with metadata
     */
    public function index_get() {
        try {                        
            $limit = (int)$this->input->get('limit');
            $offset = (int)$this->input->get('offset');
            $keywords = $this->input->get('keywords');
            $field = $this->input->get('field');
            $sort_by = $this->input->get('sort_by') ?: 'logtime';
            $sort_order = $this->input->get('sort_order') ?: 'desc';
            
            if ($limit <= 0 || $limit > 200) {
                $limit = 50;
            }
            
            if ($offset < 0) {
                $offset = 0;
            }
            
            if ($sort_order !== 'asc' && $sort_order !== 'desc') {
                $sort_order = 'desc';
            }
            
            $filter = array();
            if ($keywords && $field) {
                $filter[] = array(
                    'field' => $field,
                    'keywords' => $keywords
                );
            }
            
            $total_rows = $this->Sitelog_model->search_count($filter);
            
            if ($offset > $total_rows) {
                $offset = max(0, $total_rows - $limit);
            }
            
            $rows = $this->Sitelog_model->search($limit, $offset, $filter, $sort_by, $sort_order);
            
            $current_page = floor($offset / $limit) + 1;
            $total_pages = ceil($total_rows / $limit);
            
            $formatted_rows = array();
            foreach ($rows as $row) {
                $formatted_rows[] = array(
                    'id' => (int)$row['id'],
                    'sessionid' => $row['sessionid'],
                    'logtime' => (int)$row['logtime'],
                    'logtime_formatted' => date('Y-m-d H:i:s', $row['logtime']),
                    'ip' => $row['ip'],
                    'url' => $row['url'],
                    'logtype' => $row['logtype'],
                    'surveyid' => (int)$row['surveyid'],
                    'section' => $row['section'],
                    'keyword' => $row['keyword'],
                    'username' => $row['username'],
                    'useragent' => $row['useragent']
                );
            }
            
            $response = array(
                'status' => 'success',
                'data' => $formatted_rows,
                'pagination' => array(
                    'total_rows' => (int)$total_rows,
                    'per_page' => $limit,
                    'current_page' => $current_page,
                    'total_pages' => $total_pages,
                    'offset' => $offset,
                    'has_next' => $offset + $limit < $total_rows,
                    'has_prev' => $offset > 0
                )
            );
            
            $this->set_response($response, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * 
     * Get statistics about logs
     * 
     *  - total rows
     *  - oldest log date
     *  - retention days
     *  - cutoff date
     *  - cutoff timestamp
     *  - table size
     * 
     */
    public function stats_get() {
        try {
            $total_rows = $this->db->count_all('sitelogs');
            
            $oldest_log = $this->db->select('logtime')
                                   ->order_by('logtime', 'ASC')
                                   ->limit(1)
                                   ->get('sitelogs')
                                   ->row_array();
            
            $oldest_date = $oldest_log ? date('Y-m-d H:i:s', $oldest_log['logtime']) : null;
            
            $cutoff_timestamp = time() - ($this->retention_days * 24 * 60 * 60);
            $cutoff_date = date('Y-m-d H:i:s', $cutoff_timestamp);
            
            $table_size = null;
            try {
                $dbdriver = $this->db->dbdriver;
                
                if ($dbdriver == 'mysqli' || $dbdriver == 'mysql' || $dbdriver == 'pdo' && $this->db->subdriver == 'mysql') {
                    // MySQL/MariaDB
                    $query = $this->db->query("
                        SELECT 
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                        FROM information_schema.TABLES 
                        WHERE table_schema = DATABASE()
                        AND table_name = 'sitelogs'
                    ");
                } elseif ($dbdriver == 'sqlsrv' || ($dbdriver == 'pdo' && $this->db->subdriver == 'sqlsrv')) {
                    // SQL Server
                    $db_name = $this->db->database;
                    $query = $this->db->query("
                        SELECT 
                            ROUND(SUM(a.total_pages) * 8 / 1024.0, 2) AS size_mb
                        FROM sys.tables t
                        INNER JOIN sys.indexes i ON t.OBJECT_ID = i.object_id
                        INNER JOIN sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id
                        INNER JOIN sys.allocation_units a ON p.partition_id = a.container_id
                        WHERE t.name = 'sitelogs'
                        AND t.schema_id = SCHEMA_ID('dbo')
                        GROUP BY t.name
                    ");
                } else {
                    $query = false;
                }
                
                if ($query && $query->num_rows() > 0) {
                    $result = $query->row_array();
                    $table_size = (float)$result['size_mb'];
                }
            } catch (Exception $e) {
                // Table size not available, skip it
            }
            
            $response = [
                'status' => 'success',
                'data' => [
                    'total_rows'           => (int)$total_rows,
                    'table_size_mb'        => $table_size,
                    'oldest_log_date'      => $oldest_date,
                    'retention_days'       => $this->retention_days,
                    'cutoff_date'          => $cutoff_date,
                    'cutoff_timestamp'     => $cutoff_timestamp,
                    'directory_in_webroot' => $this->csv_dir_in_webroot,
                    'csv_dir'              => $this->csv_dir,
                    'csv_dir_exists'       => is_dir($this->csv_dir),
                    'csv_dir_writable'     => is_dir($this->csv_dir) && is_writable($this->csv_dir)
                ]
            ];
            
            $this->set_response($response, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $error_output = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * Process multiple chunks within a time window - stateless endpoint
     * 
     * 
     * Body: { "chunk_size": 10000, "time_limit": 20 } (optional, defaults to config value and 20 seconds)
     * 
     * Processes as many chunks as possible within the time limit (default 20 seconds)
     * Reads oldest rows, exports to CSV, deletes from DB
     * Returns total rows processed, rows deleted, files created
     */
    public function cleanup_chunk_post() {
        try {
            $this->check_csv_dir_safe();

            $input = json_decode($this->input->raw_input_stream, true);
            $chunk_size = isset($input['chunk_size']) ? (int)$input['chunk_size'] : $this->chunk_size;
            $time_limit = isset($input['time_limit']) ? (int)$input['time_limit'] : 20;
            
            if ($chunk_size <= 0 || $chunk_size > 20000) {
                $chunk_size = 10000;
            }
            
            if ($time_limit <= 0 || $time_limit > 30) {
                $time_limit = 10;
            }
            
            if (!is_dir($this->csv_dir)) {
                mkdir($this->csv_dir, 0755, true);
            }

            if (!is_writable($this->csv_dir)) {
                throw new Exception('CSV directory is not writable');
            }

            $cutoff_timestamp = time() - ($this->retention_days * 24 * 60 * 60);
            
            $start_time = microtime(true);
            $total_rows_processed = 0;
            $total_rows_deleted = 0;
            $all_files_created = array();
            $chunks_processed = 0;
            
            // Process chunks until time limit is reached or no more rows
            while (true) {
                $elapsed = microtime(true) - $start_time;
                
                // Check if we've exceeded the time limit
                if ($elapsed >= $time_limit) {
                    break;
                }
                
                // Process a single chunk
                $result = $this->process_chunk($cutoff_timestamp, $chunk_size);
                
                // No more rows to process, exit loop
                if ($result['rows_processed'] == 0) {
                    break;
                }
                
                // Accumulate results
                $total_rows_processed += $result['rows_processed'];
                $total_rows_deleted += $result['rows_deleted'];
                $all_files_created = array_merge($all_files_created, $result['files_created']);
                $chunks_processed++;
                
                $elapsed = microtime(true) - $start_time;
                if ($elapsed >= $time_limit) {
                    break;
                }
            }
            
            $response = [
                'rows_processed' => $total_rows_processed,
                'rows_deleted' => $total_rows_deleted,
                'files_created' => array_values(array_unique($all_files_created)),
                'chunks_processed' => $chunks_processed,
                'time_elapsed' => round(microtime(true) - $start_time, 2)
            ];
            
            if ($total_rows_processed == 0) {
                $response['message'] = 'Cleanup completed - no more rows to process';
            }
            
            $this->set_response($response, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $error_output = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * 
     * List exported CSV files
     * 
     */
    public function files_get() {
        try {
            $files = [];
            
            if (is_dir($this->csv_dir)) {
                $dir_files = scandir($this->csv_dir);
                
                foreach ($dir_files as $file) {
                if (strpos($file, 'db-log-') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                        $filepath = $this->csv_dir . $file;
                        $files[] = [
                            'filename' => $file,
                            'size' => filesize($filepath),
                            'size_formatted' => $this->format_bytes(filesize($filepath)),
                            'date' => date('Y-m-d H:i:s', filemtime($filepath))
                        ];
                    }
                }
            }
            
            usort($files, function($a, $b) {
                return strcmp($b['filename'], $a['filename']);
            });
            
            $response = [
                'status' => 'success',
                'data' => $files
            ];
            
            $this->set_response($response, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $error_output = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * 
     * Process a single chunk
     * 
     */
    private function process_chunk($cutoff_timestamp, $chunk_size = null) 
    {
        if ($chunk_size === null) {
            $chunk_size = $this->chunk_size;
        }
        
        try {
            $this->db->trans_start();
            
            $query = $this->db->select('id, sessionid, logtime, ip, url, logtype, surveyid, section, keyword, username, useragent')
                              ->where('logtime <', $cutoff_timestamp)
                              ->order_by('id', 'ASC')
                              ->limit($chunk_size)
                              ->get('sitelogs');
            
            $rows = $query->result_array();
            
            if (empty($rows)) {
                $this->db->trans_complete();
                return [
                    'rows_processed' => 0,
                    'rows_deleted' => 0,
                    'files_created' => array()
                ];
            }
            
            $ids = array_column($rows, 'id');
            
            $files_opened = array();
            $rows_by_month = array();
            $files_created = array();
            
            foreach ($rows as $row) {
                $month = date('Y-m', $row['logtime']);
                if (!isset($rows_by_month[$month])) {
                    $rows_by_month[$month] = array();
                }
                $rows_by_month[$month][] = $row;
            }
            
            foreach ($rows_by_month as $month => $month_rows) {
                $filename = 'db-log-' . $month . '.csv';
                $filepath = $this->csv_dir . $filename;
                
                if (!isset($files_opened[$month])) {
                    $file_exists = file_exists($filepath);
                    $file = fopen($filepath, 'a');
                    
                    if ($file === false) {
                        throw new Exception('Failed to open CSV file for writing: ' . $filename);
                    }
                    
                    $files_opened[$month] = $file;
                    
                    if (!$file_exists) {
                        $headers = ['id', 'sessionid', 'logtime', 'logtime_utc', 'ip', 'url', 'logtype', 'surveyid', 'section', 'keyword', 'username', 'useragent'];
                        fputcsv($file, $headers);
                    }
                    
                    $files_created[] = $filename;
                }
                
                $file = $files_opened[$month];
                
                foreach ($month_rows as $row) {
                    $csv_row = [
                        $row['id'],
                        $row['sessionid'],
                        $row['logtime'],
                        date('Y-m-d H:i:s', $row['logtime']),
                        $row['ip'],
                        $row['url'],
                        $row['logtype'],
                        $row['surveyid'],
                        $row['section'],
                        $row['keyword'],
                        $row['username'],
                        $row['useragent']
                    ];
                    fputcsv($file, $csv_row);
                }
            }
            
            foreach ($files_opened as $file) {
                fclose($file);
            }
            
            $this->db->where_in('id', $ids)->delete('sitelogs');
            $deleted_count = $this->db->affected_rows();
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }
            
            return [
                'rows_processed' => count($rows),
                'rows_deleted' => $deleted_count,
                'files_created' => array_values(array_unique($files_created))
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            if (isset($files_opened)) {
                foreach ($files_opened as $file) {
                    if (is_resource($file)) {
                        fclose($file);
                    }
                }
            }
            throw $e;
        }
    }

    /**
     * Get statistics about the api_logs table
     */
    public function api_logs_stats_get() {
        try {
            $total_rows = $this->db->count_all('api_logs');

            $oldest_log = $this->db->select('time')
                                   ->order_by('time', 'ASC')
                                   ->limit(1)
                                   ->get('api_logs')
                                   ->row_array();

            $oldest_date = $oldest_log ? date('Y-m-d H:i:s', $oldest_log['time']) : null;

            $cutoff_timestamp = time() - ($this->retention_days * 24 * 60 * 60);
            $cutoff_date = date('Y-m-d H:i:s', $cutoff_timestamp);

            $table_size = null;
            try {
                $dbdriver = $this->db->dbdriver;
                if ($dbdriver == 'mysqli' || $dbdriver == 'mysql' || ($dbdriver == 'pdo' && $this->db->subdriver == 'mysql')) {
                    $query = $this->db->query("
                        SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                        FROM information_schema.TABLES
                        WHERE table_schema = DATABASE()
                        AND table_name = 'api_logs'
                    ");
                } elseif ($dbdriver == 'sqlsrv' || ($dbdriver == 'pdo' && $this->db->subdriver == 'sqlsrv')) {
                    $query = $this->db->query("
                        SELECT ROUND(SUM(a.total_pages) * 8 / 1024.0, 2) AS size_mb
                        FROM sys.tables t
                        INNER JOIN sys.indexes i ON t.OBJECT_ID = i.object_id
                        INNER JOIN sys.partitions p ON i.object_id = p.OBJECT_ID AND i.index_id = p.index_id
                        INNER JOIN sys.allocation_units a ON p.partition_id = a.container_id
                        WHERE t.name = 'api_logs' AND t.schema_id = SCHEMA_ID('dbo')
                        GROUP BY t.name
                    ");
                } else {
                    $query = false;
                }
                if ($query && $query->num_rows() > 0) {
                    $result = $query->row_array();
                    $table_size = (float)$result['size_mb'];
                }
            } catch (Exception $e) {
                // skip
            }

            $this->set_response([
                'status' => 'success',
                'data'   => [
                    'total_rows'           => (int)$total_rows,
                    'table_size_mb'        => $table_size,
                    'oldest_log_date'      => $oldest_date,
                    'retention_days'       => $this->retention_days,
                    'cutoff_date'          => $cutoff_date,
                    'cutoff_timestamp'     => $cutoff_timestamp,
                    'directory_in_webroot' => $this->csv_dir_in_webroot,
                    'csv_dir'              => $this->csv_dir,
                    'csv_dir_exists'       => is_dir($this->csv_dir),
                    'csv_dir_writable'     => is_dir($this->csv_dir) && is_writable($this->csv_dir)
                ]
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->set_response(['status' => 'failed', 'message' => $e->getMessage()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * Export old api_logs rows to CSV (named api_log-YYYY-MM.csv) and delete them.
     * Processes as many chunks as possible within the time limit.
     */
    public function api_logs_cleanup_chunk_post() {
        try {
            $this->check_csv_dir_safe();

            $input = json_decode($this->input->raw_input_stream, true);
            $chunk_size = isset($input['chunk_size']) ? (int)$input['chunk_size'] : $this->chunk_size;
            $time_limit = isset($input['time_limit']) ? (int)$input['time_limit'] : 20;

            if ($chunk_size <= 0 || $chunk_size > 20000) { $chunk_size = 10000; }
            if ($time_limit <= 0 || $time_limit > 30)    { $time_limit = 10; }

            if (!is_dir($this->csv_dir)) {
                mkdir($this->csv_dir, 0755, true);
            }
            if (!is_writable($this->csv_dir)) {
                throw new Exception('CSV directory is not writable');
            }

            $cutoff_timestamp    = time() - ($this->retention_days * 24 * 60 * 60);
            $start_time          = microtime(true);
            $total_rows_processed = 0;
            $total_rows_deleted   = 0;
            $all_files_created    = array();
            $chunks_processed     = 0;

            while (true) {
                if (microtime(true) - $start_time >= $time_limit) { break; }

                $result = $this->process_api_logs_chunk($cutoff_timestamp, $chunk_size);

                if ($result['rows_processed'] == 0) { break; }

                $total_rows_processed += $result['rows_processed'];
                $total_rows_deleted   += $result['rows_deleted'];
                $all_files_created     = array_merge($all_files_created, $result['files_created']);
                $chunks_processed++;

                if (microtime(true) - $start_time >= $time_limit) { break; }
            }

            $response = [
                'rows_processed'   => $total_rows_processed,
                'rows_deleted'     => $total_rows_deleted,
                'files_created'    => array_values(array_unique($all_files_created)),
                'chunks_processed' => $chunks_processed,
                'time_elapsed'     => round(microtime(true) - $start_time, 2)
            ];

            if ($total_rows_processed == 0) {
                $response['message'] = 'Cleanup completed - no more rows to process';
            }

            $this->set_response($response, REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->set_response(['status' => 'failed', 'message' => $e->getMessage()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * List exported api_logs CSV files (api_log-YYYY-MM.csv)
     */
    public function api_logs_files_get() {
        try {
            $files = [];

            if (is_dir($this->csv_dir)) {
                foreach (scandir($this->csv_dir) as $file) {
                    if (strpos($file, 'api_log-') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                        $filepath = $this->csv_dir . $file;
                        $files[] = [
                            'filename'       => $file,
                            'size'           => filesize($filepath),
                            'size_formatted' => $this->format_bytes(filesize($filepath)),
                            'date'           => date('Y-m-d H:i:s', filemtime($filepath))
                        ];
                    }
                }
            }

            usort($files, function($a, $b) { return strcmp($b['filename'], $a['filename']); });

            $this->set_response(['status' => 'success', 'data' => $files], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->set_response(['status' => 'failed', 'message' => $e->getMessage()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    /**
     * Process a single chunk of api_logs:
     * reads rows older than cutoff, appends to api_log-YYYY-MM.csv, deletes from DB.
     */
    private function process_api_logs_chunk($cutoff_timestamp, $chunk_size = null)
    {
        if ($chunk_size === null) { $chunk_size = $this->chunk_size; }

        try {
            $this->db->trans_start();

            $rows = $this->db
                ->select('id, uri, method, params, user_id, api_key, ip_address, time, rtime, authorized, response_code')
                ->where('time <', $cutoff_timestamp)
                ->order_by('id', 'ASC')
                ->limit($chunk_size)
                ->get('api_logs')
                ->result_array();

            if (empty($rows)) {
                $this->db->trans_complete();
                return ['rows_processed' => 0, 'rows_deleted' => 0, 'files_created' => array()];
            }

            $ids           = array_column($rows, 'id');
            $files_opened  = array();
            $files_created = array();
            $rows_by_month = array();

            foreach ($rows as $row) {
                $rows_by_month[date('Y-m', $row['time'])][] = $row;
            }

            foreach ($rows_by_month as $month => $month_rows) {
                $filename = 'api_log-' . $month . '.csv';
                $filepath = $this->csv_dir . $filename;

                if (!isset($files_opened[$month])) {
                    $file_exists         = file_exists($filepath);
                    $file                = fopen($filepath, 'a');
                    if ($file === false) {
                        throw new Exception('Failed to open CSV file for writing: ' . $filename);
                    }
                    $files_opened[$month] = $file;
                    if (!$file_exists) {
                        fputcsv($file, ['id', 'uri', 'method', 'params', 'user_id', 'api_key',
                                        'ip_address', 'time', 'time_utc', 'rtime', 'authorized', 'response_code']);
                    }
                    $files_created[] = $filename;
                }

                $file = $files_opened[$month];
                foreach ($month_rows as $row) {
                    fputcsv($file, [
                        $row['id'],
                        $row['uri'],
                        $row['method'],
                        $row['params'],
                        $row['user_id'],
                        $row['api_key'],
                        $row['ip_address'],
                        $row['time'],
                        date('Y-m-d H:i:s', $row['time']),
                        $row['rtime'],
                        $row['authorized'],
                        $row['response_code']
                    ]);
                }
            }

            foreach ($files_opened as $file) { fclose($file); }

            $this->db->where_in('id', $ids)->delete('api_logs');
            $deleted_count = $this->db->affected_rows();

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }

            return [
                'rows_processed' => count($rows),
                'rows_deleted'   => $deleted_count,
                'files_created'  => array_values(array_unique($files_created))
            ];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            if (isset($files_opened)) {
                foreach ($files_opened as $file) {
                    if (is_resource($file)) { fclose($file); }
                }
            }
            throw $e;
        }
    }


    private function format_bytes($size, $precision = 2) {
        if ($size == 0) return '0 B';
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

}
