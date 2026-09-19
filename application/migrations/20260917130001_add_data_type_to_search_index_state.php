<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: add data_type column to search_index_state
 *
 * search_index_state.object_type is NADA's coarse bucket ('survey'/'citation'),
 * which lumps together every dataset kind — microdata, geospatial, document,
 * timeseries, etc (surveys.type). A per-data-type coverage breakdown needs to
 * know that finer type even after a survey row is deleted (the 'stale' case,
 * where surveys.type is no longer available to join against) — so it's kept
 * here too, resolved from surveys.type at write time.
 *
 * MySQL  : ALTER TABLE search_index_state ADD COLUMN data_type VARCHAR(32) NULL
 * SQLSRV : ALTER TABLE search_index_state ADD data_type VARCHAR(32) NULL
 */
class Migration_Add_data_type_to_search_index_state extends MY_Migration {

    public function up()
    {
        $driver   = $this->db->dbdriver;
        $is_mysql = in_array($driver, ['mysql', 'mysqli']);

        if ($is_mysql) {
            $this->up_mysql();
        } elseif ($driver === 'sqlsrv') {
            $this->up_sqlsrv();
        } else {
            log_message('info', 'Migration_Add_data_type_to_search_index_state: unsupported driver ' . $driver . ', skipping');
        }

        log_message('info', 'Migration_Add_data_type_to_search_index_state completed');
    }

    private function up_mysql()
    {
        if (!$this->db->table_exists('search_index_state')) {
            log_message('info', 'search_index_state does not exist (MySQL), skipping');
            return;
        }

        $_r = $this->db->query("SHOW COLUMNS FROM `search_index_state` LIKE 'data_type'");
        $exists = $_r ? $_r->row_array() : null;

        if ($exists) {
            log_message('info', 'search_index_state.data_type already exists (MySQL), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE `search_index_state` ADD COLUMN `data_type` VARCHAR(32) NULL DEFAULT NULL"
        );
        log_message('info', 'Added search_index_state.data_type VARCHAR(32) (MySQL)');
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
            AND    c.name = 'data_type'
        ");
        $exists = $_r ? $_r->row_array() : null;

        if ($exists) {
            log_message('info', 'search_index_state.data_type already exists (SQLSRV), skipping');
            return;
        }

        $this->db->query(
            "ALTER TABLE search_index_state ADD data_type VARCHAR(32) NULL"
        );
        log_message('info', 'Added search_index_state.data_type VARCHAR(32) (SQLSRV)');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
