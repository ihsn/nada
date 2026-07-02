<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: restore surveys.var_keywords column if missing
 * and update fulltext index to include var_keywords (MySQL) or recreate fulltext index on keywords and var_keywords (SQLSRV)
 *
 *
 */
class Migration_Restore_var_keywords_to_surveys extends MY_Migration {

    public function up()
    {
        $driver   = $this->db->dbdriver;
        $is_mysql = in_array($driver, ['mysql', 'mysqli']);

        if ($is_mysql) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Restore_var_keywords_to_surveys: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Restore_var_keywords_to_surveys completed');
    }

    // -------------------------------------------------------------------------

    private function up_mysql()
    {
        // 1. Add column if missing
        $_r   = $this->db->query("SHOW COLUMNS FROM `surveys` LIKE 'var_keywords'");
        $exists = $_r ? $_r->row_array() : null;

        if (!$exists) {
            $this->db->query('ALTER TABLE `surveys` ADD COLUMN `var_keywords` mediumtext DEFAULT NULL AFTER `metadata`');
            log_message('info', 'Added surveys.var_keywords (MySQL)');
        } else {
            log_message('info', 'surveys.var_keywords already exists (MySQL), skipping ADD COLUMN');
        }

        // 2. Drop the narrow index created by 20260320000001 (keywords only)
        $_r     = $this->db->query("SHOW INDEX FROM `surveys` WHERE Key_name = 'ft_keywords'");
        $indexes = $_r ? $_r->result_array() : [];

        if (!empty($indexes)) {
            $this->db->query('ALTER TABLE `surveys` DROP INDEX `ft_keywords`');
            log_message('info', 'Dropped ft_keywords index from surveys');
        }

        // 3. Recreate on (keywords, var_keywords) when missing
        if ($this->index_exists('surveys', 'ft_keywords')) {
            log_message('info', 'ft_keywords index already exists on surveys (MySQL), skipping CREATE');
        } else {
            $this->db->query('ALTER TABLE `surveys` ADD FULLTEXT KEY `ft_keywords` (`keywords`, `var_keywords`)');
            log_message('info', 'Created ft_keywords index on surveys(keywords, var_keywords)');
        }
    }

    // -------------------------------------------------------------------------

    private function up_sqlsrv()
    {
        // 1. Add column if missing
        $_r = $this->db->query("
            SELECT 1
            FROM   sys.columns c
            JOIN   sys.tables  t ON c.object_id = t.object_id
            WHERE  t.name = 'surveys'
            AND    c.name = 'var_keywords'
        ");
        $exists = $_r ? $_r->row_array() : null;

        if (!$exists) {
            $this->db->query('ALTER TABLE surveys ADD var_keywords varchar(max) NULL');
            log_message('info', 'Added surveys.var_keywords (SQLSRV)');
        } else {
            log_message('info', 'surveys.var_keywords already exists (SQLSRV), skipping ADD COLUMN');
        }

        // 2. Drop the narrowed fulltext index
        $_r = $this->db->query("
            SELECT 1
            FROM sys.fulltext_indexes fi
            JOIN sys.tables t ON fi.object_id = t.object_id
            WHERE t.name = 'surveys'
        ");
        $ft_exists = $_r ? $_r->row_array() : null;

        if ($ft_exists) {
            $this->db->query('DROP FULLTEXT INDEX ON surveys');
            log_message('info', 'Dropped fulltext index on surveys (SQLSRV)');
        }

        // 3. Recreate on (keywords, var_keywords) when missing
        $_r_after = $this->db->query("
            SELECT 1
            FROM sys.fulltext_indexes fi
            JOIN sys.tables t ON fi.object_id = t.object_id
            WHERE t.name = 'surveys'
        ");
        $ft_still_exists = $_r_after ? $_r_after->row_array() : null;

        if ($ft_still_exists) {
            log_message('info', 'Fulltext index already exists on surveys (SQLSRV), skipping CREATE');
        } else {
            $this->db->query("
                CREATE FULLTEXT INDEX ON surveys
                (
                    keywords     Language 1033,
                    var_keywords Language 1033
                )
                KEY INDEX pk_idx_surveys
            ");
            log_message('info', 'Created fulltext index on surveys(keywords, var_keywords) (SQLSRV)');
        }
    }

    // -------------------------------------------------------------------------

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
