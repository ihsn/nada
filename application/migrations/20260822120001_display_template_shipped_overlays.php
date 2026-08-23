<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Seed shipped core overlay translations (microdata FR/ES and any later sidecars).
 */
class Migration_Display_template_shipped_overlays extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')
			|| !$this->db->table_exists('display_template_translations')) {
			log_message('info', 'Migration_Display_template_shipped_overlays: tables missing, skip');
			return;
		}

		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
