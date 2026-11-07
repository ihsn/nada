<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Remove duplicate display template defaults stored under both timeseries-db and
 * timeseriesdb. Catalog lookup checks timeseriesdb first, so an older row there hid
 * the default chosen in the admin. Keeps the most recently updated row.
 */
class Migration_Display_default_alias_cleanup extends MY_Migration {

	public function up()
	{
		if (! $this->db->table_exists('display_templates_default')) {
			return;
		}

		$rows = $this->db->from('display_templates_default')
			->where_in('data_type', array('timeseries-db', 'timeseriesdb'))
			->order_by('updated_at', 'DESC')
			->order_by('id', 'DESC')
			->get()
			->result_array();

		if (count($rows) < 2) {
			return;
		}

		$keep = array_shift($rows);
		$stale_ids = array_map('intval', array_column($rows, 'id'));
		$this->db->where_in('id', $stale_ids)->delete('display_templates_default');

		$this->emit('Kept ' . $keep['data_type'] . ' default ' . $keep['template_uid']
			. '; removed ' . count($stale_ids) . ' alias row(s).' . PHP_EOL);
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
