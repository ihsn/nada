<?php

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Staff API for the data-deposit admin Vue app.
 *
 * Base: /api/admin/datadeposit
 * ACL: datadeposit view / edit / delete
 */
class Datadeposit extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('datadeposit');
		datadeposit_require_enabled();
		$this->load->library('acl_manager');
		$this->load->library('Deposit_depositor');
		$this->load->model('DD_project_model');
		$this->is_authenticated_or_die();
	}

	/**
	 * GET /api/admin/datadeposit
	 *
	 * Query: filter (all|draft|submitted|processed|accepted|closed|requested),
	 *        keywords, sort_by, sort_order
	 */
	public function index_get()
	{
		try {
			$this->require_access('datadeposit', 'view');

			$filter = $this->get('filter');
			if ($filter === null || $filter === '') {
				$filter = $this->input->get('filter', true);
			}
			$filter = strtolower(trim((string) $filter));
			$allowed = array('all', 'draft', 'submitted', 'processed', 'accepted', 'closed', 'requested');
			if (!in_array($filter, $allowed, true)) {
				$filter = 'all';
			}

			$keywords = $this->get('keywords');
			if ($keywords === null || $keywords === '') {
				$keywords = $this->input->get('keywords', true);
			}
			$keywords = trim((string) $keywords);

			$sort_by = $this->get('sort_by');
			if ($sort_by === null || $sort_by === '') {
				$sort_by = $this->input->get('sort_by', true);
			}
			$sort_order = $this->get('sort_order');
			if ($sort_order === null || $sort_order === '') {
				$sort_order = $this->input->get('sort_order', true);
			}

			if ($filter === 'requested') {
				$projects = $this->DD_project_model->all_projects_requested_reopen($sort_by, $sort_order, $keywords);
			} elseif ($filter === 'all') {
				$projects = $this->DD_project_model->all_projects_by_filter(NULL, $sort_by, $sort_order, $keywords);
			} else {
				$projects = $this->DD_project_model->all_projects_by_filter($filter, $sort_by, $sort_order, $keywords);
			}

			if (!is_array($projects)) {
				$projects = array();
			}

			$this->set_response(array(
				'status' => 'success',
				'result' => array(
					'items' => $projects,
					'total' => count($projects),
					'counts' => $this->DD_project_model->admin_project_counts(),
				),
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/projects/{id}
	 *
	 * v2 summary payload for the workspace info tab (not dd_study).
	 */
	public function projects_get($id = null)
	{
		try {
			$this->require_access('datadeposit', 'view');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$payload = $this->deposit_depositor->summary_payload($id);
			if (!$payload) {
				$this->set_response(array(
					'status' => 'failed',
					'message' => 'Project was not found',
				), REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			$this->set_response(array(
				'status' => 'success',
				'result' => $payload,
			), REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/projects/{id}/export/{format}
	 * format: ddi | json | metadata | project | rdf | external_resources
	 */
	public function projects_export_get($id = null, $format = null)
	{
		try {
			$this->require_access('datadeposit', 'view');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$file = $this->deposit_depositor->admin_export_project($id, $format, $actor);
			$this->load->helper('download');
			force_download($file['filename'], $file['body']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/projects/{id}/files/{fid}/download
	 */
	public function projects_files_download_get($id = null, $fid = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($id) || !is_numeric($fid)) {
				throw new Exception('File was not found');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$file = $this->deposit_depositor->admin_download_file($id, $fid, $actor);
			$this->load->helper('download');
			force_download3($file['path'], $file['filename']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/projects/{id}/history
	 */
	public function projects_history_get($id = null)
	{
		try {
			$this->require_access('datadeposit', 'view');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$out = $this->deposit_depositor->admin_project_history($id);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/tasks
	 */
	public function tasks_get()
	{
		try {
			$this->require_access('datadeposit', 'view');
			$out = $this->deposit_depositor->admin_tasks_list();
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/tasks/{id}
	 */
	public function tasks_item_get($id = null)
	{
		try {
			$this->require_access('datadeposit', 'view');
			if (!is_numeric($id)) {
				throw new Exception('Task was not found');
			}
			$out = $this->deposit_depositor->admin_task_detail($id);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/datadeposit/tasks/{id}/update
	 * Body: status (0 = WIP, 1 = completed)
	 */
	public function tasks_update_post($id = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');
			if (!is_numeric($id)) {
				throw new Exception('Task was not found');
			}
			$body = $this->request_body();
			$status = isset($body['status']) ? $body['status'] : null;
			$out = $this->deposit_depositor->admin_update_task($id, $status);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * DELETE /api/admin/datadeposit/tasks/{id}
	 */
	public function tasks_item_delete($id = null)
	{
		$this->respond_delete_task($id);
	}

	/**
	 * POST /api/admin/datadeposit/tasks/{id}/delete
	 */
	public function tasks_delete_post($id = null)
	{
		$this->respond_delete_task($id);
	}

	/**
	 * @param mixed $id
	 */
	private function respond_delete_task($id)
	{
		try {
			$this->require_access('datadeposit', 'delete');
			if (!is_numeric($id)) {
				throw new Exception('Task was not found');
			}
			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$out = $this->deposit_depositor->admin_delete_task($id, $actor);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/tasks/my
	 */
	public function tasks_my_get()
	{
		try {
			$this->require_access('datadeposit', 'view');
			$out = $this->deposit_depositor->admin_my_tasks_list($this->get_api_user_id());
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * DELETE /api/admin/datadeposit/projects/{id}
	 */
	public function projects_delete($id = null)
	{
		$this->respond_delete_projects(array($id));
	}

	/**
	 * POST /api/admin/datadeposit/projects/{id}/delete
	 */
	public function projects_delete_post($id = null)
	{
		$this->projects_delete($id);
	}

	/**
	 * POST /api/admin/datadeposit/delete
	 * Body: project[] (ids)
	 */
	public function delete_post()
	{
		$body = $this->request_body();
		$raw = array();
		if (isset($body['project'])) {
			$raw = is_array($body['project']) ? $body['project'] : array($body['project']);
		} elseif (isset($body['ids'])) {
			$raw = is_array($body['ids']) ? $body['ids'] : array($body['ids']);
		}
		$this->respond_delete_projects($raw);
	}

	/**
	 * @param array $ids
	 */
	private function respond_delete_projects(array $ids)
	{
		try {
			$this->require_access('datadeposit', 'delete');

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$uid = ($user && isset($user->id)) ? $user->id : $this->get_api_user_id();
			$out = $this->deposit_depositor->admin_delete_projects($ids, $actor, $uid);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/datadeposit/projects/{id}/assign
	 */
	public function projects_assign_get($id = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$out = $this->deposit_depositor->admin_assign_payload($id);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/datadeposit/projects/{id}/assign
	 * Body: user_id
	 */
	public function projects_assign_post($id = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$body = $this->request_body();
			$user_id = isset($body['user_id']) ? $body['user_id'] : 0;
			$assigner_id = $this->get_api_user_id();
			$out = $this->deposit_depositor->admin_assign_task($id, $user_id, $assigner_id);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/datadeposit/projects/{id}/process
	 * Body: status, catalog_study_id, admin_comments, notify
	 */
	public function projects_process_post($id = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$out = $this->deposit_depositor->admin_process_project($id, $this->request_body(), $actor);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/datadeposit/projects/{id}/communicate
	 * Body: to, cc, subject, body
	 */
	public function projects_communicate_post($id = null)
	{
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$out = $this->deposit_depositor->admin_send_compose_email($id, $this->request_body(), $actor);
			$this->set_response($out, REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'failed',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * @return array
	 */
	private function request_body()
	{
		$body = $this->post();
		if (is_array($body) && $body) {
			return $body;
		}
		$raw = $this->input->raw_input_stream;
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : array();
	}
}
