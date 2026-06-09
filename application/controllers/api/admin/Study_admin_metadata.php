<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — study admin metadata (API-only custom JSON attached to a study).
 *
 * Study ref is IDNO by default; pass query `id_format=id` for numeric `surveys.id`.
 *
 * Routes:
 *   GET    /api/admin/catalog/{idno}/admin-metadata
 *   POST   /api/admin/catalog/{idno}/admin-metadata — create `{ "metadata": {…} }`; fails if metadata exists unless `overwrite` is set
 *   POST   /api/admin/catalog/{idno}/admin-metadata/update — replace `{ "metadata": {…} }`
 *   DELETE /api/admin/catalog/{idno}/admin-metadata
 *   POST   /api/admin/catalog/{idno}/admin-metadata/delete — alias for DELETE
 */
class Study_admin_metadata extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper('date');
		$this->load->library('acl_manager');
		$this->load->model('Study_admin_metadata_model');
	}

	public function _auth_override_check()
	{
		if ($this->session->userdata('user_id')) {
			return true;
		}

		return parent::_auth_override_check();
	}

	public function index_get($idno = null)
	{
		try {
			$sid = $this->_authorize($idno, 'view');
			$row = $this->Study_admin_metadata_model->get_row($sid);

			$this->set_response(
				$this->_format_response($sid, $row),
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

	public function index_post($idno = null)
	{
		try {
			$options = $this->_read_json_body();
			if ($this->_is_overwrite($options)) {
				$response = $this->_save_metadata_replace($idno, $options);
			} else {
				$response = $this->_save_metadata_create($idno, $options);
			}
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$code = ($e->getMessage() === 'METADATA_ALREADY_EXISTS')
				? REST_Controller::HTTP_CONFLICT
				: REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), $code);
		}
	}

	public function update_post($idno = null)
	{
		try {
			$options = $this->_read_json_body();
			$response = $this->_save_metadata_replace($idno, $options);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_delete($idno = null)
	{
		try {
			$sid = $this->_authorize($idno, 'edit');

			if ($this->Study_admin_metadata_model->exists($sid)) {
				$this->Study_admin_metadata_model->delete($sid);
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'sid'     => (int) $sid,
					'deleted' => true,
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

	public function delete_post($idno = null)
	{
		return $this->index_delete($idno);
	}

	/**
	 * Create admin metadata for a study (POST without overwrite).
	 *
	 * @param string|null $idno
	 * @param array       $options
	 * @return array
	 */
	private function _save_metadata_create($idno, array $options)
	{
		$sid = $this->_authorize($idno, 'edit');

		if ($this->Study_admin_metadata_model->exists($sid)) {
			throw new Exception('METADATA_ALREADY_EXISTS');
		}

		$user_id = $this->get_api_user_id();
		$metadata = $this->_parse_metadata_input($options);
		$row = $this->Study_admin_metadata_model->replace($sid, $metadata, $user_id);

		return $this->_format_response($sid, $row);
	}

	/**
	 * Replace admin metadata for a study.
	 *
	 * @param string|null $idno
	 * @param array       $options
	 * @return array
	 */
	private function _save_metadata_replace($idno, array $options)
	{
		$sid = $this->_authorize($idno, 'edit');
		$user_id = $this->get_api_user_id();
		$metadata = $this->_parse_metadata_input($options);
		$row = $this->Study_admin_metadata_model->replace($sid, $metadata, $user_id);

		return $this->_format_response($sid, $row);
	}

	/**
	 * @param string|null $idno
	 * @param string      $permission view|edit
	 * @return int sid
	 */
	private function _authorize($idno, $permission)
	{
		$user = $this->api_user();
		if (! $user) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}
		if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
			throw new AclAccessDeniedException('ACCESS-DENIED');
		}

		$sid = $this->get_sid_from_idno($idno);
		$this->has_dataset_access($permission, $sid);

		return (int) $sid;
	}

	/**
	 * @return array
	 */
	private function _read_json_body()
	{
		$options = (array) $this->raw_json_input();
		if (empty($options)) {
			$options = (array) $this->input->post(null, true);
		}
		return $options;
	}

	/**
	 * Require envelope `{ "metadata": {…} }`. Top-level `overwrite` is a control field, not stored.
	 *
	 * @param array $options
	 * @return array
	 */
	private function _parse_metadata_input(array $options)
	{
		if (! array_key_exists('metadata', $options)) {
			throw new Exception('METADATA_REQUIRED');
		}
		if (! is_array($options['metadata'])) {
			throw new Exception('METADATA_MUST_BE_OBJECT');
		}
		return $options['metadata'];
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	private function _is_overwrite(array $options)
	{
		if (array_key_exists('overwrite', $options)) {
			return $this->_is_truthy($options['overwrite']);
		}

		$query = $this->input->get('overwrite');
		if ($query !== null && $query !== '') {
			return $this->_is_truthy($query);
		}

		return false;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private function _is_truthy($value)
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value)) {
			return $value === 1;
		}
		$s = strtolower(trim((string) $value));
		return in_array($s, array('1', 'true', 'yes', 'on'), true);
	}

	/**
	 * @param int        $sid
	 * @param array|null $row
	 * @return array
	 */
	private function _format_response($sid, $row)
	{
		$metadata = array();
		$created = null;
		$changed = null;
		$created_by = null;
		$changed_by = null;

		if (is_array($row)) {
			$metadata = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : array();
			$created = isset($row['created']) ? $row['created'] : null;
			$changed = isset($row['changed']) ? $row['changed'] : null;
			$created_by = isset($row['created_by']) ? (int) $row['created_by'] : null;
			$changed_by = isset($row['changed_by']) ? (int) $row['changed_by'] : null;
		}

		$response = array(
			'status'      => 'success',
			'sid'         => (int) $sid,
			'metadata'    => $metadata,
			'created'     => $created,
			'changed'     => $changed,
			'created_by'  => $created_by,
			'changed_by'  => $changed_by,
		);

		array_walk($response, 'unix_date_to_gmt_row', array('created', 'changed'));

		return $response;
	}
}
