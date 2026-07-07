<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin Codelists API
 *
 * REST API for managing codelists, items, item translations, groups, group items, group translations.
 * Base URL: /api/admin/codelists
 * Auth: session or API key (is_authenticated_or_die).
 */
class Codelists extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');
		$this->load->model('Codelist_group_model');
		$this->_apply_codelist_acl();
	}

	private function _apply_codelist_acl()
	{
		$method = strtolower((string) $this->router->fetch_method());
		if ($method === '' || $method === 'index') {
			return;
		}
		if (preg_match('/_get$/', $method)) {
			$this->require_access('codelist', 'view');
			return;
		}
		if (preg_match('/delete/', $method)) {
			$this->require_access('codelist', 'delete');
			return;
		}
		if ($method === 'index_post') {
			$this->require_access('codelist', 'create');
			return;
		}
		if (preg_match('/_(post|put)$/', $method)) {
			$this->require_access('codelist', 'edit');
		}
	}

	/**
	 * GET /api/admin/codelists — list codelists (with optional item_count; group_count omitted on catalogue rows).
	 *
	 * Default: one row per codelist family (latest head: id = pid), including versions_count.
	 * Use flat=1 for one row per stored version (full table). Alias: collapsed=1 forces family view;
	 * collapsed=0 forces flat. flat=1 wins when both are set.
	 *
	 * With `page` (1-based): returns one page plus `total`, `page`, `per_page`.
	 * Optional: `per_page` (default 50, max 200), `search` or `q`, `status` (exact smallint).
	 * With `with_counts` (default on): item_count and dsd_component_count (main grid only; no group_count).
	 * dsd_component_count: flat = by codelists.id; collapsed = DSD refs to any version with same agency+name.
	 */
	public function index_get()
	{
		try {
			$with_counts = $this->get('with_counts') !== '0' && $this->get('with_counts') !== false;
			$flat = $this->_codelists_catalog_flat_mode();
			$status_raw = $this->get('status');
			$status = ($status_raw !== null && $status_raw !== false && $status_raw !== '')
				? (int) $status_raw
				: null;
			$page_raw = $this->get('page');
			$use_paged = ($page_raw !== null && $page_raw !== false && $page_raw !== '');
			if ($use_paged) {
				$page = max(1, (int) $page_raw);
				$per_raw = $this->get('per_page');
				$per_page = ($per_raw !== null && $per_raw !== false && $per_raw !== '') ? (int) $per_raw : 50;
				$search = $this->get('search');
				if ($search === null || $search === false) {
					$search = $this->get('q');
				}
				$search = is_string($search) ? $search : '';
				$p = $this->Codelist_model->get_codelists_paged([
					'page'        => $page,
					'per_page'    => $per_page,
					'search'      => $search,
					'with_counts' => $with_counts,
					'flat'        => $flat,
					'status'      => $status,
				]);
				$this->set_response([
					'status' => 'success',
					'result' => [
						'codelists' => $p['rows'],
						'total'     => $p['total'],
						'page'      => $p['page'],
						'per_page'  => $p['per_page'],
					],
				], REST_Controller::HTTP_OK);
				return;
			}
			$rows = $flat
				? $this->Codelist_model->get_all_codelists($with_counts)
				: $this->Codelist_model->get_all_codelists_collapsed($with_counts);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelists' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * flat=1 => all version rows; collapsed=1 => family heads; default collapsed.
	 * flat=1 wins over collapsed=1.
	 */
	private function _codelists_catalog_flat_mode()
	{
		$flat = ($this->get('flat') === '1' || $this->get('flat') === 'true');
		if ($this->get('collapsed') === '1' || $this->get('collapsed') === 'true') {
			$flat = false;
		}
		if ($this->get('collapsed') === '0' || $this->get('collapsed') === 'false') {
			$flat = true;
		}
		if ($this->get('flat') === '1' || $this->get('flat') === 'true') {
			$flat = true;
		}
		return $flat;
	}

	/**
	 * GET /api/admin/codelists/versions/{name}
	 * Query: agency.
	 */
	public function versions_get($name)
	{
		try {
			$agency = $this->get('agency');
			$rows    = $this->Codelist_model->get_codelist_versions($name, $agency ?: null);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelists' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists — create codelist. Body: { "name": "...", "description": "..." }
	 */
	public function index_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name, optional description');
			}
			$id = $this->Codelist_model->create_codelist($input);
			$row = $this->Codelist_model->get_codelist_by_id($id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/by_name/{name} — one codelist by name (+ optional agency, version) with items and groups (nested).
	 *
	 * Optional query params: agency (default: 'NADA'). Omit version to resolve the latest row for that name.
	 */
	public function by_name_get($name)
	{
		try {
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
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/by_idno/{idno} — one codelist by its unique idno with items and groups (nested).
	 *
	 * idno is the compact single-string identity (default format: "{agency}_{name}_{version}").
	 */
	public function by_idno_get($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode($idno) : '';
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
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/item/{id} — one codelist with items and groups (nested).
	 *
	 * Optional: include_items=0 and/or include_groups=0 to omit large arrays; response then includes
	 * item_count and/or group_count instead of loading every row (detail UIs that page item_items / item_groups).
	 */
	public function item_get($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$include_items = $this->get('include_items') !== '0' && $this->get('include_items') !== false;
			$include_groups = $this->get('include_groups') !== '0' && $this->get('include_groups') !== false;

			if ($include_items) {
				$codelist['items'] = $this->Codelist_item_model->get_items_by_codelist($id, true);
			} else {
				$codelist['items'] = [];
				$codelist['item_count'] = $this->Codelist_item_model->count_items_by_codelist($id);
			}
			if ($include_groups) {
				$codelist['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			} else {
				$codelist['groups'] = [];
				$codelist['group_count'] = $this->Codelist_group_model->count_groups_by_codelist($id);
			}
			$codelist['has_defaults'] = $this->_seed_path($codelist['name']) !== null;
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/item_restore/{id} — restore a codelist from its seed file.
	 * Deletes all existing items and groups, re-inserts from seed, preserves translations.
	 */
	public function item_restore_post($id)
	{
		try {
			$id = (int) $id;
			$codelist = $this->Codelist_model->get_codelist_by_id($id);
			if (!$codelist) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if (Codelist_model::is_locked_status((int) ($codelist['status'] ?? 0))) {
				throw new Exception('Locked codelists cannot be restored from seed.');
			}

			$path = $this->_seed_path($codelist['name']);
			if (!$path) {
				$this->set_response(['status' => 'error', 'message' => 'No seed file found for this codelist.'], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$seed = json_decode(file_get_contents($path), true);
			if (!$seed || !isset($seed['items'])) {
				$this->set_response(['status' => 'error', 'message' => 'Invalid seed file.'], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			// 1. Save existing item translations keyed by code
			$item_translations = array();
			$existing_items = $this->Codelist_item_model->get_items_by_codelist($id, true);
			foreach ($existing_items as $item) {
				if (!empty($item['translations'])) {
					$item_translations[$item['code']] = $item['translations'];
				}
			}

			// 2. Save existing group translations keyed by group name
			$group_translations = array();
			$existing_groups = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			foreach ($existing_groups as $group) {
				if (!empty($group['translations'])) {
					$group_translations[$group['name']] = $group['translations'];
				}
			}

			// 3. Delete all groups (cascades group_items and group_translations)
			$this->db->where('codelist_id', $id)->delete('codelist_group');

			// 4. Delete all items (cascades item_translations)
			$this->db->where('codelist_id', $id)->delete('codelist_item');

			// 5. Re-insert items and restore translations
			$code_to_id = array();
			foreach ($seed['items'] as $item_data) {
				$this->db->insert('codelist_item', array(
					'codelist_id' => $id,
					'code'        => $item_data['code'],
					'title'       => isset($item_data['title']) ? $item_data['title'] : '',
					'sort_order'  => isset($item_data['sort_order']) ? (int) $item_data['sort_order'] : 0,
				));
				$new_id = $this->db->insert_id();
				$code_to_id[$item_data['code']] = $new_id;

				if (isset($item_translations[$item_data['code']])) {
					foreach ($item_translations[$item_data['code']] as $lang => $title) {
						$this->db->replace('codelist_item_translation', array(
							'codelist_item_id' => $new_id,
							'lang'             => $lang,
							'title'            => $title,
						));
					}
				}
			}

			// 6. Re-insert groups, link items, restore group translations
			$groups = isset($seed['groups']) ? $seed['groups'] : array();
			foreach ($groups as $group_data) {
				$this->db->insert('codelist_group', array(
					'codelist_id' => $id,
					'name'        => $group_data['name'],
					'sort_order'  => isset($group_data['sort_order']) ? (int) $group_data['sort_order'] : 0,
				));
				$group_id = $this->db->insert_id();

				if (!empty($group_data['item_codes'])) {
					foreach ($group_data['item_codes'] as $code) {
						if (isset($code_to_id[$code])) {
							$this->db->insert('codelist_group_item', array(
								'codelist_group_id' => $group_id,
								'codelist_item_id'  => $code_to_id[$code],
								'sort_order'        => 0,
							));
						}
					}
				}

				if (isset($group_translations[$group_data['name']])) {
					foreach ($group_translations[$group_data['name']] as $lang => $title) {
						$this->db->replace('codelist_group_translation', array(
							'codelist_group_id' => $group_id,
							'lang'              => $lang,
							'title'             => $title,
						));
					}
				}
			}

			$codelist                 = $this->Codelist_model->get_codelist_by_id($id);
			$codelist['items']        = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$codelist['groups']       = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$codelist['has_defaults'] = true;

			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $codelist],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Returns the absolute path to the seed file for $name, or null if none exists.
	 */
	private function _seed_path($name)
	{
		$name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
		$path = APPPATH . 'data/codelists/' . $name . '.json';
		return file_exists($path) ? $path : null;
	}

	/**
	 * Throws when codelist status is locked (published/archived).
	 *
	 * @param int $codelist_id
	 * @return void
	 * @throws Exception
	 */
	private function _assert_codelist_mutable($codelist_id)
	{
		$codelist = $this->Codelist_model->get_codelist_by_id((int) $codelist_id);
		if (!$codelist) {
			throw new Exception('Codelist not found.');
		}
		if (Codelist_model::is_locked_status((int) ($codelist['status'] ?? 0))) {
			throw new Exception('This codelist is locked. Items and groups cannot be modified.');
		}
	}

	/**
	 * Resolve item -> codelist and assert mutable.
	 *
	 * @param int $item_id
	 * @return void
	 * @throws Exception
	 */
	private function _assert_item_codelist_mutable($item_id)
	{
		$item = $this->Codelist_item_model->get_item_by_id((int) $item_id, false);
		if (!$item) {
			throw new Exception('Item not found.');
		}
		$this->_assert_codelist_mutable((int) $item['codelist_id']);
	}

	/**
	 * Resolve group -> codelist and assert mutable.
	 *
	 * @param int $group_id
	 * @return void
	 * @throws Exception
	 */
	private function _assert_group_codelist_mutable($group_id)
	{
		$group = $this->Codelist_group_model->get_group_by_id((int) $group_id, false, false);
		if (!$group) {
			throw new Exception('Group not found.');
		}
		$this->_assert_codelist_mutable((int) $group['codelist_id']);
	}

	/**
	 * PUT /api/admin/codelists/item/{id}
	 */
	public function item_put($id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name and/or description');
			}
			$this->Codelist_model->update_codelist($id, $input);
			$row = $this->Codelist_model->get_codelist_by_id((int) $id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Codelist not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/item_delete/{id} — POST alias for DELETE */
	public function item_delete_post($id) { return $this->item_delete($id); }

	/**
	 * DELETE /api/admin/codelists/item/{id}
	 */
	public function item_delete($id)
	{
		try {
			$this->Codelist_model->delete_codelist($id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Codelist not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * POST /api/admin/codelists/batch_delete — delete multiple codelist rows by primary key id.
	 * JSON body: { "ids": [1, 2, 3] } — deduped; max 100 per request.
	 * Same rules as single delete (published + DSD references).
	 */
	public function batch_delete_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$ids_raw = isset($input['ids']) ? $input['ids'] : null;
			if (!is_array($ids_raw)) {
				throw new Exception('ids must be a JSON array of numeric codelist ids.');
			}
			$max_batch = 100;
			$seen      = [];
			foreach ($ids_raw as $v) {
				$n = (int) $v;
				if ($n >= 1) {
					$seen[$n] = true;
				}
			}
			$ids = array_keys($seen);
			if (count($ids) === 0) {
				throw new Exception('Provide at least one valid codelist id.');
			}
			if (count($ids) > $max_batch) {
				throw new Exception('At most ' . $max_batch . ' codelists can be deleted per request.');
			}
			rsort($ids, SORT_NUMERIC);

			$deleted = [];
			$failed  = [];
			foreach ($ids as $cid) {
				try {
					$this->Codelist_model->delete_codelist((int) $cid);
					$deleted[] = (int) $cid;
				} catch (Exception $e) {
					$failed[] = [
						'id'      => (int) $cid,
						'message' => $e->getMessage(),
					];
				}
			}

			$this->set_response([
				'status' => 'success',
				'result' => [
					'deleted'       => $deleted,
					'failed'        => $failed,
					'deleted_count' => count($deleted),
					'failed_count'  => count($failed),
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$this->set_response([
				'status'  => 'error',
				'message' => $msg === 'INVALID_JSON_INPUT' ? 'Invalid JSON body' : $msg,
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/item_items/{codelist_id} — list items.
	 *
	 * Default (no view=flat, no page): full rows (optionally with translations unless with_translations=0).
	 * view=flat: each item is { value: code, label: title or code }; translations are not loaded.
	 *   With page: paginated flat list + total, page, per_page. Optional search / q on code and title.
	 *   Without page: all items as flat objects (use with care for large lists).
	 * Non-flat with page (1-based): full DB rows for one page + total, page, per_page; optional search / q;
	 * per_page default 50, max 200.
	 */
	public function item_items_get($codelist_id)
	{
		try {
			$cid = (int) $codelist_id;
			$view_flat = strtolower((string) $this->get('view')) === 'flat';

			if ($view_flat) {
				$page_raw = $this->get('page');
				$use_page = ($page_raw !== null && $page_raw !== false && $page_raw !== '');
				$search = $this->get('search');
				if ($search === null || $search === false) {
					$search = $this->get('q');
				}
				$search = is_string($search) ? $search : '';

				if ($use_page) {
					$page = max(1, (int) $page_raw);
					$per_raw = $this->get('per_page');
					$per_page = ($per_raw !== null && $per_raw !== false && $per_raw !== '') ? (int) $per_raw : 50;
					$p = $this->Codelist_item_model->get_items_by_codelist_paged($cid, [
						'page'              => $page,
						'per_page'          => $per_page,
						'search'            => $search,
						'with_translations' => false,
					]);
					$items = $this->_codelist_items_to_flat($p['rows']);
					$this->set_response([
						'status' => 'success',
						'result' => [
							'items'    => $items,
							'total'    => $p['total'],
							'page'     => $p['page'],
							'per_page' => $p['per_page'],
						],
					], REST_Controller::HTTP_OK);
					return;
				}

				$rows = $this->Codelist_item_model->get_items_by_codelist($cid, false);
				$items = $this->_codelist_items_to_flat($rows);
				$this->set_response([
					'status' => 'success',
					'result' => ['items' => $items],
				], REST_Controller::HTTP_OK);
				return;
			}

			$page_raw_nf = $this->get('page');
			$use_page_nf = ($page_raw_nf !== null && $page_raw_nf !== false && $page_raw_nf !== '');
			if ($use_page_nf) {
				$page_nf = max(1, (int) $page_raw_nf);
				$per_raw_nf = $this->get('per_page');
				$per_page_nf = ($per_raw_nf !== null && $per_raw_nf !== false && $per_raw_nf !== '') ? (int) $per_raw_nf : 50;
				$per_page_nf = min(200, max(1, $per_page_nf));
				$search_nf = $this->get('search');
				if ($search_nf === null || $search_nf === false) {
					$search_nf = $this->get('q');
				}
				$search_nf = is_string($search_nf) ? $search_nf : '';
				$with_translations_nf = $this->get('with_translations') !== '0' && $this->get('with_translations') !== false;
				$p_nf = $this->Codelist_item_model->get_items_by_codelist_paged($cid, [
					'page'              => $page_nf,
					'per_page'          => $per_page_nf,
					'search'            => $search_nf,
					'with_translations' => $with_translations_nf,
				]);
				$this->set_response([
					'status' => 'success',
					'result' => [
						'items'    => $p_nf['rows'],
						'total'    => $p_nf['total'],
						'page'     => $p_nf['page'],
						'per_page' => $p_nf['per_page'],
					],
				], REST_Controller::HTTP_OK);
				return;
			}

			$with_translations = $this->get('with_translations') !== '0' && $this->get('with_translations') !== false;
			$items = $this->Codelist_item_model->get_items_by_codelist($cid, $with_translations);
			$this->set_response([
				'status' => 'success',
				'result' => ['items' => $items],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Map DB rows to flat { value, label } (code + title only).
	 *
	 * @param array $rows
	 * @return array
	 */
	private function _codelist_items_to_flat(array $rows)
	{
		$out = [];
		foreach ($rows as $row) {
			$code = isset($row['code']) ? (string) $row['code'] : '';
			$title = isset($row['title']) && $row['title'] !== null && $row['title'] !== ''
				? trim((string) $row['title'])
				: '';
			$out[] = [
				'value' => $code,
				'label' => $title !== '' ? $title : $code,
			];
		}
		return $out;
	}

	/**
	 * POST /api/admin/codelists/item_items/{codelist_id} — create item. Body: { "code", "title", "parent_id?", "sort_order?" }
	 */
	public function item_items_post($codelist_id)
	{
		try {
			$this->_assert_codelist_mutable((int) $codelist_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['code'])) {
				throw new Exception('JSON body required: code, optional title, parent_id, sort_order');
			}
			$id = $this->Codelist_item_model->create_item((int) $codelist_id, $input);
			$row = $this->Codelist_item_model->get_item_by_id($id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/items/{item_id}
	 */
	public function items_get($item_id)
	{
		try {
			$row = $this->Codelist_item_model->get_item_by_id((int) $item_id, true);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/codelists/items/{item_id}
	 */
	public function items_put($item_id)
	{
		try {
			$this->_assert_item_codelist_mutable((int) $item_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: code and/or title, parent_id, sort_order');
			}
			$this->Codelist_item_model->update_item((int) $item_id, $input);
			$row = $this->Codelist_item_model->get_item_by_id((int) $item_id, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['item' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/items_delete/{item_id} — POST alias for DELETE */
	public function items_delete_post($item_id) { return $this->items_delete($item_id); }

	/**
	 * DELETE /api/admin/codelists/items/{item_id}
	 */
	public function items_delete($item_id)
	{
		try {
			$this->_assert_item_codelist_mutable((int) $item_id);
			$this->Codelist_item_model->delete_item((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $item_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * GET /api/admin/codelists/items_translations/{item_id}
	 */
	public function items_translations_get($item_id)
	{
		try {
			$item = $this->Codelist_item_model->get_item_by_id((int) $item_id, false);
			if (!$item) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$translations = $this->Codelist_item_model->get_item_translations((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/items_translations/{item_id} — body: { "lang", "title" }
	 */
	public function items_translations_post($item_id)
	{
		try {
			$this->_assert_item_codelist_mutable((int) $item_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['lang']) || !isset($input['title'])) {
				throw new Exception('JSON body required: lang, title');
			}
			$this->Codelist_item_model->set_item_translation((int) $item_id, $input['lang'], $input['title']);
			$translations = $this->Codelist_item_model->get_item_translations((int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Item not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/items_translation_delete/{item_id}/{lang} — POST alias for DELETE */
	public function items_translation_delete_post($item_id, $lang) { return $this->items_translation_delete($item_id, $lang); }

	/**
	 * DELETE /api/admin/codelists/items_translation/{item_id}/{lang}
	 */
	public function items_translation_delete($item_id, $lang)
	{
		try {
			$this->_assert_item_codelist_mutable((int) $item_id);
			$this->Codelist_item_model->delete_item_translation((int) $item_id, $lang);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_item_id' => (int) $item_id, 'lang' => $lang],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/item_groups/{codelist_id}
	 *
	 * Default: all groups with item_ids and translations.
	 * With page (1-based): one page + total, page, per_page; optional search / q on group name;
	 * with_translations=0 to omit translation maps. per_page default 50, max 200.
	 */
	public function item_groups_get($codelist_id)
	{
		try {
			$cid = (int) $codelist_id;
			$page_raw = $this->get('page');
			$use_page = ($page_raw !== null && $page_raw !== false && $page_raw !== '');
			if ($use_page) {
				$page = max(1, (int) $page_raw);
				$per_raw = $this->get('per_page');
				$per_page = ($per_raw !== null && $per_raw !== false && $per_raw !== '') ? (int) $per_raw : 50;
				$per_page = min(200, max(1, $per_page));
				$search = $this->get('search');
				if ($search === null || $search === false) {
					$search = $this->get('q');
				}
				$search = is_string($search) ? $search : '';
				$with_translations = $this->get('with_translations') !== '0' && $this->get('with_translations') !== false;
				$p = $this->Codelist_group_model->get_groups_by_codelist_paged($cid, [
					'page'              => $page,
					'per_page'          => $per_page,
					'search'            => $search,
					'with_translations' => $with_translations,
				]);
				$this->set_response([
					'status' => 'success',
					'result' => [
						'groups'   => $p['rows'],
						'total'    => $p['total'],
						'page'     => $p['page'],
						'per_page' => $p['per_page'],
					],
				], REST_Controller::HTTP_OK);
				return;
			}

			$groups = $this->Codelist_group_model->get_groups_by_codelist($cid, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['groups' => $groups],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/item_groups/{codelist_id} — create group. Body: { "name", "sort_order?" }
	 */
	public function item_groups_post($codelist_id)
	{
		try {
			$this->_assert_codelist_mutable((int) $codelist_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['name'])) {
				throw new Exception('JSON body required: name, optional sort_order');
			}
			$id = $this->Codelist_group_model->create_group((int) $codelist_id, $input);
			$row = $this->Codelist_group_model->get_group_by_id($id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/codelists/groups/{group_id}
	 */
	public function groups_get($group_id)
	{
		try {
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/codelists/groups/{group_id}
	 */
	public function groups_put($group_id)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required: name and/or sort_order');
			}
			$this->Codelist_group_model->update_group((int) $group_id, $input);
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/groups_delete/{group_id} — POST alias for DELETE */
	public function groups_delete_post($group_id) { return $this->groups_delete($group_id); }

	/**
	 * DELETE /api/admin/codelists/groups/{group_id}
	 */
	public function groups_delete($group_id)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$this->Codelist_group_model->delete_group((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $group_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * POST /api/admin/codelists/groups_items/{group_id} — add item. Body: { "codelist_item_id", "sort_order?" }
	 */
	public function groups_items_post($group_id)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['codelist_item_id'])) {
				throw new Exception('JSON body required: codelist_item_id, optional sort_order');
			}
			$sort = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;
			$this->Codelist_group_model->add_group_item((int) $group_id, (int) $input['codelist_item_id'], $sort);
			$row = $this->Codelist_group_model->get_group_by_id((int) $group_id, true, true);
			$this->set_response([
				'status' => 'success',
				'result' => ['group' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/** POST /api/admin/codelists/groups_items_remove_delete/{group_id}/{item_id} — POST alias for DELETE */
	public function groups_items_remove_delete_post($group_id, $item_id) { return $this->groups_items_remove_delete($group_id, $item_id); }

	/**
	 * DELETE /api/admin/codelists/groups_items_remove/{group_id}/{item_id}
	 */
	public function groups_items_remove_delete($group_id, $item_id)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$this->Codelist_group_model->remove_group_item((int) $group_id, (int) $item_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'codelist_item_id' => (int) $item_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/import_json
	 *
	 * Import one codelist (+items) using the same codelist resolution rules as DSD JSON import:
	 * - match existing by idno first, then by (name, agency, version)
	 * - overwrite=true replaces existing items when items are provided
	 * - otherwise existing codelist is reused and incoming items are ignored with a warning
	 *
	 * Body shape:
	 * {
	 *   "codelist": { "idno", "name", "agency?", "version?", "description?", "items": [...] },
	 *   "overwrite": bool,
	 *   "dry_run": bool
	 * }
	 *
	 * overwrite and dry_run are read only from the JSON body (no query overrides).
	 */
	public function import_json_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}

			$payload = isset($input['codelist']) && is_array($input['codelist'])
				? $input['codelist']
				: $input;
			if (!is_array($payload) || empty($payload)) {
				throw new Exception('Body must include a codelist object.');
			}

			$overwrite = $this->_boolish($input['overwrite'] ?? null, false);
			$dryRun    = $this->_boolish($input['dry_run'] ?? null, false);

			$summary = $this->_import_single_codelist_from_json_payload($payload, $overwrite, $dryRun);
			if ($dryRun) {
				$code = REST_Controller::HTTP_OK;
			} elseif (!empty($summary['codelists_created'])) {
				$code = REST_Controller::HTTP_CREATED;
			} else {
				// Reused or updated existing codelist — not a new resource
				$code = REST_Controller::HTTP_OK;
			}
			$this->set_response([
				'status' => 'success',
				'result' => $summary,
			], $code);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * @param array $payload codelist payload
	 * @param bool  $overwrite
	 * @param bool  $dryRun
	 * @return array
	 * @throws Exception
	 */
	private function _import_single_codelist_from_json_payload(array $payload, $overwrite, $dryRun)
	{
		$clIdno  = isset($payload['idno']) ? trim((string) $payload['idno']) : '';
		$clName  = isset($payload['name']) ? trim((string) $payload['name']) : '';
		$agency  = isset($payload['agency']) && trim((string) $payload['agency']) !== '' ? trim((string) $payload['agency']) : Codelist_model::DEFAULT_AGENCY;
		$version = isset($payload['version']) && trim((string) $payload['version']) !== '' ? trim((string) $payload['version']) : Codelist_model::DEFAULT_VERSION;
		$items   = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [];

		if ($clIdno === '' && $clName === '') {
			throw new Exception('codelist.idno or codelist.name is required.');
		}
		foreach ($items as $idx => $item) {
			if (!is_array($item)) {
				throw new Exception("codelist.items[{$idx}] must be an object.");
			}
			$code = isset($item['code']) ? trim((string) $item['code']) : '';
			if ($code === '') {
				throw new Exception("codelist.items[{$idx}].code is required.");
			}
			if (strlen($code) > 64) {
				throw new Exception("codelist.items[{$idx}].code exceeds 64 characters.");
			}
		}

		$existing = null;
		if ($clIdno !== '') {
			$existing = $this->Codelist_model->get_codelist_by_idno($clIdno);
		}
		if (!$existing && $clName !== '') {
			$existing = $this->Codelist_model->get_codelist_by_name($clName, $agency, $version);
		}

		$summary = [
			'dry_run'           => (bool) $dryRun,
			'codelist'          => null,
			'items_imported'    => count($items),
			'codelists_created' => [],
			'codelists_reused'  => [],
			'codelists_updated' => [],
			'warnings'          => [],
		];

		if ($dryRun) {
			$summary['codelist'] = [
				'idno'    => $clIdno !== '' ? $clIdno : Codelist_model::make_idno($agency, $clName, $version),
				'name'    => $clName,
				'agency'  => $agency,
				'version' => $version,
				'action'  => $existing ? (($overwrite && count($items) > 0) ? 'update' : 'reuse') : 'create',
			];
			return $summary;
		}

		$this->db->trans_begin();
		try {
			$codelistId = null;
			if ($existing) {
				$codelistId = (int) $existing['id'];
				if ($overwrite && count($items) > 0) {
					$this->Codelist_item_model->delete_all_items_for_codelist($codelistId);
					$this->_import_codelist_items($codelistId, $items);
					$summary['codelists_updated'][] = ['id' => $codelistId, 'idno' => $existing['idno']];
				} else {
					if (count($items) > 0 && !$overwrite) {
						$summary['warnings'][] = [
							'message'     => 'Codelist already exists; items not applied (set overwrite=true or omit items).',
							'codelist_id' => $codelistId,
						];
					}
					$summary['codelists_reused'][] = ['id' => $codelistId, 'idno' => $existing['idno']];
				}
			} else {
				if ($clName === '') {
					throw new Exception('codelist.name is required to create a new codelist.');
				}
				$create = [
					'name'        => $clName,
					'agency'      => $agency,
					'version'     => $version,
					'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
				];
				if ($clIdno !== '') {
					$create['idno'] = $clIdno;
				}
				$codelistId = (int) $this->Codelist_model->create_codelist($create);
				$this->_import_codelist_items($codelistId, $items);
				$row = $this->Codelist_model->get_codelist_by_id($codelistId);
				$summary['codelists_created'][] = ['id' => $codelistId, 'idno' => $row ? $row['idno'] : null];
			}

			if ($this->db->trans_status() === false) {
				throw new Exception('Database transaction failed.');
			}
			$this->db->trans_commit();
			$summary['codelist'] = $this->Codelist_model->get_codelist_by_id((int) $codelistId);
			return $summary;
		} catch (Exception $e) {
			$this->db->trans_rollback();
			throw $e;
		}
	}

	/**
	 * @param int   $codelistId
	 * @param array $items
	 * @return void
	 */
	private function _import_codelist_items($codelistId, array $items)
	{
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$code = isset($item['code']) ? trim((string) $item['code']) : '';
			if ($code === '') {
				continue;
			}
			$title = null;
			if (isset($item['label']) && $item['label'] !== '' && $item['label'] !== null) {
				$title = is_string($item['label']) ? trim($item['label']) : (string) $item['label'];
			} elseif (isset($item['title']) && $item['title'] !== '' && $item['title'] !== null) {
				$title = is_string($item['title']) ? trim($item['title']) : (string) $item['title'];
			}
			$sortOrder = isset($item['sort_order']) ? (int) $item['sort_order'] : 0;
			$parentId  = null;
			if (isset($item['parent_id']) && $item['parent_id'] !== '' && $item['parent_id'] !== null && (int) $item['parent_id'] > 0) {
				$parentId = (int) $item['parent_id'];
			}
			$this->Codelist_item_model->create_item((int) $codelistId, [
				'code'       => $code,
				'title'      => $title,
				'sort_order' => $sortOrder,
				'parent_id'  => $parentId,
			]);
		}
	}

	/**
	 * @param mixed $value
	 * @param bool  $default
	 * @return bool
	 */
	private function _boolish($value, $default)
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value) || is_float($value)) {
			return ((int) $value) === 1;
		}
		if (is_string($value)) {
			$v = strtolower(trim($value));
			if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
				return true;
			}
			if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
				return false;
			}
		}
		return (bool) $default;
	}

	/**
	 * GET /api/admin/codelists/groups_translations/{group_id}
	 */
	public function groups_translations_get($group_id)
	{
		try {
			$group = $this->Codelist_group_model->get_group_by_id((int) $group_id, false, false);
			if (!$group) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$translations = $this->Codelist_group_model->get_group_translations((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/codelists/groups_translations/{group_id} — body: { "lang", "title" }
	 */
	public function groups_translations_post($group_id)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$input = $this->raw_json_input();
			if (!$input || !is_array($input) || empty($input['lang']) || !isset($input['title'])) {
				throw new Exception('JSON body required: lang, title');
			}
			$this->Codelist_group_model->set_group_translation((int) $group_id, $input['lang'], $input['title']);
			$translations = $this->Codelist_group_model->get_group_translations((int) $group_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'translations' => $translations],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Group not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/** POST /api/admin/codelists/groups_translation_delete/{group_id}/{lang} — POST alias for DELETE */
	public function groups_translation_delete_post($group_id, $lang) { return $this->groups_translation_delete($group_id, $lang); }

	/**
	 * DELETE /api/admin/codelists/groups_translation/{group_id}/{lang}
	 */
	public function groups_translation_delete($group_id, $lang)
	{
		try {
			$this->_assert_group_codelist_mutable((int) $group_id);
			$this->Codelist_group_model->delete_group_translation((int) $group_id, $lang);
			$this->set_response([
				'status' => 'success',
				'result' => ['codelist_group_id' => (int) $group_id, 'lang' => $lang],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
