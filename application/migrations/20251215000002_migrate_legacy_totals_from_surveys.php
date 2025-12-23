<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Migrate_legacy_totals_from_surveys extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Migrate_legacy_totals_from_surveys::up() called');
        
        $db_driver = $this->db->dbdriver;
        $is_mysqli = ($db_driver === 'mysqli');
        $is_sqlsrv = ($db_driver === 'sqlsrv');
        
        // Check if analytics_monthly_studies table exists
        if (!$this->db->table_exists('analytics_monthly_studies')) {
            log_message('info', 'analytics_monthly_studies table does not exist, skipping migration');
            echo "analytics_monthly_studies table does not exist, skipping migration\n";
            return;
        }
        
        // Check if surveys table exists
        if (!$this->db->table_exists('surveys')) {
            log_message('info', 'surveys table does not exist, skipping migration');
            echo "surveys table does not exist, skipping migration\n";
            return;
        }


        //make a backup of survey table total columns
        $this->db->query("CREATE TABLE surveys_backup_totals AS SELECT id, total_views, total_downloads FROM surveys");
        log_message('info', 'Created backup of surveys table total columns');
        echo "Created backup of surveys table total columns to surveys_backup_totals table\n";

        
        // Check if migration has already been completed
        // Look for any existing all-time totals (year=0, month=0) that match surveys data
        $check_migrated = $this->db->query("
            SELECT COUNT(*) as cnt
            FROM analytics_monthly_studies ams
            INNER JOIN surveys s ON ams.study_id = s.id
            WHERE ams.year = 0 AND ams.month = 0
            AND (ams.pageviews = s.total_views OR ams.downloads = s.total_downloads)
            LIMIT 1
        ");
        
        if ($check_migrated && $check_migrated->num_rows() > 0) {
            $row = $check_migrated->row();
            if ($row->cnt > 0) {
                log_message('info', 'Legacy totals migration appears to have already been completed');
                echo "Migration appears to have already been completed (found matching all-time totals)\n";
                echo "Skipping migration to avoid duplicate data.\n";
                echo "If you need to re-run, manually clear all-time totals (year=0, month=0) first.\n";
                echo str_repeat('=', 60) . "\n";
                return;
            }
        }
        
        log_message('info', 'Starting migration of legacy totals from surveys table');
        echo "Migrating legacy totals from surveys table to analytics...\n";
        echo str_repeat('=', 60) . "\n";
        
        $migrated = 0;
        $errors = array();
        
        try {
            // Get surveys with non-zero totals
            $this->db->select('s.id as study_id, s.total_views, s.total_downloads');
            $this->db->from('surveys s');
            $this->db->where('(s.total_views > 0 OR s.total_downloads > 0)', null, false);
            $query = $this->db->get();
            
            if (!$query) {
                $db_error = $this->db->error();
                $error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
                log_message('error', 'Failed to fetch surveys: ' . $error_msg);
                echo "✗ Failed to fetch surveys: {$error_msg}\n";
                throw new Exception("Failed to fetch surveys: " . $error_msg);
            }
            
            if ($query->num_rows() === 0) {
                log_message('info', 'No surveys with non-zero totals found');
                echo "✓ No surveys with non-zero totals found (nothing to migrate)\n";
                echo str_repeat('=', 60) . "\n";
                return;
            }
            
            $total_surveys = $query->num_rows();
            echo "Found {$total_surveys} surveys with non-zero totals\n";
            echo str_repeat('-', 60) . "\n";
            
            // Process in batches for better performance
            $batch_size = 500;
            $offset = 0;
            
            while ($offset < $total_surveys) {
                $this->db->select('s.id as study_id, s.total_views, s.total_downloads');
                $this->db->from('surveys s');
                $this->db->where('(s.total_views > 0 OR s.total_downloads > 0)', null, false);
                $this->db->limit($batch_size, $offset);
                $batch_query = $this->db->get();
                
                if (!$batch_query || $batch_query->num_rows() === 0) {
                    break;
                }
                
                foreach ($batch_query->result_array() as $row) {
                    // Check if all-time totals already exist for this study
                    $this->db->where('year', 0);
                    $this->db->where('month', 0);
                    $this->db->where('study_id', $row['study_id']);
                    $existing = $this->db->get('analytics_monthly_studies')->row();
                    
                    $data = array(
                        'year' => 0,
                        'month' => 0,
                        'study_id' => $row['study_id'],
                        'pageviews' => (int)$row['total_views'],
                        'unique_visitors' => 0, // We don't have this data from surveys table
                        'downloads' => (int)$row['total_downloads']
                    );
                    
                    if ($existing) {
                        // Update existing row - overwrite with legacy totals
                        $this->db->where('year', 0);
                        $this->db->where('month', 0);
                        $this->db->where('study_id', $row['study_id']);
                        $update_result = $this->db->update('analytics_monthly_studies', array(
                            'pageviews' => (int)$row['total_views'],
                            'downloads' => (int)$row['total_downloads']
                            // Note: unique_visitors stays as is (don't overwrite with 0 if it has a value)
                        ));
                    } else {
                        // Insert new row
                        $update_result = $this->db->insert('analytics_monthly_studies', $data);
                    }
                    
                    if ($update_result) {
                        $migrated++;
                    } else {
                        $db_error = $this->db->error();
                        $error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
                        $errors[] = "Failed to migrate study_id {$row['study_id']}: " . $error_msg;
                        log_message('error', "Failed to migrate study_id {$row['study_id']}: " . $error_msg);
                    }
                }
                
                $offset += $batch_size;
                
                // Progress indicator
                if ($migrated % 1000 == 0) {
                    echo "  Migrated: {$migrated} studies...\n";
                }
            }
            
            echo str_repeat('-', 60) . "\n";
            echo "✓ Migration completed\n";
            echo "  Migrated: {$migrated} studies\n";
            
            if (!empty($errors)) {
                echo "  Errors: " . count($errors) . "\n";
                log_message('error', 'Migration completed with ' . count($errors) . ' errors');
                // Log first 10 errors
                foreach (array_slice($errors, 0, 10) as $error) {
                    log_message('error', 'Migration error: ' . $error);
                }
            }
            
            echo str_repeat('=', 60) . "\n";
            log_message('info', "Migration completed: {$migrated} studies migrated");
            
        } catch (Exception $e) {
            log_message('error', 'Migration exception: ' . $e->getMessage());
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down()
    {
        throw new Exception("Rollback not supported.");
    }
}
