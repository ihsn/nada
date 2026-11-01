<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Heavy table alterations on resources and surveys: resources upgrade, surveys.abstract,
 * var_keywords with full-text index rebuild, timeseries columns, data_structure_id foreign key
 * and the unique idno index. Runs after platform_core (needs data_structures).
 * Every step skips work that is already applied.
 */
class Migration_Core_table_alterations extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20251022000001_upgrade_resources_table.php',
			'20260323000001_add_abstract_to_surveys.php',
			'20260408000001_restore_var_keywords_to_surveys.php',
		));

		$this->execute_install_sql('nada56-surveys-ts-fields');
		$this->execute_install_sql('nada56-timeseries-value-counts');
		$this->execute_install_sql('nada56-timeseries-db-links');
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
