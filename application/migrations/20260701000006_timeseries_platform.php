<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Timeseries platform: survey columns, value counts, ts_databases cutover, db links.
 * Requires codelists/DSD migrations (20260701000004) to run first.
 */
class Migration_Timeseries_platform extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260417120001_add_data_structure_id_to_surveys.php',
			'20260426000001_create_timeseries_value_counts_table.php',
			'20260426140001_add_ts_dimensions_and_ts_sync_required_to_surveys.php',
			'20260427112001_migrate_timeseries_databases_to_surveys.php',
			'20260427114001_backfill_timeseriesdb_keywords.php',
			'20260427120001_add_ts_db_id_to_surveys.php',
			'20260506130001_add_ts_data_count_to_surveys.php',
			'20260612120001_create_timeseries_db_links.php',
			'20260614130001_add_ts_frequency_to_surveys.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
