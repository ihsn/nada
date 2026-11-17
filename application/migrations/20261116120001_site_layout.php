<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: table site_layout for Admin > Settings > Site layout (header, footer, styles).
 *
 * One row per part (header, footer, styles) and state (published, draft); settings holds the part's JSON.
 * Only the Site layout admin pages read it; public pages read the published files under catalog_root.
 *
 * Skipped when the table already exists, so it can be run again.
 */
class Migration_Site_layout extends MY_Migration {

	public function up()
	{
		$this->db->data_cache = array();

		if ($this->table_exists('site_layout')) {
			$this->emit("  site_layout already exists\n");
			return;
		}
		$this->create_table();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	private function create_table()
	{
		$driver = strtolower((string) $this->db->dbdriver);
		$table = $this->db->dbprefix('site_layout');

		if (strpos($driver, 'mysql') !== false) {
			$sql = 'CREATE TABLE `' . $table . '` (
				`part` varchar(20) NOT NULL,
				`state` varchar(20) NOT NULL,
				`settings` mediumtext NOT NULL,
				`created` int(11) DEFAULT NULL,
				`created_by` int(11) DEFAULT NULL,
				`changed` int(11) DEFAULT NULL,
				`changed_by` int(11) DEFAULT NULL,
				PRIMARY KEY (`part`,`state`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
		} elseif ($driver === 'sqlsrv') {
			$sql = 'CREATE TABLE ' . $table . ' (
				part NVARCHAR(20) NOT NULL,
				state NVARCHAR(20) NOT NULL,
				settings NVARCHAR(MAX) NOT NULL,
				created INT NULL,
				created_by INT NULL,
				changed INT NULL,
				changed_by INT NULL,
				CONSTRAINT PK_site_layout PRIMARY KEY (part, state)
			)';
		} else {
			$this->emit("  database driver '{$driver}' not handled, skipping\n");
			return;
		}

		$this->assert_db_query($this->db->query($sql), 'create site_layout');
		$this->emit("  created site_layout\n");
	}
}
