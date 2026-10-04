<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Citation and catalog search indexes.
 *
 * Split out of 20260701000003 so index builds on large SQL Server tables
 * do not share a web request with schema changes. Each index is created
 * only when missing, so a timed-out run can be started again.
 */
class Migration_Catalog_search_indexes extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260322000001_add_citation_indexes.php',
			'20260701120001_add_search_performance_indexes.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
