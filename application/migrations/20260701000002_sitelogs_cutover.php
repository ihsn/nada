<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Sitelogs cutover: rename legacy table, create optimized sitelogs, repair if missing.
 * Idempotent: skips rename/create when already applied.
 */
class Migration_Sitelogs_cutover extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20251214000001_rename_sitelogs_to_legacy.php',
			'20251214000002_create_sitelogs_table.php',
			'20260618120001_ensure_sitelogs_table.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
