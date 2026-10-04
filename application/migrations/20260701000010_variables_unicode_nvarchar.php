<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * SQL Server: convert variables text columns to NVARCHAR and recreate full-text.
 * No-op on MySQL. Safe to re-run: each column is altered only while it is still
 * varchar, and an existing full-text index is left in place once columns are done.
 */
class Migration_Variables_unicode_nvarchar extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260420000001_variables_unicode_nvarchar_sqlsrv.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
