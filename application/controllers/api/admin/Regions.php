<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin regions API.
 *
 * Base URL: /api/admin/regions
 * Auth: session or API key; ACL regions/edit (same gate as admin/Regions).
 *
 * Regions are two levels: top-level groups (pid = 0) and child regions that hold countries.
 *
 *   GET  /                 flat list ordered by pid, weight, title (+ country counts)
 *   POST /                 create   { pid, title, weight, countries: [country ids] }
 *   GET  countries         country picker list
 *   GET  {id}
 *   POST {id}              update   { pid, title, weight, countries: [] }
 *   POST {id}/delete       also deletes child regions
 */
class Regions extends MY_REST_Controller {

	const MAX_TITLE = 45;
	const MAX_WEIGHT = 999;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Country_region_model');
		$this->is_authenticated_or_die();
		$this->require_access('regions', 'edit');
	}

	/**
	 * GET /api/admin/regions
	 */
	public function index_get()
	{
		try {
			$counts = $this->Country_region_model->country_counts();
			$out = array();
			foreach ($this->Country_region_model->select_all() as $row) {
				$region = $this->_format_region($row);
				$region['countries_count'] = isset($counts[$region['id']]) ? $counts[$region['id']] : 0;
				$out[] = $region;
			}
			$this->set_response(array('status' => 'success', 'regions' => $out), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/regions
	 */
	public function index_post()
	{
		try {
			$data = $this->_validate_input($this->raw_json_input(), null);
			$countries = $data['country'];
			unset($data['country']);
			if (! $this->Country_region_model->insert($data)) {
				throw new Exception('CREATE_FAILED');
			}
			$id = (int) $this->db->insert_id();
			if (! empty($countries)) {
				$data['country'] = $countries;
				$this->Country_region_model->update($id, $data);
			}
			$this->set_response(
				array('status' => 'success', 'region' => $this->_load_region($id)),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/regions/countries
	 */
	public function countries_get()
	{
		try {
			$out = array();
			foreach ($this->Country_region_model->get_countries_compact() as $row) {
				$out[] = array('id' => (int) $row['countryid'], 'name' => (string) $row['name']);
			}
			$this->set_response(array('status' => 'success', 'countries' => $out), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/regions/{id}
	 */
	public function item_get($id = null)
	{
		try {
			$this->set_response(
				array('status' => 'success', 'region' => $this->_load_region($id)),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/regions/{id}
	 */
	public function item_post($id = null)
	{
		try {
			$region = $this->_load_region($id);
			$data = $this->_validate_input($this->raw_json_input(), $region);
			if ($this->Country_region_model->update($region['id'], $data) !== TRUE) {
				throw new Exception('UPDATE_FAILED');
			}
			$this->set_response(
				array('status' => 'success', 'region' => $this->_load_region($region['id'])),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/regions/{id}/delete
	 */
	public function item_delete_post($id = null)
	{
		try {
			$region = $this->_load_region($id);
			$this->Country_region_model->delete($region['id']);
			$this->set_response(array('status' => 'success', 'message' => 'DELETED'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * @param mixed $id
	 * @return array
	 */
	private function _load_region($id)
	{
		if (! is_numeric($id) || (int) $id <= 0) {
			throw new Exception('NOT_FOUND');
		}
		$row = $this->Country_region_model->select_single((int) $id);
		if (! $row || empty($row['id'])) {
			throw new Exception('NOT_FOUND');
		}
		$region = $this->_format_region($row);
		$region['countries'] = array_values(array_map('intval', $row['countries']));
		$region['children'] = count($this->Country_region_model->get_child_regions($region['id']));
		return $region;
	}

	/**
	 * @return array
	 */
	private function _format_region($row)
	{
		return array(
			'id'     => (int) $row['id'],
			'pid'    => (int) $row['pid'],
			'title'  => (string) $row['title'],
			'weight' => (int) $row['weight'],
		);
	}

	/**
	 * @param mixed      $input
	 * @param array|null $region existing region when updating
	 * @return array model options: pid, title, weight, country[]
	 */
	private function _validate_input($input, $region)
	{
		if (! is_array($input)) {
			throw new Exception('INVALID_JSON_INPUT');
		}

		$title = isset($input['title']) && is_scalar($input['title']) ? trim((string) $input['title']) : '';
		if ($title === '') {
			throw new Exception('TITLE_REQUIRED');
		}
		if (mb_strlen($title) > self::MAX_TITLE) {
			throw new Exception('TITLE_TOO_LONG');
		}

		$weight = isset($input['weight']) ? $input['weight'] : 0;
		if ($weight === '' || $weight === null) {
			$weight = 0;
		}
		if (! is_numeric($weight) || (int) $weight != $weight || (int) $weight < 0 || (int) $weight > self::MAX_WEIGHT) {
			throw new Exception('INVALID_WEIGHT');
		}

		$pid = isset($input['pid']) ? $input['pid'] : 0;
		if (! is_numeric($pid) || (int) $pid < 0) {
			throw new Exception('INVALID_PARENT');
		}
		$pid = (int) $pid;
		if ($pid > 0) {
			$parent_ids = array_map('intval', array_column($this->Country_region_model->get_parents(), 'id'));
			if (! in_array($pid, $parent_ids, true) || ($region && $pid === $region['id'])) {
				throw new Exception('INVALID_PARENT');
			}
			if ($region && $region['children'] > 0) {
				throw new Exception('REGION_HAS_CHILDREN');
			}
		}

		$countries = array();
		if ($pid > 0) {
			$raw = isset($input['countries']) ? $input['countries'] : array();
			if (! is_array($raw)) {
				throw new Exception('INVALID_COUNTRIES');
			}
			$valid = array_map('intval', array_column($this->Country_region_model->get_countries_compact(), 'countryid'));
			foreach ($raw as $cid) {
				if (! is_numeric($cid) || ! in_array((int) $cid, $valid, true)) {
					throw new Exception('INVALID_COUNTRIES');
				}
				$countries[(int) $cid] = (int) $cid;
			}
		}

		return array(
			'pid'     => $pid,
			'title'   => $title,
			'weight'  => (int) $weight,
			'country' => array_values($countries),
		);
	}

	private function _error(Exception $e)
	{
		$code = $e->getMessage() === 'NOT_FOUND'
			? REST_Controller::HTTP_NOT_FOUND
			: REST_Controller::HTTP_BAD_REQUEST;
		$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
	}
}
