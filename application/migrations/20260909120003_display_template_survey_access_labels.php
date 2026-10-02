<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Re-sync shipped survey display template and translation overlays after
 * data_access / access_policy section label fixes.
 */
class Migration_Display_template_survey_access_labels extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
	}

	public function down()
	{
		// Shipped template content is file-backed; no rollback.
	}
}
