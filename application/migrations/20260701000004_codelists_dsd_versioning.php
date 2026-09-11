<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'migrations/traits/Catalog_schema_gaps_trait.php');

/**
 * Codelists SDMX identity, data structures schema, and PID/versioning for codelists and DSDs.
 */
class Migration_Codelists_dsd_versioning extends MY_Migration {

	use Catalog_schema_gaps_trait;

	public function up()
	{
		$this->run_archived_steps(array(
			'20260416000001_upgrade_codelists_sdmx_identity.php',
			'20260417000001_create_data_structures_schema.php',
			'20260421000001_data_structures_versioning_pid.php',
			'20260421120001_codelists_versioning_pid.php',
		));

		// Archived steps are idempotent; run them again so a prior silent
		// SQL Server skip cannot leave agency/idno/pid missing.
		$this->ensure_codelists_sdmx_identity();
		$this->ensure_codelists_pid_versioning();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
