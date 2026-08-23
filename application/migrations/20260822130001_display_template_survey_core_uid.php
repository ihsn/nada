<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Display survey core UID is survey-system-en (not the editor microdata-system-en).
 */
class Migration_Display_template_survey_core_uid extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		$old = 'microdata-system-en';
		$new = 'survey-system-en';
		$display_file = 'templates/display/survey_display_template.json';

		$this->db->from('display_templates');
		$this->db->where('uid', $new);
		$this->db->where('is_deleted', 0);
		$new_row = $this->db->get()->row_array();

		$this->db->from('display_templates');
		$this->db->where('uid', $old);
		$this->db->where('is_deleted', 0);
		$old_row = $this->db->get()->row_array();

		if ($old_row && !$new_row) {
			$file_path = isset($old_row['file_path']) ? (string) $old_row['file_path'] : '';
			$is_display_core = ($file_path === $display_file)
				|| (isset($old_row['source']) && $old_row['source'] === 'file'
					&& isset($old_row['data_type']) && $old_row['data_type'] === 'survey');
			if ($is_display_core) {
				$this->db->where('id', (int) $old_row['id']);
				$this->db->update('display_templates', array('uid' => $new));
			}
		}

		if ($this->db->table_exists('display_templates_default')) {
			$this->db->where('data_type', 'survey');
			$this->db->where('template_uid', $old);
			$this->db->update('display_templates_default', array('template_uid' => $new));
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
