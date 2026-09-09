<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Site setting for which catalog types still use PHP study-description views.
 */
class Migration_Legacy_study_templates_configuration extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('configurations')) {
			return;
		}

		$this->load->helper('display_template');
		$types = display_template_normalize_legacy_study_types(
			display_template_legacy_study_templates_raw()
		);

		$this->insert_configuration_if_missing('legacy_study_templates', array(
			'value' => json_encode($types),
			'label' => 'Study description layout',
			'helptext' => 'JSON list of catalog types that still use PHP metadata_templates views.',
			'item_group' => null,
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
