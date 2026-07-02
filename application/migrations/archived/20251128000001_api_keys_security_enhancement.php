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
        log_message('info', 'API keys security enhancement migration completed successfully');
    }

    public function down()
    {
        throw new Exception("Rollback not supported - this is a one-way migration. Restore from database backup if needed.");
    }
}

