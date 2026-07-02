<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Repositories ACL: create table, copy legacy collection permissions, remove old rows.
 * Order is mandatory: create → copy → delete (each step is idempotent on re-run).
 */
class Migration_Repositories_acl extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260508120001_create_repositories_acl_table.php',
			'20260508130501_migrate_legacy_role_permissions_to_repositories_acl.php',
			'20260508150001_remove_collection_scoped_role_permissions.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
