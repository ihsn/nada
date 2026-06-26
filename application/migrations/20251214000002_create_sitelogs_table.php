<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'migrations/traits/Sitelogs_schema_trait.php');

class Migration_Create_sitelogs_table extends MY_Migration {

    use Sitelogs_schema_trait;

    public function up()
    {
        log_message('info', 'Migration_Create_sitelogs_table::up() called');

        if ($this->db->table_exists('sitelogs')) {
            log_message('info', 'sitelogs table already exists, skipping creation');
            echo "⚠ sitelogs table already exists, skipping creation\n";
            return;
        }

        if ($this->db->table_exists('sitelogs_legacy')) {
            echo "Creating new sitelogs table (legacy data remains in sitelogs_legacy)...\n";
        } else {
            echo "Creating new sitelogs table...\n";
        }

        log_message('info', 'Creating new sitelogs table with optimized schema');
        $this->create_sitelogs_table();

        log_message('info', 'Successfully created new sitelogs table');
        echo "✓ Successfully created new sitelogs table with optimized schema\n";
    }

    public function down()
    {
        log_message('info', 'Migration_Create_sitelogs_table::down() called');

        if ($this->db->table_exists('sitelogs_legacy')) {
            log_message('info', 'sitelogs_legacy exists, refusing to drop sitelogs in rollback');
            throw new Exception('Cannot drop sitelogs table - sitelogs_legacy exists. Manual intervention required.');
        }

        if ($this->db->table_exists('sitelogs')) {
            log_message('info', 'Dropping sitelogs table');
            $this->dbforge->drop_table('sitelogs', TRUE);
            log_message('info', 'Successfully dropped sitelogs table');
        }
    }
}
