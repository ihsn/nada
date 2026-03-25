<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Analytics extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Analytics_model');
		
	}
	
	function _auth_override_check()
	{
		//session user id
		if ($this->session->userdata('user_id')){
			return true;
		}

		return parent::_auth_override_check();
	}

	/**
	 * Get raw pageview events with pagination and filtering
	 * 
	 * GET /api/analytics/raw/pageviews
	 * 
	 * Query params:
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study ID
	 *   - sort_by: field to sort by (default: ts)
	 *   - sort_order: asc or desc (default: desc)
	 */
	function raw_pageviews_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $this->input->get('study_id')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			$sort_by = $this->input->get('sort_by') ?: 'ts';
			$sort_order = $this->input->get('sort_order') ?: 'desc';
			
			$result = $this->Analytics_model->get_raw_pageviews($filters, $limit, $offset, $sort_by, $sort_order);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get raw download events with pagination and filtering
	 * 
	 * GET /api/analytics/raw/downloads
	 * 
	 * Query params:
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study ID
	 *   - file_type: filter by file type
	 *   - sort_by: field to sort by (default: ts)
	 *   - sort_order: asc or desc (default: desc)
	 */
	function raw_downloads_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $this->input->get('study_id'),
				'file_type' => $this->input->get('file_type')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			$sort_by = $this->input->get('sort_by') ?: 'ts';
			$sort_order = $this->input->get('sort_order') ?: 'desc';
			
			$result = $this->Analytics_model->get_raw_downloads($filters, $limit, $offset, $sort_by, $sort_order);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get daily study aggregates with pagination and filtering
	 *
	 * GET /api/analytics/daily/studies
	 *
	 * Query params:
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study ID
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function daily_studies_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $this->input->get('study_id')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			
			$result = $this->Analytics_model->get_daily_studies($filters, $limit, $offset);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get daily file aggregates with pagination and filtering
	 *
	 * GET /api/analytics/daily/files
	 *
	 * Query params:
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study ID
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function daily_files_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $this->input->get('study_id')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			
			$result = $this->Analytics_model->get_daily_files($filters, $limit, $offset);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get monthly study aggregates
	 * 
	 * GET /api/analytics/monthly/studies
	 * 
	 * Query params:
	 *   - year: filter by year
	 *   - month: filter by month (1-12)
	 *   - study_id: filter by study ID
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function monthly_studies_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'year' => $this->input->get('year'),
				'month' => $this->input->get('month'),
				'study_id' => $this->input->get('study_id')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			
			$result = $this->Analytics_model->get_monthly_studies($filters, $limit, $offset);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get aggregated monthly totals across all studies
	 * 
	 * GET /api/analytics/monthly/totals
	 * 
	 * Query params:
	 *   - months: number of months to retrieve (default: 12, max: 24)
	 * 
	 * Returns array of monthly totals (pageviews, unique_visitors, downloads)
	 */
	function monthly_totals_get()
	{
		try {
			$this->is_admin_or_die();
			
			$months = (int)$this->input->get('months') ?: 12;
			$months = max(1, min(24, $months)); // clamp 1-24
			
			$result = $this->Analytics_model->get_monthly_totals($months);
			
			$response = array(
				'status' => 'success',
				'data' => $result
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get monthly file aggregates
	 * 
	 * GET /api/analytics/monthly/files
	 * 
	 * Query params:
	 *   - year: filter by year
	 *   - month: filter by month (1-12)
	 *   - study_id: filter by study ID
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function monthly_files_get()
	{
		try {
			$this->is_admin_or_die();
			
			$filters = array(
				'year' => $this->input->get('year'),
				'month' => $this->input->get('month'),
				'study_id' => $this->input->get('study_id')
			);
			
			$limit = (int)$this->input->get('limit') ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;
			
			$result = $this->Analytics_model->get_monthly_files($filters, $limit, $offset);
			
			$response = array(
				'status' => 'success',
				'data' => $result['data'],
				'total' => $result['total'],
				'limit' => $result['limit'],
				'offset' => $result['offset'],
				'has_more' => $result['has_more']
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Export daily study aggregates
	 * 
	 * GET /api/analytics/daily/studies/export?format=csv|json&date_from=&date_to=&study_id=
	 */
	function daily_studies_export_get()
	{
		try {
			$this->is_admin_or_die();

			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to'   => $this->input->get('date_to'),
				'study_id'  => $this->input->get('study_id')
			);

			$format = strtolower($this->input->get('format') ?: 'csv');
			$result = $this->Analytics_model->get_daily_studies($filters, 0, 0);
			$rows   = $result['data'];

			if ($format === 'json') {
				header('Content-Type: application/json');
				header('Content-Disposition: attachment; filename="daily_studies_export.json"');
				echo json_encode($rows);
			} else {
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="daily_studies_export.csv"');
				$out = fopen('php://output', 'w');
				fputcsv($out, array('date', 'study_id', 'title', 'nation', 'study_year', 'pageviews', 'unique_visitors', 'downloads'));
				foreach ($rows as $row) {
					fputcsv($out, array(
						isset($row['date'])            ? $row['date']            : '',
						isset($row['study_id'])        ? $row['study_id']        : '',
						isset($row['title'])           ? $row['title']           : '',
						isset($row['nation'])          ? $row['nation']          : '',
						isset($row['study_year'])      ? $row['study_year']      : '',
						isset($row['pageviews'])       ? $row['pageviews']       : 0,
						isset($row['unique_visitors']) ? $row['unique_visitors'] : 0,
						isset($row['downloads'])       ? $row['downloads']       : 0
					));
				}
				fclose($out);
			}
			exit;

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Export daily file aggregates
	 * 
	 * 
	 * GET /api/analytics/daily/files/export?format=csv|json&date_from=&date_to=&study_id=
	 */
	function daily_files_export_get()
	{
		try {
			$this->is_admin_or_die();

			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to'   => $this->input->get('date_to'),
				'study_id'  => $this->input->get('study_id')
			);

			$format = strtolower($this->input->get('format') ?: 'csv');
			$result = $this->Analytics_model->get_daily_files($filters, 0, 0);
			$rows   = $result['data'];

			if ($format === 'json') {
				header('Content-Type: application/json');
				header('Content-Disposition: attachment; filename="daily_files_export.json"');
				echo json_encode($rows);
			} else {
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="daily_files_export.csv"');
				$out = fopen('php://output', 'w');
				fputcsv($out, array('date', 'study_id', 'title', 'file_name', 'downloads'));
				foreach ($rows as $row) {
					fputcsv($out, array(
						isset($row['date'])      ? $row['date']      : '',
						isset($row['study_id'])  ? $row['study_id']  : '',
						isset($row['title'])     ? $row['title']     : '',
						isset($row['file_name']) ? $row['file_name'] : '',
						isset($row['downloads']) ? $row['downloads'] : 0
					));
				}
				fclose($out);
			}
			exit;

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Export monthly study aggregates
	 * 
	 * GET /api/analytics/monthly/studies/export?format=csv|json&year=&month=&study_id=
	 */
	function monthly_studies_export_get()
	{
		try {
			$this->is_admin_or_die();

			$filters = array(
				'year'     => $this->input->get('year'),
				'month'    => $this->input->get('month'),
				'study_id' => $this->input->get('study_id')
			);

			$format = strtolower($this->input->get('format') ?: 'csv');
			$result = $this->Analytics_model->get_monthly_studies($filters, 0, 0);
			$rows   = $result['data'];

			if ($format === 'json') {
				header('Content-Type: application/json');
				header('Content-Disposition: attachment; filename="monthly_studies_export.json"');
				echo json_encode($rows);
			} else {
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="monthly_studies_export.csv"');
				$out = fopen('php://output', 'w');
				fputcsv($out, array('year', 'month', 'study_id', 'title', 'nation', 'study_year', 'pageviews', 'unique_visitors', 'downloads', 'finalized'));
				foreach ($rows as $row) {
					fputcsv($out, array(
						isset($row['year'])            ? $row['year']            : '',
						isset($row['month'])           ? $row['month']           : '',
						isset($row['study_id'])        ? $row['study_id']        : '',
						isset($row['title'])           ? $row['title']           : '',
						isset($row['nation'])          ? $row['nation']          : '',
						isset($row['study_year'])      ? $row['study_year']      : '',
						isset($row['pageviews'])       ? $row['pageviews']       : 0,
						isset($row['unique_visitors']) ? $row['unique_visitors'] : 0,
						isset($row['downloads'])       ? $row['downloads']       : 0,
						isset($row['finalized'])       ? $row['finalized']       : 0
					));
				}
				fclose($out);
			}
			exit;

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Export monthly file aggregates
	 * 
	 * GET /api/analytics/monthly/files/export?format=csv|json&year=&month=&study_id=
	 * 
	 */
	function monthly_files_export_get()
	{
		try {
			$this->is_admin_or_die();

			$filters = array(
				'year'     => $this->input->get('year'),
				'month'    => $this->input->get('month'),
				'study_id' => $this->input->get('study_id')
			);

			$format = strtolower($this->input->get('format') ?: 'csv');
			$result = $this->Analytics_model->get_monthly_files($filters, 0, 0);
			$rows   = $result['data'];

			if ($format === 'json') {
				header('Content-Type: application/json');
				header('Content-Disposition: attachment; filename="monthly_files_export.json"');
				echo json_encode($rows);
			} else {
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="monthly_files_export.csv"');
				$out = fopen('php://output', 'w');
				fputcsv($out, array('year', 'month', 'study_id', 'title', 'file_name', 'downloads', 'finalized'));
				foreach ($rows as $row) {
					fputcsv($out, array(
						isset($row['year'])      ? $row['year']      : '',
						isset($row['month'])     ? $row['month']     : '',
						isset($row['study_id'])  ? $row['study_id']  : '',
						isset($row['title'])     ? $row['title']     : '',
						isset($row['file_name']) ? $row['file_name'] : '',
						isset($row['downloads']) ? $row['downloads'] : 0,
						isset($row['finalized']) ? $row['finalized'] : 0
					));
				}
				fclose($out);
			}
			exit;

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Run daily aggregation
	 * 
	 * POST /api/analytics/aggregate/daily
	 * 
	 * Body params:
	 *   - date: specific date to process (Y-m-d), or null for auto-detect
	 *   - offset: starting offset for chunked processing (default: 0)
	 */
	function aggregate_daily_post()
	{
		try {
			$this->is_admin_or_die();
			$date = $this->input->post('date');
			$offset = (int)$this->input->post('offset') ?: 0;
			$pv_offset = $this->input->post('pv_offset') !== null ? (int)$this->input->post('pv_offset') : $offset;
			$dl_offset = $this->input->post('dl_offset') !== null ? (int)$this->input->post('dl_offset') : $offset;
			
			// If no date provided, find missing dates
			if ($date === null || $date === '') {
				$missing_dates = $this->Analytics_model->find_missing_daily_aggregates();
				
				if (empty($missing_dates)) {
					$response = array(
						'status' => 'success',
						'message' => 'No missing daily aggregates found',
						'has_more' => false,
						'processed' => 0,
						'total' => 0
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
				
				// Process first missing date
				$date = $missing_dates[0];
			}
			
			// Validate date format
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
				throw new Exception('Invalid date format. Use YYYY-MM-DD');
			}
			
			// Aggregate pageviews (chunked)
			$result_pv = $this->Analytics_model->aggregate_pageviews_daily_chunked($date, $pv_offset);
			
			// Aggregate downloads (chunked)
			$result_dl = $this->Analytics_model->aggregate_downloads_daily_chunked($date, $dl_offset);
			
			// Check if there are more missing dates
			$remaining_dates = $this->Analytics_model->find_missing_daily_aggregates();
			$has_more_batches = !empty($result_pv['has_more']) || !empty($result_dl['has_more']);
			$has_more_dates = !empty($remaining_dates);
			$has_more = $has_more_batches || $has_more_dates;
			
			$response = array(
				'status' => 'success',
				'message' => "Daily aggregation completed for {$date}",
				'date' => $date,
				'pageviews' => array(
					'success' => $result_pv['success'],
					'affected_rows' => $result_pv['affected_rows'] ?? ($result_pv['processed'] ?? 0),
					'processed' => $result_pv['processed'] ?? 0,
					'total_studies' => $result_pv['total_studies'] ?? null,
					'offset' => $result_pv['offset'] ?? $pv_offset,
					'has_more' => $result_pv['has_more'] ?? false
				),
				'downloads' => array(
					'success' => $result_dl['success'],
					'affected_rows' => $result_dl['affected_rows'] ?? ($result_dl['processed'] ?? 0),
					'processed' => $result_dl['processed'] ?? 0,
					'total_files' => $result_dl['total_files'] ?? null,
					'offset' => $result_dl['offset'] ?? $dl_offset,
					'has_more' => $result_dl['has_more'] ?? false
				),
				'has_more' => $has_more,
				'has_more_batches' => $has_more_batches,
				'remaining_dates' => count($remaining_dates)
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Run monthly aggregation
	 * 
	 * POST /api/analytics/aggregate/monthly
	 * 
	 * Body params:
	 *   - year: year to process (default: current year)
	 *   - month: month to process (default: current month)
	 *   - offset: starting offset for chunked processing (default: 0)
	 */
	function aggregate_monthly_post()
	{
		try {
			$this->is_admin_or_die();
			$year = $this->input->post('year');
			$month = $this->input->post('month');
			$offset = (int)$this->input->post('offset') ?: 0;
			
			// Chunked monthly aggregation for large datasets
			$result = $this->Analytics_model->aggregate_daily_to_monthly_chunked($year, $month, $offset);
			
			$response = array(
				'status' => 'success',
				'message' => 'Monthly aggregation batch completed',
				'year' => $result['year'],
				'month' => $result['month'],
				'processed' => $result['processed'] ?? 0,
				'total_studies' => $result['total_studies'] ?? 0,
				'offset' => $result['offset'] ?? $offset,
				'has_more' => $result['has_more'] ?? false,
				'execution_time' => $result['execution_time'] ?? 0
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Update all-time totals
	 * 
	 * POST /api/analytics/aggregate/totals
	 * 
	 * Body params:
	 *   - study_id: optional study ID to update (updates all if omitted)
	 */
	function aggregate_totals_post()
	{
		try {
			$this->is_admin_or_die();
			$study_id = $this->input->post('study_id');
			$offset = (int)$this->input->post('offset') ?: 0;
			
			if ($study_id) {
				$result = $this->Analytics_model->update_legacy_totals($study_id);
				$success = (bool)$result;
			} else {
				$result = $this->Analytics_model->update_legacy_totals_chunked($offset);
				$success = !empty($result['success']);
			}
			
			$response = array(
				'status' => 'success',
				'message' => $study_id ? "Totals updated for study {$study_id}" : 'All-time totals updated successfully',
				'success' => $success,
				'processed' => $result['processed'] ?? null,
				'total_studies' => $result['total_studies'] ?? null,
				'offset' => $result['offset'] ?? $offset,
				'has_more' => $result['has_more'] ?? false
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Run aggregation step
	 * 
	 * POST /api/analytics/aggregate/run
	 * 
	 * Processes one step/item at a time and returns
	 * state information for frontend polling.
	 * 
	 * Response includes:
	 *   - current_step: Current step being processed
	 *   - current_item: Current item (date, month, etc.)
	 *   - has_more: Whether more processing is needed
	 *   - progress: Progress percentage (0-100)
	 *   - message: Human-readable status message
	 */
	function aggregate_run_post()
	{
		try {
			$this->is_admin_or_die();
			$user_id = $this->session->userdata('user_id');
			
			// Process one step
			$result = $this->Analytics_model->process_aggregation_step('web', $user_id);
			
			$response = array(
				'status' => isset($result['error']) && $result['error'] ? 'error' : 'success',
				'current_step' => $result['current_step'] ?? $result['step'] ?? null,
				'current_item' => $result['current_item'] ?? $result['item'] ?? null,
				'has_more' => $result['has_more'] ?? false,
				'progress' => $result['progress'] ?? 0,
				'message' => $result['message'] ?? 'Processing...'
			);
			
			if (isset($result['error']) && $result['error']) {
				$response['error'] = true;
				$this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->set_response($response, REST_Controller::HTTP_OK);
			}
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage(),
				'error' => true
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get aggregation status
	 * 
	 * GET /api/analytics/aggregate/status
	 * 
	 * Returns current aggregation status for polling
	 */
	function aggregate_status_get()
	{
		try {
			$this->is_admin_or_die();
			$status = $this->Analytics_model->get_aggregation_status();
			$last_completed = $this->Analytics_model->get_last_completed_aggregation();
			
			$response = array(
				'status' => 'success',
				'data' => array(
					'status' => $status['status'],
					'current_step' => $status['current_step'],
					'current_item' => $status['current_item'],
					'total_items' => $status['total_items'],
					'processed_items' => $status['processed_items'],
					'progress_percent' => $status['progress_percent'],
					'message' => $status['message'],
					'started_at' => $status['started_at'],
					'completed_at' => $status['completed_at'],
					'last_updated_at' => $status['last_updated_at'],
					'error_message' => $status['error_message'],
					'context' => $status['context'],
					'last_completed_at' => $last_completed ? $last_completed['completed_at'] : null,
					'last_completed_started_at' => $last_completed ? $last_completed['started_at'] : null
				)
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Stop aggregation
	 * 
	 * POST /api/analytics/aggregate/stop
	 * 
	 * Stops the current aggregation run
	 */
	function aggregate_stop_post()
	{
		try {
			$this->is_admin_or_die();
			$result = $this->Analytics_model->stop_aggregation_status();
			
			$response = array(
				'status' => $result ? 'success' : 'error',
				'message' => $result ? 'Aggregation stopped' : 'Failed to stop aggregation'
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
			
		} catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * @deprecated Use aggregate_run_post() instead
	 * Kept for backward compatibility
	 */
	function aggregate_run_all_post()
	{
		$this->is_admin_or_die();
		$this->aggregate_run_post();
	}
	
	/**
	 * Track a pageview event (public endpoint)
	 * 
	 * POST /api/analytics/pageview
	 * 
	 * Body params:
	 *   - study_id: Study identifier (required)
	 *   - session_id: Client-generated session token (optional)
	 *   - referrer: Referrer URL (optional)
	 *   - user_agent: User agent string (optional, auto-detected if not provided)
	 * 
	 * Returns:
	 *   - status: 'success' or 'error'
	 *   - message: Status message
	 */
	function pageview_post()
	{
		try {
			$options = $this->raw_json_input();
			$study_id = $options['study_id'];
			
			if (empty($options['study_id'])) {
				$output = array(
					'status' => false,
					'error' => 'study_id is required'
				);
				$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			
			$session_id = $options['session_id'] ?? null;
			
			$result = $this->analytics_tracker->track_pageview($study_id, $session_id, $options);
			
			if ($result) {
				$response = array(
					'status' => true					
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			} else {
				$response = array(
					'status' => $result					
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			}
			
		} catch (Exception $e) {
			$output = array(
				'status' => false,
				'error' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Track a download event (public endpoint)
	 * 
	 * POST /api/analytics/download
	 * 
	 * Body params:
	 *   - study_id: Study identifier (required)
	 *   - file_name: File name (required)
	 *   - file_type: File type (optional)
	 *   - user_agent: User agent string (optional, auto-detected if not provided)
	 * 
	 * Returns:
	 *   - status: 'success' or 'error'
	 *   - message: Status message
	 */
	function download_post()
	{
		try {
			$study_id = $this->input->post('study_id');
			$file_name = $this->input->post('file_name');
			
			if (empty($study_id)) {
				$output = array(
					'status' => false,
					'error' => 'study_id is required'
				);
				$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			
			if (empty($file_name)) {
				$output = array(
					'status' => false,
					'error' => 'file_name is required'
				);
				$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			
			$file_type = $this->input->post('file_type');
			$user_agent = $this->input->post('user_agent');
			
			$data = array();
			if ($file_type) {
				$data['file_type'] = $file_type;
			}
			if ($user_agent) {
				$data['user_agent'] = $user_agent;
			}
			
			$result = $this->analytics_tracker->track_download($study_id, $file_name, $data);
			
			if ($result) {
				$response = array(
					'status' => true,
					'message' => 'Download tracked successfully'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			} else {
				// Tracking was filtered (bot, invalid request, etc.) - return success but indicate it was filtered
				$response = array(
					'status' => true,
					'message' => 'Request filtered (bot or invalid request)',
					'filtered' => true
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			}
			
		} catch (Exception $e) {
			$output = array(
				'status' => false,
				'error' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
}
