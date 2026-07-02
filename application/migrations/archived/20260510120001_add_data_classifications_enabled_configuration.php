<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Move data_classifications_enabled from file config to `configurations` (site settings).
 */
class Migration_Add_data_classifications_enabled_configuration extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'data_classifications_enabled');
		$q = $this->db->get('configurations');
		if ($q && $q->num_rows() > 0) {
			return;
		}

		$this->db->insert(
			'configurations',
			array(
				'name'       => 'data_classifications_enabled',
				'value'      => 'yes',
				'label'      => 'Enable data classifications',
				'helptext'   => null,
				'item_group' => null,
			)
		);
	}

	public function down()
	{
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'data_classifications_enabled');
		$this->db->delete('configurations');
	}
}
