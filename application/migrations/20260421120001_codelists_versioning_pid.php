<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add PID-family versioning to codelists (aligned with data_structures).
 *
 * Columns: pid, version_seq, status, created, changed
 */
class Migration_Codelists_versioning_pid extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('codelists')) {
			return;
		}
		$driver = $this->db->dbdriver;
		if (in_array($driver, ['mysql', 'mysqli'], true)) {
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
		if (!$this->mysql_column_exists('codelists', 'pid')) {
			$this->db->query('ALTER TABLE `codelists` ADD COLUMN `pid` INT(11) NULL AFTER `id`');
		}
		if (!$this->mysql_column_exists('codelists', 'version_seq')) {
			$this->db->query('ALTER TABLE `codelists` ADD COLUMN `version_seq` INT(11) NULL AFTER `version`');
		}
		if (!$this->mysql_column_exists('codelists', 'status')) {
			$this->db->query('ALTER TABLE `codelists` ADD COLUMN `status` SMALLINT NOT NULL DEFAULT 0 AFTER `description`');
		}
		if (!$this->mysql_column_exists('codelists', 'created')) {
			$this->db->query('ALTER TABLE `codelists` ADD COLUMN `created` INT(11) NULL AFTER `status`');
		}
		if (!$this->mysql_column_exists('codelists', 'changed')) {
			$this->db->query('ALTER TABLE `codelists` ADD COLUMN `changed` INT(11) NULL AFTER `created`');
		}

		$this->db->query('
			UPDATE codelists c
			SET c.version_seq = (
				SELECT COUNT(*) FROM codelists x
				WHERE x.agency = c.agency AND x.name = c.name AND x.id <= c.id
			)
			WHERE c.version_seq IS NULL OR c.version_seq <= 0
		');
		$this->db->query('ALTER TABLE `codelists` MODIFY COLUMN `version_seq` INT(11) NOT NULL');

		$this->db->query("
			UPDATE codelists c
			JOIN (
				SELECT agency, name, MAX(id) AS latest_id
				FROM codelists
				GROUP BY agency, name
			) latest
			  ON latest.agency = c.agency AND latest.name = c.name
			SET c.pid = latest.latest_id
			WHERE c.pid IS NULL OR c.pid <> latest.latest_id
		");

		$this->db->query('UPDATE `codelists` SET `created` = UNIX_TIMESTAMP(), `changed` = UNIX_TIMESTAMP() WHERE `created` IS NULL');

		if (!$this->mysql_index_exists('codelists', 'unq_codelists_family_seq')) {
			$this->db->query('ALTER TABLE `codelists` ADD UNIQUE KEY `unq_codelists_family_seq` (`agency`,`name`,`version_seq`)');
		}
		if (!$this->mysql_index_exists('codelists', 'idx_codelists_pid')) {
			$this->db->query('ALTER TABLE `codelists` ADD KEY `idx_codelists_pid` (`pid`)');
		}
		if (!$this->mysql_fk_exists('codelists', 'fk_codelists_pid')) {
			$this->db->query('ALTER TABLE `codelists` ADD CONSTRAINT `fk_codelists_pid` FOREIGN KEY (`pid`) REFERENCES `codelists` (`id`) ON DELETE RESTRICT');
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('codelists', 'pid')) {
			$this->db->query('ALTER TABLE codelists ADD pid INT NULL');
		}
		if (!$this->sqlsrv_column_exists('codelists', 'version_seq')) {
			$this->db->query('ALTER TABLE codelists ADD version_seq INT NULL');
		}
		if (!$this->sqlsrv_column_exists('codelists', 'status')) {
			$this->db->query('ALTER TABLE codelists ADD status SMALLINT NOT NULL CONSTRAINT df_codelists_status DEFAULT 0');
		}
		if (!$this->sqlsrv_column_exists('codelists', 'created')) {
			$this->db->query('ALTER TABLE codelists ADD created INT NULL');
		}
		if (!$this->sqlsrv_column_exists('codelists', 'changed')) {
			$this->db->query('ALTER TABLE codelists ADD changed INT NULL');
		}

		$this->db->query("
			;WITH seq AS (
				SELECT id,
					ROW_NUMBER() OVER (PARTITION BY agency, name ORDER BY id ASC) AS rn
				FROM codelists
			)
			UPDATE c
			SET version_seq = seq.rn
			FROM codelists c
			INNER JOIN seq ON seq.id = c.id
			WHERE c.version_seq IS NULL OR c.version_seq <= 0
		");
		$this->db->query('ALTER TABLE codelists ALTER COLUMN version_seq INT NOT NULL');

		$this->db->query("
			;WITH latest AS (
				SELECT agency, name, MAX(id) AS latest_id
				FROM codelists
				GROUP BY agency, name
			)
			UPDATE c
			SET pid = latest.latest_id
			FROM codelists c
			INNER JOIN latest
				ON latest.agency = c.agency
			   AND latest.name = c.name
			WHERE c.pid IS NULL OR c.pid <> latest.latest_id
		");

		$this->db->query("
			UPDATE codelists
			SET created = DATEDIFF(SECOND, '1970-01-01', SYSUTCDATETIME()),
			    changed = DATEDIFF(SECOND, '1970-01-01', SYSUTCDATETIME())
			WHERE created IS NULL
		");

		if (!$this->sqlsrv_index_exists('codelists', 'unq_codelists_family_seq')) {
			$this->db->query('CREATE UNIQUE INDEX unq_codelists_family_seq ON codelists(agency, name, version_seq)');
		}
		if (!$this->sqlsrv_index_exists('codelists', 'idx_codelists_pid')) {
			$this->db->query('CREATE INDEX idx_codelists_pid ON codelists(pid)');
		}
		if (!$this->sqlsrv_constraint_exists('fk_codelists_pid')) {
			$this->db->query('ALTER TABLE codelists ADD CONSTRAINT fk_codelists_pid FOREIGN KEY (pid) REFERENCES codelists(id)');
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
