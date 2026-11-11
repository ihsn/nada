<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin data-access whitelist API.
 *
 * Base URL: /api/admin/dataaccess_whitelist
 * Auth: session or API key; site admin only (same gate as admin/Dataaccess_whitelist).
 *
 *   GET  /                 rows + collections for the form
 *   POST /                 create  { email, repository_id } or { email, collection_name }
 *   POST {id}/delete       delete by whitelist row id
 *   DELETE /               delete by { email, collection_name } (legacy API shape)
 *   POST delete            alias for DELETE /
 */
class Dataaccess_whitelist extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Data_access_whitelist_model');
		$this->load->model('Repository_model');
		$this->is_authenticated_or_die();
		$this->is_admin_or_die();
	}

	/**
	 * GET /api/admin/dataaccess_whitelist
	 */
	public function index_get()
	{
		try {
			$rows = $this->Data_access_whitelist_model->select_all();
			$collections = array();
			foreach ($this->Repository_model->list_all() as $repo) {
				$collections[] = array(
					'id'           => (int) $repo['id'],
					'repositoryid' => (string) $repo['repositoryid'],
					'title'        => (string) $repo['title'],
					'ispublished'  => (int) $repo['ispublished'],
				);
			}

			$this->set_response(
				array(
					'status'      => 'success',
					'rows'        => $rows ? $rows : array(),
					'collections' => $collections,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/dataaccess_whitelist
	 *
	 * JSON: email + repository_id (preferred) or collection_name
	 */
	public function index_post()
	{
		try {
			$options = $this->raw_json_input();
			if (! is_array($options)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$email = isset($options['email']) && is_scalar($options['email'])
				? trim((string) $options['email'])
				: '';
			if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Invalid values for `collection_name` or `email`');
			}

			$user_id = $this->Data_access_whitelist_model->get_user_id($email);
			$repository_id = $this->_resolve_repository_id($options);

			if (! $repository_id || ! $user_id) {
				throw new Exception('Invalid values for `collection_name` or `email`');
			}

			$result = $this->Data_access_whitelist_model->insert($repository_id, $user_id);

			$this->set_response(
				array('status' => 'success', 'result' => $result),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/dataaccess_whitelist/{id}/delete
	 */
	public function item_delete_post($id = null)
	{
		try {
			if (! is_numeric($id) || (int) $id <= 0) {
				throw new Exception('NOT_FOUND');
			}
			$this->Data_access_whitelist_model->delete_by_id((int) $id);
			$this->set_response(
				array('status' => 'success', 'message' => 'DELETED'),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * DELETE /api/admin/dataaccess_whitelist
	 * JSON body: collection_name, email (legacy external API shape).
	 */
	public function index_delete()
	{
		try {
			$options = $this->raw_json_input();
			if (! is_array($options)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$collection_name = isset($options['collection_name']) ? $options['collection_name'] : null;
			$email = isset($options['email']) ? $options['email'] : null;

			$repository_id = $this->Data_access_whitelist_model->get_repo_id($collection_name);
			$user_id = $this->Data_access_whitelist_model->get_user_id($email);

			if (! $repository_id || ! $user_id) {
				throw new Exception('Invalid values for `collection_name` or `email`');
			}

			$result = $this->Data_access_whitelist_model->delete($repository_id, $user_id);

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => 'DELETED',
					'result'  => $result,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/dataaccess_whitelist/delete
	 * Alias for DELETE /.
	 */
	public function delete_post()
	{
		return $this->index_delete();
	}

	/**
	 * @param array $options
	 * @return int|false
	 */
	private function _resolve_repository_id($options)
	{
		if (isset($options['repository_id']) && is_numeric($options['repository_id']) && (int) $options['repository_id'] > 0) {
			$repo = $this->Repository_model->get_repositoryid_by_uid((int) $options['repository_id']);
			return $repo ? (int) $options['repository_id'] : false;
		}

		$collection_name = isset($options['collection_name']) ? $options['collection_name'] : null;
		if ($collection_name === null || $collection_name === '') {
			return false;
		}

		return $this->Data_access_whitelist_model->get_repo_id($collection_name);
	}

	private function _error(Exception $e)
	{
		$message = $e->getMessage();
		$code = ($message === 'NOT_FOUND')
			? REST_Controller::HTTP_NOT_FOUND
			: REST_Controller::HTTP_BAD_REQUEST;
		$this->set_response(array('status' => 'failed', 'message' => $message), $code);
	}
}
