<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: replace the separate French display template cores with translations on the core templates.
 *
 * French labels now ship as {core}.translations.json sidecars next to each core display template. The old
 * "IHSN … Modèle v01 FR" rows (full copies of the editor layouts) are no longer registered in
 * config/display_templates.php, but the sync never deletes rows, so existing sites still list them.
 *
 * For each old FR core this removes the row and its translations, unless a site has set it as the
 * default for a data type (then it is left in place and reported). Only file-backed rows that still
 * point at the shipped editor file are removed; copies an admin made are separate rows and are not touched.
 *
 * Then re-syncs the shipped cores so the new French sidecars are loaded into display_template_translations.
 * The sync only fills empty labels, so translations an admin has edited are kept.
 *
 * Safe to run more than once.
 */
class Migration_Display_templates_fr_cores_to_translations extends MY_Migration {

	private $fr_cores = array(
		'display-232ea3aaece0cdf1db157f797f6b92e5fr' => 'templates/editor/edit_survey_template_ihsn_2.5_v1_fr.json',
		'display-b576eb7519fe8e761a239f9d36f032c3fr' => 'templates/editor/edit_timeseries_template_ihsn_fr.json',
		'display-776d77524fe8130f520035fb9b077d82' => 'templates/editor/edit_timeseries-db_template_ihsn_fr.json',
		'display-d31789f2d6dcdf3c7dc07a5729d09ac9fr' => 'templates/editor/edit_script_template_ihsn_fr.json',
		'display-4915f93564fbde26945dd9022b9d7fc1fr' => 'templates/editor/edit_document_template_ihsn_fr.json',
		'display-9454eee369c79c65cdc0b4ee23aed4e8fr' => 'templates/editor/edit_table_template_ihsn_fr.json',
		'display-6192765f2dbddb6bd93f3f92a29129d3fr' => 'templates/editor/edit_image_dcmi_template_ihsn_fr.json',
		'display-21769091754bb7c998a1caf0fa75800cfr' => 'templates/editor/edit_image_iptc_template_ihsn_fr.json',
		'display-e3e5a1d8bd0338c1b3a5d6bfff8e5517' => 'templates/editor/edit_video_template_ihsn_fr.json',
		'display-453a8bf9f800465f3cb8dc37699cb574' => 'templates/editor/edit_resource_template_ihsn_fr.json',
	);

	public function up()
	{
		$this->db->data_cache = array();

		if (!$this->table_exists('display_templates')) {
			$this->emit("  display_templates does not exist, skipping\n");
			return;
		}

		$has_defaults = $this->table_exists('display_templates_default');
		$has_translations = $this->table_exists('display_template_translations');

		foreach ($this->fr_cores as $uid => $file_path) {
			$row = $this->db->select('id, uid, source, file_path')
				->from('display_templates')
				->where('uid', $uid)
				->get()->row_array();

			if (!$row) {
				continue;
			}
			if ((string) $row['source'] !== 'file' || (string) $row['file_path'] !== $file_path) {
				$this->emit("  {$uid}: not the shipped file-backed core, left in place\n");
				continue;
			}
			if ($has_defaults) {
				$default = $this->db->select('data_type')
					->from('display_templates_default')
					->where('template_uid', $uid)
					->get()->row_array();
				if ($default) {
					$this->emit("  {$uid}: site default for '{$default['data_type']}', left in place\n");
					continue;
				}
			}

			if ($has_translations) {
				$this->assert_db_query(
					$this->db->where('template_id', (int) $row['id'])->delete('display_template_translations'),
					'delete translations for ' . $uid
				);
			}
			$this->assert_db_query(
				$this->db->where('id', (int) $row['id'])->delete('display_templates'),
				'delete ' . $uid
			);
			$this->emit("  removed {$uid}\n");
		}

		$this->load->helper('display_template');
		$this->load->model('Display_template_model');
		$this->Display_template_model->sync_shipped_cores();
		$this->emit("  synced shipped cores and translations\n");
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
