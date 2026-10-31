<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * One-time data cutover: ts_databases rows become surveys of type timeseriesdb, and their
 * keywords are backfilled. Runs after core_table_alterations (needs the surveys ts columns).
 * No-op when ts_databases no longer exists.
 */
class Migration_Timeseries_data_cutover extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260427112001_migrate_timeseries_databases_to_surveys.php',
			'20260427114001_backfill_timeseriesdb_keywords.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
