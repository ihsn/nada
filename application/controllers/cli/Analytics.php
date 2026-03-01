<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics CLI Controller
 * 
 * 
 * Usage:
 *   // Use for daily cron job, this will auto-detect missing daily aggregates, 
 *   // update current month monthly aggregates, month-end processing, cleanup raw events, 
 *   // update all-time totals, and sync counters to surveys table
 *   php index.php cli/analytics run_aggregates
 * 
 *   // Use for manual processing of a specific date
 *   php index.php cli/analytics aggregate_daily [date]
 * 
 *   // Use for manual processing of a specific month
 *   php index.php cli/analytics aggregate_monthly [year] [month]
 * 
 *   // Use for manual processing of a specific study
 *   php index.php cli/analytics update_totals [study_id]
 * 
 *   // Use for manual processing of a specific batch size
 *   php index.php cli/analytics sync_counters [study_id] [batch_size] 
 * 
 *   // Use for manual processing of a specific date range
 *   php index.php cli/analytics backfill [start_date] [end_date]
 */
class Analytics extends CI_Controller {
    
    public function __construct()
    {
        // Ensure this is a CLI request BEFORE calling parent
        if (php_sapi_name() !== 'cli') {
            die("This controller can only be accessed from the command line.\n");
        }
        
        // Temporarily remove 'template' from autoload to prevent CLI issues
        $autoload_config =& get_config();
        if (isset($autoload_config['autoload']['libraries']) && is_array($autoload_config['autoload']['libraries'])) {
            $key = array_search('template', $autoload_config['autoload']['libraries']);
            if ($key !== false) {
                unset($autoload_config['autoload']['libraries'][$key]);
                $autoload_config['autoload']['libraries'] = array_values($autoload_config['autoload']['libraries']);
            }
        }
        
        parent::__construct();
        
        // Load required models
        $this->load->model('Analytics_model');
    }
    
    /**
     * Show help information
     */
    public function index()
    {
        echo "NADA Analytics Aggregation CLI\n";
        echo "===============================\n\n";
        echo "Usage:\n";
        echo "  php index.php cli/analytics <command> [options]\n\n";
        echo "Commands:\n";
        echo "  run_aggregates             Run all aggregation tasks (RECOMMENDED)\n";
        echo "                             - Auto-detects missing daily aggregates\n";
        echo "                             - Processes all missing dates\n";
        echo "                             - Monthly aggregation (current month)\n";
        echo "                             - Month-end processing (if 1st of month)\n";
        echo "                             - Cleanup old raw events (60 days)\n";
        echo "                             - Update all-time totals\n";
        echo "                             - Sync counters to surveys table\n";
        echo "                             Recommended for daily cron job\n\n";
        echo "  aggregate_daily [date]     Aggregate events to daily summaries\n";
        echo "                             date format: YYYY-MM-DD (default: yesterday)\n\n";
        echo "  aggregate_monthly [year] [month]  Roll up daily to monthly\n";
        echo "                                     year: YYYY (default: previous month)\n";
        echo "                                     month: 1-12 (default: previous month)\n\n";
        echo "  update_totals [study_id]   Update legacy totals (year=0, month=0)\n";
        echo "                             study_id: optional, updates all if omitted\n\n";
        echo "  sync_counters [study_id] [batch_size]  Sync counters to surveys table\n";
        echo "                             study_id: optional, syncs all if omitted\n";
        echo "                             batch_size: optional, default 500 (range: 100-1000)\n\n";
        echo "  backfill [start] [end]      Backfill historical data\n";
        echo "                             start: YYYY-MM-DD\n";
        echo "                             end: YYYY-MM-DD\n\n";
        echo "Examples:\n";
        echo "  php index.php cli/analytics aggregate_daily\n";
        echo "  php index.php cli/analytics aggregate_daily 2024-01-15\n";
        echo "  php index.php cli/analytics aggregate_monthly 2024 1\n";
        echo "  php index.php cli/analytics update_totals\n";
        echo "  php index.php cli/analytics sync_counters\n";
        echo "  php index.php cli/analytics sync_counters 12345\n";
        echo "  php index.php cli/analytics sync_counters null 1000\n";
        echo "  php index.php cli/analytics run_aggregates\n";
        echo "  php index.php cli/analytics backfill 2024-01-01 2024-01-31\n";
    }
    
    /**
     * Run all aggregation tasks with auto-detection (unified approach)
     * 
     * This is the recommended endpoint for daily cron jobs or on-demand processing.
     * Uses the unified process_aggregation_step() method that works for both CLI and web.
     * 
     * It automatically detects what needs to be processed and handles:
     * 1. Daily aggregation (auto-detects missing dates and processes them)
     * 2. Monthly aggregation (updates current month)
     * 3. Month-end processing (if 1st of month: finalizes previous month, deletes daily aggregates)
     * 4. Cleanup raw events (older than 60 days)
     * 5. Update all-time totals
     * 6. Sync counters to surveys table
     * 
     * Usage for cron:
     *   0 2 * * * cd /path/to/nada && php index.php cli/analytics run_aggregates
     * 
     * Can also be run on-demand to catch up on missed aggregations.
     */
    public function run_aggregates()
    {
        echo "NADA Analytics Aggregation\n";
        echo str_repeat('=', 60) . "\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('-', 60) . "\n\n";
        
        $overall_start_time = microtime(true);
        $step_count = 0;
        
        // Check if already running
        $status = $this->Analytics_model->get_aggregation_status();
        if ($status['status'] === 'running') {
            $last_updated = $status['last_updated_at'] ? strtotime($status['last_updated_at']) : 0;
            $minutes_ago = $last_updated > 0 ? (time() - $last_updated) / 60 : 999;
            
            if ($minutes_ago < 5) {
                echo "⚠ WARNING: Another aggregation is already running.\n";
                echo "   Started: {$status['started_at']}\n";
                echo "   Last updated: {$status['last_updated_at']}\n";
                echo "   Current step: {$status['current_step']}\n";
                echo "\nExiting to avoid conflicts.\n";
                exit(1);
            } else {
                echo "⚠ Previous run appears stale (last updated {$minutes_ago} minutes ago).\n";
                echo "   Starting new run...\n\n";
            }
        }
        
        // Initialize status
        if (!$this->Analytics_model->init_aggregation_status('cli')) {
            echo "✗ ERROR: Failed to initialize aggregation status\n";
            exit(1);
        }
        
        echo "Processing aggregation steps...\n";
        echo str_repeat('-', 60) . "\n\n";
        
        // Process steps in loop
        while (true) {
            $step_count++;
            
            // Process one step
            $result = $this->Analytics_model->process_aggregation_step('cli');
            
            // Check for errors
            if (isset($result['error']) && $result['error']) {
                echo "✗ ERROR: {$result['message']}\n";
                echo "\nAggregation failed. Check status table for details.\n";
                exit(1);
            }
            
            // Output progress
            $step_name = ucfirst($result['current_step'] ?? 'unknown');
            $item = $result['current_item'] ?? 'N/A';
            $progress = $result['progress'] ?? 0;
            $message = $result['message'] ?? '';
            
            echo "[Step {$step_count}] {$step_name}: {$item}\n";
            echo "  Progress: {$progress}%\n";
            echo "  {$message}\n";
            
            // Check if done
            if (!$result['has_more']) {
                echo "\n";
                break;
            }
            
            // Small delay to avoid hammering database
            usleep(100000); // 0.1 seconds
        }
        
        // Summary
        $total_time = round(microtime(true) - $overall_start_time, 2);
        echo str_repeat('-', 60) . "\n";
        echo "✓ Aggregation completed successfully\n";
        echo "  Total steps: {$step_count}\n";
        echo "  Total time: {$total_time} seconds\n";
        echo "  Completed: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 60) . "\n";
    }
    
    /**
     * Legacy method - kept for backward compatibility
     * Now just calls run_aggregates()
     * 
     * @deprecated Use run_aggregates() instead
     */
    public function run_daily()
    {
        echo "⚠ WARNING: run_daily() is deprecated. Use run_aggregates() instead.\n";
        echo "   Redirecting to run_aggregates()...\n\n";
        $this->run_aggregates();
    }
    
    /**
     * Reset stuck aggregation status
     * 
     * Marks any running aggregation as failed if it's been stuck
     * Useful for recovering from crashed or stuck aggregation runs
     */
    public function reset_status()
    {
        echo "NADA Analytics - Reset Aggregation Status\n";
        echo str_repeat('=', 60) . "\n";
        
        $status = $this->Analytics_model->get_aggregation_status();
        
        if ($status['status'] === 'running') {
            $last_updated = $status['last_updated_at'] ? strtotime($status['last_updated_at']) : 0;
            $minutes_ago = $last_updated > 0 ? (time() - $last_updated) / 60 : 999;
            
            echo "Current status: RUNNING\n";
            echo "Started: {$status['started_at']}\n";
            echo "Last updated: {$status['last_updated_at']} ({$minutes_ago} minutes ago)\n";
            echo "Current step: {$status['current_step']}\n\n";
            
            // Force mark as failed
            $this->db->where('id', $status['id']);
            $this->db->update('analytics_aggregation_status', array(
                'status' => 'failed',
                'error_message' => 'Manually reset - was stuck in ' . $status['current_step'],
                'completed_at' => date('Y-m-d H:i:s'),
                'last_updated_at' => date('Y-m-d H:i:s')
            ));
            
            echo "✓ Status reset to 'failed'\n";
            echo "You can now run 'run_aggregates' again.\n";
        } else {
            echo "Current status: {$status['status']}\n";
            echo "No reset needed - aggregation is not running.\n";
        }
        
        echo str_repeat('=', 60) . "\n";
    }
    
    /**
     * Aggregate pageviews and downloads to daily summaries
     * 
     * @param string $date Date in YYYY-MM-DD format (default: yesterday)
     */
    public function aggregate_daily($date = null)
    {
        echo "Starting daily aggregation...\n";
        echo str_repeat('=', 60) . "\n";
        
        // Default to yesterday if not provided
        if ($date === null) {
            $date = date('Y-m-d', strtotime('-1 day'));
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo "ERROR: Invalid date format. Use YYYY-MM-DD\n";
            exit(1);
        }
        
        echo "Date: {$date}\n";
        echo str_repeat('-', 60) . "\n";
        
        $start_time = microtime(true);
        
        try {
            // Chunked pageviews
            echo "Aggregating pageviews (chunked)...\n";
            $pv_offset = 0;
            do {
                $result_pv = $this->Analytics_model->aggregate_pageviews_daily_chunked($date, $pv_offset);
                if (empty($result_pv['success'])) {
                    echo "✗ Pageview aggregation batch failed\n";
                    exit(1);
                }

                $total_studies = $result_pv['total_studies'] ?? 0;
                $processed = $result_pv['processed'] ?? 0;
                echo "  Batch processed: {$processed}/{$total_studies} studies (offset {$result_pv['offset']})\n";
                $pv_offset = $result_pv['offset'] ?? ($pv_offset + 0);
            } while (!empty($result_pv['has_more']));
            echo "✓ Pageviews aggregation complete\n";
            
            // Chunked downloads
            echo "Aggregating downloads (chunked)...\n";
            $dl_offset = 0;
            do {
                $result_dl = $this->Analytics_model->aggregate_downloads_daily_chunked($date, $dl_offset);
                if (empty($result_dl['success'])) {
                    echo "✗ Download aggregation batch failed\n";
                    exit(1);
                }

                $total_files = $result_dl['total_files'] ?? 0;
                $processed = $result_dl['processed'] ?? 0;
                echo "  Batch processed: {$processed}/{$total_files} files (offset {$result_dl['offset']})\n";
                $dl_offset = $result_dl['offset'] ?? ($dl_offset + 0);
            } while (!empty($result_dl['has_more']));
            echo "✓ Download aggregation complete\n";
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            echo str_repeat('-', 60) . "\n";
            echo "Daily aggregation completed in {$execution_time} seconds\n";
            echo str_repeat('=', 60) . "\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    /**
     * Roll up daily aggregates to monthly summaries
     * 
     * @param int $year Year (default: previous month's year)
     * @param int $month Month 1-12 (default: previous month)
     */
    public function aggregate_monthly($year = null, $month = null)
    {
        echo "Starting monthly rollup...\n";
        echo str_repeat('=', 60) . "\n";
        
        // Default to previous month if not provided
        if ($year === null || $month === null) {
            $prev_month = date('Y-m', strtotime('first day of last month'));
            list($year, $month) = explode('-', $prev_month);
            $year = (int)$year;
            $month = (int)$month;
        } else {
            $year = (int)$year;
            $month = (int)$month;
        }
        
        // Validate month
        if ($month < 1 || $month > 12) {
            echo "ERROR: Month must be between 1 and 12\n";
            exit(1);
        }
        
        echo "Period: {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "\n";
        echo str_repeat('-', 60) . "\n";
        
        $start_time = microtime(true);
        
        try {
            $offset = 0;
            do {
                $result = $this->Analytics_model->aggregate_daily_to_monthly_chunked($year, $month, $offset);

                if (empty($result['success'])) {
                    echo "✗ Monthly rollup batch failed\n";
                    exit(1);
                }

                $total_studies = $result['total_studies'] ?? 0;
                $processed = $result['processed'] ?? 0;
                echo "  Batch processed: {$processed}/{$total_studies} studies (offset {$result['offset']})\n";
                $offset = $result['offset'] ?? ($offset + 0);
            } while (!empty($result['has_more']));

            echo "✓ Monthly rollup completed\n";
            echo "  Files affected will be updated after final batch\n";
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            echo str_repeat('-', 60) . "\n";
            echo "Monthly rollup completed in {$execution_time} seconds\n";
            echo str_repeat('=', 60) . "\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    /**
     * Update legacy totals (year=0, month=0)
     * 
     * @param string $study_id Optional study ID (updates all if omitted)
     */
    public function update_totals($study_id = null)
    {
        echo "Updating legacy totals...\n";
        echo str_repeat('=', 60) . "\n";
        
        if ($study_id) {
            echo "Study ID: {$study_id}\n";
        } else {
            echo "Updating all studies\n";
        }
        
        echo str_repeat('-', 60) . "\n";
        
        $start_time = microtime(true);
        
        try {
            if ($study_id !== null) {
                $result = $this->Analytics_model->update_legacy_totals($study_id);
                if ($result) {
                    echo "✓ Legacy totals updated for study\n";
                } else {
                    echo "✗ Legacy totals update failed for study\n";
                }
            } else {
                $offset = 0;
                do {
                    $result = $this->Analytics_model->update_legacy_totals_chunked($offset);
                    if (empty($result['success'])) {
                        echo "✗ Legacy totals batch failed\n";
                        exit(1);
                    }

                    $total_studies = $result['total_studies'] ?? 0;
                    $processed = $result['processed'] ?? 0;
                    echo "  Batch processed: {$processed}/{$total_studies} studies (offset {$result['offset']})\n";
                    $offset = $result['offset'] ?? ($offset + 0);
                } while (!empty($result['has_more']));
                echo "✓ Legacy totals updated successfully\n";
            }
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            echo str_repeat('-', 60) . "\n";
            echo "Legacy totals update completed in {$execution_time} seconds\n";
            echo str_repeat('=', 60) . "\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    /**
     * Sync study counters from analytics aggregates to surveys table
     * 
     * Updates surveys.total_views and surveys.total_downloads from
     * analytics_monthly_studies (year=0, month=0) all-time totals.
     * 
     * @param string|null $study_id Optional study ID (syncs all if omitted or 'null')
     * @param int $batch_size Batch size for processing (default: 500, range: 100-1000)
     */
    public function sync_counters($study_id = null, $batch_size = 500)
    {
        echo "Syncing study counters...\n";
        echo str_repeat('=', 60) . "\n";
        
        // Handle 'null' string as actual null
        if ($study_id === 'null' || $study_id === '') {
            $study_id = null;
        }
        
        if ($study_id) {
            echo "Study ID: {$study_id}\n";
        } else {
            echo "Syncing all studies\n";
        }
        
        // Validate and clamp batch size
        $batch_size = (int)$batch_size;
        if ($batch_size < 100) {
            $batch_size = 100;
        } elseif ($batch_size > 1000) {
            $batch_size = 1000;
        }
        
        echo "Batch size: {$batch_size}\n";
        echo str_repeat('-', 60) . "\n";
        
        $start_time = microtime(true);
        
        try {
            $result = $this->Analytics_model->sync_counters($study_id, $batch_size);
            
            if ($result['success']) {
                echo "✓ Counters synced successfully\n";
                echo "  Updated: {$result['updated']} surveys\n";
                
                if (!empty($result['errors'])) {
                    echo "  Warnings: " . count($result['errors']) . " errors encountered\n";
                    foreach ($result['errors'] as $error) {
                        echo "    - {$error}\n";
                    }
                }
            } else {
                echo "✗ Counter sync failed\n";
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        echo "  ERROR: {$error}\n";
                    }
                }
            }
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            echo str_repeat('-', 60) . "\n";
            echo "Counter sync completed in {$execution_time} seconds\n";
            echo str_repeat('=', 60) . "\n";
            
            // Exit with error code if failed
            if (!$result['success']) {
                exit(1);
            }
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    /**
     * Backfill historical data
     * Processes multiple dates in sequence
     * 
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     */
    public function backfill($start_date = null, $end_date = null)
    {
        echo "Starting backfill...\n";
        echo str_repeat('=', 60) . "\n";
        
        if ($start_date === null || $end_date === null) {
            echo "ERROR: Both start_date and end_date are required\n";
            echo "Usage: php index.php cli/analytics backfill YYYY-MM-DD YYYY-MM-DD\n";
            exit(1);
        }
        
        // Validate date formats
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || 
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            echo "ERROR: Invalid date format. Use YYYY-MM-DD\n";
            exit(1);
        }
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        if ($start_timestamp > $end_timestamp) {
            echo "ERROR: start_date must be before end_date\n";
            exit(1);
        }
        
        echo "Date range: {$start_date} to {$end_date}\n";
        echo str_repeat('-', 60) . "\n";
        
        $start_time = microtime(true);
        $current_date = $start_date;
        $processed = 0;
        $failed = 0;
        
        try {
            while (strtotime($current_date) <= $end_timestamp) {
                echo "Processing: {$current_date}... ";
                
                try {
                    $success_daily = $this->run_daily_chunked($current_date);
                    if ($success_daily) {
                        echo "✓\n";
                        $processed++;
                    } else {
                        echo "✗\n";
                        $failed++;
                    }
                } catch (Exception $e) {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                    $failed++;
                }
                
                // Move to next day
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            echo str_repeat('-', 60) . "\n";
            echo "Backfill completed in {$execution_time} seconds\n";
            echo "Processed: {$processed} dates\n";
            echo "Failed: {$failed} dates\n";
            echo str_repeat('=', 60) . "\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    /**
     * Run chunked daily aggregation for a single date.
     *
     * @param string $date
     * @return bool
     */
    private function run_daily_chunked($date)
    {
        $pv_offset = 0;
        do {
            $result_pv = $this->Analytics_model->aggregate_pageviews_daily_chunked($date, $pv_offset);
            if (empty($result_pv['success'])) {
                return false;
            }
            $pv_offset = $result_pv['offset'] ?? ($pv_offset + 0);
        } while (!empty($result_pv['has_more']));

        $dl_offset = 0;
        do {
            $result_dl = $this->Analytics_model->aggregate_downloads_daily_chunked($date, $dl_offset);
            if (empty($result_dl['success'])) {
                return false;
            }
            $dl_offset = $result_dl['offset'] ?? ($dl_offset + 0);
        } while (!empty($result_dl['has_more']));

        return true;
    }
}

/* End of file Analytics.php */
/* Location: ./application/controllers/cli/Analytics.php */


