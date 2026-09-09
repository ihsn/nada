<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');
require_once(APPPATH . 'migrations/traits/Catalog_schema_gaps_trait.php');

/**
 * Upgrade codelists with SDMX-style identity (agency, version, idno).
 */
class Migration_Upgrade_codelists_sdmx_identity extends MY_Migration {

	use Catalog_schema_gaps_trait;

	public function up()
	{
		$this->ensure_codelists_sdmx_identity();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
