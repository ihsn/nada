<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Public Timeseries API (read-only).
 *
 * Base: /api/timeseries — no auth required (see application/config/rest.php).
 *
 * Study data (catalogue dataset idno; must be published OR caller has dataset view access):
 *   GET .../data/{idno}/schema
 *   GET .../data/{idno}/export — canonical JSON export (data_structure + components + codelist summary)
 *   GET .../data/{idno}/count
 *   GET .../data/{idno} — d[role] / c[COMPONENT] / legacy; paging: offset (or skip), limit, sort (asc|desc), sort_by (optional API column: sid, period_start, period_end, reporting_year, reporting_freq, or DSD component name); result includes total, found, offset; row JSON strips internal fields and adds period_start / period_end (ISO 8601 UTC), reporting_year / reporting_freq from _ts_year / _ts_freq (see strip_public_observation_fields + append_public_observation_timeseries_fields).
 *   GET .../data/{idno}/chart — same filters as data list (from/to on _ts_year, d[…], c[…]); optional limit (capped); returns chart-ready records (time_period, observation_value, series_key, plus series-dimension fields) aggregated server-side from Mongo rows (see Timeseries_mongo_model::build_catalog_chart_records). Metadata includes time_bounds (min/max time_period among returned chart rows).
 *   GET .../data/{idno}/schema — includes time_period_component and observation_value_component (DSD field names) when present.
 *
 * Global data structures (same catalogue as admin, read-only):
 *   GET .../data-structures/by_idno/{idno}     ?with_components=1|0
 *   GET .../data-structures/by_identity/{name} ?agency=&version=&with_components=
 *   GET .../data-structures/versions/{name}    ?agency=
 *   GET .../data-structures/item/{id}         ?with_components=
 *
 * Global codelists (read-only):
 *   GET .../codelists/by_idno/{idno}
 *   GET .../codelists/by_name/{name}           ?agency=&version=
 *   GET .../codelists/item/{id}
 *   GET .../codelists/item/{id}/items — paging like data list: offset (or skip), limit, search; result items, limit, offset, total, found, codelist_id
 *
 * @see application/config/routes.php
 */
class Timeseries_public extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Timeseries_mongo_model');
		$this->load->model('Timeseries_dsd_model');
		$this->load->model('Timeseries_value_count_model');
		$this->load->model('Dataset_model');
		$this->load->model('Data_structure_model');
		$this->load->model('Data_structure_component_model');
		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');
		$this->load->model('Codelist_group_model');
		$this->config->load('indicator_timeseries');
	}

	public function _auth_override_check()
	{
		return true;
	}

	// ---------------------------------------------------------------------
	// Study-scoped
	// ---------------------------------------------------------------------

	public function data_count_get($idno = null)
	{
		try {
			$ctx    = $this->_context_from_idno_public($idno);
			$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
				(int) $ctx['sid'],
				$ctx['components'],
				(array) $this->input->get()
			);
			$count  = $this->Timeseries_mongo_model->count_observations($ctx['dsd_id'], $filter);
			$this->set_response([
				'status' => 'success',
				'result' => ['count' => $count],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function data_get($idno = null)
	{
		try {
			$ctx    = $this->_context_from_idno_public($idno);
			$query  = (array) $this->input->get();
			$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
				(int) $ctx['sid'],
				$ctx['components'],
				$query
			);
			$limit  = $this->_query_limit();
			$offset = Timeseries_mongo_model::parse_observation_list_offset($query);
			$dir    = $this->get('sort') === 'desc' ? -1 : 1;
			$sortBy = isset($query['sort_by']) ? trim((string) $query['sort_by']) : '';
			$sort   = $this->Timeseries_mongo_model->build_public_observations_list_sort(
				$sortBy !== '' ? $sortBy : null,
				$dir,
				$ctx['components']
			);
			$rows = $this->Timeseries_mongo_model->find_observations($ctx['dsd_id'], $filter, [
				'limit' => $limit,
				'skip'  => $offset,
				'sort'  => $sort,
			]);
			$list  = $this->_bson_list_to_array($rows);
			$obs   = [];
			foreach ($list as $row) {
				$arr   = is_array($row) ? $row : [];
				$strip = $this->Timeseries_mongo_model->strip_public_observation_fields($arr);
				$obs[] = $this->Timeseries_mongo_model->append_public_observation_timeseries_fields($arr, $strip);
			}
			$total = $this->Timeseries_mongo_model->count_observations($ctx['dsd_id'], $filter);
			$found = count($obs);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'data'         => $obs,
					'limit'        => $limit,
					'offset'       => $offset,
					'total'        => $total,
					'found'        => $found,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * Chart rows for catalog visualization (Metadata Editor–style records), aggregated on the server.
	 *
	 * GET .../data/{idno}/chart — query params match data list (from, to on reporting year _ts_year, d[…], c[…]); optional limit (capped by indicator_timeseries_chart_max_raw_rows).
	 */
	public function data_chart_get($idno = null)
	{
		try {
			$ctx    = $this->_context_from_idno_public($idno);
			$query  = (array) $this->input->get();
			$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
				(int) $ctx['sid'],
				$ctx['components'],
				$query
			);
			$maxRaw = $this->_query_chart_raw_limit();
			$rows   = $this->Timeseries_mongo_model->find_observations($ctx['dsd_id'], $filter, [
				'limit' => $maxRaw,
				'skip'  => 0,
				'sort'  => ['_ts_year' => 1, '_ts_period_start' => 1],
			]);
			$list = $this->_bson_list_to_array($rows);
			$obs  = [];
			foreach ($list as $row) {
				$arr   = is_array($row) ? $row : [];
				$strip = $this->Timeseries_mongo_model->strip_public_observation_fields($arr);
				$obs[] = $this->Timeseries_mongo_model->append_public_observation_timeseries_fields($arr, $strip);
			}
			$built       = $this->Timeseries_mongo_model->build_catalog_chart_records($obs, $ctx['components']);
			$records     = isset($built['records']) && is_array($built['records']) ? $built['records'] : array();
			$time_bounds = isset($built['time_bounds']) && is_array($built['time_bounds']) ? $built['time_bounds'] : array();
			$tb_min      = array_key_exists('min', $time_bounds) ? $time_bounds['min'] : null;
			$tb_max      = array_key_exists('max', $time_bounds) ? $time_bounds['max'] : null;
			$scanned     = count($obs);
			$truncated   = $scanned >= $maxRaw;
			$this->set_response([
				'status' => 'success',
				'result' => [
					'records'  => $records,
					'metadata' => [
						'source_rows_scanned' => $scanned,
						'chart_points'        => count($records),
						'raw_row_limit'       => $maxRaw,
						'truncated'           => $truncated,
						'time_bounds'         => array(
							'min' => $tb_min,
							'max' => $tb_max,
						),
					],
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function data_schema_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno_public($idno);
			$tpField = $this->Timeseries_mongo_model->get_component_name_for_column_type($ctx['components'], 'time_period');
			$ovField = $this->Timeseries_mongo_model->get_component_name_for_column_type($ctx['components'], 'observation_value');
			$yearBounds = $this->Timeseries_mongo_model->reporting_year_min_max_for_study(
				(int) $ctx['dsd_id'],
				(int) $ctx['sid'],
				$ctx['components']
			);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid'            => (int) $ctx['sid'],
					'idno'           => $ctx['idno'],
					'dsd_id'         => (int) $ctx['dsd_id'],
					'data_structure' => $ctx['structure'],
					'components'     => $ctx['components'],
					'collection'     => $this->Timeseries_mongo_model->get_collection_name((int) $ctx['dsd_id']),
					'time_period_component'     => $tpField,
					'observation_value_component' => $ovField,
					'reporting_year_bounds' => [
						'min' => $yearBounds['min'],
						'max' => $yearBounds['max'],
					],
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/export
	 *
	 * Returns a canonical DSD export payload for the dataset-linked structure:
	 * - data_structure: structure metadata
	 * - components: component rows with codelist summary attached (when present)
	 */
	public function data_export_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno_public($idno);
			$components = isset($ctx['components']) && is_array($ctx['components']) ? $ctx['components'] : [];
			$exportPayload = $this->Timeseries_dsd_model->build_inline_dsd_export_from_structure_components(
				$ctx['structure'],
				$components
			);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'export' => $exportPayload,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/filter-options
	 *
	 * Returns observed-only code options per codelist-backed component.
	 */
	public function data_filter_options_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno_public($idno);
			$filters = [];
			foreach ($ctx['components'] as $component) {
				if (!is_array($component)) {
					continue;
				}
				$componentName = isset($component['name']) ? trim((string) $component['name']) : '';
				$codelistId = isset($component['codelist_id']) ? (int) $component['codelist_id'] : 0;
				if ($componentName === '' || $codelistId <= 0) {
					continue;
				}
				$counts = $this->Timeseries_value_count_model->get_counts(
					(int) $ctx['sid'],
					(int) $ctx['dsd_id'],
					$componentName
				);
				if (empty($counts)) {
					continue;
				}
				$codes = [];
				foreach ($counts as $row) {
					if (!is_array($row) || !isset($row['code'])) {
						continue;
					}
					$code = trim((string) $row['code']);
					if ($code !== '') {
						$codes[] = $code;
					}
				}
				if (empty($codes)) {
					continue;
				}
				$labels = $this->Codelist_item_model->get_items_map_by_codes($codelistId, $codes);
				$options = [];
				foreach ($counts as $row) {
					if (!is_array($row) || !isset($row['code'])) {
						continue;
					}
					$code = trim((string) $row['code']);
					if ($code === '') {
						continue;
					}
					$label = isset($labels[$code]) && array_key_exists('title', $labels[$code]) && $labels[$code]['title'] !== null && trim((string) $labels[$code]['title']) !== ''
						? (string) $labels[$code]['title']
						: $code;
					$options[] = [
						'code' => $code,
						'label' => $label,
						'count' => isset($row['obs_count']) ? (int) $row['obs_count'] : 0,
					];
				}
				usort($options, function ($a, $b) {
					$al = isset($a['label']) ? (string) $a['label'] : '';
					$bl = isset($b['label']) ? (string) $b['label'] : '';
					$c = strcmp($al, $bl);
					if ($c !== 0) {
						return $c;
					}
					$ac = isset($a['code']) ? (string) $a['code'] : '';
					$bc = isset($b['code']) ? (string) $b['code'] : '';
					return strcmp($ac, $bc);
				});
				if (empty($options)) {
					continue;
				}
				$filters[] = [
					'component_name' => $componentName,
					'column_type' => isset($component['column_type']) ? (string) $component['column_type'] : null,
					'codelist_id' => $codelistId,
					'options' => $options,
				];
			}

			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid' => (int) $ctx['sid'],
					'idno' => $ctx['idno'],
					'dsd_id' => (int) $ctx['dsd_id'],
					'filters' => $filters,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// ---------------------------------------------------------------------
	// Global data structures
	// ---------------------------------------------------------------------

	public function structure_by_idno_get($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode((string) $idno) : '';
			if ($idno === '') {
				$idno = (string) $this->get('idno');
			}
			$with = $this->get('with_components') !== '0' && $this->get('with_components') !== false;
			$row  = $this->Data_structure_model->get_structure_by_idno($idno);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if ($with) {
				$row['components'] = $this->Data_structure_component_model->get_components_by_structure_id((int) $row['id']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function structure_by_identity_get($name)
	{
		try {
			$agency  = $this->get('agency');
			$version = $this->get('version');
			$with    = $this->get('with_components') !== '0' && $this->get('with_components') !== false;
			$row     = $this->Data_structure_model->get_structure_by_identity($name, $agency ?: null, $version ?: null);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if ($with) {
				$row['components'] = $this->Data_structure_component_model->get_components_by_structure_id((int) $row['id']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function structure_versions_get($name)
	{
		try {
			$agency = $this->get('agency');
			$rows   = $this->Data_structure_model->get_structure_versions($name, $agency ?: null);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structures' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function structure_item_get($id)
	{
		try {
			$with = $this->get('with_components') !== '0' && $this->get('with_components') !== false;
			$row  = $this->Data_structure_model->get_structure_by_id((int) $id, $with);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// ---------------------------------------------------------------------
	// Global codelists
	// ---------------------------------------------------------------------

	public function codelist_by_idno_get($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode((string) $idno) : '';
			if ($idno === '') {
				$idno = (string) $this->get('idno');
			}
			$codelist = $this->Codelist_model->get_codelist_by_idno($idno);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$id = (int) $codelist['id'];
			$codelist['items']  = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function codelist_by_name_get($name)
	{
		try {
			$name    = rawurldecode((string) $name);
			$agency  = $this->get('agency');
			$version = $this->get('version');
			$codelist = $this->Codelist_model->get_codelist_by_name($name, $agency ?: null, $version ?: null);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$id = (int) $codelist['id'];
			$codelist['items']  = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	public function codelist_item_get($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$codelist['items']  = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * Paginated items for a single codelist (anonymous-readable).
	 *
	 * Paging matches GET …/data/{idno}: offset (or skip), limit (same caps as indicator_timeseries_* config via _query_limit()).
	 *
	 * Query params:
	 *   offset | skip  int >= 0 (default 0)
	 *   limit           int    (default/max from config, same as data list)
	 *   search          string (optional substring match on code/title)
	 *
	 * Response (same shape as data list for paging fields):
	 *   { status, result: { codelist_id, items, limit, offset, total, found } }
	 */
	public function codelist_item_items_get($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$query  = (array) $this->input->get();
			$limit  = $this->_query_limit();
			// Keep in sync with Codelist_item_model::get_items_by_codelist_paged per_page cap (500).
			$limit  = min($limit, 500);
			$offset = Timeseries_mongo_model::parse_observation_list_offset($query);
			$search = trim((string) $this->get('search'));

			$page = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;

			$paged = $this->Codelist_item_model->get_items_by_codelist_paged($id, [
				'page'              => $page,
				'per_page'          => $limit,
				'search'            => $search,
				'with_translations' => false,
			]);

			$rows  = $paged['rows'] ?? [];
			$found = count($rows);

			$effectiveLimit = (int) ($paged['per_page'] ?? $limit);

			$this->set_response([
				'status' => 'success',
				'result' => [
					'codelist_id' => $id,
					'items'       => $rows,
					'limit'       => $effectiveLimit,
					'offset'      => $offset,
					'total'       => (int) ($paged['total'] ?? 0),
					'found'       => $found,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * Published catalogue studies are readable anonymously; unpublished require dataset view ACL.
	 */
	private function _assert_dataset_readable_for_public_api($sid)
	{
		$sid = (int) $sid;
		if ($this->Dataset_model->is_published($sid)) {
			return;
		}
		try {
			$this->has_dataset_access('view', $sid);
		} catch (Exception $e) {
			$m = $e->getMessage();
			if ($m === 'ACCESS-DENIED' || stripos($m, 'access denied') !== false) {
				throw new Exception('ACCESS-DENIED');
			}
			throw $e;
		}
	}

	/**
	 * @return array{sid:int,idno:string,dsd_id:int,structure:array,components:array}
	 */
	private function _context_from_idno_public($idno)
	{
		$idno = $idno !== null ? rawurldecode((string) $idno) : '';
		if ($idno === '') {
			throw new Exception('Dataset idno is required');
		}
		$sid = $this->get_sid_from_idno($idno);
		$this->_assert_dataset_readable_for_public_api($sid);
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			throw new Exception('No data structure linked for this dataset (set metadata.data_structure_reference to the global DSD idno)');
		}
		$row = $this->Dataset_model->get_row((int) $sid);
		$idno_out = $row && !empty($row['idno']) ? (string) $row['idno'] : (string) $idno;
		return [
			'sid'        => (int) $sid,
			'idno'       => $idno_out,
			'dsd_id'     => (int) $ctx['dsd_id'],
			'structure'  => $ctx['structure'],
			'components' => $ctx['components'],
		];
	}

	private function _query_limit()
	{
		$def = (int) $this->config->item('indicator_timeseries_default_page_size');
		$max = (int) $this->config->item('indicator_timeseries_max_page_size');
		if ($def < 1) {
			$def = 100;
		}
		if ($max < 1) {
			$max = 500;
		}
		$lim = $this->input->get('limit');
		if ($lim === null || $lim === '') {
			return $def;
		}
		$lim = (int) $lim;
		if ($lim < 1) {
			return $def;
		}
		return min($lim, $max);
	}

	/**
	 * Max Mongo documents to read for GET …/chart (optional request limit is capped).
	 *
	 * @return int
	 */
	private function _query_chart_raw_limit()
	{
		$max = (int) $this->config->item('indicator_timeseries_chart_max_raw_rows');
		if ($max < 1) {
			$max = 5000;
		}
		$lim = $this->input->get('limit');
		if ($lim === null || $lim === '') {
			return $max;
		}
		$lim = (int) $lim;
		if ($lim < 1) {
			return $max;
		}
		return min($lim, $max);
	}

	private function _bson_list_to_array(array $rows)
	{
		$out = [];
		foreach ($rows as $doc) {
			$out[] = json_decode(json_encode($doc), true);
		}
		return $out;
	}

	private function _error_response(Exception $e)
	{
		$msg = $e->getMessage();
		$code = REST_Controller::HTTP_BAD_REQUEST;
		if ($msg === 'IDNO-NOT-FOUND' || stripos($msg, 'not found') !== false) {
			$code = REST_Controller::HTTP_NOT_FOUND;
		}
		if ($msg === 'ACCESS-DENIED') {
			$code = REST_Controller::HTTP_FORBIDDEN;
		}
		$this->set_response([
			'status'  => 'error',
			'message' => $msg === 'INVALID_JSON_INPUT' ? 'Invalid JSON body' : $msg,
		], $code);
	}
}
