<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: add last_error column to search_index_state
 *
 * Mirrors search_index_queue.last_error — the bulk/full-reindex reporting
 * path (Search_index_manager::bulk_set_state()) previously recorded only
 * that an object failed to index, not why. This lets it store the same
 * truncated error message the queue-ack path already keeps.
 *
 * MySQL  : ALTER TABLE search_index_state ADD COLUMN last_error VARCHAR(500) NULL
 * SQLSRV : ALTER TABLE search_index_state ADD last_error VARCHAR(500) NULL
 */
class Migration_Add_last_error_to_search_index_state extends MY_Migration {

    public function up()
    {
        $driver   = $this->db->dbdriver;
        $is_mysql = in_array($driver, ['mysql', 'mysqli']);

        if ($is_mysql) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Add_last_error_to_search_index_state: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Add_last_error_to_search_index_state completed');
    }

    private function up_mysql()
    {
        if (!$this->db->table_exists('search_index_state')) {
            log_message('info', 'search_index_state does not exist (MySQL), skipping');
            return;
        }

        $_r = $this->db->query("SHOW COLUMNS FROM `search_index_state` LIKE 'last_error'");
        $exists = $_r ? $_r->row_array() : null;

        if ($exists) {
            log_message('info', 'search_index_state.last_error already exists (MySQL), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE `search_index_state` ADD COLUMN `last_error` VARCHAR(500) NULL DEFAULT NULL"
        );
        log_message('info', 'Added search_index_state.last_error VARCHAR(500) (MySQL)');
    }

    private function up_sqlsrv()
    {
        if (!$this->db->table_exists('search_index_state')) {
            log_message('info', 'search_index_state does not exist (SQLSRV), skipping');
            return;
        }

        $_r = $this->db->query("
            SELECT 1
            FROM   sys.columns c
            JOIN   sys.tables  t ON c.object_id = t.object_id
            WHERE  t.name = 'search_index_state'
            AND    c.name = 'last_error'
        ");
        $exists = $_r ? $_r->row_array() : null;

        if ($exists) {
            log_message('info', 'search_index_state.last_error already exists (SQLSRV), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE search_index_state ADD last_error VARCHAR(500) NULL"
        );
        log_message('info', 'Added search_index_state.last_error VARCHAR(500) (SQLSRV)');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
