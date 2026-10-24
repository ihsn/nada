<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Api_keys_security_enhancement extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Api_keys_security_enhancement::up() called');
        
        $sql_file = $this->get_sql_file_path('nada56-api-keys-security');
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found: " . $sql_file);
        }
        
        log_message('info', 'Starting API keys security enhancement migration...');
        $this->execute_sql_file($sql_file);
        $this->fix_api_keys_unique_index_sqlsrv();
        log_message('info', 'API keys security enhancement migration completed successfully');
    }

    /**
     * SQL Server: replace IX_api_keys (unique on api_key) with a filtered index.
     * Idempotent so re-runs are safe if the SQL file steps were partially applied.
     */
    protected function fix_api_keys_unique_index_sqlsrv()
    {
        if ($this->db->dbdriver !== 'sqlsrv' || !$this->table_exists('api_keys')) {
            return;
        }

        if ($this->index_exists('api_keys', 'IX_api_keys')) {
            $this->db->query('DROP INDEX [IX_api_keys] ON [api_keys]');
        }

        if (!$this->index_exists('api_keys', 'IX_api_keys_legacy')) {
            $this->db->query(
                'CREATE UNIQUE NONCLUSTERED INDEX [IX_api_keys_legacy] ON [api_keys]([api_key] ASC)
                WHERE [api_key] IS NOT NULL'
            );
        }
    }

    public function down()
    {
        throw new Exception("Rollback not supported - this is a one-way migration. Restore from database backup if needed.");
    }
}

