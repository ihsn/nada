<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Prefix display UIDs with display- and update file_path after the
 * display_/edit_ filename rename. Editor templates are file/config only.
 */
class Migration_Prefix_display_and_editor_template_uids extends MY_Migration {

	public function up()
	{
		if (!$this->db->table_exists('display_templates')) {
			return;
		}

		foreach ($this->uid_map() as $old => $new) {
			$this->remap_uid($old, $new);
		}

		foreach ($this->file_path_map() as $old => $new) {
			$this->db->where('file_path', $old);
			$this->db->update('display_templates', array('file_path' => $new));
		}

		if ($this->db->table_exists('display_templates_default')) {
			foreach ($this->uid_map() as $old => $new) {
				$this->db->where('template_uid', $old);
				$this->db->update('display_templates_default', array('template_uid' => $new));
			}
		}

		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}

	/**
	 * @param string $old
	 * @param string $new
	 * @return void
	 */
	private function remap_uid($old, $new)
	{
		$this->db->from('display_templates');
		$this->db->where('uid', $old);
		$this->db->where('is_deleted', 0);
		$old_row = $this->db->get()->row_array();

		$this->db->from('display_templates');
		$this->db->where('uid', $new);
		$this->db->where('is_deleted', 0);
		$new_row = $this->db->get()->row_array();

		if ($old_row && !$new_row) {
			$this->db->where('id', (int) $old_row['id']);
			$this->db->update('display_templates', array('uid' => $new));
			return;
		}

		if ($old_row && $new_row) {
			$source = isset($old_row['source']) ? $old_row['source'] : '';
			$type = isset($old_row['template_type']) ? $old_row['template_type'] : '';
			if ($source === 'file' || $type === 'system') {
				$this->db->where('id', (int) $old_row['id']);
				$this->db->update('display_templates', array('is_deleted' => 1));
			}
		}
	}

	/**
	 * @return array<string, string>
	 */
	private function uid_map()
	{
		$old = array(
			'survey-system-en',
			'timeseries-system-en',
			'timeseries-db-system-en',
			'script-system-en',
			'geospatial-system-en',
			'geospatial-gemini-inspire-en',
			'document-system-en',
			'document-system-es',
			'table-system-en',
			'image-system-en',
			'image-system-dcmi',
			'image-system-iptc',
			'video-system-en',
			'resource-system-en',
			'microdata-system-es',
			'6740f5f920502baf3f6cbcaa5c113deeen',
			'232ea3aaece0cdf1db157f797f6b92e5fr',
			'6740f5f920502baf3f6cbcaa5c113uzbek',
			'8603d94e27bccc2bdad1e00dbbf0fe32en',
			'b576eb7519fe8e761a239f9d36f032c3fr',
			'f3e2d1c8c494be27bc229463a265a33d',
			'776d77524fe8130f520035fb9b077d82',
			'd0e54377885c64c360259b65398e319den',
			'd31789f2d6dcdf3c7dc07a5729d09ac9fr',
			'2f62a6b2716ab55b4426005abdbe1600en',
			'4915f93564fbde26945dd9022b9d7fc1fr',
			'4b56b6c4ec82324c7c2865ad61c4f2c0en',
			'9454eee369c79c65cdc0b4ee23aed4e8fr',
			'0d8e25111cee667a4b4636088cdb33e3',
			'6192765f2dbddb6bd93f3f92a29129d3fr',
			'2bfd3fb47e291331a43e949e9e38675een',
			'21769091754bb7c998a1caf0fa75800cfr',
			'1f899824931c133f162f584c928d4256',
			'e3e5a1d8bd0338c1b3a5d6bfff8e5517',
			'e6670ad469892a3871ee0e5d47cf243den',
			'453a8bf9f800465f3cb8dc37699cb574',
		);

		$map = array();
		foreach ($old as $uid) {
			$map[$uid] = 'display-'.$uid;
		}
		return $map;
	}

	/**
	 * @return array<string, string>
	 */
	private function file_path_map()
	{
		return array(
			'templates/display/survey_display_template.json' => 'templates/display/display_survey_template.json',
			'templates/display/timeseries_display_template.json' => 'templates/display/display_timeseries_template.json',
			'templates/display/timeseriesdb_display_template.json' => 'templates/display/display_timeseries-db_template.json',
			'templates/display/script_display_template.json' => 'templates/display/display_script_template.json',
			'templates/display/geospatial_display_template.json' => 'templates/display/display_geospatial_template.json',
			'templates/display/document_display_template.json' => 'templates/display/display_document_template.json',
			'templates/display/table_display_template.json' => 'templates/display/display_table_template.json',
			'templates/display/image_display_template.json' => 'templates/display/display_image_template.json',
			'templates/display/video_display_template.json' => 'templates/display/display_video_template.json',
			'templates/display/resource_display_template.json' => 'templates/display/display_resource_template.json',
			'templates/editor/survey_template_ihsn_2.5_v1.json' => 'templates/editor/edit_survey_template_ihsn_2.5_v1.json',
			'templates/editor/survey_template_ihsn_2.5_v1_fr.json' => 'templates/editor/edit_survey_template_ihsn_2.5_v1_fr.json',
			'templates/editor/survey_template_es.json' => 'templates/editor/edit_survey_template_es.json',
			'templates/editor/microdata_template_uzbek.json' => 'templates/editor/edit_microdata_template_uzbek.json',
			'templates/editor/timeseries_template_ihsn.json' => 'templates/editor/edit_timeseries_template_ihsn.json',
			'templates/editor/timeseries_template_ihsn_fr.json' => 'templates/editor/edit_timeseries_template_ihsn_fr.json',
			'templates/editor/timeseries-db_template_ihsn.json' => 'templates/editor/edit_timeseries-db_template_ihsn.json',
			'templates/editor/timeseries-db_template_ihsn_fr.json' => 'templates/editor/edit_timeseries-db_template_ihsn_fr.json',
			'templates/editor/script_template_ihsn.json' => 'templates/editor/edit_script_template_ihsn.json',
			'templates/editor/script_template_ihsn_fr.json' => 'templates/editor/edit_script_template_ihsn_fr.json',
			'templates/editor/geospatial_form_template_gemini.json' => 'templates/editor/edit_geospatial_form_template_gemini.json',
			'templates/editor/document_form_template_es.json' => 'templates/editor/edit_document_form_template_es.json',
			'templates/editor/document_template_ihsn.json' => 'templates/editor/edit_document_template_ihsn.json',
			'templates/editor/document_template_ihsn_fr.json' => 'templates/editor/edit_document_template_ihsn_fr.json',
			'templates/editor/table_template_ihsn.json' => 'templates/editor/edit_table_template_ihsn.json',
			'templates/editor/table_template_ihsn_fr.json' => 'templates/editor/edit_table_template_ihsn_fr.json',
			'templates/editor/image_dcmi_form_template.json' => 'templates/editor/edit_image_dcmi_form_template.json',
			'templates/editor/image_iptc_form_template.json' => 'templates/editor/edit_image_iptc_form_template.json',
			'templates/editor/image_dcmi_template_ihsn.json' => 'templates/editor/edit_image_dcmi_template_ihsn.json',
			'templates/editor/image_dcmi_template_ihsn_fr.json' => 'templates/editor/edit_image_dcmi_template_ihsn_fr.json',
			'templates/editor/image_iptc_template_ihsn.json' => 'templates/editor/edit_image_iptc_template_ihsn.json',
			'templates/editor/image_iptc_template_ihsn_fr.json' => 'templates/editor/edit_image_iptc_template_ihsn_fr.json',
			'templates/editor/video_template_ihsn.json' => 'templates/editor/edit_video_template_ihsn.json',
			'templates/editor/video_template_ihsn_fr.json' => 'templates/editor/edit_video_template_ihsn_fr.json',
			'templates/editor/resource_template_ihsn.json' => 'templates/editor/edit_resource_template_ihsn.json',
			'templates/editor/resource_template_ihsn_fr.json' => 'templates/editor/edit_resource_template_ihsn_fr.json',
		);
	}
}
