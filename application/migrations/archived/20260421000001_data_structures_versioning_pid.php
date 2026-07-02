<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add PID-family versioning fields to data_structures.
 *
 * - pid (latest row id for family)
 * - version_seq (monotonic sequence per agency+name family)
 * - status as numeric lifecycle code
 */
class Migration_Data_structures_versioning_pid extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('data_structures')) {
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
		if (!$this->mysql_column_exists('data_structures', 'pid')) {
			$this->db->query('ALTER TABLE `data_structures` ADD COLUMN `pid` INT(11) NULL AFTER `id`');
		}
		if (!$this->mysql_column_exists('data_structures', 'version_seq')) {
			$this->db->query('ALTER TABLE `data_structures` ADD COLUMN `version_seq` INT(11) NULL AFTER `version`');
		}

		$statusType = $this->mysql_column_type('data_structures', 'status');
		if ($statusType !== null && strpos(strtolower($statusType), 'int') === false) {
			$this->db->query("
				UPDATE `data_structures`
				SET `status` = CASE LOWER(TRIM(COALESCE(`status`, '')))
					WHEN 'draft' THEN '0'
					WHEN 'review' THEN '10'
					WHEN 'published' THEN '20'
					WHEN 'deprecated' THEN '30'
					WHEN 'archived' THEN '40'
					WHEN '' THEN '0'
					ELSE '0'
				END
			");
			$this->db->query('ALTER TABLE `data_structures` MODIFY COLUMN `status` SMALLINT NOT NULL DEFAULT 0');
		}

		$this->db->query("
			UPDATE data_structures ds
			SET ds.version_seq = (
				SELECT COUNT(*)
				FROM data_structures x
				WHERE x.agency = ds.agency
				  AND x.name = ds.name
				  AND (
					COALESCE(x.created, 0) < COALESCE(ds.created, 0)
					OR (COALESCE(x.created, 0) = COALESCE(ds.created, 0) AND x.id <= ds.id)
				  )
			)
			WHERE ds.version_seq IS NULL OR ds.version_seq <= 0
		");
		$this->db->query('ALTER TABLE `data_structures` MODIFY COLUMN `version_seq` INT(11) NOT NULL');

		$this->db->query("
			UPDATE data_structures ds
			JOIN (
				SELECT agency, name, MAX(id) AS latest_id
				FROM data_structures
				GROUP BY agency, name
			) latest
			  ON latest.agency = ds.agency AND latest.name = ds.name
			SET ds.pid = latest.latest_id
			WHERE ds.pid IS NULL OR ds.pid <> latest.latest_id
		");
		$this->db->query('ALTER TABLE `data_structures` MODIFY COLUMN `pid` INT(11) NULL');

		if (!$this->mysql_index_exists('data_structures', 'unq_data_structures_family_seq')) {
			$this->db->query('ALTER TABLE `data_structures` ADD UNIQUE KEY `unq_data_structures_family_seq` (`agency`,`name`,`version_seq`)');
		}
		if (!$this->mysql_index_exists('data_structures', 'idx_data_structures_pid')) {
			$this->db->query('ALTER TABLE `data_structures` ADD KEY `idx_data_structures_pid` (`pid`)');
		}
		if (!$this->mysql_fk_exists('data_structures', 'fk_data_structures_pid')) {
			$this->db->query('ALTER TABLE `data_structures` ADD CONSTRAINT `fk_data_structures_pid` FOREIGN KEY (`pid`) REFERENCES `data_structures` (`id`) ON DELETE RESTRICT');
		}
	}

	private function up_sqlsrv()
	{
		if (!$this->sqlsrv_column_exists('data_structures', 'pid')) {
			$this->db->query('ALTER TABLE data_structures ADD pid INT NULL');
		}
		if (!$this->sqlsrv_column_exists('data_structures', 'version_seq')) {
			$this->db->query('ALTER TABLE data_structures ADD version_seq INT NULL');
		}

		$statusType = $this->sqlsrv_column_type('data_structures', 'status');
		if ($statusType !== null && strpos(strtolower($statusType), 'int') === false) {
			$this->db->query("
				UPDATE data_structures
				SET status = CASE LOWER(LTRIM(RTRIM(COALESCE(status, ''))))
					WHEN 'draft' THEN '0'
					WHEN 'review' THEN '10'
					WHEN 'published' THEN '20'
					WHEN 'deprecated' THEN '30'
					WHEN 'archived' THEN '40'
					WHEN '' THEN '0'
					ELSE '0'
				END
			");
			$this->db->query('ALTER TABLE data_structures ALTER COLUMN status SMALLINT NOT NULL');
			$this->db->query("IF OBJECT_ID('df_data_structures_status', 'D') IS NOT NULL ALTER TABLE data_structures DROP CONSTRAINT df_data_structures_status");
			$this->db->query('ALTER TABLE data_structures ADD CONSTRAINT df_data_structures_status DEFAULT 0 FOR status');
		}

		$this->db->query("
			;WITH seq AS (
				SELECT id,
					ROW_NUMBER() OVER (
						PARTITION BY agency, name
						ORDER BY ISNULL(created,0) ASC, id ASC
					) AS rn
				FROM data_structures
			)
			UPDATE ds
			SET version_seq = seq.rn
			FROM data_structures ds
			INNER JOIN seq ON seq.id = ds.id
			WHERE ds.version_seq IS NULL OR ds.version_seq <= 0
		");
		$this->db->query('ALTER TABLE data_structures ALTER COLUMN version_seq INT NOT NULL');

		$this->db->query("
			;WITH latest AS (
				SELECT agency, name, MAX(id) AS latest_id
				FROM data_structures
				GROUP BY agency, name
			)
			UPDATE ds
			SET pid = latest.latest_id
			FROM data_structures ds
			INNER JOIN latest
				ON latest.agency = ds.agency
			   AND latest.name = ds.name
			WHERE ds.pid IS NULL OR ds.pid <> latest.latest_id
		");
		$this->db->query('ALTER TABLE data_structures ALTER COLUMN pid INT NULL');

		if (!$this->sqlsrv_index_exists('data_structures', 'unq_data_structures_family_seq')) {
			$this->db->query('CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq)');
		}
		if (!$this->sqlsrv_index_exists('data_structures', 'idx_data_structures_pid')) {
			$this->db->query('CREATE INDEX idx_data_structures_pid ON data_structures(pid)');
		}
		if (!$this->sqlsrv_constraint_exists('fk_data_structures_pid')) {
			$this->db->query('ALTER TABLE data_structures ADD CONSTRAINT fk_data_structures_pid FOREIGN KEY (pid) REFERENCES data_structures(id)');
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

	private function mysql_column_type($table, $column)
	{
		$_r = $this->db->query(
			'SELECT data_type FROM information_schema.columns
			 WHERE table_schema = DATABASE()
			   AND table_name   = ?
			   AND column_name  = ?',
			[$table, $column]
		);
		$row = $_r ? $_r->row_array() : null;
		return $row ? (string) $row['data_type'] : null;
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

	private function sqlsrv_column_type($table, $column)
	{
		$_r = $this->db->query(
			'SELECT t.name AS data_type
			 FROM sys.columns c
			 JOIN sys.types t ON c.user_type_id = t.user_type_id
			 WHERE c.object_id = OBJECT_ID(?) AND c.name = ?',
			[$table, $column]
		);
		$row = $_r ? $_r->row_array() : null;
		return $row ? (string) $row['data_type'] : null;
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
