<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'migrations/traits/Sitelogs_schema_trait.php');

/**
 * Repairs installs where sitelogs_legacy exists but sitelogs was never created
 * (e.g. migration 20251214000001 applied and 20251214000002 failed silently).
 */
class Migration_Ensure_sitelogs_table extends MY_Migration {

    use Sitelogs_schema_trait;

    public function up()
    {
        log_message('info', 'Migration_Ensure_sitelogs_table::up() called');

        if ($this->db->table_exists('sitelogs')) {
            log_message('info', 'sitelogs table already exists');
            echo "✓ sitelogs table already exists\n";
            return;
        }

        if ($this->db->table_exists('sitelogs_legacy')) {
            $legacy_count = $this->db->count_all('sitelogs_legacy');
            echo "Repairing: sitelogs_legacy exists ({$legacy_count} rows) but sitelogs is missing\n";
            log_message(
                'info',
                'Repairing missing sitelogs table; sitelogs_legacy row count=' . (int) $legacy_count
            );
        } else {
            echo "Creating missing sitelogs table...\n";
        }

        $this->create_sitelogs_table();

        echo "✓ sitelogs table created\n";

        if ($this->db->table_exists('sitelogs_legacy')) {
            $legacy_count = $this->db->count_all('sitelogs_legacy');
            $new_count = $this->db->count_all('sitelogs');
            if ($legacy_count > 0 && $new_count === 0) {
                echo "⚠ Historical rows ({$legacy_count}) remain in sitelogs_legacy.\n";
                echo "  Copy manually or use install/nada56-fix-sitelogs-schema-table-rename-mysql.sql\n";
                echo "  (batch migrate) or utils/sitelogs_export to archive legacy data.\n";
            }
        }
    }

    public function down()
    {
        throw new Exception('Rollback not supported for ensure_sitelogs_table migration.');
    }
}
