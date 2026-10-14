<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Widen dd_projects title/shortname and submission note mirror columns.
 */
class Migration_Datadeposit_field_limits extends MY_Migration {

	public function up()
	{
		$this->execute_install_sql('nada57-dd-projects-field-limits');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
