<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Codelists SDMX identity, data structures schema, and PID/versioning for codelists and DSDs.
 */
class Migration_Codelists_dsd_versioning extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260416000001_upgrade_codelists_sdmx_identity.php',
			'20260417000001_create_data_structures_schema.php',
			'20260421000001_data_structures_versioning_pid.php',
			'20260421120001_codelists_versioning_pid.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
