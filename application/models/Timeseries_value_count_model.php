<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Timeseries_value_count_model
 *
 * Aggregated observed value counts by study/DSD/component/code.
 */
class Timeseries_value_count_model extends CI_Model {

	/**
	 * Replace all rows for one study (and optionally one DSD) with the supplied aggregate rows.
	 *
	 * @param int   $sid
	 * @param array $rows each row: dsd_id, component_name, code, obs_count
	 * @param int|null $dsd_id Optional scope limiter for delete step
	 * @return int inserted row count
	 * @throws Exception
	 */
	public function replace_counts_for_sid($sid, array $rows, $dsd_id = null)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			throw new Exception('Invalid sid');
		}

		$this->db->trans_begin();
		$this->db->where('sid', $sid);
		if ($dsd_id !== null) {
			$this->db->where('dsd_id', (int) $dsd_id);
		}
		$this->db->delete('timeseries_value_counts');

		$inserted = 0;
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$did = isset($row['dsd_id']) ? (int) $row['dsd_id'] : 0;
			$component = isset($row['component_name']) ? trim((string) $row['component_name']) : '';
			$code = isset($row['code']) ? trim((string) $row['code']) : '';
			$count = isset($row['obs_count']) ? (int) $row['obs_count'] : 0;
			if ($did <= 0 || $component === '' || $code === '' || $count < 1) {
				continue;
			}
			$this->db->insert('timeseries_value_counts', [
				'sid'            => $sid,
				'dsd_id'         => $did,
				'component_name' => $component,
				'code'           => $code,
				'obs_count'      => $count,
			]);
			$inserted++;
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			throw new Exception('Failed to replace timeseries value counts');
		}
		$this->db->trans_commit();
		return $inserted;
	}

	/**
	 * Return observed value counts for one study/DSD/component.
	 *
	 * @param int    $sid
	 * @param int    $dsd_id
	 * @param string $component_name
	 * @return array
	 */
	public function get_counts($sid, $dsd_id, $component_name)
	{
		$this->db->select('code, obs_count');
		$this->db->from('timeseries_value_counts');
		$this->db->where('sid', (int) $sid);
		$this->db->where('dsd_id', (int) $dsd_id);
		$this->db->where('component_name', trim((string) $component_name));
		$this->db->order_by('obs_count', 'DESC');
		$this->db->order_by('code', 'ASC');
		$_r = $this->db->get();
		return $_r ? $_r->result_array() : [];
	}

	/**
	 * Summary counts for one study + DSD, grouped by component_name.
	 *
	 * @param int $sid
	 * @param int $dsd_id
	 * @return array{total_rows:int,total_distinct_codes:int,total_observations:int,components:array}
	 */
	public function get_summary($sid, $dsd_id)
	{
		$sid = (int) $sid;
		$dsd_id = (int) $dsd_id;
		if ($sid <= 0 || $dsd_id <= 0) {
			return [
				'total_rows' => 0,
				'total_distinct_codes' => 0,
				'total_observations' => 0,
				'components' => [],
			];
		}

		$this->db->select('component_name, COUNT(*) AS distinct_codes, SUM(obs_count) AS observations', false);
		$this->db->from('timeseries_value_counts');
		$this->db->where('sid', $sid);
		$this->db->where('dsd_id', $dsd_id);
		$this->db->group_by('component_name');
		$this->db->order_by('component_name', 'ASC');
		$_r = $this->db->get();
		$rows = $_r ? $_r->result_array() : [];

		$components = [];
		$totalRows = 0;
		$totalObservations = 0;
		foreach ($rows as $row) {
			$distinct = isset($row['distinct_codes']) ? (int) $row['distinct_codes'] : 0;
			$obs = isset($row['observations']) ? (int) $row['observations'] : 0;
			$totalRows += $distinct;
			$totalObservations += $obs;
			$components[] = [
				'component_name' => isset($row['component_name']) ? (string) $row['component_name'] : '',
				'distinct_codes' => $distinct,
				'observations' => $obs,
			];
		}

		return [
			'total_rows' => $totalRows,
			'total_distinct_codes' => $totalRows,
			'total_observations' => $totalObservations,
			'components' => $components,
		];
	}
}
