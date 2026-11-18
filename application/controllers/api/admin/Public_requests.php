<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin public-use file requests API.
 *
 * Base URL: /api/admin/public_requests
 * Auth: session or API key; ACL reports/view.
 *
 *   GET search          paginated list
 *   GET export          CSV download
 *   GET {id}            single request with study + requester fields
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
	 * GET /api/admin/public_requests/search
	 *
	 * Query: page, ps, keywords, date_from, date_to (Y-m-d), sort_by, sort_order
	 */
	public function search_get()
	{
		try {
			$page_size = $this->_get_page_size(30, 1, 100);
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			$options = $this->_list_options_from_request();
			$total = $this->public_model->admin_search_requests_count($options);
			$rows  = $this->public_model->admin_search_requests($page_size, $offset, $options);

			$formatted = array();
			foreach ($rows as $row) {
				$formatted[] = $this->_format_list_row($row);
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'rows'      => $formatted,
						'total'     => $total,
						'page'      => $page,
						'pages'     => $page_size > 0 ? (int) ceil($total / $page_size) : 1,
						'page_size' => $page_size,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * GET /api/admin/public_requests/export
	 *
	 * Same filters as search; keyword column set only (no custom field expansion here).
	 */
	public function export_get()
	{
		try {
			$options = $this->_list_options_from_request();
			$rows = $this->public_model->admin_search_requests(10000, 0, $options);

			$filename = 'public-requests-' . date('m-d-Y') . '.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			echo "\xEF\xBB\xBF";

			$out = fopen('php://output', 'w');
			fputcsv($out, array(
				'id',
				'posted',
				'study_title',
				'idno',
				'username',
				'email',
				'organization',
				'country',
				'intended_use',
			));

			foreach ($rows as $row) {
				$posted = isset($row['posted']) ? (int) $row['posted'] : 0;
				fputcsv($out, array(
					isset($row['id']) ? (int) $row['id'] : '',
					$posted > 0 ? date('Y-m-d H:i:s', $posted) : '',
					isset($row['survey_title']) ? $row['survey_title'] : (isset($row['request_title']) ? $row['request_title'] : ''),
					isset($row['idno']) ? $row['idno'] : '',
					isset($row['username']) ? $row['username'] : '',
					isset($row['email']) ? $row['email'] : '',
					isset($row['company']) ? $row['company'] : '',
					isset($row['country']) ? $row['country'] : '',
					isset($row['abstract']) ? $row['abstract'] : '',
				));
			}
			fclose($out);
			exit;
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
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
					'request' => $this->_format_detail($row),
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
	 * @return array
	 */
	private function _list_options_from_request()
	{
		$date_from = trim((string) $this->input->get('date_from'));
		$date_to = trim((string) $this->input->get('date_to'));

		$options = array(
			'keywords'   => $this->input->get('keywords', true),
			'sort_by'    => $this->input->get('sort_by', true),
			'sort_order' => $this->input->get('sort_order', true),
			'date_from'  => 0,
			'date_to'    => 0,
		);

		if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
			$options['date_from'] = (int) strtotime($date_from . ' 00:00:00');
		}
		if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
			$options['date_to'] = (int) strtotime($date_to . ' 23:59:59');
		}

		return $options;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function _format_list_row($row)
	{
		$title = '';
		if (!empty($row['request_title'])) {
			$title = (string) $row['request_title'];
		} elseif (!empty($row['survey_title'])) {
			$title = (string) $row['survey_title'];
		}

		return array(
			'id'           => isset($row['id']) ? (int) $row['id'] : 0,
			'posted'       => isset($row['posted']) ? (int) $row['posted'] : null,
			'title'        => $title,
			'survey_title' => isset($row['survey_title']) ? (string) $row['survey_title'] : '',
			'idno'         => isset($row['idno']) ? (string) $row['idno'] : '',
			'surveyid'     => isset($row['surveyid']) ? (int) $row['surveyid'] : null,
			'username'     => isset($row['username']) ? (string) $row['username'] : '',
			'email'        => isset($row['email']) ? (string) $row['email'] : '',
			'company'      => isset($row['company']) ? (string) $row['company'] : '',
			'country'      => isset($row['country']) ? (string) $row['country'] : '',
			'abstract'     => isset($row['abstract']) ? (string) $row['abstract'] : '',
		);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function _format_detail($row)
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

	/**
	 * @param int $default
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	private function _get_page_size($default = 30, $min = 1, $max = 100)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= $min && $ps <= $max) {
			return $ps;
		}
		return $default;
	}
}
