<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Drop unused visualization catalog type from survey_types and display-template rows.
 */
class Migration_Remove_visualization_survey_type extends MY_Migration {

	public function up()
	{
		if ($this->db->table_exists('display_templates_default')
			&& $this->db->field_exists('data_type', 'display_templates_default')
		) {
			$this->db->where('data_type', 'visualization')->delete('display_templates_default');
		}

		if ($this->db->table_exists('display_templates')
			&& $this->db->field_exists('data_type', 'display_templates')
		) {
			$this->db->where('data_type', 'visualization')->delete('display_templates');
		}

		if ($this->db->table_exists('survey_types')
			&& $this->db->field_exists('code', 'survey_types')
		) {
			$this->db->where('code', 'visualization')->delete('survey_types');
		}
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
