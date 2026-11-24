<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: index survey_data_api.sid.
 *
 * The catalog cards look up which studies on a page have a data API (sid IN (...)), and the study page
 * looks it up by sid. The table had no index besides its primary key.
 *
 * Skipped when the index already exists, so it can be run again.
 */
class Migration_Survey_data_api_sid_index extends MY_Migration {

	public function up()
	{
		$this->db->data_cache = array();

		if (!$this->table_exists('survey_data_api')) {
			$this->emit("  survey_data_api does not exist, skipping\n");
			return;
		}
		if ($this->index_exists('survey_data_api', 'idx_sda_sid')) {
			$this->emit("  idx_sda_sid already exists\n");
			return;
		}

		$driver = strtolower((string) $this->db->dbdriver);
		$table = $this->db->dbprefix('survey_data_api');

		if (strpos($driver, 'mysql') !== false) {
			$sql = 'ALTER TABLE `' . $table . '` ADD INDEX `idx_sda_sid` (`sid`)';
		} elseif ($driver === 'sqlsrv') {
			$sql = 'CREATE NONCLUSTERED INDEX idx_sda_sid ON ' . $table . ' (sid)';
		} else {
			$this->emit("  database driver '{$driver}' not handled, skipping\n");
			return;
		}

		$this->assert_db_query($this->db->query($sql), 'idx_sda_sid');
		$this->emit("  added idx_sda_sid\n");
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
