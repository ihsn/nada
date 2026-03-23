<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: drop surveys.var_keywords column
 *
 * var_keywords was a denormalized cache of variable name/label/question text
 * stored at the survey level for MySQL fulltext search. It was removed from
 * the fulltext index in 20260320000001 and is no longer queried anywhere.
 * OpenSearch derives the same data directly from the variables table at
 * index time — the column is pure write-only dead weight.
 */
class Migration_Drop_var_keywords_from_surveys extends MY_Migration {

    public function up()
    {
        $driver   = $this->db->dbdriver;
        $is_mysql = in_array($driver, ['mysql', 'mysqli']);

        if ($is_mysql) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Drop_var_keywords_from_surveys: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Drop_var_keywords_from_surveys completed');
    }

    private function up_mysql()
    {
        $_r = $this->db->query("SHOW COLUMNS FROM `surveys` LIKE 'var_keywords'");
        $exists = $_r ? $_r->row_array() : null;

        if (!$exists) {
            log_message('info', 'surveys.var_keywords does not exist (MySQL), skipping');
            return;
        }

        $this->db->query('ALTER TABLE `surveys` DROP COLUMN `var_keywords`');
        log_message('info', 'Dropped surveys.var_keywords (MySQL)');
    }

    private function up_sqlsrv()
    {
        $_r = $this->db->query("
            SELECT 1
            FROM   sys.columns c
            JOIN   sys.tables  t ON c.object_id = t.object_id
            WHERE  t.name = 'surveys'
            AND    c.name = 'var_keywords'
        ");
        $exists = $_r ? $_r->row_array() : null;

        if (!$exists) {
            log_message('info', 'surveys.var_keywords does not exist (SQLSRV), skipping');
            return;
        }

        $this->db->query('ALTER TABLE surveys DROP COLUMN var_keywords');
        log_message('info', 'Dropped surveys.var_keywords (SQLSRV)');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
