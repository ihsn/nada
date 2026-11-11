<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin public-use file request detail API.
 *
 * Base URL: /api/admin/public_requests
 * Auth: session or API key; ACL reports/view (same gate as admin/Public_requests).
 *
 *   GET {id}   single request with study + requester fields
 */
class Public_requests extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('public_model');
		$this->is_authenticated_or_die();
		$this->require_access('reports', 'view');
	}

	/**
	 * GET /api/admin/public_requests/{id}
	 */
	public function item_get($id = null)
	{
		try {
			if (! is_numeric($id) || (int) $id <= 0) {
				throw new Exception('NOT_FOUND');
			}

			$row = $this->public_model->select_single((int) $id);
			if (! $row) {
				throw new Exception('NOT_FOUND');
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'request' => $this->_format($row),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = $e->getMessage() === 'NOT_FOUND'
				? REST_Controller::HTTP_NOT_FOUND
				: REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function _format($row)
	{
		return array(
			'id'           => (int) $row['id'],
			'posted'       => isset($row['posted']) ? (int) $row['posted'] : null,
			'title'        => isset($row['title']) ? (string) $row['title'] : '',
			'nation'       => isset($row['nation']) ? (string) $row['nation'] : '',
			'year_start'   => isset($row['year_start']) ? $row['year_start'] : null,
			'idno'         => isset($row['idno']) ? (string) $row['idno'] : '',
			'surveyid'     => isset($row['surveyid']) ? (int) $row['surveyid'] : null,
			'username'     => isset($row['username']) ? (string) $row['username'] : '',
			'email'        => isset($row['email']) ? (string) $row['email'] : '',
			'first_name'   => isset($row['first_name']) ? (string) $row['first_name'] : '',
			'last_name'    => isset($row['last_name']) ? (string) $row['last_name'] : '',
			'company'      => isset($row['company']) ? (string) $row['company'] : '',
			'phone'        => isset($row['phone']) ? (string) $row['phone'] : '',
			'country'      => isset($row['country']) ? (string) $row['country'] : '',
			'abstract'     => isset($row['abstract']) ? (string) $row['abstract'] : '',
			'request_type' => isset($row['request_type']) ? (string) $row['request_type'] : '',
		);
	}
}
