<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * @deprecated Applied by install/nada56-codelists-dsd-*.sql via migration 20260701000004.
 */
class Migration_Data_structures_versioning_pid extends MY_Migration {

	public function up()
	{
		log_message('info', 'Migration_Data_structures_versioning_pid: superseded by nada56-codelists-dsd SQL');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
