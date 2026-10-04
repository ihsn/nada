<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Move site_user_register from file config to `configurations` (site settings).
 */
class Migration_Add_site_user_register_configuration extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'site_user_register');
		$q = $this->db->get('configurations');
		if ($q && $q->num_rows() > 0) {
			return;
		}

		$this->db->insert(
			'configurations',
			array(
				'name'       => 'site_user_register',
				'value'      => 'yes',
				'label'      => 'Allow user self-registration',
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

		$this->db->where('name', 'site_user_register');
		$this->db->delete('configurations');
	}
}
