<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Timeseries platform: survey columns, value counts, ts_databases cutover, db links.
 * Schema DDL is plain SQL; data backfills remain in archived PHP steps.
 * Requires codelists/DSD migrations (20260701000004) to run first.
 */
class Migration_Timeseries_platform extends MY_Migration {

	public function up()
	{
		$this->execute_install_sql('nada56-surveys-ts-fields');
		$this->execute_install_sql('nada56-timeseries-value-counts');
		$this->execute_install_sql('nada56-timeseries-db-links');

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
