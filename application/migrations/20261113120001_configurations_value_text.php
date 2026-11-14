<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: widen configurations.value from varchar(5000) to a long text column.
 *
 * The site header settings (site_header, site_header_draft) are stored as one JSON document each and hold the
 * admin's own header HTML and CSS, which do not fit in 5000 characters.
 *
 *   MySQL       value MEDIUMTEXT NOT NULL
 *   SQL Server  value NVARCHAR(MAX) NOT NULL
 *
 * Existing values are kept. The step checks the current type first and can be run again.
 */
class Migration_Configurations_value_text extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('configurations')) {
			$this->emit("  configurations does not exist, skipping\n");
			return;
		}

		$driver = strtolower((string) $this->db->dbdriver);

		if (strpos($driver, 'mysql') !== false) {
			$row = $this->db->query(
				"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
				 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'value'",
				array($this->db->dbprefix('configurations'))
			)->row_array();
			$type = $row ? strtolower($row['DATA_TYPE']) : '';
			if (in_array($type, array('mediumtext', 'longtext'), true)) {
				$this->emit("  configurations.value is already {$type}\n");
				return;
			}
			$result = $this->db->query('ALTER TABLE ' . $this->db->protect_identifiers('configurations', true) . ' MODIFY `value` MEDIUMTEXT NOT NULL');
			$this->assert_db_query($result, 'widen configurations.value (MySQL)');
			$this->emit("  configurations.value -> MEDIUMTEXT\n");
			return;
		}

		if ($driver === 'sqlsrv') {
			$row = $this->db->query(
				"SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
				 WHERE TABLE_NAME = ? AND COLUMN_NAME = 'value'",
				array($this->db->dbprefix('configurations'))
			)->row_array();
			if ($row && strtolower($row['DATA_TYPE']) === 'nvarchar' && (int) $row['CHARACTER_MAXIMUM_LENGTH'] === -1) {
				$this->emit("  configurations.value is already NVARCHAR(MAX)\n");
				return;
			}
			$result = $this->db->query('ALTER TABLE ' . $this->db->dbprefix('configurations') . ' ALTER COLUMN value NVARCHAR(MAX) NOT NULL');
			$this->assert_db_query($result, 'widen configurations.value (SQL Server)');
			$this->emit("  configurations.value -> NVARCHAR(MAX)\n");
			return;
		}

		$this->emit("  database driver '{$driver}' not handled, skipping\n");
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
