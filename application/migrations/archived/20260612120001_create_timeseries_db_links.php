<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Create timeseries_db_links pivot table.
 * Tracks which timeseries series belong to which timeseriesdb entries.
 */
class Migration_Create_timeseries_db_links extends MY_Migration {

	public function up()
	{
		$this->create_table();
	}

	private function create_table()
	{
		if ($this->db->table_exists('timeseries_db_links')) {
			return;
		}

		$driver = $this->db->dbdriver;

		if (in_array($driver, array('mysql', 'mysqli'))) {
			$this->db->query("
				CREATE TABLE `timeseries_db_links` (
					`id`         INT(11)      NOT NULL AUTO_INCREMENT,
					`series_id`  INT(11)      NOT NULL,
					`db_idno`    VARCHAR(255) NOT NULL,
					`is_primary` TINYINT(1)   NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `uq_series_db`    (`series_id`, `db_idno`),
					KEY `idx_series_primary`     (`series_id`, `is_primary`),
					KEY `idx_db_idno`            (`db_idno`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			");
			return;
		}

		if ($driver === 'sqlsrv') {
			$this->db->query("
				CREATE TABLE timeseries_db_links (
					id         INT IDENTITY(1,1) PRIMARY KEY,
					series_id  INT          NOT NULL,
					db_idno    NVARCHAR(255) NOT NULL,
					is_primary TINYINT      NOT NULL DEFAULT 0,
					CONSTRAINT uq_series_db UNIQUE (series_id, db_idno)
				)
			");
			$this->db->query('CREATE NONCLUSTERED INDEX idx_tsdbl_series_primary ON timeseries_db_links (series_id ASC, is_primary ASC)');
			$this->db->query('CREATE NONCLUSTERED INDEX idx_tsdbl_db_idno       ON timeseries_db_links (db_idno ASC)');
			// surveys.idno has no dedicated index in SQL Server; required for the
			// self-join: surveys tsdb ON tsdb.idno = tdbl.db_idno
			$chk = $this->db->query("SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_idno' AND object_id = OBJECT_ID('surveys')");
			if (!$chk || $chk->num_rows() === 0) {
				$this->db->query('CREATE UNIQUE NONCLUSTERED INDEX idx_surveys_idno ON surveys ([idno] ASC)');
			}
		}
	}

}
