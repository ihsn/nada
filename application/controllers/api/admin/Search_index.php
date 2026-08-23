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
