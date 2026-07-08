<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Pageview tracking: store page_url/section and index session-based dedup.
 */
class Migration_Analytics_pageview_tracking extends MY_Migration {

	public function up()
	{
		$db_driver = $this->db->dbdriver;

		if ($db_driver !== 'mysqli' && $db_driver !== 'sqlsrv') {
			throw new Exception(
				'Analytics pageview migration requires dbdriver mysqli (MySQL) or sqlsrv (SQL Server); got: ' . $db_driver
			);
		}

		if (!$this->db->table_exists('analytics_pageview_events')) {
			log_message('info', 'analytics_pageview_events not found; skipping pageview tracking migration');
			return;
		}

		$this->add_pageview_columns($db_driver);
		$this->replace_pageview_dedup_index($db_driver);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function add_pageview_columns($db_driver)
	{
		if ($db_driver === 'mysqli') {
			if (!$this->column_exists_mysqli('analytics_pageview_events', 'page_url')) {
				$this->db->query("
					ALTER TABLE `analytics_pageview_events`
					ADD COLUMN `page_url` VARCHAR(512) NULL COMMENT 'Client page URL or AJAX request path' AFTER `session_id`
				");
			}

			if (!$this->column_exists_mysqli('analytics_pageview_events', 'section')) {
				$this->db->query("
					ALTER TABLE `analytics_pageview_events`
					ADD COLUMN `section` VARCHAR(100) NULL COMMENT 'Study tab/section for AJAX pageviews' AFTER `page_url`
				");
			}

			return;
		}

		if (!$this->column_exists_sqlsrv('analytics_pageview_events', 'page_url')) {
			$this->db->query("
				ALTER TABLE [analytics_pageview_events]
				ADD [page_url] NVARCHAR(512) NULL
			");
		}

		if (!$this->column_exists_sqlsrv('analytics_pageview_events', 'section')) {
			$this->db->query("
				ALTER TABLE [analytics_pageview_events]
				ADD [section] NVARCHAR(100) NULL
			");
		}
	}

	private function replace_pageview_dedup_index($db_driver)
	{
		if ($db_driver === 'mysqli') {
			$this->db->query("DROP INDEX IF EXISTS `idx_dedup` ON `analytics_pageview_events`");
			$this->db->query("
				CREATE INDEX `idx_dedup` ON `analytics_pageview_events`
				(`study_id`, `session_id`, `section`, `page_url`(191), `ts`)
			");
			return;
		}

		$result = $this->db->query("
			SELECT COUNT(*) as cnt
			FROM sys.indexes
			WHERE object_id = OBJECT_ID('analytics_pageview_events')
			  AND name = 'idx_dedup'
		");

		if ($result && (int) $result->row()->cnt > 0) {
			$this->db->query("DROP INDEX [idx_dedup] ON [analytics_pageview_events]");
		}

		$this->db->query("
			CREATE NONCLUSTERED INDEX [idx_dedup]
			ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [page_url] ASC, [ts] ASC)
		");
	}

	private function column_exists_mysqli($table, $column)
	{
		$result = $this->db->query("
			SELECT COUNT(*) as cnt
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = ?
			  AND COLUMN_NAME = ?
		", array($table, $column));

		return $result && (int) $result->row()->cnt > 0;
	}

	private function column_exists_sqlsrv($table, $column)
	{
		$result = $this->db->query("
			SELECT COUNT(*) as cnt
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = SCHEMA_NAME()
			  AND TABLE_NAME = ?
			  AND COLUMN_NAME = ?
		", array($table, $column));

		return $result && (int) $result->row()->cnt > 0;
	}
}
