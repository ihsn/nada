<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Platform baseline: resources table upgrade, OAuth authtype columns, API key security.
 * Idempotent: each bundled step skips already-applied schema changes.
 */
class Migration_Platform_baseline extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20251022000001_upgrade_resources_table.php',
			'20251027000001_add_authtype_to_users.php',
			'20251128000001_api_keys_security_enhancement.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
