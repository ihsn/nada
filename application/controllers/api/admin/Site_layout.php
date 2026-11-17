<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin API for the Site layout: the public header, footer and site-wide CSS (styles).
 *
 * Base URL: /api/admin/site_layout
 * Auth: a signed-in user with configurations/edit. Browser writes need the CSRF token (X-CSRF-TOKEN).
 * {part} is header, footer or styles.
 *
 *   GET  state                 every part's state: {status, header:{...}, footer:{...}, styles:{...}}
 *   GET  state/{part}          one part: published and draft settings, defaults, limits, file status,
 *                              plus presets and tokens (header), tokens (footer), example CSS (styles)
 *   POST draft/{part}          {settings:{...}} save the draft (cleaned); returns the part state
 *   POST publish/{part}        {settings:{...}} optional; publish it, or the stored draft
 *   POST render/{part}         {settings:{...}, as_guest} live preview, nothing saved:
 *                              header {html, template}, footer {html}, styles {css}
 *   POST discard/{part}        delete the draft
 *   POST reset/{part}          delete the draft and the published settings: the theme's own is shown
 *   POST republish/{part}      write the published file again from the published settings
 *   POST upload/logo           multipart "file": header logo, png, jpg, gif, webp or svg (max 2 MB)
 *   POST upload/background     multipart "file": header background, png, jpg, gif or webp (max 5 MB)
 *   GET  export?parts=header,footer,styles&state=published|draft   download a package (.zip)
 *   POST inspect               multipart "file": what the package holds; nothing is saved
 *   POST import                multipart "file", "parts" (comma list): the chosen parts become drafts
 *
 * Errors: {status: failed, message: <code>, errors: {field: code}}.
 * Settings are stored in table site_layout; publish, discard and reset: application/libraries/Site_layout_parts.php.
 * Package format and checks: application/libraries/Site_layout_package.php.
 */
class Site_layout extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('site_layout_parts');
		$this->lang->load('general');
		$this->is_authenticated_or_die();
		$this->require_access('configurations', 'edit');
		$this->require_session_csrf();
	}

	// =============================================================================================
	// Parts
	// =============================================================================================

	public function state_get($part = null)
	{
		try {
			$parts = $this->site_layout_parts;
			if ($part !== null) {
				$this->set_response($parts->state($parts->check_part($part)), REST_Controller::HTTP_OK);
				return;
			}
			$out = array('status' => 'success');
			foreach (Site_layout_parts::$parts as $p) {
				$out[$p] = $parts->state($p);
			}
			$this->set_response($out, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function draft_post($part = null)
	{
		try {
			$parts = $this->site_layout_parts;
			$parts->check_part($part);
			$body = $this->_json_body();
			$valid = $parts->validate($part, isset($body['settings']) ? $body['settings'] : null);
			$parts->store($part, 'draft', $valid['settings'], $this->get_api_user_id());
			$this->_respond_state($part, $valid['warnings']);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function publish_post($part = null)
	{
		try {
			$parts = $this->site_layout_parts;
			$parts->check_part($part);
			$body = $this->_json_body();
			$settings = null;
			$warnings = array();
			if (isset($body['settings'])) {
				$valid = $parts->validate($part, $body['settings']);
				$settings = $valid['settings'];
				$warnings = $valid['warnings'];
			}
			$parts->publish($part, $settings, $this->get_api_user_id());
			$this->_respond_state($part, $warnings);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function render_post($part = null)
	{
		try {
			$builder = $this->site_layout_parts->builder($part);
			$body = $this->_json_body();
			$errors = array();
			$settings = $builder->normalize(isset($body['settings']) && is_array($body['settings']) ? $body['settings'] : array(), $errors);

			$out = array('status' => 'success');
			if ($part === 'styles') {
				$out['css'] = isset($settings['css']) ? $settings['css'] : '';
			} else {
				if ($part === 'header') {
					$builder->set_preview(TRUE, !empty($body['as_guest']));
				} else {
					$builder->set_preview(TRUE);
				}
				$out['html'] = $builder->render_settings($settings);
				if ($part === 'header') {
					$out['template'] = $builder->builder_template($settings);
				}
			}
			$out['errors'] = (object) $errors;
			$this->set_response($out, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function discard_post($part = null)
	{
		$this->_change($part, 'discard');
	}

	public function reset_post($part = null)
	{
		$this->_change($part, 'reset');
	}

	public function republish_post($part = null)
	{
		$this->_change($part, 'republish');
	}

	/** Header logo or background image */
	public function upload_post($kind = null)
	{
		try {
			if (!isset(Site_layout_images::$limits[$kind])) {
				throw new Site_layout_error('UNKNOWN_UPLOAD', 404);
			}
			$file = $this->_uploaded_file('file');
			$images = $this->site_layout_images;
			$ext = $images->extension($file['name']);
			$code = $images->check($file['tmp_name'], $ext, $kind);
			if ($code !== '') {
				$http = array('FILE_TOO_LARGE' => 413, 'UNKNOWN_UPLOAD' => 404);
				throw new Site_layout_error($code, isset($http[$code]) ? $http[$code] : 422, array('file' => $code));
			}
			try {
				$path = $images->store($file['tmp_name'], $kind, $ext, true);
			}
			catch (Exception $e) {
				$code = $e->getMessage();
				throw new Site_layout_error($code, 500, array('file' => $code));
			}
			$this->set_response(array(
				'status' => 'success',
				'path'   => $path,
				'url'    => base_url($path),
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	// =============================================================================================
	// Package export and import
	// =============================================================================================

	public function export_get()
	{
		try {
			$this->load->library('site_layout_package');
			$package = $this->site_layout_package;
			$state = $this->input->get('state') === 'draft' ? 'draft' : 'published';
			$tmp = $package->build($package->parts_param($this->input->get('parts')), $state);

			$filename = 'site-layout-' . ($state === 'draft' ? 'draft-' : '') . date('Y-m-d') . '.zip';
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($tmp));
			header('Cache-Control: no-store');
			readfile($tmp);
			@unlink($tmp);
			exit;
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/** What the package holds and what importing it would replace; nothing is saved */
	public function inspect_post()
	{
		try {
			$this->load->library('site_layout_package');
			$file = $this->_uploaded_file('file');
			$opened = $this->site_layout_package->open($file['tmp_name']);
			$summary = $this->site_layout_package->summary($opened);
			$opened['zip']->close();

			$this->set_response(array(
				'status'   => 'success',
				'package'  => $summary['package'],
				'parts'    => (object) $summary['parts'],
				'warnings' => $summary['warnings'],
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/** Store the chosen parts of the package as drafts */
	public function import_post()
	{
		try {
			$this->load->library('site_layout_package');
			$package = $this->site_layout_package;
			$selected = $package->parts_param($this->input->post('parts'));
			$file = $this->_uploaded_file('file');
			$opened = $package->open($file['tmp_name']);
			$warnings = array();
			try {
				$prepared = $package->prepare($opened, $selected, $warnings);
			}
			finally {
				$opened['zip']->close();
			}
			if (!$prepared) {
				// every chosen part was left out (for example, HTML over the size limit)
				$this->set_response(array(
					'status'   => 'failed',
					'message'  => $warnings ? 'NOTHING_IMPORTED' : 'NOTHING_SELECTED',
					'warnings' => $warnings,
				), REST_Controller::HTTP_UNPROCESSABLE_ENTITY);
				return;
			}

			foreach ($prepared as $part => $json) {
				$this->site_layout_parts->store($part, 'draft', $json, $this->get_api_user_id());
			}

			$this->set_response(array(
				'status'   => 'success',
				'imported' => array_keys($prepared),
				'warnings' => $warnings,
			), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	// =============================================================================================
	// Helpers
	// =============================================================================================

	/** Run discard, reset or republish on a part and return its state */
	private function _change($part, $action)
	{
		try {
			$parts = $this->site_layout_parts;
			$parts->check_part($part);
			$parts->$action($part);
			$this->_respond_state($part);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	private function _respond_state($part, array $warnings = array())
	{
		$state = $this->site_layout_parts->state($part);
		$state['warnings'] = $warnings;
		$this->set_response($state, REST_Controller::HTTP_OK);
	}

	private function _json_body()
	{
		$body = json_decode((string) file_get_contents('php://input'), true);
		return is_array($body) ? $body : array();
	}

	/** The uploaded file ($_FILES entry) after the upload checks */
	private function _uploaded_file($field)
	{
		if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || is_array($_FILES[$field]['name'])) {
			throw new Site_layout_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		$file = $_FILES[$field];
		if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
			throw new Site_layout_error('FILE_TOO_LARGE', 413, array($field => 'FILE_TOO_LARGE'));
		}
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			throw new Site_layout_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
			throw new Site_layout_error('UPLOAD_FAILED', 400, array($field => 'UPLOAD_FAILED'));
		}
		return $file;
	}

	private function _error(Exception $e)
	{
		if ($e->getMessage() === Site_layout_model::TABLE_MISSING) {
			$e = new Site_layout_error('RUN_MIGRATIONS', 500);
		}
		if ($e instanceof Site_layout_error) {
			$this->set_response(array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
				'errors'  => (object) $e->errors,
			), $e->http);
			return;
		}
		log_message('error', 'Site layout API: ' . $e->getMessage());
		$this->set_response(array('status' => 'failed', 'message' => 'SERVER_ERROR'), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
	}
}
