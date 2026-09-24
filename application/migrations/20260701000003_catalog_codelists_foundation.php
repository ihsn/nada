<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Catalog foundation: dctypes/codelists seed and survey search columns.
 * Index builds and the SQLSRV variables Unicode conversion run later
 * (20260701000009 and 20260701000010) so a web request can finish.
 */
class Migration_Catalog_codelists_foundation extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260310000001_dctypes_code_and_translations.php',
			'20260312000001_create_codelists_schema.php',
			'20260323000001_add_abstract_to_surveys.php',
			'20260408000001_restore_var_keywords_to_surveys.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
