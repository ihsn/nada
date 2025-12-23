<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Legacy Sitelogs Export Controller
 * 
 * Exports data from sitelogs_legacy table to CSV and deletes chunks progressively.
 * Supports both CLI and Web access.
 * 
 * CLI Usage:
 *   php index.php utils/sitelogs_export start
 *   php index.php utils/sitelogs_export process_all
 *   php index.php utils/sitelogs_export status
 * 
 * Web Usage:
 *   /utils/sitelogs_export
 *   /utils/sitelogs_export/start
 *   /utils/sitelogs_export/process_all
 *   /utils/sitelogs_export/status
 */
class Sitelogs_export extends CI_Controller {

    const CHUNK_SIZE = 10000;        // Rows per chunk
    const MAX_CHUNKS_PER_RUN = 100;  // Max chunks to process per run (1M rows)
    const MAX_ROWS_PER_FILE = 5000000;  // 5M rows per file (~2GB each)
    const CSV_FILENAME_PATTERN = 'sitelogs-legacy-export-%03d.csv';
    
    private $is_cli;
    private $current_file_number = 1;        // Current file number (001, 002, etc.)
    private $current_file_row_count = 0;     // Rows written to current file
    private $current_file_handle = null;     // File handle for current CSV file
    private $current_file_path = null;       // Path to current CSV file

    // Directory for storing exported csv files
    private $export_dir = FCPATH . 'logs' . DIRECTORY_SEPARATOR;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->is_cli = $this->input->is_cli_request();
        
        // Web mode: require admin authentication
        if (!$this->is_cli) {
            $this->load->library('acl_manager');
            if (!$this->acl_manager->user_is_admin()) {
                show_error('Access denied. Admin privileges required.');
            }
            $this->load->library('template');
            $this->template->set_template('admin');
            $this->load->helper('url');
        }
        
        $this->load->database();
        
        if (!$this->is_cli && $this->session->userdata('export_file_number')) {
            $this->current_file_number = (int)$this->session->userdata('export_file_number');
            $this->current_file_row_count = (int)$this->session->userdata('export_file_row_count');
        }
        
        if ($this->is_cli) {
            set_time_limit(0);
            ini_set('max_execution_time', '0');
        } else {
            set_time_limit(300); // Timeout for web, 5 minutes
        }
    }
    
    
    public function index()
    {
        if ($this->is_cli) {
            $this->show_help();
        } else {
            $this->show_status_page();
        }
    }
    
    /**
     * Start export - Process chunks (batch mode)
     * 
     * CLI: Processes limited chunks and exits
     * Web: Processes limited chunks and shows progress
     */
    public function start()
    {
        $max_chunks = $this->input->get_post('max_chunks') ?: self::MAX_CHUNKS_PER_RUN;
        
        if ($this->is_cli) {
            $this->process_export($max_chunks);
        } else {
            ob_start();
            $result = $this->process_export($max_chunks);
            $output = ob_get_clean();
            
            $status = $this->get_status();
            $data = array();
            $data['page_title'] = 'Legacy Sitelogs Export';
            $data['export_result'] = $result;
            $data['export_output'] = $output;
            $data['remaining'] = $this->get_table_count();
            $data['status'] = $status;
            
            $content = $this->load->view('admin/utils/sitelogs_export/start', $data, TRUE);
            
            $this->template->write('title', $data['page_title'], TRUE);
            $this->template->write('content', $content, TRUE);
            $this->template->render();
        }
    }
    
    /**
     * Process all data - Continues until table is empty
     * 
     * CLI: Loops until all data is processed
     * Web: Redirects back to itself to process chunks incrementally
     */
    public function process_all()
    {
        if ($this->is_cli) {
            echo "Starting full export of sitelogs_legacy...\n";
            echo "This will process all data until the table is empty.\n\n";
            
            $total_exported = 0;
            $iteration = 0;
            
            while (true) {
                $iteration++;
                $count_before = $this->get_table_count();
                
                if ($count_before == 0) {
                    echo "\n" . str_repeat('=', 80) . "\n";
                    echo "Export complete! Total rows exported: {$total_exported}\n";
                    echo str_repeat('=', 80) . "\n";
                    break;
                }
                
                echo "\n--- Iteration {$iteration} ---\n";
                echo "Rows remaining: {$count_before}\n";
                echo "Processing up to " . (self::MAX_CHUNKS_PER_RUN * self::CHUNK_SIZE) . " rows...\n";
                
                $result = $this->process_export(self::MAX_CHUNKS_PER_RUN);
                $total_exported += $result['processed'];
                
                $count_after = $this->get_table_count();
                echo "Exported {$result['processed']} rows in this iteration. Remaining: {$count_after}\n";
                
                // Check if table is now empty
                if ($count_after == 0) {
                    echo "\n" . str_repeat('=', 80) . "\n";
                    echo "Export complete! Total rows exported: {$total_exported}\n";
                    
                    // Drop empty table
                    if ($this->db->table_exists('sitelogs_legacy')) {
                        echo "Dropping empty table...\n";
                        $this->load->dbforge();
                        $this->dbforge->drop_table('sitelogs_legacy', TRUE);
                        echo "✓ Table dropped successfully\n";
                    }
                    
                    $csv_files = $this->get_all_csv_files();
                    echo "CSV files created: " . count($csv_files) . "\n";
                    foreach ($csv_files as $file) {
                        echo "  - {$file['filename']} ({$file['size_formatted']})\n";
                    }
                    echo str_repeat('=', 80) . "\n";
                    break;
                }
            }
            
        } else {
            // Web: Process chunks and redirect back to continue
            // Start output buffering to capture progress
            ob_start();
            
            $result = $this->process_export(self::MAX_CHUNKS_PER_RUN);
            $output = ob_get_clean();
            
            $remaining = $this->get_table_count();
            
            // Check if complete
            if ($remaining == 0) {
                // Drop empty table
                if ($this->db->table_exists('sitelogs_legacy')) {
                    $this->load->dbforge();
                    $this->dbforge->drop_table('sitelogs_legacy', TRUE);
                }
                
                // Get totals from session before clearing
                $total_exported = $this->session->userdata('export_total_exported') ?: $result['processed'];
                $total_iterations = $this->session->userdata('export_iteration') ?: 1;
                
            // Clear session data
            $this->session->unset_userdata('export_progress');
            $this->session->unset_userdata('export_iteration');
            $this->session->unset_userdata('export_total_exported');
            $this->session->unset_userdata('export_file_number');
            $this->session->unset_userdata('export_file_row_count');
                
                // Redirect to completion page
                $this->session->set_flashdata('message', "Export complete! Total rows exported: " . number_format($total_exported) . " in " . $total_iterations . " iteration(s).");
                redirect('utils/sitelogs_export');
                return;
            }
            
            // Store progress in session for display
            $iteration = $this->session->userdata('export_iteration') ? ($this->session->userdata('export_iteration') + 1) : 1;
            
            $progress = array(
                'processed' => $result['processed'],
                'chunks' => $result['chunks'],
                'remaining' => $remaining,
                'output' => $output,
                'iteration' => $iteration,
                'total_exported' => $this->session->userdata('export_total_exported') ? ($this->session->userdata('export_total_exported') + $result['processed']) : $result['processed']
            );
            $this->session->set_userdata('export_progress', $progress);
            $this->session->set_userdata('export_iteration', $iteration);
            $this->session->set_userdata('export_total_exported', $progress['total_exported']);
            
            // Show progress page that redirects back to continue
            $data = array();
            $data['page_title'] = 'Legacy Sitelogs Export - Processing...';
            $data['progress'] = $progress;
            $data['redirect_url'] = site_url('utils/sitelogs_export/process_all');
            
            $content = $this->load->view('admin/utils/sitelogs_export/process_all', $data, TRUE);
            
            $this->template->write('title', $data['page_title'], TRUE);
            $this->template->write('content', $content, TRUE);
            $this->template->render();
        }
    }
    
    /**
     * Get current status
     */
    public function status()
    {
        $status = $this->get_status();
        
        if ($this->is_cli) {
            $this->output_cli_status($status);
        } else {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($status));
        }
    }
    
    /**
     * Core export logic - Process chunks
     */
    private function process_export($max_chunks = null)
    {
        if ($max_chunks === null) {
            $max_chunks = self::MAX_CHUNKS_PER_RUN;
        }
        
        // Check if sitelogs_legacy table exists
        if (!$this->db->table_exists('sitelogs_legacy')) {
            $this->output_message("✓ sitelogs_legacy table does not exist", 'success');
            return array('processed' => 0, 'chunks' => 0, 'complete' => true);
        }
        
        // Check if table is empty
        $count = $this->get_table_count();
        if ($count == 0) {
            $this->output_message("✓ sitelogs_legacy table is empty", 'success');
            return array('processed' => 0, 'chunks' => 0, 'complete' => true);
        }
        
        $this->output_message("Starting export of sitelogs_legacy ({$count} rows remaining)...");
        $this->output_message("Processing up to " . ($max_chunks * self::CHUNK_SIZE) . " rows per run");
        $this->output_message("Files will be split at " . number_format(self::MAX_ROWS_PER_FILE) . " rows each (~2GB per file)");
        
        // Ensure file is open (will create first file or resume existing)
        $this->ensure_file_open();
        
        try {
            // Process chunks
            $result = $this->process_chunks($max_chunks);
            $processed = $result['processed'];
            $chunks_processed = $result['chunks'];
            $files_created = $result['files_created'];
            
            // Close current file if open
            $this->close_current_file();
            
            // Check if table is now empty
            $remaining = $this->get_table_count();
            
            if ($remaining > 0) {
                $progress_msg = "Progress: Exported {$processed} rows in {$chunks_processed} chunks, {$remaining} rows remaining.";
                $this->output_message($progress_msg);
                log_message('info', $progress_msg);
            } else {
                // Table is empty
                $this->output_message("Export complete: {$processed} rows exported in {$chunks_processed} chunks", 'success');
                log_message('info', "Export complete: {$processed} rows exported.");
            }
            
            return array(
                'processed' => $processed,
                'chunks' => $chunks_processed,
                'remaining' => $remaining,
                'complete' => ($remaining == 0),
                'files_created' => $files_created,
                'current_file_number' => $this->current_file_number
            );
            
        } catch (Exception $e) {
            // Close file on error
            $this->close_current_file();
            log_message('error', 'Export failed: ' . $e->getMessage());
            $this->output_message("Export failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Process chunks from sitelogs_legacy table
     */
    private function process_chunks($max_chunks)
    {
        $db_driver = $this->db->dbdriver;
        $chunks_processed = 0;
        $total_exported = 0;
        $files_created = 0;
        $initial_file_number = $this->current_file_number;
        
        // Get initial minimum ID to start from
        $min_query = $this->db->query("SELECT MIN(id) as min_id FROM sitelogs_legacy");
        $min_row = $min_query->row();
        $last_id = $min_row ? ($min_row->min_id - 1) : 0;
        
        while ($chunks_processed < $max_chunks) {
            // Get chunk using primary key iteration
            $chunk = $this->get_chunk($last_id, self::CHUNK_SIZE);
            
            if (empty($chunk)) {
                break; // No more rows
            }
            
            // Extract IDs for deletion
            $ids = array_column($chunk, 'id');
            $last_id = max($ids);
            $chunk_size = count($chunk);
            
            // Process chunk in transaction
            $this->db->trans_start();
            
            try {
                // Write to CSV (with file rotation as needed)
                foreach ($chunk as $row) {
                    // Ensure file is open and rotate if needed (FAST: just integer comparison)
                    if ($this->current_file_row_count >= self::MAX_ROWS_PER_FILE) {
                        $this->rotate_file();
                        $files_created++;
                    }
                    
                    // Ensure file handle is open
                    if ($this->current_file_handle === null) {
                        $this->ensure_file_open();
                    }
                    
                    // Write row
                    fputcsv($this->current_file_handle, [
                        $row['id'],
                        $row['sessionid'] ?? '',
                        $row['logtime'] ?? '',
                        $row['ip'] ?? '',
                        $row['url'] ?? '',
                        $row['logtype'] ?? '',
                        $row['surveyid'] ?? '0',
                        $row['section'] ?? '',
                        $row['keyword'] ?? '',
                        $row['username'] ?? '',
                        $row['useragent'] ?? ''
                    ]);
                    
                    // Increment row count (FAST: O(1) operation)
                    $this->current_file_row_count++;
                    
                    // Save to session for web mode (persist file tracking)
                    if (!$this->is_cli) {
                        $this->session->set_userdata('export_file_number', $this->current_file_number);
                        $this->session->set_userdata('export_file_row_count', $this->current_file_row_count);
                    }
                }
                
                // Flush to disk after chunk
                if ($this->current_file_handle !== null) {
                    fflush($this->current_file_handle);
                }
                
                // Delete chunk (handle SQL Server parameter limit)
                $this->delete_chunk($ids);
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception("Transaction failed while processing chunk starting at ID {$ids[0]}");
                }
                
                $chunks_processed++;
                $total_exported += $chunk_size;
                
                // Log progress every 10 chunks
                if ($chunks_processed % 10 == 0) {
                    $remaining = $this->get_table_count();
                    $this->output_message("Processed {$chunks_processed} chunks ({$total_exported} rows), {$remaining} rows remaining... File #{$this->current_file_number}: {$this->current_file_row_count} rows");
                }
                
                // Free memory
                unset($chunk);
                unset($ids);
                
            } catch (Exception $e) {
                $this->db->trans_rollback();
                throw $e;
            }
        }
        
        // Calculate files created in this run
        if ($this->current_file_number > $initial_file_number) {
            $files_created = $this->current_file_number - $initial_file_number;
        }
        
        return array(
            'processed' => $total_exported,
            'chunks' => $chunks_processed,
            'files_created' => $files_created
        );
    }
    
    /**
     * Get chunk of rows using primary key iteration
     */
    private function get_chunk($last_id, $limit)
    {
        $db_driver = $this->db->dbdriver;
        
        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $sql = "
                SELECT id, sessionid, logtime, ip, url, logtype, surveyid, section, keyword, username, useragent
                FROM sitelogs_legacy
                WHERE id > ?
                ORDER BY id ASC
                LIMIT ?
            ";
            $query = $this->db->query($sql, [$last_id, $limit]);
        } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
            $sql = "
                SELECT TOP ({$limit}) id, sessionid, logtime, ip, url, logtype, surveyid, section, keyword, username, useragent
                FROM sitelogs_legacy
                WHERE id > ?
                ORDER BY id ASC
            ";
            $query = $this->db->query($sql, [$last_id]);
        } else {
            throw new Exception("Unsupported database driver: " . $db_driver);
        }
        
        if (!$query) {
            $error = $this->db->error();
            throw new Exception("Query failed: " . $error['message']);
        }
        
        return $query->result_array();
    }
    
    /**
     * Delete chunk of IDs, handling SQL Server parameter limits
     */
    private function delete_chunk($ids)
    {
        $db_driver = $this->db->dbdriver;
        
        // SQL Server has parameter limit of ~2100, split into smaller batches
        if (($db_driver === 'sqlsrv' || $db_driver === 'mssql') && count($ids) > 2000) {
            $batches = array_chunk($ids, 2000);
            foreach ($batches as $batch) {
                $this->db->where_in('id', $batch);
                $this->db->delete('sitelogs_legacy');
            }
        } else {
            $this->db->where_in('id', $ids);
            $this->db->delete('sitelogs_legacy');
        }
    }
    
    /**
     * Get CSV file path for a specific file number
     */
    private function get_csv_file_path($file_number = null)
    {
        if ($file_number === null) {
            $file_number = $this->current_file_number;
        }
        
        if (!is_dir($this->export_dir)) {
            mkdir($this->export_dir, 0755, true);
        }
        
        $filename = sprintf(self::CSV_FILENAME_PATTERN, $file_number);
        return $this->export_dir . $filename;
    }
    
    /**
     * Get all CSV export files
     */
    private function get_all_csv_files()
    {
        $files = array();
        
        if (!is_dir($this->export_dir)) {
            return $files;
        }
        
        // Find all matching CSV files
        $pattern = $this->export_dir . 'sitelogs-legacy-export-*.csv';
        $matches = glob($pattern);
        
        if ($matches) {
            foreach ($matches as $filepath) {
                $filename = basename($filepath);
                // Extract file number from filename
                if (preg_match('/sitelogs-legacy-export-(\d+)\.csv/', $filename, $matches_num)) {
                    $file_number = (int)$matches_num[1];
                    $files[$file_number] = array(
                        'number' => $file_number,
                        'path' => $filepath,
                        'filename' => $filename,
                        'size' => file_exists($filepath) ? filesize($filepath) : 0,
                        'size_formatted' => $this->format_bytes(file_exists($filepath) ? filesize($filepath) : 0)
                    );
                }
            }
            ksort($files); // Sort by file number
        }
        
        return $files;
    }
    
    /**
     * Ensure current file is open (open if closed, create if doesn't exist)
     */
    private function ensure_file_open()
    {
        // If file is already open, return
        if ($this->current_file_handle !== null && is_resource($this->current_file_handle)) {
            return;
        }
        
        // Get current file path
        $this->current_file_path = $this->get_csv_file_path();
        
        // Check if file exists (for resuming)
        $exists = file_exists($this->current_file_path);
        
        // If resuming and file exists, need to count existing rows to set counter correctly
        if ($exists && !$this->is_cli) {
            // For resuming, we might want to append, but for simplicity, we'll count rows
            // Actually, for safety, we should append and count existing rows
            $handle = fopen($this->current_file_path, 'r');
            if ($handle) {
                $existing_rows = 0;
                // Count rows (skip header)
                while (fgets($handle) !== false) {
                    $existing_rows++;
                }
                fclose($handle);
                // Subtract 1 for header
                $this->current_file_row_count = max(0, $existing_rows - 1);
            }
        }
        
        // Open file (append if exists and resuming, write if new)
        $mode = ($exists && $this->current_file_row_count > 0) ? 'a' : 'w';
        $this->current_file_handle = fopen($this->current_file_path, $mode);
        
        if (!$this->current_file_handle) {
            throw new Exception("Cannot open CSV file: {$this->current_file_path}");
        }
        
        // Increase buffer size for better performance
        stream_set_write_buffer($this->current_file_handle, 8192 * 10); // 80KB buffer
        
        // Write headers only if new file (not appending)
        if ($mode === 'w') {
            $headers = ['id', 'sessionid', 'logtime', 'ip', 'url', 'logtype', 'surveyid', 'section', 'keyword', 'username', 'useragent'];
            fputcsv($this->current_file_handle, $headers);
            $this->current_file_row_count = 0;
        }
        
        $this->output_message("Opened file: " . basename($this->current_file_path) . " (row count: {$this->current_file_row_count})");
    }
    
    /**
     * Rotate to new file
     */
    private function rotate_file()
    {
        // Close current file
        $this->close_current_file();
        
        // Move to next file number
        $this->current_file_number++;
        $this->current_file_row_count = 0;
        $this->current_file_path = null;
        $this->current_file_handle = null;
        
        // Save to session
        if (!$this->is_cli) {
            $this->session->set_userdata('export_file_number', $this->current_file_number);
            $this->session->set_userdata('export_file_row_count', 0);
        }
        
        // Open new file
        $this->ensure_file_open();
        
        $this->output_message("Rotated to new file: " . basename($this->current_file_path));
    }
    
    /**
     * Close current file handle
     */
    private function close_current_file()
    {
        if ($this->current_file_handle !== null && is_resource($this->current_file_handle)) {
            fclose($this->current_file_handle);
            $this->output_message("Closed file #{$this->current_file_number} with {$this->current_file_row_count} rows: " . basename($this->current_file_path));
            $this->current_file_handle = null;
        }
    }
    
    /**
     * Get table row count
     */
    private function get_table_count()
    {
        if (!$this->db->table_exists('sitelogs_legacy')) {
            return 0;
        }
        return $this->db->count_all('sitelogs_legacy');
    }
    
    /**
     * Get export status
     */
    private function get_status()
    {
        $table_exists = $this->db->table_exists('sitelogs_legacy');
        $row_count = $table_exists ? $this->get_table_count() : 0;
        $csv_files = $this->get_all_csv_files();
        
        // Calculate total size across all files
        $total_size = 0;
        foreach ($csv_files as $file) {
            $total_size += $file['size'];
        }
        
        return array(
            'table_exists' => $table_exists,
            'row_count' => $row_count,
            'csv_files' => $csv_files,
            'csv_file_count' => count($csv_files),
            'csv_total_size' => $total_size,
            'csv_total_size_formatted' => $this->format_bytes($total_size),
            'current_file_number' => $this->current_file_number,
            'current_file_row_count' => $this->current_file_row_count
        );
    }
    
    /**
     * Output message (CLI or Web)
     */
    private function output_message($message, $type = 'info')
    {
        if ($this->is_cli) {
            $prefix = '';
            if ($type === 'success') $prefix = '✓ ';
            if ($type === 'error') $prefix = '✗ ';
            echo $prefix . $message . "\n";
            flush();
        } else {
            // For web, messages are captured in output buffer
            echo $message . "\n";
            flush();
        }
    }
    
    /**
     * Show help (CLI only)
     */
    private function show_help()
    {
        echo "Legacy Sitelogs Export Tool\n";
        echo "============================\n\n";
        echo "Available commands:\n";
        echo "  php index.php utils/sitelogs_export start          - Process limited chunks (default: 100 chunks = 1M rows)\n";
        echo "  php index.php utils/sitelogs_export process_all    - Process all data until table is empty\n";
        echo "  php index.php utils/sitelogs_export status         - Show current status\n";
        echo "\n";
    }
    
    /**
     * Show status page (Web only)
     */
    private function show_status_page()
    {
        $status = $this->get_status();
        $progress = $this->session->userdata('export_progress');
        
        // Clear session iteration counter if viewing status page (not actively processing)
        // Only clear if not in the middle of process_all
        if (!$this->input->get('processing')) {
            // Don't clear if process_all is actively running
            if (!$progress || !isset($progress['remaining']) || $progress['remaining'] == 0) {
                $this->session->unset_userdata('export_iteration');
                $this->session->unset_userdata('export_total_exported');
                $this->session->unset_userdata('export_file_number');
                $this->session->unset_userdata('export_file_row_count');
            }
        }
        
        $data = array();
        $data['page_title'] = 'Legacy Sitelogs Export';
        $data['status'] = $status;
        $data['progress'] = $progress;
        
        // Clear progress from session after displaying (but keep iteration counters)
        if ($progress && isset($progress['remaining']) && $progress['remaining'] == 0) {
            $this->session->unset_userdata('export_progress');
        }
        
        $content = $this->load->view('admin/utils/sitelogs_export/index', $data, TRUE);
        
        $this->template->write('title', $data['page_title'], TRUE);
        $this->template->write('content', $content, TRUE);
        $this->template->render();
    }
    
    /**
     * Output CLI status
     */
    private function output_cli_status($status)
    {
        echo "Legacy Sitelogs Export Status\n";
        echo "==============================\n\n";
        
        if ($status['table_exists']) {
            echo "Table: sitelogs_legacy EXISTS\n";
            echo "Rows remaining: {$status['row_count']}\n";
        } else {
            echo "Table: sitelogs_legacy DOES NOT EXIST\n";
        }
        
        echo "\nCSV Files: {$status['csv_file_count']} file(s)\n";
        echo "Total Size: {$status['csv_total_size_formatted']}\n";
        
        if ($status['csv_file_count'] > 0) {
            echo "\nFiles:\n";
            foreach ($status['csv_files'] as $file) {
                echo "  {$file['filename']}: {$file['size_formatted']}\n";
            }
        } else {
            echo "No CSV files found.\n";
        }
        
        if ($status['current_file_number'] > 0) {
            echo "\nCurrent file: #{$status['current_file_number']} ({$status['current_file_row_count']} rows)\n";
        }
        
        echo "\n";
    }
    
    /**
     * Format bytes to human-readable format
     */
    private function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
