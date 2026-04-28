<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: Add surveys.data_structure_id as a first-class foreign key
 *
 * Previously, timeseries studies linked to a global DSD only via the string idno
 * stored at metadata.data_structure_reference inside surveys.metadata (JSON).
 * That approach has no referential integrity: renaming a DSD idno silently
 * breaks studies, deleting a DSD silently orphans them, and "find all studies
 * using this DSD" requires scanning the JSON blob.
 *
 * This migration adds:
 *   - surveys.data_structure_id INT NULL
 *   - index idx_surveys_data_structure_id
 *   - FK fk_surveys_data_structure -> data_structures(id) ON DELETE RESTRICT
 *
 * The metadata.data_structure_reference string is kept as the public /
 * API-facing form; the new column is the authoritative link used by queries
 * and enforced by FK. On every timeseries save the model resolves the idno
 * and writes the column.
 */
class Migration_Add_data_structure_id_to_surveys extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Add_data_structure_id_to_surveys::up() called');

		if (!$this->db->table_exists('surveys')) {
			log_message('info', 'surveys table not present; skipping');
			return;
		}
		if (!$this->db->table_exists('data_structures')) {
			log_message('info', 'data_structures table not present; skipping (run data_structures migration first)');
			return;
		}

		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'])) {
			$this->up_mysql();
		} elseif ($driver === 'sqlsrv') {
			$this->up_sqlsrv();
		} else {
			log_message('info', 'Migration_Add_data_structure_id_to_surveys: unsupported driver ' . $driver . ', skipping');
			return;
		}

		log_message('info', 'Migration_Add_data_structure_id_to_surveys completed successfully');
	}

	// =========================================================================
	// MySQL
	// =========================================================================

	private function up_mysql()
	{
		if (!$this->mysql_column_exists('surveys', 'data_structure_id')) {
			$this->db->query("
				ALTER TABLE `surveys`
				ADD COLUMN `data_structure_id` INT(11) NULL AFTER `data_class_id`
			");
			log_message('info', 'Added surveys.data_structure_id');
		}

		if (!$this->mysql_index_exists('surveys', 'idx_surveys_data_structure_id')) {
			$this->db->query("
				ALTER TABLE `surveys`
				ADD KEY `idx_surveys_data_structure_id` (`data_structure_id`)
			");
			log_message('info', 'Added idx_surveys_data_structure_id');
		}

		if (!$this->mysql_fk_exists('surveys', 'fk_surveys_data_structure')) {
			$this->db->query("
				ALTER TABLE `surveys`
				ADD CONSTRAINT `fk_surveys_data_structure`
				FOREIGN KEY (`data_structure_id`) REFERENCES `data_structures` (`id`)
				ON DELETE RESTRICT
			");
			log_message('info', 'Added FK fk_surveys_data_structure');
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

	private function mysql_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.statistics
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND index_name   = ?',
			[$table, $index_name]
		);
		return $_r && $_r->row_array();
	}

	private function mysql_fk_exists($table, $constraint_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM information_schema.table_constraints
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND constraint_name = ?
			   AND constraint_type = "FOREIGN KEY"',
			[$table, $constraint_name]
		);
		return $_r && $_r->row_array();
	}

	// =========================================================================
	// SQL Server
	// =========================================================================

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('surveys', 'data_structure_id')) {
			$this->db->query("ALTER TABLE surveys ADD data_structure_id INT NULL");
			log_message('info', 'Added surveys.data_structure_id');
		}

		if (!$this->sqlsrv_index_exists('surveys', 'idx_surveys_data_structure_id')) {
			$this->db->query("CREATE INDEX idx_surveys_data_structure_id ON surveys (data_structure_id)");
			log_message('info', 'Added idx_surveys_data_structure_id');
		}

		if (!$this->sqlsrv_constraint_exists('fk_surveys_data_structure')) {
			$this->db->query("
				ALTER TABLE surveys
				ADD CONSTRAINT fk_surveys_data_structure
				FOREIGN KEY (data_structure_id) REFERENCES data_structures(id)
				ON DELETE NO ACTION
			");
			log_message('info', 'Added FK fk_surveys_data_structure');
		}
	}

	private function sqlsrv_column_exists($table, $column)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?',
			[$table, $column]
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_index_exists($table, $index_name)
	{
		$_r = $this->db->query(
			'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
			[$index_name, $table]
		);
		return $_r && $_r->row_array();
	}

	private function sqlsrv_constraint_exists($constraint_name)
	{
		$_r = $this->db->query(
			"SELECT 1 FROM sys.objects WHERE name = ? AND type IN ('F','PK','UQ','C')",
			[$constraint_name]
		);
		return $_r && $_r->row_array();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
