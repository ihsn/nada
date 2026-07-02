<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add surveys.ts_db_id to link timeseries -> timeseriesdb (surveys.id).
 */
class Migration_Add_ts_db_id_to_surveys extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('surveys')) {
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->up_mysql();
			return;
		}
		if ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
			return;
		}
	}

	private function up_mysql()
	{
		if ($this->mysql_column_exists('surveys', 'ts_db_id')) {
			return;
		}

		$after = $this->mysql_column_exists('surveys', 'data_structure_id')
			? 'data_structure_id'
			: 'data_class_id';

		$this->db->query("
			ALTER TABLE `surveys`
				ADD COLUMN `ts_db_id` INT(11) NULL DEFAULT NULL AFTER `{$after}`
		");
	}

	private function up_sqlsrv()
	{
		if ($this->sqlsrv_column_exists('surveys', 'ts_db_id')) {
			return;
		}

		$this->db->query('ALTER TABLE surveys ADD ts_db_id INT NULL');
	}

	private function mysql_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.columns
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND column_name  = ?',
			array($table, $column)
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?',
			array($table, $column)
		);
		return $_r && $_r->row_array();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
