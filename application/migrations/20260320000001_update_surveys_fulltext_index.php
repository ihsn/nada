<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: remove var_keywords from surveys fulltext index
 *
 * Study search now operates exclusively on study-level metadata.
 * Variable search uses the variables table / nada_variables index directly.
 *
 * MySQL  : drops ft_keywords (keywords, var_keywords) and recreates on (keywords) only.
 * SQLSRV : drops and recreates the surveys fulltext index without var_keywords.
 */
class Migration_Update_surveys_fulltext_index extends MY_Migration {

    public function up()
    {
        $driver = $this->db->dbdriver;

        if (in_array($driver, ['mysql', 'mysqli'])) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Update_surveys_fulltext_index: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Update_surveys_fulltext_index completed');
    }

    private function up_mysql()
    {
        // Check the index exists before dropping to make migration re-runnable
        $indexes = $this->db->query('SHOW INDEX FROM surveys WHERE Key_name = ?', ['ft_keywords'])->result_array();

        if (!empty($indexes)) {
            $this->db->query('ALTER TABLE `surveys` DROP INDEX `ft_keywords`');
            log_message('info', 'Dropped ft_keywords index from surveys');
        }

        $this->db->query('ALTER TABLE `surveys` ADD FULLTEXT KEY `ft_keywords` (`keywords`)');
        log_message('info', 'Created ft_keywords index on surveys(keywords)');
    }

    private function up_sqlsrv()
    {
        // Check if a fulltext index exists on surveys
        $exists = $this->db->query("
            SELECT 1
            FROM sys.fulltext_indexes fi
            JOIN sys.tables t ON fi.object_id = t.object_id
            WHERE t.name = 'surveys'
        ")->row_array();

        if ($exists) {
            $this->db->query('DROP FULLTEXT INDEX ON surveys');
            log_message('info', 'Dropped fulltext index on surveys');
        }

        $this->db->query("
            CREATE FULLTEXT INDEX ON surveys
            (
                keywords Language 1033
            )
            KEY INDEX pk_idx_surveys
        ");
        log_message('info', 'Created fulltext index on surveys(keywords)');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
