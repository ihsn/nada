<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: surveys.ts_frequency
 *
 * Comma-separated titles of codelist items from the DSD's periodicity component
 * (e.g. "Annual", "Annual, Quarterly"). NULL when no periodicity component exists.
 * Populated/refreshed whenever the survey's data_structure_id changes.
 */
class Migration_Add_ts_frequency_to_surveys extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Add_ts_frequency_to_surveys::up() called');

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
			log_message('info', 'Migration_Add_ts_frequency_to_surveys: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Add_ts_frequency_to_surveys completed successfully');
	}

	private function up_mysql()
	{
		if ($this->mysql_column_exists('surveys', 'ts_frequency')) {
			log_message('info', 'surveys.ts_frequency already exists; skipping');
			return;
		}
		$after = $this->mysql_column_exists('surveys', 'ts_dimensions') ? 'ts_dimensions' : 'data_structure_id';
		$this->db->query("
			ALTER TABLE `surveys`
				ADD COLUMN `ts_frequency` VARCHAR(500) NULL DEFAULT NULL
				AFTER `{$after}`
		");
		log_message('info', 'Added surveys.ts_frequency');
	}

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('surveys', 'ts_frequency')) {
			$this->db->query('ALTER TABLE surveys ADD ts_frequency NVARCHAR(500) NULL');
			log_message('info', 'Added surveys.ts_frequency');
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
