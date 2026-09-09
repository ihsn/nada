<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Install dd_* tables when missing, then add v2 columns on existing catalogs.
 */
class Migration_Datadeposit_platform extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260829120001_datadeposit_install_tables.php',
			'20260823220001_datadeposit_schema_version.php',
			'20260908120001_datadeposit_projects_data_type.php',
			'20260908130001_datadeposit_projects_metadata.php',
			'20260908140001_datadeposit_project_resources_columns.php',
			'20260829140001_deposit_max_upload_size_configuration.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
