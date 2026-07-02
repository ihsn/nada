<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Public catalog search UI: classic or Vue shell (both at /catalog).
 */
class Migration_Add_catalog_public_search_ui_configuration extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'catalog_public_search_ui');
		$q = $this->db->get('configurations');
		if ($q && $q->num_rows() > 0) {
			return;
		}

		$this->db->insert(
			'configurations',
			array(
				'name'       => 'catalog_public_search_ui',
				'value'      => 'classic',
				'label'      => 'Public catalog search UI',
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

		$this->db->where('name', 'catalog_public_search_ui');
		$this->db->delete('configurations');
	}
}
