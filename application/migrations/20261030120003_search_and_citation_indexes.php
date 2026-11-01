<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Index builds on surveys, variables, citations and the survey_* filter tables, plus the
 * search index queue tables. Kept separate because index builds can take long on large catalogs.
 * Every step skips indexes that already exist.
 */
class Migration_Search_and_citation_indexes extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260322000001_add_citation_indexes.php',
			'20260701120001_add_search_performance_indexes.php',
			'20260909120002_catalog_search_queue_filters.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
