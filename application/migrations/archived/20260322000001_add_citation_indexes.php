<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Citation search and batch-loading indexes.
 * Runs install/nada56-citation-indexes-{mysql,sqlsrv}.sql via execute_sql_file (idempotent).
 */
class Migration_Add_citation_indexes extends MY_Migration {

    public function up()
    {
        $driver = $this->db->dbdriver;

        if ($driver !== 'mysqli' && $driver !== 'sqlsrv') {
            log_message('info', 'Migration_Add_citation_indexes: unsupported driver ' . $driver . ', skipping');
            return;
        }

        $sql_file = $this->get_sql_file_path('nada56-citation-indexes');

        if (!file_exists($sql_file)) {
            throw new Exception('SQL file not found: ' . $sql_file);
        }

        log_message('info', 'Migration_Add_citation_indexes: executing ' . basename($sql_file));
        $this->execute_sql_file($sql_file);
        log_message('info', 'Migration_Add_citation_indexes: completed');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
