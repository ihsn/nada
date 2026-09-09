<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Search index queue/state plus catalog sidebar filter indexes.
 */
class Migration_Catalog_search_indexes extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260822180001_create_search_index_schema.php',
			'20260906120001_catalog_search_filter_indexes.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
