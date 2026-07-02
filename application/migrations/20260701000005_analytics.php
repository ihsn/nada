<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Analytics schema, legacy totals backfill, and pageview dedup index.
 */
class Migration_Analytics extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260326000001_create_analytics_schema.php',
			'20260326000002_migrate_analytics_legacy_totals.php',
			'20260531000001_add_pageview_dedup_index.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
