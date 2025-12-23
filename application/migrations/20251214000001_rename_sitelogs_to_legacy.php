<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Rename_sitelogs_to_legacy extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Rename_sitelogs_to_legacy::up() called');
        
        $db_driver = $this->db->dbdriver;
        
        // Check if sitelogs table exists and sitelogs_legacy doesn't exist
        $sitelogs_exists = $this->db->table_exists('sitelogs');
        $legacy_exists = $this->db->table_exists('sitelogs_legacy');
        
        if (!$sitelogs_exists) {
            log_message('info', 'sitelogs table does not exist, skipping rename');
            echo "⚠ sitelogs table does not exist, skipping rename\n";
            return;
        }
        
        if ($legacy_exists) {
            log_message('info', 'sitelogs_legacy table already exists, skipping rename');
            echo "⚠ sitelogs_legacy table already exists, skipping rename\n";
            return;
        }
        
        // Rename table
        log_message('info', 'Renaming sitelogs to sitelogs_legacy');
        echo "Renaming sitelogs to sitelogs_legacy...\n";
        
        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $this->db->query("RENAME TABLE sitelogs TO sitelogs_legacy");
        } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
            $this->db->query("EXEC sp_rename 'sitelogs', 'sitelogs_legacy'");
        } else {
            throw new Exception("Unsupported database driver: " . $db_driver);
        }
        
        log_message('info', 'Successfully renamed sitelogs to sitelogs_legacy');
        echo "✓ Successfully renamed sitelogs to sitelogs_legacy\n";
    }

    public function down()
    {
        log_message('info', 'Migration_Rename_sitelogs_to_legacy::down() called');
        
        $db_driver = $this->db->dbdriver;
        
        // Check if sitelogs_legacy exists and sitelogs doesn't exist
        $legacy_exists = $this->db->table_exists('sitelogs_legacy');
        $sitelogs_exists = $this->db->table_exists('sitelogs');
        
        if (!$legacy_exists) {
            log_message('info', 'sitelogs_legacy table does not exist, skipping rollback');
            return;
        }
        
        if ($sitelogs_exists) {
            log_message('info', 'sitelogs table already exists, skipping rollback');
            return;
        }
        
        // Rename back
        log_message('info', 'Rolling back: renaming sitelogs_legacy to sitelogs');
        
        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $this->db->query("RENAME TABLE sitelogs_legacy TO sitelogs");
        } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
            $this->db->query("EXEC sp_rename 'sitelogs_legacy', 'sitelogs'");
        }
        
        log_message('info', 'Successfully rolled back rename');
    }
}
