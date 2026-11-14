<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin API for the public site header.
 *
 * Base URL: /api/admin/site_header
 * Auth: a signed-in user with configurations/edit. Writes from a browser session need the CSRF token
 * (X-CSRF-TOKEN header).
 *
 *   GET  /                       published and draft settings, presets, tokens, limits
 *   POST draft                   {header:{...}} save the draft (cleaned); returns the stored draft
 *   POST publish                 {header:{...}} optional; publish it (or the stored draft)
 *   POST discard                 delete the draft
 *   POST reset                   delete the draft and the published settings: the default header is shown
 *   POST upload/logo             multipart "file": png, jpg, gif, webp or svg (max 2 MB)
 *   POST upload/background       multipart "file": png, jpg, gif or webp (max 5 MB)
 *
 * Settings are stored as JSON in configurations.site_header and configurations.site_header_draft.
 * Images go to files/site/.
 */
class Site_header extends MY_REST_Controller {

	private static $upload_limits = array(
		'logo'       => array('max' => 2097152, 'types' => array('png', 'jpg', 'jpeg', 'gif', 'webp', 'svg')),
		'background' => array('max' => 5242880, 'types' => array('png', 'jpg', 'jpeg', 'gif', 'webp')),
	);

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Configurations_model');
		$this->load->library('site_header_builder');
		$this->is_authenticated_or_die();
		$this->require_access('configurations', 'edit');
		$this->require_session_csrf();
	}

	public function index_get()
	{
		$this->set_response($this->_state(), REST_Controller::HTTP_OK);
	}

	public function draft_post()
	{
		try {
			$header = $this->_validated_body();
			$this->_store('site_header_draft', $header['settings']);
			$this->set_response(array_merge($this->_state(), array('warnings' => $header['warnings'])), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function publish_post()
	{
		try {
			$body = $this->_json_body();
			if (isset($body['header'])) {
				$header = $this->_validated_body($body);
				$settings = $header['settings'];
				$warnings = $header['warnings'];
			} else {
				$settings = $this->site_header_builder->draft();
				$warnings = array();
				if ($settings === null) {
					throw new Site_header_api_error('NO_DRAFT', 422);
				}
			}

			$this->_store('site_header', $settings);
			$this->Configurations_model->delete_by_name('site_header_draft');
			$this->config->set_item('site_header_draft', '');
			$this->_remove_unused_uploads();

			$this->set_response(array_merge($this->_state(), array('warnings' => $warnings)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	public function discard_post()
	{
		$this->Configurations_model->delete_by_name('site_header_draft');
		$this->config->set_item('site_header_draft', '');
		$this->_remove_unused_uploads();
		$this->set_response($this->_state(), REST_Controller::HTTP_OK);
	}

	public function reset_post()
	{
		$this->Configurations_model->delete_by_name('site_header_draft');
		$this->Configurations_model->upsert('site_header', '');
		$this->config->set_item('site_header_draft', '');
		$this->config->set_item('site_header', '');
		$this->set_response($this->_state(), REST_Controller::HTTP_OK);
	}

	public function upload_post($kind = null)
	{
		try {
			if (!isset(self::$upload_limits[$kind])) {
				throw new Site_header_api_error('UNKNOWN_UPLOAD', 404);
			}
			$limits = self::$upload_limits[$kind];
			$file = $this->_uploaded_file('file');

			if ($file['size'] > $limits['max']) {
				throw new Site_header_api_error('FILE_TOO_LARGE', 413, array('file' => 'FILE_TOO_LARGE'));
			}
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			if (!in_array($ext, $limits['types'], true)) {
				throw new Site_header_api_error('FILE_TYPE_NOT_ALLOWED', 422, array('file' => 'FILE_TYPE_NOT_ALLOWED'));
			}
			if ($ext === 'jpeg') {
				$ext = 'jpg';
			}
			if (!$this->_image_matches_type($file['tmp_name'], $ext)) {
				throw new Site_header_api_error('FILE_CONTENT_MISMATCH', 422, array('file' => 'FILE_CONTENT_MISMATCH'));
			}
			if ($ext === 'svg' && !$this->_svg_is_safe((string) file_get_contents($file['tmp_name']))) {
				throw new Site_header_api_error('SVG_NOT_SAFE', 422, array('file' => 'SVG_NOT_SAFE'));
			}

			$dir = $this->_upload_dir();
			$name = $kind . '-' . substr(sha1_file($file['tmp_name']), 0, 16) . '.' . $ext;
			$target = $dir . '/' . $name;
			if (!is_file($target)) {
				if (!move_uploaded_file($file['tmp_name'], $target)) {
					throw new Exception('UPLOAD_FAILED');
				}
				@chmod($target, 0644);
			}

			$path = Site_header_builder::UPLOAD_DIR . '/' . $name;
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
	// Helpers
	// =============================================================================================

	private function _state()
	{
		$builder = $this->site_header_builder;
		$published = $builder->published();
		$draft = $builder->draft();

		$presets = array();
		foreach ($builder->presets() as $preset) {
			$presets[] = array(
				'id'          => $preset['id'],
				'title'       => $preset['title'],
				'description' => $preset['description'],
				'html'        => $preset['html'],
				'css'         => $preset['css'],
				'thumbnail'   => $preset['thumbnail'],
			);
		}

		$languages = $this->config->item('supported_languages');

		return array(
			'status'            => 'success',
			'published'         => $this->_with_urls($published),
			'draft'             => $draft !== null ? $this->_with_urls($draft) : null,
			'is_default'        => trim((string) $this->config->item('site_header')) === '',
			'force_default'     => (bool) $this->config->item('site_header_force_default'),
			'defaults'          => $builder->defaults(),
			'presets'           => $presets,
			'tokens'            => $builder->token_docs(),
			'languages_enabled' => is_array($languages) ? count($languages) : 1,
			'theme_logo_url'    => $builder->logo_url(array('logo' => '')),
			'limits'            => array(
				'html'       => Site_header_builder::MAX_HTML,
				'css'        => Site_header_builder::MAX_CSS,
				'logo'       => self::$upload_limits['logo'],
				'background' => self::$upload_limits['background'],
			),
		);
	}

	private function _with_urls(array $settings)
	{
		$settings['logo_url'] = $settings['logo'] !== '' ? base_url($settings['logo']) : '';
		$settings['bg_image_url'] = $settings['bg_image'] !== '' ? base_url($settings['bg_image']) : '';
		return $settings;
	}

	private function _json_body()
	{
		$body = json_decode((string) file_get_contents('php://input'), true);
		return is_array($body) ? $body : array();
	}

	/**
	 * @return array [settings, warnings]
	 * @throws Site_header_api_error when a value is refused
	 */
	private function _validated_body($body = null)
	{
		if ($body === null) {
			$body = $this->_json_body();
		}
		if (!isset($body['header']) || !is_array($body['header'])) {
			throw new Site_header_api_error('HEADER_REQUIRED', 400);
		}
		$input = $body['header'];

		$errors = array();
		$settings = $this->site_header_builder->normalize($input, $errors);
		if ($errors) {
			throw new Site_header_api_error('VALIDATION_FAILED', 422, $errors);
		}

		foreach (array('logo', 'bg_image') as $key) {
			if ($settings[$key] !== '' && !is_file(FCPATH . $settings[$key])) {
				throw new Site_header_api_error('VALIDATION_FAILED', 422, array($key => 'IMAGE_NOT_FOUND'));
			}
		}

		// tell the admin when cleaning removed something from their HTML or CSS
		$warnings = array();
		$squash = function ($s) {
			return preg_replace('/\s+/', '', (string) $s);
		};
		if (isset($input['html']) && $squash($input['html']) !== $squash($settings['html'])) {
			$warnings[] = 'HTML_CLEANED';
		}
		if (isset($input['css']) && $squash($input['css']) !== $squash($settings['css'])) {
			$warnings[] = 'CSS_CLEANED';
		}

		return array('settings' => $settings, 'warnings' => $warnings);
	}

	/** Save settings; refuse when the database cut the value short (migration not run) */
	private function _store($name, array $settings)
	{
		$json = $this->site_header_builder->to_json($settings);
		if (strlen($json) > 5000 && !$this->_value_column_is_long()) {
			throw new Site_header_api_error('VALUE_TOO_LONG_RUN_MIGRATIONS', 500);
		}
		$this->Configurations_model->upsert($name, $json);

		$saved = $this->Configurations_model->get_config_item($name);
		if ((string) $saved !== $json) {
			if (strlen((string) $saved) < strlen($json)) {
				$this->Configurations_model->delete_by_name($name);
				throw new Site_header_api_error('VALUE_TOO_LONG_RUN_MIGRATIONS', 500);
			}
			throw new Exception('SAVE_FAILED');
		}
		$this->config->set_item($name, $json);
	}

	/** configurations.value is a long text column (migration 20261113120001_configurations_value_text) */
	private function _value_column_is_long()
	{
		foreach ($this->db->field_data('configurations') as $field) {
			if ($field->name === 'value') {
				$type = strtolower((string) $field->type);
				return strpos($type, 'text') !== false || (int) $field->max_length <= 0 || (int) $field->max_length > 5000;
			}
		}
		return false;
	}

	private function _upload_dir()
	{
		$dir = rtrim(FCPATH, '/\\') . '/' . Site_header_builder::UPLOAD_DIR;
		if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new Exception('STORAGE_NOT_WRITABLE');
		}
		if (!is_file($dir . '/index.html')) {
			@file_put_contents($dir . '/index.html', '');
		}
		return $dir;
	}

	/** Delete uploaded header images that neither the published header nor the draft uses */
	private function _remove_unused_uploads()
	{
		$dir = rtrim(FCPATH, '/\\') . '/' . Site_header_builder::UPLOAD_DIR;
		if (!is_dir($dir)) {
			return;
		}
		$keep = array();
		$draft = $this->site_header_builder->draft();
		foreach (array($this->site_header_builder->published(), $draft) as $s) {
			if (is_array($s)) {
				$keep[] = basename($s['logo']);
				$keep[] = basename($s['bg_image']);
			}
		}
		$files = array_merge(glob($dir . '/logo-*.*') ?: array(), glob($dir . '/background-*.*') ?: array());
		foreach ($files as $file) {
			if (!in_array(basename($file), $keep, true)) {
				@unlink($file);
			}
		}
	}

	private function _uploaded_file($field)
	{
		if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || is_array($_FILES[$field]['name'])) {
			throw new Site_header_api_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		$file = $_FILES[$field];
		if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
			throw new Site_header_api_error('FILE_TOO_LARGE', 413, array($field => 'FILE_TOO_LARGE'));
		}
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			throw new Site_header_api_error('NO_FILE', 400, array($field => 'NO_FILE'));
		}
		if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
			throw new Site_header_api_error('UPLOAD_FAILED', 400, array($field => 'UPLOAD_FAILED'));
		}
		return $file;
	}

	/** The file has to be the image type its name says */
	private function _image_matches_type($path, $ext)
	{
		if ($ext === 'svg') {
			$head = (string) @file_get_contents($path, false, null, 0, 4096);
			return stripos($head, '<svg') !== false && strpos($head, "\0") === false;
		}
		$info = @getimagesize($path);
		if (!$info) {
			return false;
		}
		$types = array(
			'png'  => IMAGETYPE_PNG,
			'jpg'  => IMAGETYPE_JPEG,
			'gif'  => IMAGETYPE_GIF,
			'webp' => IMAGETYPE_WEBP,
		);
		return isset($types[$ext]) && $info[2] === $types[$ext];
	}

	/** SVG logos are refused when they hold scripts, event handlers, external references or entities */
	private function _svg_is_safe($svg)
	{
		$patterns = array(
			'/<\s*script/i',
			'/<\s*foreignObject/i',
			'/<\s*(iframe|embed|object|audio|video|animate|set)\b/i',
			'/\son[a-z]+\s*=/i',
			'/javascript\s*:/i',
			'/<!ENTITY/i',
			'/<!DOCTYPE[^>]*\[/i',
			'/(xlink:)?href\s*=\s*["\']?\s*(?!#)[^"\'\s>]/i',
			'/@import/i',
			'/url\s*\(\s*["\']?\s*(?!#)/i',
		);
		foreach ($patterns as $p) {
			if (preg_match($p, $svg)) {
				return false;
			}
		}
		return true;
	}

	private function _error(Exception $e)
	{
		if ($e instanceof Site_header_api_error) {
			$this->set_response(array(
				'status'  => 'failed',
				'message' => $e->getMessage(),
				'errors'  => $e->errors,
			), $e->status);
			return;
		}
		log_message('error', 'Site header API: ' . $e->getMessage());
		$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_INTERNAL_ERROR);
	}
}

class Site_header_api_error extends Exception {

	public $status;
	public $errors;

	public function __construct($message, $status = 400, array $errors = array())
	{
		parent::__construct($message);
		$this->status = $status;
		$this->errors = $errors;
	}
}
