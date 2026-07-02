<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * 
 * Seeds analytics_legacy_counts and year=0, month=0 rows in analytics_monthly_studies
 * from surveys.total_views / total_downloads. Idempotent: uses Analytics_model checks
 * and bulk SQL that only inserts missing rows.
 *
 */
class Migration_Migrate_analytics_legacy_totals extends MY_Migration {

    /** @return void */
    private function require_supported_driver($db_driver)
    {
        if ($db_driver !== 'mysqli' && $db_driver !== 'sqlsrv') {
            throw new Exception(
                'Legacy analytics totals migration requires dbdriver mysqli (MySQL) or sqlsrv (SQL Server); got: ' . $db_driver
            );
        }
    }

    public function up()
    {
        log_message('info', 'Migration_Migrate_analytics_legacy_totals::up() called');

        $this->require_supported_driver($this->db->dbdriver);

        if (!$this->db->table_exists('analytics_monthly_studies')) {
            log_message('info', 'analytics_monthly_studies missing; skip legacy totals (run create analytics schema first)');
            echo "Skipping legacy totals: analytics_monthly_studies does not exist.\n";
            return;
        }

        if (!$this->db->table_exists('surveys')) {
            log_message('info', 'surveys table missing; skip legacy totals migration');
            echo "Skipping legacy totals: surveys table does not exist.\n";
            return;
        }

        if (!$this->db->table_exists('analytics_legacy_counts')) {
            log_message('info', 'analytics_legacy_counts missing; skip (schema incomplete)');
            echo "Skipping legacy totals: analytics_legacy_counts does not exist.\n";
            return;
        }

        $this->load->model('Analytics_model', 'analytics_model');

        if (!$this->analytics_model->needs_legacy_migration()) {
            log_message('info', 'Legacy analytics totals already migrated or nothing to migrate');
            echo "Legacy totals: nothing to do (already migrated or no legacy counts).\n";
            return;
        }

        echo "Migrating legacy survey totals into analytics...\n";
        $result = $this->analytics_model->migrate_legacy_counts();

        if (empty($result['success'])) {
            $err = !empty($result['errors']) ? implode('; ', $result['errors']) : 'Unknown error';
            log_message('error', 'migrate_legacy_counts failed: ' . $err);
            throw new Exception('Legacy totals migration failed: ' . $err);
        }

        echo 'Backed up surveys: ' . (int) $result['backed_up'] . "\n";
        echo 'Seeded legacy total rows: ' . (int) $result['migrated'] . "\n";
        log_message('info', 'Legacy totals migration done: backed_up=' . (int) $result['backed_up'] . ', migrated=' . (int) $result['migrated']);
    }

    public function down()
    {
        throw new Exception('Rollback not supported.');
    }
}
