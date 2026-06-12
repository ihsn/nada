<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API for bulk data access collections (Vue admin UI).
 *
 * Routed URL base: /api/admin/bulk-data-access/ — see routes.php
 *
 * IT note: use POST aliases for update (PATCH) and delete — no PATCH/DELETE verbs.
 */
class Bulk_da extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('Bulk_data_access', null, 'bulk_da_lib');
		$this->lang->load('general');
		$this->lang->load('da_collection');
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
	}

	/**
	 * Session auth (same as other admin JSON APIs).
	 */

	/**
	 * User must be allowed to use admin catalog (bulk DA UI).
	 *
	 * @throws AclAccessDeniedException
	 */
	protected function _require_bulk_da_catalog_access()
	{
		$user = $this->api_user();
		if (! $user) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
	}

	/**
	 * GET /collections
	 */
	public function collections_get()
	{
		try {
			$this->_require_bulk_da_catalog_access();

			$rows = $this->bulk_da_lib->select_all();
			if (! is_array($rows)) {
				$rows = array();
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array('rows' => $rows),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /collection/{id}
	 */
	public function collection_get($id = null)
	{
		try {
			$this->_require_bulk_da_catalog_access();

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$row = $this->bulk_da_lib->get_collection((int) $id);
			if (! $row) {
				$this->set_response(array('status' => 'error', 'message' => 'NOT_FOUND'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array('collection' => $row),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /collection
	 */
	public function collection_post()
	{
		try {
			$this->_require_bulk_da_catalog_access();

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_JSON_INPUT'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$title = isset($input['title']) ? trim((string) $input['title']) : '';
			if ($title === '') {
				$this->set_response(array('status' => 'error', 'message' => 'TITLE_REQUIRED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$options = array(
				'title'       => $title,
				'description' => isset($input['description']) ? trim((string) $input['description']) : '',
			);

			$ok = $this->bulk_da_lib->insert($options);
			if (! $ok) {
				$this->set_response(array('status' => 'error', 'message' => 'INSERT_FAILED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$id = (int) $this->db->insert_id();
			$row = $this->bulk_da_lib->get_collection($id);

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $this->lang->line('form_update_success') ? $this->lang->line('form_update_success') : 'OK',
					'result'  => array('collection' => $row),
				),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /collection_update/{id} — PATCH alias.
	 */
	public function collection_update_post($id = null)
	{
		try {
			$this->_require_bulk_da_catalog_access();

			if (! is_numeric($id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_JSON_INPUT'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$title = isset($input['title']) ? trim((string) $input['title']) : '';
			if ($title === '') {
				$this->set_response(array('status' => 'error', 'message' => 'TITLE_REQUIRED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$options = array(
				'title'       => $title,
				'description' => isset($input['description']) ? trim((string) $input['description']) : '',
			);

			$exists = $this->bulk_da_lib->get_collection((int) $id);
			if (! $exists) {
				$this->set_response(array('status' => 'error', 'message' => 'NOT_FOUND'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$ok = $this->bulk_da_lib->update((int) $id, $options);
			if (! $ok) {
				$this->set_response(array('status' => 'error', 'message' => 'UPDATE_FAILED'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$row = $this->bulk_da_lib->get_collection((int) $id);

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $this->lang->line('form_update_success') ? $this->lang->line('form_update_success') : 'OK',
					'result'  => array('collection' => $row),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /collections_delete — DELETE alias. JSON: { "ids": [1,2,3] }
	 */
	public function collections_delete_post()
	{
		try {
			$this->_require_bulk_da_catalog_access();

			$input = $this->raw_json_input();
			if (! is_array($input) || ! isset($input['ids']) || ! is_array($input['ids'])) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_IDS'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$deleted = array();
			foreach ($input['ids'] as $raw) {
				if (! is_numeric($raw)) {
					continue;
				}
				$cid = (int) $raw;
				if ($cid < 1) {
					continue;
				}
				if ($this->bulk_da_lib->get_collection($cid)) {
					$this->bulk_da_lib->delete($cid);
					$deleted[] = $cid;
				}
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => 'OK',
					'result'  => array('deleted_ids' => $deleted),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /studies_search/{collection_id}
	 */
	public function studies_search_get($collection_id = null)
	{
		try {
			$this->_require_bulk_da_catalog_access();

			if (! is_numeric($collection_id)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_ID'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$cid = (int) $collection_id;
			$col = $this->bulk_da_lib->get_collection($cid);
			if (! $col) {
				$this->set_response(array('status' => 'error', 'message' => 'NOT_FOUND'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$ps = (int) $this->input->get('ps');
			if ($ps < 1 || $ps > 500) {
				$ps = 50;
			}

			$page = max(1, (int) $this->input->get('page'));
			$offset = ($page - 1) * $ps;

			$sort_by    = $this->input->get('sort_by', true);
			$sort_order = $this->input->get('sort_order', true);
			$keywords   = $this->input->get('keywords', true);

			$search_options = array();
			if (is_string($keywords) && trim($keywords) !== '') {
				$search_options['keywords'] = $keywords;
			}

			if ((int) $this->input->get('selected_only') === 1) {
				$search_options['selected_only'] = $this->bulk_da_lib->get_study_id_list_by_set($cid);
			}

			$total = $this->bulk_da_lib->search_count($search_options);
			$rows  = $this->bulk_da_lib->search($search_options, $ps, $offset, $sort_by, $sort_order);

			if (! is_array($rows)) {
				$rows = array();
			}

			$linked = $this->bulk_da_lib->get_study_id_list_by_set($cid);
			$regional = $this->config->item('regional_search') === 'yes';

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'collection'       => $col,
						'rows'             => $rows,
						'total'            => (int) $total,
						'page'             => $page,
						'page_size'        => $ps,
						'linked_study_ids' => $linked,
						'regional_search'  => $regional,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /study_link — JSON: collection_id, sid, linked (1|0)
	 */
	public function study_link_post()
	{
		try {
			$this->_require_bulk_da_catalog_access();

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_JSON_INPUT'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$cid = isset($input['collection_id']) ? (int) $input['collection_id'] : 0;
			$sid = isset($input['sid']) ? (int) $input['sid'] : 0;
			$linked = isset($input['linked']) ? (int) $input['linked'] : -1;

			if ($cid < 1 || $sid < 1 || ($linked !== 0 && $linked !== 1)) {
				$this->set_response(array('status' => 'error', 'message' => 'INVALID_PARAMS'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			if (! $this->bulk_da_lib->get_collection($cid)) {
				$this->set_response(array('status' => 'error', 'message' => 'NOT_FOUND'), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$this->has_dataset_access('edit', $sid);

			if ($linked === 1) {
				if (in_array($sid, $this->bulk_da_lib->get_study_id_list_by_set($cid), true)) {
					$this->set_response(array('status' => 'success', 'result' => array('collection_id' => $cid, 'sid' => $sid, 'linked' => true)), REST_Controller::HTTP_OK);
					return;
				}
				$this->bulk_da_lib->attach_study($cid, $sid);
			}
			else {
				$this->bulk_da_lib->detach_study($cid, $sid);
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'collection_id' => $cid,
						'sid'           => $sid,
						'linked'        => (bool) $linked,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'error', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
