<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Add_finalized_to_analytics_monthly extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Add_finalized_to_analytics_monthly::up() called');
        
        $db_driver = $this->db->dbdriver;
        
        // Check if column already exists
        $column_exists_studies = false;
        $column_exists_files = false;
        
        if ($db_driver === 'mysqli') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'analytics_monthly_studies' 
                AND COLUMN_NAME = 'finalized'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $column_exists_studies = ($row->cnt > 0);
            }
            
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'analytics_monthly_files' 
                AND COLUMN_NAME = 'finalized'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $column_exists_files = ($row->cnt > 0);
            }
        } elseif ($db_driver === 'sqlsrv') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'analytics_monthly_studies' 
                AND COLUMN_NAME = 'finalized'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $column_exists_studies = ($row->cnt > 0);
            }
            
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'analytics_monthly_files' 
                AND COLUMN_NAME = 'finalized'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $column_exists_files = ($row->cnt > 0);
            }
        }
        
        // Add finalized column to analytics_monthly_studies
        if (!$column_exists_studies) {
            if ($db_driver === 'mysqli') {
                $this->db->query("
                    ALTER TABLE `analytics_monthly_studies` 
                    ADD COLUMN `finalized` TINYINT(1) NOT NULL DEFAULT 0,
                    ADD COLUMN `finalized_at` DATETIME NULL DEFAULT NULL
                ");
            } elseif ($db_driver === 'sqlsrv') {
                $this->db->query("
                    ALTER TABLE analytics_monthly_studies 
                    ADD finalized TINYINT NOT NULL DEFAULT 0,
                    finalized_at DATETIME NULL
                ");
            }
            log_message('info', 'Added finalized column to analytics_monthly_studies');
            echo "Added finalized column to analytics_monthly_studies\n";
        } else {
            log_message('info', 'finalized column already exists in analytics_monthly_studies');
            echo "finalized column already exists in analytics_monthly_studies\n";
        }
        
        // Add finalized column to analytics_monthly_files
        if (!$column_exists_files) {
            if ($db_driver === 'mysqli') {
                $this->db->query("
                    ALTER TABLE `analytics_monthly_files` 
                    ADD COLUMN `finalized` TINYINT(1) NOT NULL DEFAULT 0,
                    ADD COLUMN `finalized_at` DATETIME NULL DEFAULT NULL
                ");
            } elseif ($db_driver === 'sqlsrv') {
                $this->db->query("
                    ALTER TABLE analytics_monthly_files 
                    ADD finalized TINYINT NOT NULL DEFAULT 0,
                    finalized_at DATETIME NULL
                ");
            }
            log_message('info', 'Added finalized column to analytics_monthly_files');
            echo "Added finalized column to analytics_monthly_files\n";
        } else {
            log_message('info', 'finalized column already exists in analytics_monthly_files');
            echo "finalized column already exists in analytics_monthly_files\n";
        }
    }

    public function down()
    {
        throw new Exception("Rollback not supported.");
    }
}
