<?php

require APPPATH.'/libraries/MY_REST_Controller.php';

/**
 * Depositor Vue API (session or API key + project collaborator).
 *
 * Base: /api/datadeposit
 * Staff review is a later /api/admin/datadeposit. Legacy /api/datadeposits stays as-is.
 */
class Datadeposit extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('datadeposit');
		datadeposit_require_enabled();
		$this->load->library('Deposit_depositor');
		$this->lang->load('dd_projects');
		$this->is_authenticated_or_die();
	}

	/**
	 * GET /api/datadeposit
	 * Query: page, page_size, q, status, sort_by, sort_order
	 */
	public function index_get()
	{
		$this->_run(function ($user) {
			return $this->deposit_depositor->list_projects($user['id'], $this->_list_params());
		});
	}

	/**
	 * POST /api/datadeposit
	 */
	public function index_post()
	{
		$this->_run(function ($user) {
			return $this->deposit_depositor->create_project($user, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}
	 */
	public function item_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->get_project($user, $id);
		});
	}

	/**
	 * POST /api/datadeposit/{id}
	 */
	public function item_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->update_project($user, $id, $this->_body());
		});
	}

	/**
	 * POST /api/datadeposit/{id}/delete
	 */
	public function delete_item_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->delete_project($user, $id);
		});
	}

	/**
	 * POST /api/datadeposit/{id}/reopen
	 */
	public function reopen_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->reopen_project($user, $id, $this->_body());
		});
	}

	/**
	 * POST /api/datadeposit/{id}/email
	 */
	public function email_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->email_summary($user, $id, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}/validate — stored metadata + submission.
	 * POST /api/datadeposit/{id}/validate — optional { metadata, submission } overlay.
	 */
	public function validate_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->validate_project($user, $id, array());
		});
	}

	public function validate_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->validate_project($user, $id, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}/import — same-type v2 projects that can be copied from.
	 */
	public function import_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->list_import_sources($user, $id, $this->input->get('q'));
		});
	}

	/**
	 * POST /api/datadeposit/{id}/import
	 * Body: { "source_project_id": 12 } or { "json": { ... } }
	 */
	public function import_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->import_metadata($user, $id, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}/export/{format}
	 * format: ddi | json | metadata | project | rdf | external_resources
	 */
	public function export_get($id = null, $format = null)
	{
		try {
			$user = $this->_user();
			$file = $this->deposit_depositor->export_project($user, $id, $format);
			$this->load->helper('download');
			force_download($file['filename'], $file['body']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * GET /api/datadeposit/{id}/metadata
	 */
	public function metadata_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->get_metadata($user, $id);
		});
	}

	/**
	 * POST /api/datadeposit/{id}/metadata
	 */
	public function metadata_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->save_metadata($user, $id, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}/submission
	 */
	public function submission_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->get_submission($user, $id);
		});
	}

	/**
	 * POST /api/datadeposit/{id}/submission
	 */
	public function submission_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->save_submission($user, $id, $this->_body());
		});
	}

	/**
	 * POST /api/datadeposit/{id}/submit
	 */
	public function submit_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->submit_project($user, $id, $this->_body());
		});
	}

	/**
	 * GET /api/datadeposit/{id}/files/{fid}/download
	 */
	public function files_download_get($id = null, $fid = null)
	{
		try {
			$user = $this->_user();
			$file = $this->deposit_depositor->download_file($user, $id, $fid);
			$this->load->helper('download');
			force_download3($file['path'], $file['filename']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * GET /api/datadeposit/{id}/files
	 */
	public function files_get($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->list_files($user, $id);
		});
	}

	/**
	 * POST /api/datadeposit/{id}/files  (commit resumable upload)
	 */
	public function files_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->commit_file($user, $id, $this->_body());
		});
	}

	/**
	 * POST /api/datadeposit/{id}/files/{fid}
	 */
	public function files_item_post($id = null, $fid = null)
	{
		$this->_run(function ($user) use ($id, $fid) {
			return $this->deposit_depositor->save_file($user, $id, $fid, $this->_body());
		});
	}

	/**
	 * POST /api/datadeposit/{id}/files/delete
	 */
	public function files_delete_post($id = null)
	{
		$this->_run(function ($user) use ($id) {
			return $this->deposit_depositor->delete_files($user, $id, $this->_body());
		});
	}

	private function _run($fn)
	{
		try {
			$user = $this->_user();
			$this->set_response($fn($user), REST_Controller::HTTP_OK);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	private function _user()
	{
		$user = $this->api_user();
		if (!$user) {
			throw new Deposit_depositor_exception('ACCESS_DENIED', REST_Controller::HTTP_FORBIDDEN);
		}
		return $this->deposit_depositor->user_from_object($user);
	}

	private function _list_params()
	{
		$sort_by = $this->input->get('sort_by');
		$sort_order = strtolower((string) $this->input->get('sort_order'));
		$allowed = array('title', 'created_by', 'status', 'created_on', 'last_modified');
		if (!in_array($sort_by, $allowed, true)) {
			$sort_by = 'created_on';
		}
		if ($sort_order !== 'asc' && $sort_order !== 'desc') {
			$sort_order = 'desc';
		}
		return array(
			'page'       => (int) $this->input->get('page'),
			'page_size'  => (int) $this->input->get('page_size'),
			'keywords'   => trim((string) $this->input->get('q')),
			'status'     => $this->input->get('status'),
			'sort_by'    => $sort_by,
			'sort_order' => $sort_order,
		);
	}

	private function _body()
	{
		$payload = $this->input->post('payload', false);
		if ($payload !== null && $payload !== false && $payload !== '') {
			$decoded = json_decode($payload, true);
			return is_array($decoded) ? $decoded : array();
		}
		$raw = $this->input->raw_input_stream;
		if (!$raw || trim((string) $raw) === '') {
			return array();
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : array();
	}
}
