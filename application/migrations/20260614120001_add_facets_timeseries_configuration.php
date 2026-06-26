<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Add ts_database to the facets registry and add facets_timeseries configuration.
 * The facets table drives the admin reorder UI; without a row there the facet
 * is invisible to admins even if the config value references it.
 */
class Migration_Add_facets_timeseries_configuration extends MY_Migration {

	public function up()
	{
		// 1. Register ts_database in the facets table so it appears in the admin UI
		if ($this->db->table_exists('facets')) {
			$this->db->where('name', 'database');
			$q = $this->db->get('facets');
			if (! $q || $q->num_rows() === 0) {
				$this->db->insert('facets', array(
					'name'       => 'database',
					'title'      => 'Dataset',
					'facet_type' => 'core',
					'enabled'    => 1,
				));
			}
		}

		// 2. Add facets_timeseries configuration
		if (! $this->db->table_exists('configurations')) {
			return;
		}

		$this->db->where('name', 'facets_timeseries');
		$q = $this->db->get('configurations');
		if ($q && $q->num_rows() > 0) {
			return;
		}

		$this->db->insert(
			'configurations',
			array(
				'name'       => 'facets_timeseries',
				'value'      => '["database","country"]',
				'label'      => null,
				'helptext'   => null,
				'item_group' => null,
			)
		);
	}

}
