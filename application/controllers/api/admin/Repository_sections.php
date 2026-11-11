<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin repository sections (collection groups) API.
 *
 * Base URL: /api/admin/repository_sections
 * Auth: session or API key; ACL collection_type (same gates as admin/Repository_sections).
 *
 *   GET  /                 list sections
 *   POST /                 create  { title, weight }
 *   GET  {id}
 *   POST {id}              update  { title, weight }
 *   POST {id}/delete
 *   POST batch_delete      { ids: [] }
 */
class Repository_sections extends MY_REST_Controller {

	const MAX_TITLE = 255;
	const MAX_WEIGHT = 999;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Repository_sections_model');
		$this->is_authenticated_or_die();
	}

	/**
	 * GET /api/admin/repository_sections
	 */
	public function index_get()
	{
		$this->require_access('collection_type', 'view');

		try {
			$sort_by = $this->input->get('sort_by') ?: 'weight';
			$sort_order = strtolower((string) $this->input->get('sort_order')) === 'desc' ? 'desc' : 'asc';
			$allowed = array('title', 'weight', 'id');
			if (! in_array($sort_by, $allowed, true)) {
				$sort_by = 'weight';
			}

			$sections = array();
			foreach ($this->Repository_sections_model->select_all($sort_by, $sort_order) as $row) {
				$sections[] = $this->_format($row);
			}

			$this->set_response(
				array('status' => 'success', 'sections' => $sections),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/repository_sections
	 */
	public function index_post()
	{
		$this->require_access('collection_type', 'edit');

		try {
			$data = $this->_validate_input($this->raw_json_input());
			if ($this->Repository_sections_model->insert($data) !== TRUE) {
				throw new Exception('CREATE_FAILED');
			}
			$id = (int) $this->db->insert_id();
			$this->set_response(
				array('status' => 'success', 'section' => $this->_load($id)),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/repository_sections/{id}
	 */
	public function item_get($id = null)
	{
		$this->require_access('collection_type', 'view');

		try {
			$this->set_response(
				array('status' => 'success', 'section' => $this->_load($id)),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/repository_sections/{id}
	 */
	public function item_post($id = null)
	{
		$this->require_access('collection_type', 'edit');

		try {
			$section = $this->_load($id);
			$data = $this->_validate_input($this->raw_json_input());
			if ($this->Repository_sections_model->update($section['id'], $data) !== TRUE) {
				throw new Exception('UPDATE_FAILED');
			}
			$this->set_response(
				array('status' => 'success', 'section' => $this->_load($section['id'])),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/repository_sections/{id}/delete
	 */
	public function item_delete_post($id = null)
	{
		$this->require_access('collection_type', 'delete');

		try {
			$section = $this->_load($id);
			$this->Repository_sections_model->delete($section['id']);
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
	 * POST /api/admin/repository_sections/batch_delete
	 *
	 * JSON: { ids: [1,2,3] }
	 */
	public function batch_delete_post()
	{
		$this->require_access('collection_type', 'delete');

		try {
			$input = $this->raw_json_input();
			if (! is_array($input) || ! isset($input['ids']) || ! is_array($input['ids'])) {
				throw new Exception('INVALID_IDS');
			}

			$ids = array();
			foreach ($input['ids'] as $id) {
				if (is_numeric($id) && (int) $id > 0) {
					$ids[] = (int) $id;
				}
			}
			$ids = array_values(array_unique($ids));
			if (count($ids) === 0) {
				throw new Exception('INVALID_IDS');
			}

			foreach ($ids as $id) {
				$this->Repository_sections_model->delete($id);
			}

			$this->set_response(
				array('status' => 'success', 'message' => 'DELETED', 'deleted' => count($ids)),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * @param mixed $id
	 * @return array
	 */
	private function _load($id)
	{
		if (! is_numeric($id) || (int) $id <= 0) {
			throw new Exception('NOT_FOUND');
		}
		$row = $this->Repository_sections_model->select_single((int) $id);
		if (! $row) {
			throw new Exception('NOT_FOUND');
		}
		return $this->_format($row);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function _format($row)
	{
		return array(
			'id'     => (int) $row['id'],
			'title'  => (string) $row['title'],
			'weight' => isset($row['weight']) ? (int) $row['weight'] : 0,
		);
	}

	/**
	 * @param mixed $input
	 * @return array
	 */
	private function _validate_input($input)
	{
		if (! is_array($input)) {
			throw new Exception('INVALID_JSON_INPUT');
		}

		$title = isset($input['title']) && is_scalar($input['title'])
			? trim((string) $input['title'])
			: '';
		if ($title === '' || mb_strlen($title) > self::MAX_TITLE) {
			throw new Exception('INVALID_TITLE');
		}

		$weight = isset($input['weight']) ? $input['weight'] : 0;
		if ($weight === '' || $weight === null) {
			$weight = 0;
		}
		if (! is_numeric($weight) || (int) $weight != $weight || (int) $weight < 0 || (int) $weight > self::MAX_WEIGHT) {
			throw new Exception('INVALID_WEIGHT');
		}

		return array(
			'title'  => $title,
			'weight' => (int) $weight,
		);
	}

	private function _error(Exception $e)
	{
		$message = $e->getMessage();
		$code = ($message === 'NOT_FOUND')
			? REST_Controller::HTTP_NOT_FOUND
			: REST_Controller::HTTP_BAD_REQUEST;
		$this->set_response(array('status' => 'error', 'message' => $message), $code);
	}
}
