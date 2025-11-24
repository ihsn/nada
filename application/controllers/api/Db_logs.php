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

    public function __construct() {
        parent::__construct();
        
        $this->load->helper('file');
        $this->load->config('db_logs');
        
        $this->chunk_size = $this->config->item('db_logs_chunk_size') ?: 10000;
        $this->csv_dir = $this->config->item('db_logs_csv_dir') ?: FCPATH . 'logs/db_logs/';
        $this->retention_days = $this->config->item('db_logs_retention_days') ?: 180;
                
        $this->is_admin_or_die();
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
                } elseif ($dbdriver == 'postgre' || ($dbdriver == 'pdo' && $this->db->subdriver == 'pgsql')) {
                    // PostgreSQL
                    $query = $this->db->query("
                        SELECT 
                            ROUND(pg_total_relation_size('sitelogs') / 1024.0 / 1024.0, 2) AS size_mb
                    ");
                } else {
                    // For other databases, skip table size
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
                    'total_rows' => (int)$total_rows,
                    'table_size_mb' => $table_size,
                    'oldest_log_date' => $oldest_date,
                    'retention_days' => $this->retention_days,
                    'cutoff_date' => $cutoff_date,
                    'cutoff_timestamp' => $cutoff_timestamp
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
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
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

    private function format_bytes($size, $precision = 2) {
        if ($size == 0) return '0 B';
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    public function _auth_override_check()
    {
        if ($this->session->userdata('user_id')){
            return true;
        }
        parent::_auth_override_check();
    }

}
