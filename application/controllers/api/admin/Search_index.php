<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — search index change queue for the configured provider.
 *
 * Routes:
 *   GET  /api/admin/search-index/status
 *   GET  /api/admin/search-index/queue
 *   POST /api/admin/search-index/queue/{id}/ack
 *   POST /api/admin/search-index/requeue
 *   POST /api/admin/search-index/state/bulk
 *   GET  /api/admin/search-index/diff/missing
 *   GET  /api/admin/search-index/diff/stale
 *   GET  /api/admin/search-index/summary
 *   GET  /api/admin/search-index/type-breakdown
 */
class Search_index extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->library('Search_index_manager');
	}

	/**
	 * GET /api/admin/search-index/status
	 */
	public function status_get()
	{
		try {
			$this->_require_admin_catalog_access();
			$this->set_response(
				array('status' => 'success') + $this->search_index_manager->status(),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-index/summary?object_type=survey
	 *
	 * How many catalog entries of object_type exist vs. their
	 * search_index_state breakdown — scoped to one object_type, unlike
	 * status() above (which is a global total across every tracked type).
	 */
	public function summary_get()
	{
		try {
			$this->_require_admin_catalog_access();
			$object_type = $this->input->get('object_type');
			$summary = $this->search_index_manager->object_type_summary(
				($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey'
			);
			$this->set_response(array('status' => 'success') + $summary, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-index/type-breakdown?object_type=survey
	 *
	 * Per-data-type (surveys.type) coverage — catalog_total/indexed/missing/
	 * stale broken out per microdata/geospatial/document/timeseries/etc,
	 * rather than summary()'s single total lumped across all of them.
	 */
	public function type_breakdown_get()
	{
		try {
			$this->_require_admin_catalog_access();
			$object_type = $this->input->get('object_type');
			$items = $this->search_index_manager->type_breakdown(
				($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : 'survey'
			);
			$this->set_response(array('status' => 'success', 'items' => $items), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-index/queue
	 */
	public function queue_get()
	{
		try {
			$this->_require_admin_catalog_access();

			$status      = $this->input->get('status');
			$object_type = $this->input->get('object_type');
			$limit       = $this->input->get('limit');

			$page = $this->search_index_manager->list_queue(
				($status !== false && $status !== null && $status !== '') ? (string) $status : 'pending',
				(int) $limit,
				($object_type !== false && $object_type !== null && $object_type !== '') ? (string) $object_type : null
			);

			$this->set_response(
				array(
					'status'            => 'success',
					'tracking_enabled'  => $page['tracking_enabled'],
					'total'             => $page['total'],
					'limit'             => $page['limit'],
					'items'             => $page['items'],
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/search-index/queue/{id}/ack
	 */
	public function ack_post($id = null)
	{
		try {
			$this->_require_admin_catalog_access();

			$body    = $this->_json_body();
			$result  = isset($body['result']) ? (string) $body['result'] : '';
			$changed = isset($body['changed']) ? $body['changed'] : null;
			$error   = isset($body['error']) ? (string) $body['error'] : null;

			if ($changed === null || $changed === '') {
				throw new Search_index_exception('CHANGED_REQUIRED', 400);
			}

			$ack = $this->search_index_manager->ack((int) $id, $result, (int) $changed, $error);
			$this->set_response(
				array('status' => 'success') + $ack,
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/search-index/requeue
	 */
	public function requeue_post()
	{
		try {
			$this->_require_admin_catalog_access();
			$body = $this->_json_body();

			if (isset($body['status']) && $body['status'] === 'failed') {
				$count = $this->search_index_manager->requeue_failed();
				$this->set_response(
					array('status' => 'success', 'reset' => $count),
					REST_Controller::HTTP_OK
				);
				return;
			}

			$object_type = isset($body['object_type']) ? $body['object_type'] : null;
			$object_id   = isset($body['object_id']) ? $body['object_id'] : null;
			if ($object_type === null || $object_id === null) {
				throw new Search_index_exception('OBJECT_ID_REQUIRED', 400);
			}

			$item = $this->search_index_manager->requeue_object($object_type, $object_id);
			$this->set_response(
				array('status' => 'success', 'item' => $item),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/search-index/state/bulk
	 * body: {items: [{object_type, object_key, status}, ...]}
	 *
	 * Direct search_index_state upsert for a batch of items — for content
	 * indexed/deleted via an admin/bulk operation, which has no pre-existing
	 * search_index_queue row to ack against (see queue_get()/ack_post() docs
	 * above for the queue-driven path this is distinct from). Status 'deleted'
	 * removes the state row rather than storing it.
	 */
	public function state_bulk_post()
	{
		try {
			$this->_require_admin_catalog_access();

			$body  = $this->_json_body();
			$items = isset($body['items']) && is_array($body['items']) ? $body['items'] : array();
			if (empty($items)) {
				throw new Search_index_exception('ITEMS_REQUIRED', 400);
			}

			$result = $this->search_index_manager->bulk_set_state($items);
			$this->set_response(
				array('status' => 'success') + $result,
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-index/diff/missing?object_type=survey&limit=&offset=
	 *
	 * Published catalog entries of object_type with no current 'indexed'
	 * search_index_state row — never indexed, or indexed then failed/stale
	 * since. Pairs with diff_stale_get() for the other direction.
	 */
	public function diff_missing_get()
	{
		try {
			$this->_require_admin_catalog_access();

			$object_type = $this->input->get('object_type');
			$limit       = $this->input->get('limit');
			$offset      = $this->input->get('offset');
			$data_type   = $this->input->get('data_type');
			$has_error   = $this->input->get('has_error');

			$page = $this->search_index_manager->diff_missing(
				(string) $object_type,
				$limit !== false && $limit !== null && $limit !== '' ? (int) $limit : 100,
				$offset !== false && $offset !== null && $offset !== '' ? (int) $offset : 0,
				$data_type !== false && $data_type !== null && $data_type !== '' ? (string) $data_type : null,
				in_array($has_error, array('1', 'true', 'yes'), true)
			);
			$this->set_response(array('status' => 'success') + $page, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/search-index/diff/stale?object_type=survey&limit=&offset=
	 *
	 * search_index_state rows of object_type marked 'indexed' whose catalog
	 * entry is gone or unpublished — should be removed from the index.
	 */
	public function diff_stale_get()
	{
		try {
			$this->_require_admin_catalog_access();

			$object_type = $this->input->get('object_type');
			$limit       = $this->input->get('limit');
			$offset      = $this->input->get('offset');
			$data_type   = $this->input->get('data_type');

			$page = $this->search_index_manager->diff_stale(
				(string) $object_type,
				$limit !== false && $limit !== null && $limit !== '' ? (int) $limit : 100,
				$offset !== false && $offset !== null && $offset !== '' ? (int) $offset : 0,
				$data_type !== false && $data_type !== null && $data_type !== '' ? (string) $data_type : null
			);
			$this->set_response(array('status' => 'success') + $page, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Search_index_exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				$e->http_code
			);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	private function _json_body()
	{
		$raw = trim((string) $this->input->raw_input_stream);
		if ($raw === '') {
			return array();
		}
		$data = json_decode($raw, true);
		return is_array($data) ? $data : array();
	}

	/**
	 * @throws AclAccessDeniedException
	 */
	private function _require_admin_catalog_access()
	{
		$user = $this->api_user();
		if (!$user) {
			throw new AclAccessDeniedException('ACCESS_DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS_DENIED');
		}
	}
}
