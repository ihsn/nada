<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'migrations/traits/Catalog_schema_gaps_trait.php');

/**
 * Add PID-family versioning to codelists (aligned with data_structures).
 */
class Migration_Codelists_versioning_pid extends MY_Migration {

	use Catalog_schema_gaps_trait;

	public function up()
	{
		$this->ensure_codelists_pid_versioning();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
