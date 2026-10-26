<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Admin Analytics API
 *
 * Base URL: /api/admin/analytics
 * Auth: session or API key; ACL via require_analytics_access() per endpoint.
 *
 * Query param study_id accepts surveys.id (numeric sid) or catalog IDNO unless
 * id_format=id is set (see get_sid_from_idno).
 *
 * Paginated list endpoints return:
 *   { status: success, data, total, limit, offset, has_more }
 * with limit clamped to analytics_api_list_max_rows (default 50). Export endpoints cap rows at
 * analytics_export_max_rows and may set X-Analytics-Export-* headers when truncated.
 *
 * Errors use { status: error, message } (HTTP 400). Access denied uses HTTP 403
 * with the same envelope on this controller.
 *
 * OpenAPI: api-documentation/catalog-admin (Analytics tag).
 */
class Analytics extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Analytics_model');
		$this->is_authenticated_or_die();
	}

	/**
	 * Resolve optional study_id to numeric surveys.id for filters.
	 *
	 * @param mixed $study_id_raw
	 * @return int|null
	 */
	protected function resolve_optional_study_sid_value($study_id_raw)
	{
		if ($study_id_raw === null || $study_id_raw === '') {
			return null;
		}
		if (is_numeric($study_id_raw)) {
			return (int) $study_id_raw;
		}
		return $this->get_sid_from_idno($study_id_raw);
	}

	/**
	 * ACL check for optional study_id query param; returns resolved sid.
	 *
	 * @param mixed $study_id_raw defaults to study_id query param
	 * @return int|null
	 */
	protected function require_analytics_access_for_study_param($study_id_raw = null)
	{
		if ($study_id_raw === null) {
			$study_id_raw = $this->input->get('study_id');
		}
		$sid = $this->resolve_optional_study_sid_value($study_id_raw);
		$this->require_analytics_access($sid);
		return $sid;
	}

	/**
	 * @param int $total       matching row count before export cap
	 * @param int $row_count   rows in export payload
	 */
	protected function send_export_truncation_headers($total, $row_count)
	{
		$max = $this->Analytics_model->get_export_max_rows();
		if ((int) $total > (int) $row_count || (int) $total > $max) {
			header('X-Analytics-Export-Truncated: 1');
			header('X-Analytics-Export-Total: ' . (int) $total);
			header('X-Analytics-Export-Row-Count: ' . (int) $row_count);
			header('X-Analytics-Export-Max-Rows: ' . (int) $max);
		}
	}

	/**
	 * Analytics API uses status "error" for ACL denial (not "failed").
	 */
	protected function access_denied_response()
	{
		$this->response(
			array(
				'status'  => 'error',
				'message' => 'ACCESS-DENIED',
			),
			REST_Controller::HTTP_FORBIDDEN,
			false
		);
		exit;
	}

	/**
	 * @return string csv|json
	 */
	protected function parse_export_format()
	{
		$format = strtolower($this->input->get('format') ?: 'csv');
		if ($format !== 'csv' && $format !== 'json') {
			throw new Exception('Invalid format. Use csv or json');
		}
		return $format;
	}

	/**
	 * Stream CSV or JSON export for aggregate list endpoints.
	 *
	 * @param string   $basename   File basename without extension
	 * @param string   $format     csv or json
	 * @param array    $result     Model result with data and total
	 * @param array    $csv_headers
	 * @param callable $map_csv_row function(array $row): array
	 */
	protected function deliver_aggregate_export($basename, $format, $result, array $csv_headers, callable $map_csv_row)
	{
		$rows = $result['data'];
		$this->send_export_truncation_headers($result['total'], count($rows));

		if ($format === 'json') {
			$this->deliver_export_attachment('application/json', $basename . '.json', function () use ($rows) {
				echo json_encode($rows);
			});
			return;
		}

		$this->deliver_export_attachment('text/csv', $basename . '.csv', function () use ($csv_headers, $rows, $map_csv_row) {
			$out = fopen('php://output', 'w');
			fputcsv($out, $csv_headers);
			foreach ($rows as $row) {
				fputcsv($out, $map_csv_row($row));
			}
			fclose($out);
		});
	}

	/**
	 * @param string   $content_type
	 * @param string   $filename
	 * @param callable $writer
	 */
	protected function deliver_export_attachment($content_type, $filename, callable $writer)
	{
		header('Content-Type: ' . $content_type);
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		$writer();
		exit;
	}

	protected function respond_export_exception(Exception $e)
	{
		if (!headers_sent()) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * Get raw pageview events with pagination and filtering
	 * 
	 * GET /api/admin/analytics/raw/pageviews
	 * 
	 * Query params:
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study (numeric sid or IDNO)
	 *   - sort_by: field to sort by (default: ts)
	 *   - sort_order: asc or desc (default: desc)
	 */
	function raw_pageviews_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $study_sid
			);
			$this->Analytics_model->validate_admin_raw_date_range($filters['date_from'], $filters['date_to']);
			
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
	 * GET /api/admin/analytics/raw/downloads
	 * 
	 * Query params:
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 *   - date_from: filter from date (Y-m-d)
	 *   - date_to: filter to date (Y-m-d)
	 *   - study_id: filter by study (numeric sid or IDNO)
	 *   - file_type: filter by file type
	 *   - sort_by: field to sort by (default: ts)
	 *   - sort_order: asc or desc (default: desc)
	 */
	function raw_downloads_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $study_sid,
				'file_type' => $this->input->get('file_type')
			);
			$this->Analytics_model->validate_admin_raw_date_range($filters['date_from'], $filters['date_to']);
			
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
	 * GET /api/admin/analytics/daily/studies
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
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $study_sid
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
	 * GET /api/admin/analytics/daily/files
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
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $study_sid
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
	 * GET /api/admin/analytics/monthly/studies
	 * 
	 * Query params:
	 *   - year: filter by year
	 *   - month: filter by month (1-12)
	 *   - study_id: filter by study (numeric sid or IDNO)
	 *   - sort_by: pageviews|unique_visitors|downloads|year|month|study_id
	 *   - sort_order: asc|desc (default: desc when sort_by set)
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function monthly_studies_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'year' => $this->input->get('year'),
				'month' => $this->input->get('month'),
				'study_id' => $study_sid,
				'sort_by' => $this->input->get('sort_by'),
				'sort_order' => $this->input->get('sort_order'),
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
	 * GET /api/admin/analytics/monthly/totals
	 * 
	 * Query params:
	 *   - months: number of months to retrieve (default: 12, max: 24)
	 *   - study_id: optional (numeric sid or IDNO) for single-study series
	 * 
	 * Returns array of monthly totals (pageviews, unique_visitors, downloads)
	 */
	function monthly_totals_get()
	{
		try {
			$study_raw = $this->input->get('study_id');
			$study_sid = null;
			if ($study_raw !== null && $study_raw !== '') {
				$study_sid = $this->require_analytics_access_for_study_param($study_raw);
			} else {
				$this->require_analytics_access(null, false);
			}

			$months = (int)$this->input->get('months') ?: 12;
			$months = max(1, min(24, $months)); // clamp 1-24
			
			$result = $this->Analytics_model->get_monthly_totals($months, $study_sid);
			
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
	 * Per-day totals from daily study aggregates (for charts).
	 *
	 * GET /api/admin/analytics/daily/totals
	 *
	 * Query params:
	 *   - date_from: required (Y-m-d)
	 *   - date_to: required (Y-m-d)
	 *   - study_id: optional (numeric sid or IDNO)
	 */
	function daily_totals_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$data = $this->Analytics_model->get_daily_totals(array(
				'date_from' => $this->input->get('date_from'),
				'date_to' => $this->input->get('date_to'),
				'study_id' => $study_sid,
			));

			$this->set_response(array(
				'status' => 'success',
				'data' => $data,
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'error',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Analytics configuration and freshness metadata.
	 *
	 * GET /api/admin/analytics/meta
	 */
	function meta_get()
	{
		$this->require_analytics_access(null, false);
		try {
			$this->set_response(array(
				'status' => 'success',
				'data' => $this->Analytics_model->get_analytics_meta(),
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'error',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Dashboard overview bundle for admin analytics home.
	 *
	 * GET /api/admin/analytics/overview
	 *
	 * Query params:
	 *   - study_id: optional (numeric sid or IDNO) — scopes all metrics to one study
	 */
	function overview_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$data = $this->Analytics_model->get_admin_overview(array(
				'study_id' => $study_sid,
			));

			$this->set_response(array(
				'status' => 'success',
				'data' => $data,
			), REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'error',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get monthly file aggregates
	 * 
	 * GET /api/admin/analytics/monthly/files
	 * 
	 * Query params:
	 *   - year: filter by year
	 *   - month: filter by month (1-12)
	 *   - study_id: filter by study (numeric sid or IDNO)
	 *   - sort_by: downloads|year|month|study_id|file_name
	 *   - sort_order: asc|desc
	 *   - limit: number of records (default: 50)
	 *   - offset: pagination offset (default: 0)
	 */
	function monthly_files_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'year' => $this->input->get('year'),
				'month' => $this->input->get('month'),
				'study_id' => $study_sid,
				'sort_by' => $this->input->get('sort_by'),
				'sort_order' => $this->input->get('sort_order'),
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
	 * GET /api/admin/analytics/daily/studies/export?format=csv|json&date_from=&date_to=&study_id=
	 */
	function daily_studies_export_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to'   => $this->input->get('date_to'),
				'study_id'  => $study_sid
			);

			$format = $this->parse_export_format();
			$export_max = $this->Analytics_model->get_export_max_rows();
			$result = $this->Analytics_model->get_daily_studies($filters, $export_max, 0);

			$this->deliver_aggregate_export(
				'daily_studies_export',
				$format,
				$result,
				array('date', 'study_id', 'idno', 'title', 'nation', 'study_year', 'pageviews', 'unique_visitors', 'downloads'),
				function ($row) {
					return array(
						isset($row['date'])            ? $row['date']            : '',
						isset($row['study_id'])        ? $row['study_id']        : '',
						isset($row['idno'])            ? $row['idno']            : '',
						isset($row['title'])           ? $row['title']           : '',
						isset($row['nation'])          ? $row['nation']          : '',
						isset($row['study_year'])      ? $row['study_year']      : '',
						isset($row['pageviews'])       ? $row['pageviews']       : 0,
						isset($row['unique_visitors']) ? $row['unique_visitors'] : 0,
						isset($row['downloads'])       ? $row['downloads']       : 0
					);
				}
			);
		} catch (Exception $e) {
			$this->respond_export_exception($e);
		}
	}

	/**
	 * 
	 * Export daily file aggregates
	 * 
	 * 
	 * GET /api/admin/analytics/daily/files/export?format=csv|json&date_from=&date_to=&study_id=
	 */
	function daily_files_export_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'date_from' => $this->input->get('date_from'),
				'date_to'   => $this->input->get('date_to'),
				'study_id'  => $study_sid
			);

			$format = $this->parse_export_format();
			$export_max = $this->Analytics_model->get_export_max_rows();
			$result = $this->Analytics_model->get_daily_files($filters, $export_max, 0);

			$this->deliver_aggregate_export(
				'daily_files_export',
				$format,
				$result,
				array('date', 'study_id', 'idno', 'title', 'file_name', 'downloads'),
				function ($row) {
					return array(
						isset($row['date'])      ? $row['date']      : '',
						isset($row['study_id'])  ? $row['study_id']  : '',
						isset($row['idno'])      ? $row['idno']      : '',
						isset($row['title'])     ? $row['title']     : '',
						isset($row['file_name']) ? $row['file_name'] : '',
						isset($row['downloads']) ? $row['downloads'] : 0
					);
				}
			);
		} catch (Exception $e) {
			$this->respond_export_exception($e);
		}
	}

	/**
	 * 
	 * Export monthly study aggregates
	 * 
	 * GET /api/admin/analytics/monthly/studies/export?format=csv|json&year=&month=&study_id=
	 */
	function monthly_studies_export_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'year'     => $this->input->get('year'),
				'month'    => $this->input->get('month'),
				'study_id' => $study_sid
			);

			$format = $this->parse_export_format();
			$export_max = $this->Analytics_model->get_export_max_rows();
			$result = $this->Analytics_model->get_monthly_studies($filters, $export_max, 0);

			$this->deliver_aggregate_export(
				'monthly_studies_export',
				$format,
				$result,
				array('year', 'month', 'study_id', 'idno', 'title', 'nation', 'study_year', 'pageviews', 'unique_visitors', 'downloads', 'finalized'),
				function ($row) {
					return array(
						isset($row['year'])            ? $row['year']            : '',
						isset($row['month'])           ? $row['month']           : '',
						isset($row['study_id'])        ? $row['study_id']        : '',
						isset($row['idno'])            ? $row['idno']            : '',
						isset($row['title'])           ? $row['title']           : '',
						isset($row['nation'])          ? $row['nation']          : '',
						isset($row['study_year'])      ? $row['study_year']      : '',
						isset($row['pageviews'])       ? $row['pageviews']       : 0,
						isset($row['unique_visitors']) ? $row['unique_visitors'] : 0,
						isset($row['downloads'])       ? $row['downloads']       : 0,
						isset($row['finalized'])       ? $row['finalized']       : 0
					);
				}
			);
		} catch (Exception $e) {
			$this->respond_export_exception($e);
		}
	}

	/**
	 * 
	 * Export monthly file aggregates
	 * 
	 * GET /api/admin/analytics/monthly/files/export?format=csv|json&year=&month=&study_id=
	 * 
	 */
	function monthly_files_export_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'year'     => $this->input->get('year'),
				'month'    => $this->input->get('month'),
				'study_id' => $study_sid
			);

			$format = $this->parse_export_format();
			$export_max = $this->Analytics_model->get_export_max_rows();
			$result = $this->Analytics_model->get_monthly_files($filters, $export_max, 0);

			$this->deliver_aggregate_export(
				'monthly_files_export',
				$format,
				$result,
				array('year', 'month', 'study_id', 'idno', 'title', 'file_name', 'downloads', 'finalized'),
				function ($row) {
					return array(
						isset($row['year'])      ? $row['year']      : '',
						isset($row['month'])     ? $row['month']     : '',
						isset($row['study_id'])  ? $row['study_id']  : '',
						isset($row['idno'])      ? $row['idno']      : '',
						isset($row['title'])     ? $row['title']     : '',
						isset($row['file_name']) ? $row['file_name'] : '',
						isset($row['downloads']) ? $row['downloads'] : 0,
						isset($row['finalized']) ? $row['finalized'] : 0
					);
				}
			);
		} catch (Exception $e) {
			$this->respond_export_exception($e);
		}
	}

	/**
	 * Run daily aggregation
	 * 
	 * POST /api/admin/analytics/aggregate/daily
	 * 
	 * Body params:
	 *   - date: specific date to process (Y-m-d), or null for auto-detect
	 *   - offset: starting offset for chunked processing (default: 0)
	 */
	function aggregate_daily_post()
	{
		$this->require_analytics_access(null, false);
		try {
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
	 * POST /api/admin/analytics/aggregate/monthly
	 * 
	 * Body params:
	 *   - year: year to process (default: current year)
	 *   - month: month to process (default: current month)
	 *   - offset: starting offset for chunked processing (default: 0)
	 */
	function aggregate_monthly_post()
	{
		$this->require_analytics_access(null, false);
		try {
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
	 * POST /api/admin/analytics/aggregate/totals
	 * 
	 * Body params:
	 *   - study_id: optional study ID to update (updates all if omitted)
	 */
	function aggregate_totals_post()
	{
		$this->require_analytics_access(null, false);
		try {
			$study_id_raw = $this->input->post('study_id');
			$study_sid = $study_id_raw ? $this->resolve_optional_study_sid_value($study_id_raw) : null;
			$offset = (int)$this->input->post('offset') ?: 0;
			
			if ($study_sid) {
				$result = $this->Analytics_model->update_legacy_totals($study_sid);
				$success = (bool)$result;
			} else {
				$result = $this->Analytics_model->update_legacy_totals_chunked($offset);
				$success = !empty($result['success']);
			}
			
			$response = array(
				'status' => 'success',
				'message' => $study_sid ? "Totals updated for study {$study_sid}" : 'All-time totals updated successfully',
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
	 * POST /api/admin/analytics/aggregate/run
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
		$this->require_analytics_access(null, false);
		try {
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
	 * GET /api/admin/analytics/aggregate/status
	 * 
	 * Returns current aggregation status for polling
	 */
	function aggregate_status_get()
	{
		$this->require_analytics_access(null, false);
		try {
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
	 * POST /api/admin/analytics/aggregate/stop
	 * 
	 * Stops the current aggregation run
	 */
	function aggregate_stop_post()
	{
		$this->require_analytics_access(null, false);
		try {
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
		$this->aggregate_run_post();
	}

	/**
	 * Get legacy (all-time) study totals stored with year=0, month=0
	 *
	 * GET /api/admin/analytics/legacy/studies
	 *
	 * Query params:
	 *   - study_id: filter by study ID (optional)
	 *   - limit:    number of records (default: 50)
	 *   - offset:   pagination offset (default: 0)
	 */
	function legacy_studies_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'study_id' => $study_sid
			);
			$limit  = (int)$this->input->get('limit')  ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;

			$result = $this->Analytics_model->get_legacy_studies($filters, $limit, $offset);

			$response = array(
				'status'   => 'success',
				'data'     => $result['data'],
				'total'    => $result['total'],
				'limit'    => $result['limit'],
				'offset'   => $result['offset'],
				'has_more' => $result['has_more']
			);

			$this->set_response($response, REST_Controller::HTTP_OK);

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Get legacy (all-time) file totals stored with year=0, month=0
	 *
	 * GET /api/admin/analytics/legacy/files
	 *
	 * Query params:
	 *   - study_id: filter by study ID (optional)
	 *   - limit:    number of records (default: 50)
	 *   - offset:   pagination offset (default: 0)
	 */
	function legacy_files_get()
	{
		try {
			$study_sid = $this->require_analytics_access_for_study_param();
			$filters = array(
				'study_id' => $study_sid
			);
			$limit  = (int)$this->input->get('limit')  ?: 50;
			$offset = (int)$this->input->get('offset') ?: 0;

			$result = $this->Analytics_model->get_legacy_files($filters, $limit, $offset);

			$response = array(
				'status'   => 'success',
				'data'     => $result['data'],
				'total'    => $result['total'],
				'limit'    => $result['limit'],
				'offset'   => $result['offset'],
				'has_more' => $result['has_more']
			);

			$this->set_response($response, REST_Controller::HTTP_OK);

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Set legacy (all-time) totals for studies using caller-provided values
	 *
	 * POST /api/admin/analytics/legacy/studies
	 *
	 * Body params (JSON array):
	 *   [
	 *     {
	 *       "study_id":        "IDNO or numeric sid" (required),
	 *       "pageviews":       0,
	 *       "unique_visitors": 0,
	 *       "downloads":       0
	 *     },
	 *     ...
	 *   ]
	 *
	 * Pass ?id_format=id to treat study_id as a numeric database id.
	 */
	function legacy_studies_post()
	{
		$this->require_analytics_access(null, false);
		try {
			$input = $this->raw_json_input();

			if (empty($input) || !is_array($input)) {
				$this->set_response(array('status' => 'error', 'message' => 'Request body must be a JSON array'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$results  = array();
			$errors   = array();

			foreach ($input as $index => $item) {
				if (empty($item['study_id'])) {
					$errors[] = array('index' => $index, 'study_id' => null, 'error' => 'study_id is required');
					continue;
				}

				try {
					// Resolve idno or numeric sid (pass ?id_format=id to use numeric sid directly)
					$sid             = $this->get_sid_from_idno($item['study_id']);
					$pageviews       = isset($item['pageviews'])       ? (int)$item['pageviews']       : 0;
					$unique_visitors = isset($item['unique_visitors']) ? (int)$item['unique_visitors'] : 0;
					$downloads       = isset($item['downloads'])       ? (int)$item['downloads']       : 0;

					$ok = $this->Analytics_model->set_legacy_study_totals($sid, $pageviews, $unique_visitors, $downloads);

					if ($ok) {
						$results[] = array('study_id' => $item['study_id'], 'sid' => $sid, 'status' => 'updated');
					} else {
						$errors[] = array('index' => $index, 'study_id' => $item['study_id'], 'error' => 'DB update failed');
					}
				} catch (Exception $e) {
					$errors[] = array('index' => $index, 'study_id' => $item['study_id'], 'error' => $e->getMessage());
				}
			}

			$response = array(
				'status'  => empty($errors) ? 'success' : (empty($results) ? 'error' : 'partial'),
				'updated' => count($results),
				'failed'  => count($errors),
				'results' => $results,
				'errors'  => $errors
			);

			$http_status = empty($results) && !empty($errors)
				? REST_Controller::HTTP_BAD_REQUEST
				: REST_Controller::HTTP_OK;

			$this->set_response($response, $http_status);

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Set legacy (all-time) download total for a file using a caller-provided value
	 *
	 * POST /api/admin/analytics/legacy/files
	 *
	 * Body params (JSON):
	 *   - study_id:  Study identifier (required)
	 *   - file_name: File name (required)
	 *   - downloads: Total downloads to store (required)
	 */
	function legacy_files_post()
	{
		$this->require_analytics_access(null, false);
		try {
			$input = $this->raw_json_input();

			if (empty($input['study_id'])) {
				$this->set_response(array('status' => 'error', 'message' => 'study_id is required'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			if (empty($input['file_name'])) {
				$this->set_response(array('status' => 'error', 'message' => 'file_name is required'), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			// Resolve idno or numeric sid (pass ?id_format=id to use numeric sid directly)
			$study_id  = $this->get_sid_from_idno($input['study_id']);
			$file_name = $input['file_name'];
			$downloads = isset($input['downloads']) ? (int)$input['downloads'] : 0;

			$result = $this->Analytics_model->set_legacy_file_totals($study_id, $file_name, $downloads);

			if ($result) {
				$this->set_response(array(
					'status'  => 'success',
					'message' => "Legacy file totals updated for {$file_name} in study {$study_id}"
				), REST_Controller::HTTP_OK);
			} else {
				$this->set_response(array('status' => 'error', 'message' => 'Failed to update legacy file totals'), REST_Controller::HTTP_BAD_REQUEST);
			}

		} catch (Exception $e) {
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

}
