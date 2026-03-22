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
     * Index all citations
     *
     * Usage: php index.php cli/solr/index_citations [start_row]
     *
     * @param int $start_row Optional starting citation ID (default: 0)
     */
    public function index_citations($start_row = 0): void
    {
        $start_row = (int)$start_row;
        echo "Starting SOLR citation indexing...\n";
        echo "Start row: " . ($start_row > 0 ? $start_row : "beginning") . "\n\n";

        try {
            $batch_size       = 100;
            $current_start    = $start_row;
            $total_processed  = 0;
            $batches          = 0;
            $start_time       = microtime(true);

            set_time_limit(0);

            while (true) {
                echo "Processing batch " . ($batches + 1) . " (start_row: {$current_start})... ";

                $result = $this->solr_manager->import_citations_batch($current_start, $batch_size, $loop = false);

                if ($result === false || empty($result['rows_processed'])) {
                    echo "No more rows to process.\n";
                    break;
                }

                $total_processed += $result['rows_processed'];
                $batches++;
                $current_start = $result['last_row_id'];

                echo "Processed {$result['rows_processed']} citations. Total: {$total_processed}\n";

                if ($result['rows_processed'] < $batch_size) {
                    echo "Reached end of data.\n";
                    break;
                }
            }

            $elapsed = round(microtime(true) - $start_time, 2);
            echo "\nCitation indexing completed.\n";
            echo "   Total citations indexed: {$total_processed}\n";
            echo "   Time elapsed: {$elapsed}s\n";
            echo "   Run 'commit_index' to make changes visible.\n";

        } catch (Exception $e) {
            echo "Error indexing citations: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Create / update the citation schema fields in Solr.
     *
     * Safe to re-run — skips fields that already exist unless --replace is passed.
     *
     * Usage:
     *   php index.php cli/solr/schema_citations           # add missing fields
     *   php index.php cli/solr/schema_citations replace   # replace existing definitions
     */
    public function schema_citations($replace = ''): void
    {
        $replace_existing = ($replace === 'replace' || $replace === 'true' || $replace === '1');

        echo "Setting up Solr citation schema fields" . ($replace_existing ? ' (replacing existing)' : '') . "...\n";

        try {
            $this->load->library('solr_schema_manager');
            $result = $this->solr_schema_manager->setup_citation_fields($replace_existing);

            if (isset($result['error'])) {
                echo "Error: " . $result['error'] . "\n";
                exit(1);
            }

            // Report per-field results
            $added   = 0;
            $skipped = 0;
            $errors  = 0;

            if (isset($result['results']) && is_array($result['results'])) {
                foreach ($result['results'] as $field_result) {
                    $field_name = $field_result['field'] ?? 'unknown';
                    $status     = $field_result['status'] ?? 'unknown';
                    if ($status === 'added' || $status === 'replaced' || $status === 'success') {
                        echo "  [OK]      {$field_name}\n";
                        $added++;
                    } elseif ($status === 'skipped') {
                        echo "  [SKIPPED] {$field_name} (already exists)\n";
                        $skipped++;
                    } else {
                        echo "  [ERROR]   {$field_name}: " . json_encode($field_result) . "\n";
                        $errors++;
                    }
                }
            }

            echo "\nDone. Added/updated: {$added}, skipped: {$skipped}, errors: {$errors}\n";

            if ($added > 0) {
                echo "\nRe-index citations to populate the new fields:\n";
                echo "  php index.php cli/solr/index_citations\n";
                echo "  php index.php cli/solr/commit_index\n";
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Delete all citation documents from SOLR index (keeps surveys/variables)
     *
     * Usage: php index.php cli/solr/clean_citations
     */
    public function clean_citations() {
        echo "Deleting all citation documents from SOLR index...\n";

        try {
            $this->solr_manager->delete_document('doctype:3');
            echo "Done. Citation documents removed.\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
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
     * Set up the full SOLR schema and clear all indexed data.
     *
     * Clears the entire index, then creates/replaces all schema fields
     * (surveys, variables, citations).  Use this for a fresh install or
     * when the schema needs to be rebuilt from scratch.
     *
     * Usage: php index.php cli/solr/setup_schema
     */
    public function setup_schema() {
        echo "Setting up full SOLR schema...\n";
        echo "WARNING: This will delete ALL indexed data.\n\n";

        try {
            // 1. Clear all documents
            echo "Step 1/2: Clearing index... ";
            $this->solr_manager->clear_index();
            echo "done.\n";

            // 2. Create/replace all schema fields
            echo "Step 2/2: Creating schema fields...\n";
            $this->load->library('solr_schema_manager');
            $results = $this->solr_schema_manager->setup_complete_schema(true);

            $added   = 0;
            $skipped = 0;
            $errors  = 0;

            foreach ($results as $section => $section_result) {
                $section_label = str_replace('_', ' ', ucfirst($section));
                echo "\n  [{$section_label}]\n";

                if (isset($section_result['results']) && is_array($section_result['results'])) {
                    foreach ($section_result['results'] as $field_result) {
                        $field_name = $field_result['field'] ?? 'unknown';
                        $status     = $field_result['status'] ?? 'unknown';
                        if ($status === 'added' || $status === 'replaced' || $status === 'success') {
                            echo "    [OK]      {$field_name}\n";
                            $added++;
                        } elseif ($status === 'skipped') {
                            echo "    [SKIPPED] {$field_name}\n";
                            $skipped++;
                        } else {
                            echo "    [ERROR]   {$field_name}: " . json_encode($field_result) . "\n";
                            $errors++;
                        }
                    }
                } elseif (isset($section_result['error'])) {
                    echo "    [ERROR] " . $section_result['error'] . "\n";
                    $errors++;
                }
            }

            echo "\nSchema setup complete. Fields: added/updated={$added}, skipped={$skipped}, errors={$errors}\n";

            if ($errors === 0) {
                echo "\nNext: index all content and commit:\n";
                echo "  php index.php cli/solr/index_studies && php index.php cli/solr/commit_index\n";
                echo "  php index.php cli/solr/index_variables && php index.php cli/solr/commit_index\n";
                echo "  php index.php cli/solr/index_citations && php index.php cli/solr/commit_index\n";
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
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
        echo "  setup_schema                            - Clear index and create full schema (fresh install)\n";
        echo "  index_studies [start_row]              - Index all studies (surveys)\n";
        echo "  index_variables [start_row] [limit] [batch_size] - Index all variables\n";
        echo "  index_citations [start_row]            - Index all citations\n";
        echo "  schema_citations [replace]             - Create/update citation schema fields only\n";
        echo "  clean_index                             - Clear the SOLR index\n";
        echo "  clean_citations                         - Delete only citation documents\n";
        echo "  commit_index                            - Commit changes to SOLR index\n";
        echo "  status                                  - Get index status (JSON output)\n";
        echo "  help                                    - Show this help\n\n";
        echo "Fresh install / full rebuild:\n";
        echo "  php index.php cli/solr/setup_schema\n";
        echo "  php index.php cli/solr/index_studies && php index.php cli/solr/commit_index\n";
        echo "  php index.php cli/solr/index_variables && php index.php cli/solr/commit_index\n";
        echo "  php index.php cli/solr/index_citations && php index.php cli/solr/commit_index\n\n";
        echo "Note: Indexing commands do NOT automatically commit.\n";
        echo "      Run 'commit_index' after indexing to make changes visible.\n\n";
        echo "Run in the background (nohup):\n";
        echo "  nohup php index.php cli/solr/index_studies > /dev/null 2>&1 &\n";
        echo "  nohup php index.php cli/solr/index_variables > /dev/null 2>&1 &\n";
        echo "  nohup php index.php cli/solr/index_citations > /dev/null 2>&1 &\n";
    }
    
    /**
     * Alias for index() - show help
     */
    public function help() {
        $this->index();
    }
}

