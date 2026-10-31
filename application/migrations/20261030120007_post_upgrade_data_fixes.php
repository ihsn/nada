<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Small data fixes: raise the default minimum password length and remove the legacy
 * 'user' role. Both only act on data that still has the old value.
 */
class Migration_Post_upgrade_data_fixes extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20261022180001_password_min_length.php',
			'20260701000011_remove_user_role.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
