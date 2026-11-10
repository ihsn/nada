<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin database migrations API.
 *
 * Base URL: /api/admin/database_migration
 * Auth: session or API key; site admin only (same gate as admin/Database_migration).
 *
 *   GET  /                 status + migration list
 *   POST /run              { version: "YYYYMMDDHHIISS"|"latest" }  — one migration
 *   POST /mark_applied     { version }
 *   POST /unmark           { version }
 *   POST /set_version      { version }  — advanced watermark set
 */
class Database_migration extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('migration_manager');
		$this->is_authenticated_or_die();
		$this->_require_site_admin();
	}

	/**
	 * GET /api/admin/database_migration
	 */
	public function index_get()
	{
		try {
			$status = $this->migration_manager->get_status();
			$this->set_response(array('status' => 'success') + $status, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/database_migration/run
	 */
	public function run_post()
	{
		try {
			$input = $this->_json_body();
			$version = isset($input['version']) ? trim((string) $input['version']) : '';
			if ($version === '') {
				throw new Exception('version is required');
			}

			$result = $this->migration_manager->run($version);
			$status = $this->migration_manager->get_status();

			if ($result['type'] !== 'success') {
				$this->set_response(
					array(
						'status'  => 'error',
						'message' => $result['msg'],
						'result'  => $result,
					) + $status,
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $result['msg'],
					'result'  => $result,
				) + $status,
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/database_migration/mark_applied
	 */
	public function mark_applied_post()
	{
		$this->_watermark_action('mark_applied');
	}

	/**
	 * POST /api/admin/database_migration/unmark
	 */
	public function unmark_post()
	{
		$this->_watermark_action('unmark');
	}

	/**
	 * POST /api/admin/database_migration/set_version
	 */
	public function set_version_post()
	{
		$this->_watermark_action('set_version');
	}

	private function _watermark_action($action)
	{
		try {
			$input = $this->_json_body();
			$version = isset($input['version']) ? trim((string) $input['version']) : '';
			if ($version === '') {
				throw new Exception('version is required');
			}

			if ($action === 'mark_applied') {
				$result = $this->migration_manager->mark_applied($version);
			} elseif ($action === 'unmark') {
				$result = $this->migration_manager->unmark($version);
			} else {
				$result = $this->migration_manager->set_version($version);
			}

			$status = $this->migration_manager->get_status();

			if ($result['type'] !== 'success') {
				$this->set_response(
					array(
						'status'  => 'error',
						'message' => $result['msg'],
					) + $status,
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $result['msg'],
				) + $status,
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	private function _json_body()
	{
		$input = $this->raw_json_input();
		return is_array($input) ? $input : array();
	}

	private function _require_site_admin()
	{
		try {
			if (! $this->acl_manager->user_is_admin()) {
				$this->access_denied_response();
			}
		}
		catch (Exception $e) {
			$this->access_denied_response();
		}
	}

	private function _error(Exception $e)
	{
		$this->set_response(
			array('status' => 'error', 'message' => $e->getMessage()),
			REST_Controller::HTTP_BAD_REQUEST
		);
	}
}
