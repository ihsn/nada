<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Site configuration defaults and study admin metadata schema.
 */
class Migration_Site_configuration extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260510120001_add_data_classifications_enabled_configuration.php',
			'20260608120001_create_study_admin_metadata_schema.php',
			'20260611120001_add_catalog_public_search_ui_configuration.php',
			'20260614120001_add_facets_timeseries_configuration.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
