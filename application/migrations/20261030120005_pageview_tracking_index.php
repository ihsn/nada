<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Pageview tracking: page_url/section columns and the session-based dedup index on
 * analytics_pageview_events. Kept separate because the index can take long on large event tables.
 * Skips the index rebuild when it already has the target shape.
 */
class Migration_Pageview_tracking_index extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260707120001_analytics_pageview_tracking.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
