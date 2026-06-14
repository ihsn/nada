<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Create timeseries_db_links pivot table and backfill from existing timeseries metadata.
 *
 * Tracks which timeseries series belong to which timeseriesdb entries.
 * Populated from series_description.databases[].id (idno of the database).
 */
class Migration_Create_timeseries_db_links extends MY_Migration {

	public function up()
	{
		$this->create_table();
		$this->backfill();
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
		}
	}

	/**
	 * Backfill from existing timeseries rows: read series_description.databases[].
	 */
	private function backfill()
	{
		$this->db->select('id, metadata');
		$this->db->where('type', 'timeseries');
		$rows = $this->db->get('surveys')->result_array();

		if (!is_array($rows) || count($rows) === 0) {
			return;
		}

		foreach ($rows as $row) {
			$metadata = $this->decode_metadata($row['metadata']);
			if (!is_array($metadata)) {
				continue;
			}

			$entries = array(); // [ idno => is_primary ]

			// New format: series_description.databases[]
			$databases = isset($metadata['series_description']['databases'])
				? $metadata['series_description']['databases']
				: array();

			if (is_array($databases)) {
				foreach ($databases as $db) {
					$idno = isset($db['id']) ? trim((string)$db['id']) : '';
					if ($idno !== '') {
						$entries[$idno] = !empty($db['is_primary']) ? 1 : 0;
					}
				}
			}

			// Legacy format: series_description.database_id (single value)
			$legacy_id = isset($metadata['series_description']['database_id'])
				? trim((string)$metadata['series_description']['database_id'])
				: '';

			if ($legacy_id !== '' && !isset($entries[$legacy_id])) {
				$entries[$legacy_id] = 1; // legacy single db is always primary
			}

			foreach ($entries as $idno => $is_primary) {
				$this->db->query(
					'INSERT IGNORE INTO timeseries_db_links (series_id, db_idno, is_primary) VALUES (?, ?, ?)',
					array((int)$row['id'], $idno, $is_primary)
				);
			}
		}
	}

	private function decode_metadata($raw)
	{
		if (is_array($raw)) {
			return $raw;
		}
		if (!is_string($raw) || $raw === '') {
			return null;
		}
		return json_decode($raw, true);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
