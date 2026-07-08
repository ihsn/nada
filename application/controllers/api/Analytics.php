<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Public analytics ingest (no login).
 *
 * Base URL: /api/analytics
 * Auth: none (CSRF required for pageview POST)
 *
 * Responses are status-only (no body) to avoid leaking details to anonymous clients.
 * Pageview POST: 204 inserted, 409 duplicate, 400 rejected, 403 CSRF failure.
 */
class Analytics extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('analytics_tracker');
		$this->load->model('Analytics_event_tracker_model');
	}

	/**
	 * POST /api/analytics/pageview
	 *
	 * 204 — pageview recorded
	 * 409 — duplicate pageview within dedupe window
	 * 400 — invalid payload
	 * 403 — invalid or missing CSRF token
	 */
	function pageview_post()
	{
		try {
			$options = $this->raw_json_input();
			$csrf_token_name = $this->security->get_csrf_token_name();
			$csrf_submitted = isset($options[$csrf_token_name]) ? $options[$csrf_token_name] : null;
			if (empty($csrf_submitted) || $csrf_submitted !== $this->security->get_csrf_hash()) {
				$this->respond_status(REST_Controller::HTTP_FORBIDDEN);
				return;
			}

			if (empty($options['study_id'])) {
				$this->respond_status(REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$study_id = $this->resolve_analytics_study_id($options['study_id']);
			$session_id = $options['session_id'] ?? null;

			$result = $this->analytics_tracker->track_pageview($study_id, $session_id, $options);

			if ($result === Analytics_event_tracker_model::PAGEVIEW_INSERTED) {
				$this->respond_status(REST_Controller::HTTP_NO_CONTENT);
				return;
			}

			if ($result === Analytics_event_tracker_model::PAGEVIEW_DUPLICATE) {
				$this->respond_status(REST_Controller::HTTP_CONFLICT);
				return;
			}

			$this->respond_status(REST_Controller::HTTP_BAD_REQUEST);

		} catch (Exception $e) {
			$this->respond_status(REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/analytics/download
	 *
	 * 204 — accepted (tracked or filtered server-side)
	 * 400 — invalid payload
	 */
	function download_post()
	{
		try {
			$study_id = $this->input->post('study_id');
			$file_name = $this->input->post('file_name');

			if (empty($study_id) || empty($file_name)) {
				$this->respond_status(REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$study_id = $this->resolve_analytics_study_id($study_id);

			$file_type = $this->input->post('file_type');
			$user_agent = $this->input->post('user_agent');

			$data = array();
			if ($file_type) {
				$data['file_type'] = $file_type;
			}
			if ($user_agent) {
				$data['user_agent'] = $user_agent;
			}

			$this->analytics_tracker->track_download($study_id, $file_name, $data);
			$this->respond_status(REST_Controller::HTTP_NO_CONTENT);

		} catch (Exception $e) {
			$this->respond_status(REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * @param int $http_code
	 */
	private function respond_status($http_code)
	{
		$this->set_response(NULL, $http_code);
	}

	/**
	 * @param mixed $study_id
	 * @return int
	 * @throws Exception
	 */
	private function resolve_analytics_study_id($study_id)
	{
		$s = is_scalar($study_id) ? trim((string) $study_id) : '';
		if ($s === '') {
			throw new Exception('study_id is required');
		}

		if (ctype_digit($s) && (int) $s > 0) {
			return (int) $s;
		}

		return $this->get_sid_from_idno($s);
	}
}
