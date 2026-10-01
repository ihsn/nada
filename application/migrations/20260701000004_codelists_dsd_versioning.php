<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Filestore, codelists SDMX/PID columns, and data structures schema.
 * Schema changes are plain SQL (install/nada56-*); re-runs skip duplicate errors.
 */
class Migration_Codelists_dsd_versioning extends MY_Migration {

	public function up()
	{
		$this->execute_install_sql('nada56-filestore');
		$this->execute_install_sql('nada56-codelists-dsd');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
