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

	/**
	 * Key columns of idx_dedup in order, or an empty array when the index does not exist.
	 */
	private function dedup_index_columns($db_driver)
	{
		if ($db_driver === 'mysqli') {
			$result = $this->db->query("
				SELECT COLUMN_NAME AS col FROM INFORMATION_SCHEMA.STATISTICS
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'analytics_pageview_events' AND INDEX_NAME = 'idx_dedup'
				ORDER BY SEQ_IN_INDEX
			");
		} else {
			$result = $this->db->query("
				SELECT c.name AS col FROM sys.indexes i
				INNER JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id AND ic.is_included_column = 0
				INNER JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
				WHERE i.object_id = OBJECT_ID(N'analytics_pageview_events') AND i.name = 'idx_dedup'
				ORDER BY ic.key_ordinal
			");
		}

		$cols = array();
		if ($result) {
			foreach ($result->result_array() as $row) {
				$cols[] = $row['col'];
			}
		}

		return $cols;
	}

	private function replace_pageview_dedup_index($db_driver)
	{
		$target = ($db_driver === 'mysqli')
			? array('study_id', 'session_id', 'section', 'page_url', 'ts')
			: array('study_id', 'session_id', 'section', 'ts');

		if ($this->dedup_index_columns($db_driver) === $target) {
			echo "idx_dedup already has the target columns; skipping rebuild.\n";
			return;
		}

		if ($db_driver === 'mysqli') {
			// MySQL has no DROP INDEX IF EXISTS (MariaDB only), so check first.
			if ($this->index_exists('analytics_pageview_events', 'idx_dedup')) {
				$this->assert_db_query(
					$this->db->query("DROP INDEX `idx_dedup` ON `analytics_pageview_events`"),
					'DROP INDEX idx_dedup ON analytics_pageview_events'
				);
			}
			$this->assert_db_query(
				$this->db->query("
					CREATE INDEX `idx_dedup` ON `analytics_pageview_events`
					(`study_id`, `session_id`, `section`, `page_url`(191), `ts`)
				"),
				'CREATE idx_dedup on analytics_pageview_events'
			);
			return;
		}

		if ($this->index_exists('analytics_pageview_events', 'idx_dedup')) {
			$this->assert_db_query(
				$this->db->query('DROP INDEX [idx_dedup] ON [analytics_pageview_events]'),
				'DROP INDEX idx_dedup ON analytics_pageview_events'
			);
		}

		// SQL Server: page_url cannot be a key column (1700-byte index key limit).
		$this->assert_db_query(
			$this->db->query('
				CREATE NONCLUSTERED INDEX [idx_dedup]
				ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [ts] ASC)
				INCLUDE ([page_url])
			'),
			'CREATE idx_dedup on analytics_pageview_events'
		);
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
