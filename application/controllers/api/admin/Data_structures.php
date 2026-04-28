<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin Data Structures API (global DSD catalogue)
 *
 * Base URL: /api/admin/data_structures
 * Auth: session or API key; **admin only** (is_admin_or_die), same bar as other catalogue admin APIs.
 *
 * Endpoints (PUT/DELETE also have POST aliases where noted):
 *
 *   GET    /api/admin/data_structures                                ?flat=1&page=&per_page=&search|q=&status=  (no page: full list; with page: paginated + total; search filters name/title/agency/idno/version/description/notes/id)
 *   POST   /api/admin/data_structures/create                       — create DSD (JSON body)
 *   POST   /api/admin/data_structures/update/{id_or_idno}         — update DSD (JSON body); path: digits only = id (PK), else idno
 *   POST   /api/admin/data_structures/status/{id_or_idno}         — change DSD status only (JSON body: status)
 *   GET    /api/admin/data_structures/{segment}                 ?with_components=1|0 — segment: digits only = id (PK); otherwise idno (idno must never be purely numeric)
 *   DELETE /api/admin/data_structures/{segment}                 — same segment rules as GET (remove one structure row)
 *   GET    /api/admin/data_structures/by_identity                   ?name= (required) &agency= (required) &version= (optional; omit = latest for name+agency) &with_components=
 *   GET    /api/admin/data_structures/versions/{id_or_idno}       — path: digits only = id (PK); else catalogue idno
 *   GET    /api/admin/data_structures/projects/{id_or_idno}       — list projects/surveys linked to this DSD version
 *   POST   /api/admin/data_structures/delete/{id_or_idno}         — POST when DELETE is blocked (same resolution as DELETE …/{segment})
 *   GET    /api/admin/data_structures/export/{id_or_idno}       — canonical JSON export (same id/idno rules as GET …/{segment})
 *   POST   /api/admin/data_structures/validate
 *   GET    /api/admin/data_structures/components/{segment}      — non-digit: structure idno → list. Digit: one component by PK if found, else list for structure id
 *   POST   /api/admin/data_structures/components/{segment}      — structure id (digits) or idno; create component (JSON body)
 *   PUT    /api/admin/data_structures/components/{component_id}
 *   POST   /api/admin/data_structures/components_update/{component_id}   (alias for PUT components)
 *   DELETE /api/admin/data_structures/components/{component_id}
 *   POST   /api/admin/data_structures/components_delete/{component_id}    (alias for DELETE components)
 *   POST   /api/admin/data_structures/import_json — JSON body (data-structure-schema.json): structure + components + optional import_options.
 *          Query: overwrite_codelists=1|0, dry_run=1|0 (override body flags).
 *   POST   /api/admin/data_structures/import — multipart field `file` (SDMX-ML structure XML) or `application/xml` body.
 *          Query/form: overwrite_codelists=1|0, optional dsd_id (DataStructure @id when multiple in file).
 *
 * SDMX import (SDMX-ML XML structure messages, v2.1 structure namespace):
 *
 *   - **Format:** **SDMX-ML (XML) structure messages only.** SDMX-JSON / other formats are not supported
 *     for import.
 *   - Codelists are imported before the DSD row and components. Each SDMX codelist is mapped to the
 *     same catalogue identity as elsewhere (agency, name, version / idno).
 *   - **Default:** if a codelist with that identity **already exists**, **do not create** a new row;
 *     **reuse** the existing `codelists.id` and bind DSD components with that `codelist_id`.
 *   - **Overwrite:** boolean **`overwrite_codelists`** (e.g. query `?overwrite_codelists=1` or a field in
 *     `multipart/form-data` next to the XML file). When **true**, for each referenced codelist that
 *     already exists, **replace** its codes from the import (existing `codelist_item` rows are cleared
 *     then re-inserted from the XML). When **false** (default), never mutate existing codelists;
 *     only create missing ones and reuse the rest.
 */
class Data_structures extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_admin_or_die();
		$this->load->model('Data_structure_model');
		$this->load->model('Data_structure_component_model');
		$this->load->model('Codelist_model');
		$this->load->model('Codelist_item_model');
		$this->load->model('Codelist_group_model');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}
		return parent::_auth_override_check();
	}

	/**
	 * GET /api/admin/data_structures — list structures (default: one row per DSD family).
	 * Use query flat=1 for one row per stored version (full table projection).
	 * With query page (1-based): returns data_structures, total, page, per_page (same pattern as codelists).
	 * Optional: per_page (default 50, max 200), search or q, status (exact smallint).
	 */
	public function index_get()
	{
		try {
			$flat = $this->get('flat') === '1' || $this->get('flat') === 'true';
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
				$status_raw = $this->get('status');
				$status = ($status_raw !== null && $status_raw !== false && $status_raw !== '')
					? (int) $status_raw
					: null;
				$p = $this->Data_structure_model->get_structures_catalog_paged([
					'page'     => $page,
					'per_page' => $per_page,
					'search'   => $search,
					'flat'     => $flat,
					'status'   => $status,
				]);
				$this->set_response([
					'status' => 'success',
					'result' => [
						'data_structures' => $p['rows'],
						'total'           => $p['total'],
						'page'            => $p['page'],
						'per_page'        => $p['per_page'],
					],
				], REST_Controller::HTTP_OK);
				return;
			}
			$rows = $flat
				? $this->Data_structure_model->get_all_structures()
				: $this->Data_structure_model->get_all_structures_collapsed();
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structures' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/data_structures — not supported (use POST .../create).
	 */
	public function index_post()
	{
		$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
	}

	/**
	 * POST /api/admin/data_structures/create — create structure version row.
	 */
	public function create_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$this->_merge_audit_ids_on_create($input);
			$id = $this->Data_structure_model->create_structure($input);
			$row = $this->Data_structure_model->get_structure_by_id($id, false);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$code = ($msg === 'INVALID_JSON_INPUT') ? REST_Controller::HTTP_BAD_REQUEST : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $msg === 'INVALID_JSON_INPUT' ? 'Invalid JSON body' : $msg,
			], $code);
		}
	}

	/**
	 * GET /api/admin/data_structures/{segment}
	 * If segment is digits only, load by primary key id; otherwise by idno.
	 * idno values must never be purely numeric (enforced on create/update) so the two cases do not collide.
	 * Query: with_components=1 (default) or 0.
	 */
	public function structure_lookup_get($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Structure id or idno path segment is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$with = $this->get('with_components') !== '0' && $this->get('with_components') !== false;
			$row  = $this->_resolve_structure_row_from_key($key);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if ($with) {
				$this->load->model('Data_structure_component_model');
				$row['components'] = $this->Data_structure_component_model->get_components_by_structure_id((int) $row['id']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * DELETE /api/admin/data_structures/{segment} — same path disambiguation as GET …/{segment}.
	 */
	public function structure_lookup_delete($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Structure id or idno path segment is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$row = $this->_resolve_structure_row_from_key($key);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$id = (int) $row['id'];
			$this->Data_structure_model->delete_structure($id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => $id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			$code = REST_Controller::HTTP_BAD_REQUEST;
			if ($msg === 'Data structure not found.') {
				$code = REST_Controller::HTTP_NOT_FOUND;
			} elseif (strpos($msg, 'in use by') !== false) {
				$code = REST_Controller::HTTP_CONFLICT;
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], $code);
		}
	}

	/**
	 * GET /api/admin/data_structures/by_identity
	 * Query: name (required), agency (required), version (optional), with_components.
	 * When version is omitted or empty, returns the latest row for that name+agency (highest version_seq, then id).
	 * Pass an explicit version to resolve a specific release.
	 */
	public function by_identity_get()
	{
		try {
			$name_raw = $this->get('name');
			$name = ($name_raw !== null && $name_raw !== false && $name_raw !== '')
				? trim(rawurldecode((string) $name_raw))
				: '';
			if ($name === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'name query parameter is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$agency_raw  = $this->get('agency');
			$version_raw = $this->get('version');
			$agency  = ($agency_raw !== null && $agency_raw !== false && $agency_raw !== '') ? trim((string) $agency_raw) : '';
			$version = ($version_raw !== null && $version_raw !== false && $version_raw !== '') ? trim((string) $version_raw) : '';
			if ($agency === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'agency query parameter is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$with = $this->get('with_components') !== '0' && $this->get('with_components') !== false;
			$row  = $this->Data_structure_model->get_structure_by_identity(
				$name,
				$agency,
				$version === '' ? null : $version
			);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if ($with) {
				$this->load->model('Data_structure_component_model');
				$row['components'] = $this->Data_structure_component_model->get_components_by_structure_id((int) $row['id']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/data_structures/versions/{id_or_idno}
	 * Path segment: same disambiguation as GET .../data_structures/{segment} (digits only = PK id;
	 * otherwise idno). Lists all version rows for that maintainable (agency + name).
	 */
	public function versions_get($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'id or idno path segment is required after /versions/',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$anchor = $this->_resolve_structure_row_from_key($key);
			if (!$anchor) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$name   = trim((string) $anchor['name']);
			$agency = trim((string) $anchor['agency']);
			$rows   = $this->Data_structure_model->get_structure_versions($name, $agency);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structures' => $rows],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/data_structures/projects/{id_or_idno}
	 * Returns paginated surveys linked via surveys.data_structure_id = data_structures.id.
	 * Query: page (1-based, default 1), per_page (default 25, max 200).
	 */
	public function projects_get($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'id or idno path segment is required after /projects/',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$row = $this->_resolve_structure_row_from_key($key);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$page_raw = $this->get('page');
			$per_raw = $this->get('per_page');
			$page = ($page_raw !== null && $page_raw !== false && $page_raw !== '') ? max(1, (int) $page_raw) : 1;
			$per_page = ($per_raw !== null && $per_raw !== false && $per_raw !== '') ? (int) $per_raw : 25;
			$p = $this->Data_structure_model->get_structure_projects_paged((int) $row['id'], [
				'page'     => $page,
				'per_page' => $per_page,
			]);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'projects' => $p['rows'],
					'total'    => $p['total'],
					'page'     => $p['page'],
					'per_page' => $p['per_page'],
					'data_structure_id' => (int) $row['id'],
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/data_structures/update/{id_or_idno} — update mutable fields (JSON body); path disambiguation same as GET .../{segment}.
	 */
	public function update_post($segment = null)
	{
		$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
		if ($key === '') {
			$this->set_response([
				'status'  => 'error',
				'message' => 'id or idno path segment is required',
			], REST_Controller::HTTP_BAD_REQUEST);
			return;
		}
		$row = $this->_resolve_structure_row_from_key($key);
		if (!$row) {
			$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
			return;
		}
		$this->_apply_data_structure_update((int) $row['id']);
	}

	/**
	 * POST /api/admin/data_structures/status/{id_or_idno} — update only status (and audit fields).
	 */
	public function status_post($segment = null)
	{
		$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
		if ($key === '') {
			$this->set_response([
				'status'  => 'error',
				'message' => 'id or idno path segment is required',
			], REST_Controller::HTTP_BAD_REQUEST);
			return;
		}
		$row = $this->_resolve_structure_row_from_key($key);
		if (!$row) {
			$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
			return;
		}
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			if (!array_key_exists('status', $input) && !array_key_exists('status_code', $input)) {
				throw new Exception('status is required');
			}
			$this->_merge_audit_ids_on_update($input);
			$status = array_key_exists('status_code', $input) ? $input['status_code'] : $input['status'];
			$this->Data_structure_model->update_structure_status((int) $row['id'], $status, isset($input['updated_by']) ? $input['updated_by'] : null);
			$updated = $this->Data_structure_model->get_structure_by_id((int) $row['id'], false);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $updated],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg  = $e->getMessage();
			$code = ($msg === 'Data structure not found.') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			if ($msg === 'INVALID_JSON_INPUT') {
				$msg = 'Invalid JSON body';
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], $code);
		}
	}

	/**
	 * @param int $id data_structures.id
	 */
	private function _apply_data_structure_update($id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$this->_merge_audit_ids_on_update($input);
			$this->Data_structure_model->update_structure((int) $id, $input);
			$row = $this->Data_structure_model->get_structure_by_id((int) $id, false);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg  = $e->getMessage();
			$code = ($msg === 'Data structure not found.') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			if ($msg === 'INVALID_JSON_INPUT') {
				$msg = 'Invalid JSON body';
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], $code);
		}
	}

	/**
	 * POST /api/admin/data_structures/delete/{id_or_idno} — POST when DELETE …/{segment} is blocked.
	 */
	public function delete_post($segment = null)
	{
		$this->structure_lookup_delete($segment);
	}

	/**
	 * POST /api/admin/data_structures/import — import first (or selected) DataStructure + codelists from SDMX-ML XML.
	 *
	 * Input: multipart/form-data with field `file`, optional `overwrite_codelists` (1/0), optional `dsd_id`.
	 * Alternative: raw body as application/xml or text/xml with flags as query params.
	 */
	public function import_post()
	{
		try {
			$overwrite = $this->query('overwrite_codelists') === '1' || $this->query('overwrite_codelists') === 'true';
			if ($this->input->post('overwrite_codelists') === '1' || $this->input->post('overwrite_codelists') === 'true') {
				$overwrite = true;
			}
			$dsd_id = trim((string) ($this->query('dsd_id') ?: $this->input->post('dsd_id')));

			$xml = '';
			if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				$xml = (string) file_get_contents($_FILES['file']['tmp_name']);
			} else {
				$ct = (string) $this->input->get_request_header('Content-Type', true);
				if ($ct !== '' && (stripos($ct, 'application/xml') !== false || stripos($ct, 'text/xml') !== false)) {
					$xml = (string) $this->input->raw_input_stream;
				}
			}
			if ($xml === '') {
				throw new Exception('Provide SDMX structure XML as multipart field "file" or as application/xml body.');
			}

			$this->load->library('Sdmx_structure_xml_import');
			$result = $this->sdmx_structure_xml_import->import_from_xml_string($xml, [
				'overwrite_codelists' => $overwrite,
				'dsd_id'              => $dsd_id !== '' ? $dsd_id : null,
			]);

			$this->set_response([
				'status' => 'success',
				'result' => $result,
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/data_structures/import_json — create global DSD + codelists + components from JSON.
	 *
	 * Body: application/json per application/schemas/data-structure-schema.json (structure, components, import_options).
	 * Query (optional): overwrite_codelists, dry_run — same as import_options when set to 1/true.
	 */
	public function import_json_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			if (empty($input['structure']) || !is_array($input['structure'])) {
				throw new Exception('Body must include a structure object.');
			}
			if (!isset($input['components']) || !is_array($input['components'])) {
				throw new Exception('Body must include a components array.');
			}

			$this->_merge_audit_ids_on_structure($input['structure']);

			$impOpts = isset($input['import_options']) && is_array($input['import_options']) ? $input['import_options'] : [];
			$opts = [
				'overwrite_codelists' => !empty($impOpts['overwrite_codelists']) && $impOpts['overwrite_codelists'] === true,
				'dry_run'             => !empty($impOpts['dry_run']) && $impOpts['dry_run'] === true,
				'user_id'             => $this->get_api_user_id(),
			];
			if ($this->query('overwrite_codelists') === '1' || $this->query('overwrite_codelists') === 'true') {
				$opts['overwrite_codelists'] = true;
			}
			if ($this->query('dry_run') === '1' || $this->query('dry_run') === 'true') {
				$opts['dry_run'] = true;
			}

			$this->load->library('data_structure_json_import');
			$result = $this->data_structure_json_import->import_from_array($input, $opts);

			$code = !empty($opts['dry_run']) ? REST_Controller::HTTP_OK : REST_Controller::HTTP_CREATED;
			$this->set_response([
				'status' => 'success',
				'result' => $result,
			], $code);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			if (strpos($msg, 'VALIDATION_FAILED: ') === 0) {
				$json = substr($msg, strlen('VALIDATION_FAILED: '));
				$errors = json_decode($json, true);
				$this->set_response([
					'status'  => 'error',
					'message' => 'Validation failed',
					'errors'  => is_array($errors) ? $errors : [],
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/data_structures/export/{id_or_idno} — canonical JSON (structure + components + codelist summary).
	 */
	public function export_get($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Structure id or idno path segment is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$anchor = $this->_resolve_structure_row_from_key($key);
			if (!$anchor) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$row = $this->Data_structure_model->get_structure_by_id((int) $anchor['id'], true);
			if (!$row) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$components = isset($row['components']) ? $row['components'] : [];
			unset($row['components']);
			foreach ($components as &$c) {
				$c = $this->_enrich_component_codelist($c);
			}
			unset($c);
			$exportPayload = $this->_sanitize_export_payload([
				'data_structure' => $row,
				'components'     => $components,
			]);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'export' => $exportPayload,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/data_structures/validate — validate existing id or proposed structure + components (no writes).
	 *
	 * Body option A: { "data_structure_id": int } — validate persisted row + components.
	 * Body option B: { "structure": { ... }, "components": [ ... ] } — validate a create/update payload.
	 */
	public function validate_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$errors   = [];
			$warnings = [];

			if (!empty($input['data_structure_id'])) {
				$sid = (int) $input['data_structure_id'];
				$st  = $this->Data_structure_model->get_structure_by_id($sid, false);
				if (!$st) {
					$errors[] = ['path' => 'data_structure_id', 'message' => 'Data structure not found.'];
				} else {
					$errors = array_merge($errors, $this->_validate_structure_row($st));
					$comps  = $this->Data_structure_component_model->get_components_by_structure_id($sid);
					$errors = array_merge($errors, $this->_validate_components_list($comps, true));
				}
			} else {
				$structure = isset($input['structure']) ? $input['structure'] : null;
				if (!is_array($structure)) {
					$errors[] = ['path' => 'structure', 'message' => 'Required when data_structure_id is omitted.'];
				} else {
					$errors = array_merge($errors, $this->_validate_structure_payload_for_create($structure));
				}
				$components = isset($input['components']) && is_array($input['components']) ? $input['components'] : [];
				if (empty($components)) {
					$warnings[] = ['path' => 'components', 'message' => 'No components supplied.'];
				}
				$errors = array_merge($errors, $this->_validate_components_list($components, false));
			}

			$this->set_response([
				'status' => empty($errors) ? 'success' : 'error',
				'result' => [
					'valid'    => empty($errors),
					'errors'   => $errors,
					'warnings' => $warnings,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			if ($msg === 'INVALID_JSON_INPUT') {
				$msg = 'Invalid JSON body';
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/data_structures/components/{segment} — see class docblock (structure list vs single component).
	 */
	public function components_get($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Path segment is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			if (!ctype_digit($key)) {
				$st = $this->_resolve_structure_row_from_key($key);
				if (!$st) {
					$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
					return;
				}
				$items = $this->Data_structure_component_model->get_components_by_structure_id((int) $st['id']);
				$this->set_response([
					'status' => 'success',
					'result' => ['components' => $items],
				], REST_Controller::HTTP_OK);
				return;
			}
			$n = (int) $key;
			$row = $this->Data_structure_component_model->get_component_by_id($n);
			if ($row) {
				$this->set_response([
					'status' => 'success',
					'result' => ['component' => $row],
				], REST_Controller::HTTP_OK);
				return;
			}
			if ($this->Data_structure_model->get_structure_by_id($n, false)) {
				$items = $this->Data_structure_component_model->get_components_by_structure_id($n);
				$this->set_response([
					'status' => 'success',
					'result' => ['components' => $items],
				], REST_Controller::HTTP_OK);
				return;
			}
			$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
		} catch (Exception $e) {
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/data_structures/components/{segment} — create component under structure (id or idno).
	 */
	public function components_post($segment = null)
	{
		try {
			$key = $segment !== null ? trim(rawurldecode((string) $segment)) : '';
			if ($key === '') {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Structure id or idno path segment is required',
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$st = $this->_resolve_structure_row_from_key($key);
			if (!$st) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$sid = (int) $st['id'];
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$this->_merge_audit_ids_on_create($input);
			$newId = $this->Data_structure_component_model->create_component($sid, $input);
			$row   = $this->Data_structure_component_model->get_component_by_id($newId);
			$this->set_response([
				'status' => 'success',
				'result' => ['component' => $row],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$msg = $e->getMessage();
			if ($msg === 'INVALID_JSON_INPUT') {
				$msg = 'Invalid JSON body';
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * PUT /api/admin/data_structures/components/{component_id}
	 */
	public function components_put($component_id)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$this->_merge_audit_ids_on_update($input);
			$this->Data_structure_component_model->update_component((int) $component_id, $input);
			$row = $this->Data_structure_component_model->get_component_by_id((int) $component_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['component' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$msg  = $e->getMessage();
			$code = ($msg === 'Component not found.') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			if ($msg === 'INVALID_JSON_INPUT') {
				$msg = 'Invalid JSON body';
			}
			$this->set_response([
				'status'  => 'error',
				'message' => $msg,
			], $code);
		}
	}

	/**
	 * POST /api/admin/data_structures/components_update/{component_id} — POST alias for PUT components.
	 */
	public function components_update_post($component_id)
	{
		return $this->components_put($component_id);
	}

	/**
	 * DELETE /api/admin/data_structures/components/{component_id}
	 */
	public function components_delete($component_id)
	{
		try {
			$this->Data_structure_component_model->delete_component((int) $component_id);
			$this->set_response([
				'status' => 'success',
				'result' => ['id' => (int) $component_id],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$code = $e->getMessage() === 'Component not found.' ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response([
				'status'  => 'error',
				'message' => $e->getMessage(),
			], $code);
		}
	}

	/**
	 * POST /api/admin/data_structures/components_delete/{component_id} — POST alias for DELETE components.
	 */
	public function components_delete_post($component_id)
	{
		return $this->components_delete($component_id);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function _merge_audit_ids_on_create(array &$input)
	{
		$uid = $this->get_api_user_id();
		if (!$uid) {
			return;
		}
		if (!isset($input['created_by']) || $input['created_by'] === '' || $input['created_by'] === null) {
			$input['created_by'] = (int) $uid;
		}
		if (!isset($input['updated_by']) || $input['updated_by'] === '' || $input['updated_by'] === null) {
			$input['updated_by'] = (int) $uid;
		}
	}

	/**
	 * Set created_by / updated_by on nested structure payload for import_json.
	 *
	 * @param array $structure
	 */
	private function _merge_audit_ids_on_structure(array &$structure)
	{
		$uid = $this->get_api_user_id();
		if (!$uid) {
			return;
		}
		if (!isset($structure['created_by']) || $structure['created_by'] === '' || $structure['created_by'] === null) {
			$structure['created_by'] = (int) $uid;
		}
		if (!isset($structure['updated_by']) || $structure['updated_by'] === '' || $structure['updated_by'] === null) {
			$structure['updated_by'] = (int) $uid;
		}
	}

	private function _merge_audit_ids_on_update(array &$input)
	{
		$uid = $this->get_api_user_id();
		if (!$uid) {
			return;
		}
		if (!array_key_exists('updated_by', $input) || $input['updated_by'] === '' || $input['updated_by'] === null) {
			$input['updated_by'] = (int) $uid;
		}
	}

	/**
	 * @param array $component
	 * @return array
	 */
	private function _enrich_component_codelist(array $component)
	{
		if (empty($component['codelist_id'])) {
			$component['codelist'] = null;
			return $component;
		}
		$cl = $this->Codelist_model->get_codelist_by_id((int) $component['codelist_id']);
		if ($cl) {
			$id = (int) $cl['id'];
			$cl['items'] = $this->Codelist_item_model->get_items_by_codelist($id, true);
			$cl['groups'] = $this->Codelist_group_model->get_groups_by_codelist($id, true);
			$component['codelist'] = $cl;
		} else {
			$component['codelist'] = null;
		}
		return $component;
	}

	/**
	 * Strip internal/export-irrelevant fields from DSD export payload.
	 *
	 * Removes:
	 * - internal ids: id and *_id keys
	 * - content_hash, metadata, created_by, updated_by
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function _sanitize_export_payload($value)
	{
		if (!is_array($value)) {
			return $value;
		}
		$is_list = array_keys($value) === range(0, count($value) - 1);
		if ($is_list) {
			$out = [];
			foreach ($value as $item) {
				$out[] = $this->_sanitize_export_payload($item);
			}
			return $out;
		}
		$blocked = [
			'content_hash' => true,
			'metadata'     => true,
			'created_by'   => true,
			'updated_by'   => true,
			'pid'          => true,
		];
		$out = [];
		foreach ($value as $k => $v) {
			$key = (string) $k;
			if ($key === 'id' || isset($blocked[$key]) || preg_match('/_id$/', $key)) {
				continue;
			}
			$sanitized = $this->_sanitize_export_payload($v);
			if (($key === 'created' || $key === 'updated' || $key === 'changed') && (is_string($sanitized) || is_numeric($sanitized))) {
				$sanitized = $this->_to_utc_datetime_string($sanitized);
			}
			if ($key === 'translations' && is_array($sanitized) && empty($sanitized)) {
				continue;
			}
			$out[$key] = $sanitized;
		}
		return $out;
	}

	/**
	 * Convert date/time value to UTC string when parseable.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function _to_utc_datetime_string($value)
	{
		$raw = is_string($value) ? trim($value) : (string) $value;
		if ($raw === '') {
			return $value;
		}
		$ts = null;
		if (preg_match('/^\d+$/', $raw)) {
			$num = (float) $raw;
			// Heuristic: treat 13+ digits as milliseconds, otherwise seconds.
			$ts = strlen($raw) >= 13 ? (int) floor($num / 1000) : (int) $num;
		} else {
			$parsed = strtotime($raw);
			$ts = $parsed === false ? null : $parsed;
		}
		if ($ts === null) {
			return $value;
		}
		return gmdate('Y-m-d\TH:i:s\Z', $ts);
	}

	/**
	 * @param array $structure DB row
	 * @return array List of error objects
	 */
	private function _validate_structure_row(array $structure)
	{
		$errors = [];
		if (empty($structure['name'])) {
			$errors[] = ['path' => 'structure.name', 'message' => 'Name is missing.'];
		}
		return $errors;
	}

	/**
	 * @param array $structure Proposed fields for create
	 * @return array
	 */
	private function _validate_structure_payload_for_create(array $structure)
	{
		$errors = [];
		$name    = isset($structure['name']) ? trim((string) $structure['name']) : '';
		$agency  = isset($structure['agency']) && trim((string) $structure['agency']) !== '' ? trim((string) $structure['agency']) : Data_structure_model::DEFAULT_AGENCY;
		$version = isset($structure['version']) && trim((string) $structure['version']) !== '' ? trim((string) $structure['version']) : Data_structure_model::DEFAULT_VERSION;
		if (preg_match('/^\d+\.\d+$/', $version)) {
			$version .= '.0';
		}
		if ($name === '') {
			$errors[] = ['path' => 'structure.name', 'message' => 'Required.'];
			return $errors;
		}
		if (!preg_match(Data_structure_model::VERSION_REGEX, $version)) {
			$errors[] = ['path' => 'structure.version', 'message' => 'Version must be semantic version (e.g. 1.2.3).'];
		}
		if ($this->Data_structure_model->get_structure_by_identity($name, $agency, $version)) {
			$errors[] = ['path' => 'structure', 'message' => "Data structure already exists for agency '{$agency}', name '{$name}', version '{$version}'."]; 
		}
		$idno = isset($structure['idno']) ? trim((string) $structure['idno']) : '';
		if ($idno === '') {
			$idno = Data_structure_model::make_idno($agency, $name, $version);
		}
		if ($idno !== '' && ctype_digit($idno)) {
			$errors[] = ['path' => 'structure.idno', 'message' => 'idno must not be a purely numeric string (reserved for id-based paths).'];
		}
		if ($this->Data_structure_model->get_structure_by_idno($idno)) {
			$errors[] = ['path' => 'structure.idno', 'message' => "idno '{$idno}' already exists."];
		}
		return $errors;
	}

	/**
	 * @param array $components List of rows (DB shape or proposed)
	 * @param bool  $from_db    When true, rows are full DB rows (skip duplicate-name-in-request check).
	 * @return array
	 */
	private function _validate_components_list(array $components, $from_db)
	{
		$errors = [];
		$names  = [];
		foreach ($components as $idx => $row) {
			if (!is_array($row)) {
				$errors[] = ['path' => "components[{$idx}]", 'message' => 'Must be an object.'];
				continue;
			}
			$name = isset($row['name']) ? trim((string) $row['name']) : '';
			if ($name === '') {
				$errors[] = ['path' => "components[{$idx}].name", 'message' => 'Required.'];
			} elseif (!$from_db) {
				if (isset($names[$name])) {
					$errors[] = ['path' => "components[{$idx}].name", 'message' => 'Duplicate component name in payload.'];
				}
				$names[$name] = true;
			}
			$ct = isset($row['column_type']) ? trim((string) $row['column_type']) : '';
			if ($ct === '' || !in_array($ct, Data_structure_component_model::$allowed_column_types, true)) {
				$errors[] = ['path' => "components[{$idx}].column_type", 'message' => 'Invalid or missing column_type.'];
			}
			if (isset($row['data_type']) && $row['data_type'] !== null && trim((string) $row['data_type']) !== '') {
				$dt = trim((string) $row['data_type']);
				if (!in_array($dt, Data_structure_component_model::$allowed_data_types, true)) {
					$errors[] = ['path' => "components[{$idx}].data_type", 'message' => 'Invalid data_type.'];
				}
			}
			$cid = isset($row['codelist_id']) ? $row['codelist_id'] : null;
			if ($cid !== null && $cid !== '' && (int) $cid > 0) {
				if (!$this->Codelist_model->get_codelist_by_id((int) $cid)) {
					$errors[] = ['path' => "components[{$idx}].codelist_id", 'message' => 'Codelist not found.'];
				}
			}
			$needs_codelist = in_array($ct, ['dimension', 'geography'], true);
			if ($needs_codelist && (empty($cid) || (int) $cid <= 0)) {
				$errors[] = ['path' => "components[{$idx}].codelist_id", 'message' => 'codelist_id is required for dimension and geography.'];
			}
		}
		return $errors;
	}

	/**
	 * @param string $key non-empty trimmed id (digits) or catalogue idno
	 * @return array|null data_structures row (no components)
	 */
	private function _resolve_structure_row_from_key($key)
	{
		$key = trim((string) $key);
		if ($key === '') {
			return null;
		}
		if (ctype_digit($key)) {
			return $this->Data_structure_model->get_structure_by_id((int) $key, false);
		}
		return $this->Data_structure_model->get_structure_by_idno($key);
	}
}
