<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Catalog foundation: dctypes/codelists seed, citation indexes, survey search columns.
 * Includes SQLSRV-only variables Unicode step (no-op on MySQL).
 */
class Migration_Catalog_codelists_foundation extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260310000001_dctypes_code_and_translations.php',
			'20260312000001_create_codelists_schema.php',
			'20260322000001_add_citation_indexes.php',
			'20260701120001_add_search_performance_indexes.php',
			'20260323000001_add_abstract_to_surveys.php',
			'20260408000001_restore_var_keywords_to_surveys.php',
			'20260420000001_variables_unicode_nvarchar_sqlsrv.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
