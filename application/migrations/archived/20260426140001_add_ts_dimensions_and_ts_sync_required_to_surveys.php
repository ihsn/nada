<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: surveys.ts_dimensions + surveys.ts_sync_required
 *
 * ts_dimensions — Sorted, comma-separated DSD component names with column_type
 * `dimension` (lexicographic). For catalog search facets; NULL if unset.
 *
 * ts_sync_required — TINYINT 0/1: when 1, indicator timeseries should be
 * re-synced (import/rehash) vs the linked DSD. Cleared by app after successful sync.
 */
class Migration_Add_ts_dimensions_and_ts_sync_required_to_surveys extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Add_ts_dimensions_and_ts_sync_required_to_surveys::up() called');

		if (!$this->db->table_exists('surveys')) {
			log_message('info', 'surveys table not present; skipping');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Add_ts_dimensions_and_ts_sync_required_to_surveys: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Add_ts_dimensions_and_ts_sync_required_to_surveys completed successfully');
	}

	private function up_mysql()
	{
		$hasDim  = $this->mysql_column_exists('surveys', 'ts_dimensions');
		$hasSync = $this->mysql_column_exists('surveys', 'ts_sync_required');

		if (!$hasDim && !$hasSync) {
			$this->db->query("
				ALTER TABLE `surveys`
					ADD COLUMN `ts_dimensions` VARCHAR(2000) NULL DEFAULT NULL AFTER `data_structure_id`,
					ADD COLUMN `ts_sync_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `ts_dimensions`
			");
			log_message('info', 'Added surveys.ts_dimensions and surveys.ts_sync_required');
			return;
		}
		if (!$hasDim) {
			$this->db->query("
				ALTER TABLE `surveys`
					ADD COLUMN `ts_dimensions` VARCHAR(2000) NULL DEFAULT NULL AFTER `data_structure_id`
			");
			log_message('info', 'Added surveys.ts_dimensions');
		}
		if (!$hasSync) {
			$after = $this->mysql_column_exists('surveys', 'ts_dimensions') ? 'ts_dimensions' : 'data_structure_id';
			$this->db->query("
				ALTER TABLE `surveys`
					ADD COLUMN `ts_sync_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
					AFTER `{$after}`
			");
			log_message('info', 'Added surveys.ts_sync_required');
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('surveys', 'ts_dimensions')) {
			$this->db->query('ALTER TABLE surveys ADD ts_dimensions NVARCHAR(2000) NULL');
			log_message('info', 'Added surveys.ts_dimensions');
		}
		if (!$this->sqlsrv_column_exists('surveys', 'ts_sync_required')) {
			$this->db->query('ALTER TABLE surveys ADD ts_sync_required TINYINT NOT NULL DEFAULT 0');
			log_message('info', 'Added surveys.ts_sync_required');
		}
	}

	private function mysql_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.columns
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND column_name  = ?',
			[$table, $column]
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?',
			[$table, $column]
		);
		return $_r && $_r->row_array();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
