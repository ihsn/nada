<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * 
 * Installs analytics tables from install/analytics-schema-*.sql.
 * Ensures monthly finalized columns when missing.
 *
 * 
 */
class Migration_Create_analytics_schema extends MY_Migration {

    /** @return void */
    private function require_supported_driver($db_driver)
    {
        if ($db_driver !== 'mysqli' && $db_driver !== 'sqlsrv') {
            throw new Exception(
                'Analytics schema migration requires dbdriver mysqli (MySQL) or sqlsrv (SQL Server); got: ' . $db_driver
            );
        }
    }

    public function up()
    {
        log_message('info', 'Migration_Create_analytics_schema::up() called');

        $db_driver = $this->db->dbdriver;
        $this->require_supported_driver($db_driver);

        $this->create_tables($db_driver);
        $this->check_and_update_schema($db_driver);
        $this->ensure_monthly_finalized_columns($db_driver);
    }

    private function check_and_update_schema($db_driver)
    {
        $needs_update = false;

        if ($db_driver === 'mysqli') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'analytics_download_events'
                AND COLUMN_NAME = 'file_id'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $needs_update = ($row->cnt > 0);
            }
        } elseif ($db_driver === 'sqlsrv') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = SCHEMA_NAME()
                AND TABLE_NAME = 'analytics_download_events'
                AND COLUMN_NAME = 'file_id'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $needs_update = ($row->cnt > 0);
            }
        }

        if ($needs_update) {
            log_message('info', 'Detected old analytics schema (file_id), applying updates...');
            $this->update_old_schema($db_driver);
        } else {
            log_message('info', 'Analytics download schema is current (no file_id rename needed)');
        }
    }

    private function update_old_schema($db_driver)
    {
        if ($db_driver === 'mysqli') {
            $this->db->query("ALTER TABLE `analytics_download_events` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");
            $this->db->query("ALTER TABLE `analytics_daily_files` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");
            $this->db->query("ALTER TABLE `analytics_monthly_files` CHANGE COLUMN `file_id` `file_name` VARCHAR(255) NOT NULL");

            $this->db->query("DROP INDEX IF EXISTS `idx_study_file` ON `analytics_download_events`");
            $this->db->query("DROP INDEX IF EXISTS `idx_ts_study_file` ON `analytics_download_events`");
            $this->db->query("CREATE INDEX `idx_study_file` ON `analytics_download_events` (`study_id`, `file_name`)");
            $this->db->query("CREATE INDEX `idx_ts_study_file` ON `analytics_download_events` (`ts`, `study_id`, `file_name`)");

            $this->db->query("ALTER TABLE `analytics_pageview_events` MODIFY COLUMN `user_agent` VARCHAR(200) NULL");
            $this->db->query("ALTER TABLE `analytics_download_events` MODIFY COLUMN `user_agent` VARCHAR(200) NULL");

            $this->db->query("UPDATE `analytics_pageview_events` SET `user_agent` = LEFT(`user_agent`, 200) WHERE LENGTH(`user_agent`) > 200");
            $this->db->query("UPDATE `analytics_download_events` SET `user_agent` = LEFT(`user_agent`, 200) WHERE LENGTH(`user_agent`) > 200");
        } elseif ($db_driver === 'sqlsrv') {
            $this->db->query("EXEC sp_rename 'analytics_download_events.file_id', 'file_name', 'COLUMN'");
            $this->db->query("EXEC sp_rename 'analytics_daily_files.file_id', 'file_name', 'COLUMN'");
            $this->db->query("EXEC sp_rename 'analytics_monthly_files.file_id', 'file_name', 'COLUMN'");

            $this->db->query("DROP INDEX IF EXISTS idx_study_file ON analytics_download_events");
            $this->db->query("DROP INDEX IF EXISTS idx_ts_study_file ON analytics_download_events");
            $this->db->query("CREATE INDEX idx_study_file ON analytics_download_events (study_id, file_name)");
            $this->db->query("CREATE INDEX idx_ts_study_file ON analytics_download_events (ts, study_id, file_name)");

            $this->db->query("ALTER TABLE analytics_pageview_events ALTER COLUMN [user_agent] nvarchar(200) NULL");
            $this->db->query("ALTER TABLE analytics_download_events ALTER COLUMN [user_agent] nvarchar(200) NULL");

            $this->db->query("UPDATE analytics_pageview_events SET [user_agent] = LEFT([user_agent], 200) WHERE LEN([user_agent]) > 200");
            $this->db->query("UPDATE analytics_download_events SET [user_agent] = LEFT([user_agent], 200) WHERE LEN([user_agent]) > 200");
        }

        log_message('info', 'Analytics old schema updates completed');
    }

    /**
     * Older installs may have monthly tables without finalized columns (predates current SQL file).
     */
    private function ensure_monthly_finalized_columns($db_driver)
    {
        if (!$this->db->table_exists('analytics_monthly_studies') || !$this->db->table_exists('analytics_monthly_files')) {
            return;
        }

        $column_exists_studies = $this->column_exists('analytics_monthly_studies', 'finalized', $db_driver);
        $column_exists_files = $this->column_exists('analytics_monthly_files', 'finalized', $db_driver);

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
            log_message('info', 'Added finalized columns to analytics_monthly_studies');
            echo "Added finalized columns to analytics_monthly_studies\n";
        }

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
            log_message('info', 'Added finalized columns to analytics_monthly_files');
            echo "Added finalized columns to analytics_monthly_files\n";
        }
    }

    private function column_exists($table, $column, $db_driver)
    {
        if ($db_driver === 'mysqli') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = " . $this->db->escape($table) . "
                AND COLUMN_NAME = " . $this->db->escape($column) . "
            ");
        } elseif ($db_driver === 'sqlsrv') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = SCHEMA_NAME()
                AND TABLE_NAME = " . $this->db->escape($table) . "
                AND COLUMN_NAME = " . $this->db->escape($column) . "
            ");
        } else {
            return false;
        }

        if ($result && $result->num_rows() > 0) {
            return ((int) $result->row()->cnt) > 0;
        }

        return false;
    }

    private function create_tables($db_driver)
    {
        if ($db_driver === 'sqlsrv') {
            $sql_file = FCPATH . 'install/analytics-schema-sqlsrv.sql';
        } elseif ($db_driver === 'mysqli') {
            $sql_file = FCPATH . 'install/analytics-schema-mysql.sql';
        } else {
            throw new Exception('Unsupported database driver for analytics schema: ' . $db_driver . ' (use mysqli or sqlsrv)');
        }

        if (!file_exists($sql_file)) {
            throw new Exception('SQL file not found: ' . $sql_file);
        }

        log_message('info', 'Executing analytics schema SQL file: ' . $sql_file);
        $this->execute_sql_file($sql_file);
        log_message('info', 'Analytics schema SQL completed');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from backup if needed.');
    }
}
