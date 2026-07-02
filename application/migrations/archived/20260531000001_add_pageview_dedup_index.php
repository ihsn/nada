<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Adds a composite index on analytics_pageview_events (study_id, hashed_ip, ts)
 * to support efficient server-side pageview deduplication lookups.
 */
class Migration_Add_pageview_dedup_index extends MY_Migration {

    public function up()
    {
        $db_driver = $this->db->dbdriver;

        if ($db_driver === 'mysqli') {
            // Only add the index if it does not already exist
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'analytics_pageview_events'
                  AND INDEX_NAME   = 'idx_dedup'
            ");
            if ($result && $result->row()->cnt == 0) {
                $this->db->query("
                    ALTER TABLE `analytics_pageview_events`
                    ADD INDEX `idx_dedup` (`study_id`, `hashed_ip`, `ts`)
                ");
            }
        } elseif ($db_driver === 'sqlsrv') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt
                FROM sys.indexes
                WHERE object_id = OBJECT_ID('analytics_pageview_events')
                  AND name = 'idx_dedup'
            ");
            if ($result && $result->row()->cnt == 0) {
                $this->db->query("
                    CREATE NONCLUSTERED INDEX [idx_dedup]
                    ON [analytics_pageview_events] ([study_id] ASC, [hashed_ip] ASC, [ts] ASC)
                ");
            }
        }
    }

    public function down()
    {
        $db_driver = $this->db->dbdriver;

        if ($db_driver === 'mysqli') {
            $this->db->query("ALTER TABLE `analytics_pageview_events` DROP INDEX IF EXISTS `idx_dedup`");
        } elseif ($db_driver === 'sqlsrv') {
            $this->db->query("DROP INDEX IF EXISTS [idx_dedup] ON [analytics_pageview_events]");
        }
    }
}
