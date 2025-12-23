<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Create_analytics_tables extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Create_analytics_tables::up() called');
        
        $db_driver = $this->db->dbdriver;
        
        $needs_update = false;
        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $result = $this->db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'analytics_download_events' AND COLUMN_NAME = 'file_id'");
            if ($result) {
                $row = $result->row();
                $needs_update = $row && $row->cnt > 0;
            }
        } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
            $result = $this->db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'analytics_download_events' AND COLUMN_NAME = 'file_id'");
            if ($result) {
                $row = $result->row();
                $needs_update = $row && $row->cnt > 0;
            }
        }
        
        if ($needs_update) {
            log_message('info', 'Detected old schema, applying updates (file_id -> file_name, user_agent truncation)...');
            
            if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
                $this->db->query("ALTER TABLE `analytics_download_events` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");
                $this->db->query("ALTER TABLE `analytics_daily_files` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");
                $this->db->query("ALTER TABLE `analytics_monthly_files` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");
                
                $this->db->query("DROP INDEX IF EXISTS `idx_study_file` ON `analytics_download_events`");
                $this->db->query("DROP INDEX IF EXISTS `idx_ts_study_file` ON `analytics_download_events`");
                $this->db->query("CREATE INDEX `idx_study_file` ON `analytics_download_events` (`study_id`, `file_name`)");
                $this->db->query("CREATE INDEX `idx_ts_study_file` ON `analytics_download_events` (`ts`, `study_id`, `file_name`)");
                
                $this->db->query("DROP INDEX IF EXISTS `idx_file` ON `analytics_daily_files`");
                $this->db->query("CREATE INDEX `idx_file` ON `analytics_daily_files` (`file_name`)");
                
                $this->db->query("DROP INDEX IF EXISTS `idx_file` ON `analytics_monthly_files`");
                $this->db->query("CREATE INDEX `idx_file` ON `analytics_monthly_files` (`file_name`)");
                
                $this->db->query("ALTER TABLE `analytics_pageview_events` MODIFY COLUMN `user_agent` VARCHAR(200) NULL");
                $this->db->query("ALTER TABLE `analytics_download_events` MODIFY COLUMN `user_agent` VARCHAR(200) NULL");
                
                $this->db->query("UPDATE `analytics_pageview_events` SET `user_agent` = LEFT(`user_agent`, 200) WHERE LENGTH(`user_agent`) > 200");
                $this->db->query("UPDATE `analytics_download_events` SET `user_agent` = LEFT(`user_agent`, 200) WHERE LENGTH(`user_agent`) > 200");
            } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
                $this->db->query("EXEC sp_rename 'analytics_download_events.file_id', 'file_name', 'COLUMN'");
                $this->db->query("EXEC sp_rename 'analytics_daily_files.file_id', 'file_name', 'COLUMN'");
                $this->db->query("EXEC sp_rename 'analytics_monthly_files.file_id', 'file_name', 'COLUMN'");
                
                $this->db->query("DROP INDEX IF EXISTS idx_study_file ON analytics_download_events");
                $this->db->query("DROP INDEX IF EXISTS idx_ts_study_file ON analytics_download_events");
                $this->db->query("CREATE INDEX idx_study_file ON analytics_download_events (study_id, file_name)");
                $this->db->query("CREATE INDEX idx_ts_study_file ON analytics_download_events (ts, study_id, file_name)");
                
                $this->db->query("DROP INDEX IF EXISTS idx_file ON analytics_daily_files");
                $this->db->query("CREATE INDEX idx_file ON analytics_daily_files (file_name)");
                
                $this->db->query("DROP INDEX IF EXISTS idx_file ON analytics_monthly_files");
                $this->db->query("CREATE INDEX idx_file ON analytics_monthly_files (file_name)");
                
                $this->db->query("ALTER TABLE analytics_pageview_events ALTER COLUMN [user_agent] nvarchar(200) NULL");
                $this->db->query("ALTER TABLE analytics_download_events ALTER COLUMN [user_agent] nvarchar(200) NULL");
                
                $this->db->query("UPDATE analytics_pageview_events SET [user_agent] = LEFT([user_agent], 200) WHERE LEN([user_agent]) > 200");
                $this->db->query("UPDATE analytics_download_events SET [user_agent] = LEFT([user_agent], 200) WHERE LEN([user_agent]) > 200");
            }
            
            log_message('info', 'Schema updates completed successfully');
        } else {
            $sql_file = $this->get_sql_file_path('analytics-schema');
            
            if (!file_exists($sql_file)) {
                throw new Exception("SQL file not found: " . $sql_file);
            }
            
            log_message('info', 'Starting analytics tables migration...');
            $this->execute_sql_file($sql_file);
            log_message('info', 'Analytics tables migration completed successfully');
        }
    }

    public function down()
    {
        throw new Exception("Rollback not supported - this is a one-way migration. Restore from database backup if needed.");
    }
}

