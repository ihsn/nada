<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SOLR CLI Controller
 * 
 * Command-line interface for SOLR indexing operations
 */
class Solr extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Ensure this is a CLI request
        if (!$this->input->is_cli_request()) {
            die("This controller can only be accessed from the command line.\n");
        }
        
        // Load required libraries
        $this->load->library('solr_manager');
    }
    
    /**
     * Index all studies (surveys)
     * 
     * Usage: php index.php cli/solr/index_studies [start_row]
     * 
     * @param int $start_row Optional starting row ID (default: 0)
     */
    public function index_studies($start_row = 0) {
        echo "Starting SOLR study indexing...\n";
        echo "Start row: " . ($start_row > 0 ? $start_row : "beginning") . "\n\n";
        
        try {
            $batch_size = 50;
            $current_start_row = $start_row;
            $total_processed = 0;
            $batches_processed = 0;
            $start_time = microtime(true);
            
            set_time_limit(0);
            
            while (true) {
                $start_row_display = $current_start_row > 0 ? $current_start_row : 'beginning';
                echo "Processing batch " . ($batches_processed + 1) . " (start_row: {$start_row_display})... ";
                
                $result = $this->solr_manager->import_surveys_batch($current_start_row, $batch_size, $loop = false);
                
                if ($result === false || !isset($result['rows_processed']) || $result['rows_processed'] == 0) {
                    echo "No more rows to process.\n";
                    break;
                }
                
                $rows_processed = $result['rows_processed'];
                $total_processed += $rows_processed;
                $batches_processed++;
                $current_start_row = $result['last_row_id'];
                
                echo "Processed {$rows_processed} studies. Total: {$total_processed}\n";
                
                if ($rows_processed < $batch_size) {
                    echo "Reached end of data.\n";
                    break;
                }
            }
            
            $elapsed_time = round(microtime(true) - $start_time, 2);
            
            echo "\n✅ Study indexing completed!\n";
            echo "   Total studies indexed: {$total_processed}\n";
            echo "   Batches processed: {$batches_processed}\n";
            echo "   Time elapsed: {$elapsed_time} seconds\n";
            
        } catch (Exception $e) {
            echo "❌ Error indexing studies: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Index all variables
     * 
     * Usage: php index.php cli/solr/index_variables [start_row] [limit] [internal_batch_size]
     * 
     * @param int $start_row Optional starting row ID (default: 0)
     * @param int $limit Optional total limit (0 = no limit, process all)
     * @param int $internal_batch_size Optional internal batch size (default: 100)
     */
    public function index_variables($start_row = 0, $limit = 0, $internal_batch_size = 100) {
        echo "Starting SOLR variable indexing...\n";
        echo "Start row: " . ($start_row > 0 ? $start_row : "beginning") . "\n";
        echo "Limit: " . ($limit > 0 ? $limit : "unlimited") . "\n";
        echo "Internal batch size: {$internal_batch_size}\n\n";
        
        try {
            $current_start_row = $start_row;
            $total_processed = 0;
            $batches_processed = 0;
            $start_time = microtime(true);
            
            set_time_limit(0);
            
            while (true) {
                if ($limit > 0 && $total_processed >= $limit) {
                    echo "Reached limit of {$limit} variables.\n";
                    break;
                }
                
                $remaining_needed = $limit > 0 ? ($limit - $total_processed) : $internal_batch_size;
                $batch_size = min($internal_batch_size, $remaining_needed);
                
                echo "Processing batch " . ($batches_processed + 1) . " (start_row: {$current_start_row}, batch_size: {$batch_size})... ";
                
                $result = $this->solr_manager->import_variables_batch($current_start_row, $batch_size, $loop = false);
                
                if ($result === false || !isset($result['rows_processed']) || $result['rows_processed'] == 0) {
                    echo "No more rows to process.\n";
                    break;
                }
                
                $rows_processed = $result['rows_processed'];
                $total_processed += $rows_processed;
                $batches_processed++;
                $current_start_row = $result['last_row_id'];
                
                echo "Processed {$rows_processed} variables. Total: {$total_processed}\n";
                
                if ($rows_processed < $batch_size) {
                    echo "Reached end of data.\n";
                    break;
                }
            }
            
            $elapsed_time = round(microtime(true) - $start_time, 2);
            
            echo "\n✅ Variable indexing completed!\n";
            echo "   Total variables indexed: {$total_processed}\n";
            echo "   Batches processed: {$batches_processed}\n";
            echo "   Time elapsed: {$elapsed_time} seconds\n";
            
        } catch (Exception $e) {
            echo "❌ Error indexing variables: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Clean (clear) the SOLR index
     * 
     * Usage: php index.php cli/solr/clean_index
     */
    public function clean_index() {
        echo "Clearing SOLR index...\n";
        
        try {
            $result = $this->solr_manager->clear_index();
            
            if (isset($result['status']) && $result['status'] == 0) {
                echo "✅ SOLR index cleared successfully!\n";
            } else {
                echo "⚠️  Index cleared (status: " . (isset($result['status']) ? $result['status'] : 'unknown') . ")\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error clearing index: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Commit changes to SOLR index
     * 
     * Usage: php index.php cli/solr/commit_index
     */
    public function commit_index() {
        echo "Committing SOLR index changes...\n";
        
        try {
            $status = $this->solr_manager->commit_index_changes();
            
            if ($status == 0) {
                echo "✅ SOLR index committed successfully!\n";
            } else {
                echo "⚠️  Index committed (status: {$status})\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error committing index: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Get SOLR index status
     * Returns JSON with counts from both SOLR and database
     * 
     * Usage: php index.php cli/solr/status
     */
    public function status() {
        try {
            $solr_counts = $this->solr_manager->count_solr_records();
            $db_counts = $this->solr_manager->count_database_records();
            
            $status = array(
                'solr' => array(
                    'studies' => isset($solr_counts['datasets']) ? (int)$solr_counts['datasets'] : 0,
                    'variables' => isset($solr_counts['variables']) ? (int)$solr_counts['variables'] : 0,
                    'citations' => isset($solr_counts['citations']) ? (int)$solr_counts['citations'] : 0
                ),
                'database' => array(
                    'studies' => isset($db_counts['datasets']) ? (int)$db_counts['datasets'] : 0,
                    'variables' => isset($db_counts['variables']) ? (int)$db_counts['variables'] : 0,
                    'citations' => isset($db_counts['citations']) ? (int)$db_counts['citations'] : 0
                ),
                'sync_status' => array(
                    'studies' => array(
                        'indexed' => isset($solr_counts['datasets']) ? (int)$solr_counts['datasets'] : 0,
                        'in_database' => isset($db_counts['datasets']) ? (int)$db_counts['datasets'] : 0,
                        'difference' => (isset($db_counts['datasets']) ? (int)$db_counts['datasets'] : 0) - (isset($solr_counts['datasets']) ? (int)$solr_counts['datasets'] : 0)
                    ),
                    'variables' => array(
                        'indexed' => isset($solr_counts['variables']) ? (int)$solr_counts['variables'] : 0,
                        'in_database' => isset($db_counts['variables']) ? (int)$db_counts['variables'] : 0,
                        'difference' => (isset($db_counts['variables']) ? (int)$db_counts['variables'] : 0) - (isset($solr_counts['variables']) ? (int)$solr_counts['variables'] : 0)
                    ),
                    'citations' => array(
                        'indexed' => isset($solr_counts['citations']) ? (int)$solr_counts['citations'] : 0,
                        'in_database' => isset($db_counts['citations']) ? (int)$db_counts['citations'] : 0,
                        'difference' => (isset($db_counts['citations']) ? (int)$db_counts['citations'] : 0) - (isset($solr_counts['citations']) ? (int)$solr_counts['citations'] : 0)
                    )
                )
            );
            
            echo json_encode($status, JSON_PRETTY_PRINT) . "\n";
            
        } catch (Exception $e) {
            $error_status = array(
                'error' => $e->getMessage(),
                'solr' => null,
                'database' => null
            );
            echo json_encode($error_status, JSON_PRETTY_PRINT) . "\n";
            exit(1);
        }
    }
    
    /**
     * Show help
     */
    public function index() {
        echo "SOLR CLI Commands\n";
        echo "================\n\n";
        echo "Available commands:\n";
        echo "  index_studies [start_row]              - Index all studies (surveys)\n";
        echo "  index_variables [start_row] [limit] [batch_size] - Index all variables\n";
        echo "  clean_index                             - Clear the SOLR index\n";
        echo "  commit_index                            - Commit changes to SOLR index\n";
        echo "  status                                  - Get index status (JSON output)\n";
        echo "  help                                    - Show this help\n\n";
        echo "Examples:\n";
        echo "  php index.php cli/solr/index_studies\n";
        echo "  php index.php cli/solr/index_studies 100\n";
        echo "  php index.php cli/solr/index_variables\n";
        echo "  php index.php cli/solr/index_variables 0 10000 100\n";
        echo "  php index.php cli/solr/clean_index\n";
        echo "  php index.php cli/solr/commit_index\n";
        echo "  php index.php cli/solr/status\n";
    }
    
    /**
     * Alias for index() - show help
     */
    public function help() {
        $this->index();
    }
}

