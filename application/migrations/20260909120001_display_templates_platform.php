<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Display templates, shipped cores/overlays, and leftover admin menu URL cleanup.
 */
class Migration_Display_templates_platform extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20260506113001_create_display_templates_schema.php',
			'20260820120001_remove_visualization_survey_type.php',
			'20260821154001_display_templates_file_backed_cores.php',
			'20260821162001_display_template_translations.php',
			'20260822120001_display_template_shipped_overlays.php',
			'20260822130001_display_template_survey_core_uid.php',
			'20260822210001_legacy_study_templates_configuration.php',
			'20260823120001_prefix_display_and_editor_template_uids.php',
			'20260705120001_migrate_site_menu_repositories_urls.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
