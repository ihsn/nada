<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin countries API.
 *
 * Base URL: /api/admin/countries
 * Auth: session or API key; ACL countries/edit (same gate as admin/Countries).
 *
 *   GET  /                 countries with aliases + study counts
 *   POST /                 create   { name, iso, aliases: [] }
 *   GET  {id}
 *   POST {id}              update   { name, iso, aliases: [] }
 *   POST {id}/delete
 *   GET  mappings          study country names without a country (cid = 0)
 *   POST mappings          { name, cid } add alias + link those studies
 */
class Countries extends MY_REST_Controller {

	const MAX_NAME = 65;
	const MAX_ALIAS = 100;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Country_model');
		$this->is_authenticated_or_die();
		$this->require_access('countries', 'edit');
	}

	/**
	 * GET /api/admin/countries
	 */
	public function index_get()
	{
		try {
			$aliases = $this->Country_model->get_country_aliases();
			$counts = $this->Country_model->study_counts();
			$out = array();
			foreach ($this->Country_model->select_all() as $row) {
				$id = (int) $row['countryid'];
				$out[] = $this->_format_country(
					$row,
					isset($aliases[$id]) ? $aliases[$id] : array(),
					isset($counts[$id]) ? $counts[$id] : 0
				);
			}
			$this->set_response(array('status' => 'success', 'countries' => $out), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/countries
	 */
	public function index_post()
	{
		try {
			$data = $this->_validate_input($this->raw_json_input(), null);
			if (! $this->Country_model->insert($data)) {
				throw new Exception('CREATE_FAILED');
			}
			$id = $this->Country_model->find_country_by_iso($data['iso']);
			$this->set_response(
				array('status' => 'success', 'country' => $this->_load_country($id)),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/countries/{id}
	 */
	public function item_get($id = null)
	{
		try {
			$this->set_response(
				array('status' => 'success', 'country' => $this->_load_country($id)),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/countries/{id}
	 */
	public function item_post($id = null)
	{
		try {
			$country = $this->_load_country($id);
			$data = $this->_validate_input($this->raw_json_input(), $country['id']);
			if ($this->Country_model->update($country['id'], $data) !== TRUE) {
				throw new Exception('UPDATE_FAILED');
			}
			$this->set_response(
				array('status' => 'success', 'country' => $this->_load_country($country['id'])),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/countries/{id}/delete
	 */
	public function item_delete_post($id = null)
	{
		try {
			$country = $this->_load_country($id);
			$this->Country_model->delete($country['id']);
			$this->set_response(array('status' => 'success', 'message' => 'DELETED'), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/countries/mappings
	 */
	public function mappings_get()
	{
		try {
			$rows = array();
			foreach ($this->Country_model->get_broken_study_countries() as $row) {
				if (trim((string) $row['country_name']) === '') {
					continue;
				}
				$rows[] = array(
					'country_name' => (string) $row['country_name'],
					'total'        => (int) $row['total'],
				);
			}
			$this->set_response(array('status' => 'success', 'mappings' => $rows), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/countries/mappings
	 *
	 * JSON: name (study country name), cid (country id)
	 */
	public function mappings_post()
	{
		try {
			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}
			$name = isset($input['name']) && is_scalar($input['name']) ? trim((string) $input['name']) : '';
			if ($name === '' || mb_strlen($name) > self::MAX_ALIAS) {
				throw new Exception('INVALID_NAME');
			}
			$country = $this->_load_country(isset($input['cid']) ? $input['cid'] : null);

			$owner = $this->Country_model->find_alias_owner($name);
			if ($owner !== false && $owner !== $country['id']) {
				throw new Exception('ALIAS_IN_USE: ' . $name);
			}

			$this->Country_model->add_alias($country['id'], $name);
			$this->Country_model->update_survey_country_code($name, $country['id']);

			$this->set_response(
				array('status' => 'success', 'country' => $this->_load_country($country['id'])),
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
	private function _load_country($id)
	{
		if (! is_numeric($id) || (int) $id <= 0) {
			throw new Exception('NOT_FOUND');
		}
		$row = $this->Country_model->select_single((int) $id);
		if (! $row || empty($row['countryid'])) {
			throw new Exception('NOT_FOUND');
		}
		$counts = $this->Country_model->study_counts();
		$id = (int) $row['countryid'];
		return $this->_format_country(
			$row,
			isset($row['aliases']) ? $row['aliases'] : array(),
			isset($counts[$id]) ? $counts[$id] : 0
		);
	}

	/**
	 * @return array
	 */
	private function _format_country($row, $aliases, $studies)
	{
		return array(
			'id'      => (int) $row['countryid'],
			'name'    => (string) $row['name'],
			'iso'     => (string) $row['iso'],
			'aliases' => array_values(array_map('strval', (array) $aliases)),
			'studies' => (int) $studies,
		);
	}

	/**
	 * @param mixed    $input
	 * @param int|null $country_id existing country when updating
	 * @return array model options: name, iso, alias[]
	 */
	private function _validate_input($input, $country_id)
	{
		if (! is_array($input)) {
			throw new Exception('INVALID_JSON_INPUT');
		}

		$name = isset($input['name']) && is_scalar($input['name']) ? trim((string) $input['name']) : '';
		if ($name === '') {
			throw new Exception('NAME_REQUIRED');
		}
		if (mb_strlen($name) > self::MAX_NAME) {
			throw new Exception('NAME_TOO_LONG');
		}

		$iso = isset($input['iso']) && is_scalar($input['iso']) ? strtoupper(trim((string) $input['iso'])) : '';
		if (! preg_match('/^[A-Z0-9]{2,3}$/', $iso)) {
			throw new Exception('INVALID_ISO');
		}
		$iso_owner = $this->Country_model->find_country_by_iso($iso);
		if ($iso_owner !== false && (int) $iso_owner !== (int) $country_id) {
			throw new Exception('ISO_IN_USE');
		}

		$aliases = array();
		$raw = isset($input['aliases']) ? $input['aliases'] : array();
		if (! is_array($raw)) {
			throw new Exception('INVALID_ALIASES');
		}
		foreach ($raw as $alias) {
			if (! is_scalar($alias)) {
				throw new Exception('INVALID_ALIASES');
			}
			$alias = trim((string) $alias);
			if ($alias === '') {
				continue;
			}
			if (mb_strlen($alias) > self::MAX_ALIAS) {
				throw new Exception('ALIAS_TOO_LONG: ' . $alias);
			}
			$owner = $this->Country_model->find_alias_owner($alias);
			if ($owner !== false && $owner !== (int) $country_id) {
				throw new Exception('ALIAS_IN_USE: ' . $alias);
			}
			$aliases[$alias] = $alias;
		}

		return array(
			'name'  => $name,
			'iso'   => $iso,
			'alias' => array_values($aliases),
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
