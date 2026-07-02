<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Cached total of MongoDB indicator observation documents for this study (all indicator_ts_* collections).
 *
 * surveys.ts_data_count — BIGINT UNSIGNED NOT NULL DEFAULT 0
 */
class Migration_Add_ts_data_count_to_surveys extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('surveys')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
			return;
		}
		if ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		}
	}

	private function up_mysql()
	{
		if ($this->mysql_column_exists('surveys', 'ts_data_count')) {
			return;
		}

		if ($this->mysql_column_exists('surveys', 'ts_sync_required')) {
			$after = 'ts_sync_required';
		} elseif ($this->mysql_column_exists('surveys', 'ts_dimensions')) {
			$after = 'ts_dimensions';
		} elseif ($this->mysql_column_exists('surveys', 'ts_db_id')) {
			$after = 'ts_db_id';
		} elseif ($this->mysql_column_exists('surveys', 'data_structure_id')) {
			$after = 'data_structure_id';
		} else {
			$after = 'data_class_id';
		}

		$this->db->query("
			ALTER TABLE `surveys`
				ADD COLUMN `ts_data_count` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `{$after}`
		");
	}

	private function up_sqlsrv()
	{
		if ($this->sqlsrv_column_exists('surveys', 'ts_data_count')) {
			return;
		}

		$this->db->query('ALTER TABLE surveys ADD ts_data_count BIGINT NOT NULL DEFAULT 0');
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
