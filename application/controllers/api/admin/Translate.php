<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin site translations API.
 *
 * Base URL: /api/admin/translate
 * Auth: session or API key; ACL translate/edit (same gate as admin/Translate).
 *
 *   GET    /                              languages + completeness
 *   POST   /                              create language { language_name }
 *   POST   /import                        multipart lang_zip (+ optional dry_run)
 *   GET    /{lang}/files                  file list + status
 *   GET    /{lang}/files/{file}           keys for one file
 *   POST   /{lang}/files/{file}           save { translations: { key: value } }
 *   POST   /{lang}/files/{file}/reset     drop userdata override
 *   GET    /{lang}/search?q=&missing=1    cross-file search
 *   GET    /{lang}/export                 JSON zip download
 *   POST   /{lang}/delete                 delete custom language
 */
class Translate extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('translator');
		$this->is_authenticated_or_die();
		$this->require_access('translate', 'edit');
	}

	/**
	 * GET /api/admin/translate
	 */
	public function index_get()
	{
		try {
			$languages = array();
			foreach ($this->translator->get_languages_array() as $lang) {
				$info = $this->translator->get_language_info($lang);
				$completeness = $this->translator->get_language_completeness($lang);
				$languages[] = array(
					'code'         => $lang,
					'name'         => ucfirst($lang),
					'is_base'      => ($lang === 'english'),
					'has_official' => ! empty($info['has_official']),
					'has_userdata' => ! empty($info['has_userdata']),
					'source'       => $this->_source_label($lang, $info),
					'completeness' => $completeness,
				);
			}
			$this->set_response(array('status' => 'success', 'languages' => $languages), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/translate
	 * Body: { language_name: "arabic" }
	 */
	public function index_post()
	{
		try {
			$input = $this->raw_json_input();
			if (! is_array($input)) {
				$input = array();
			}
			$lang = isset($input['language_name']) ? strtolower(trim((string) $input['language_name'])) : '';
			$result = $this->translator->create_language($lang);
			if ($result['type'] !== 'success') {
				throw new Exception($result['msg']);
			}
			$this->set_response(
				array(
					'status'   => 'success',
					'message'  => $result['msg'],
					'language' => $lang,
				),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/translate/import
	 * multipart: lang_zip
	 */
	public function import_post()
	{
		try {
			$upload_error = isset($_FILES['lang_zip']['error']) ? (int) $_FILES['lang_zip']['error'] : UPLOAD_ERR_NO_FILE;
			$tmp_name = isset($_FILES['lang_zip']['tmp_name']) ? $_FILES['lang_zip']['tmp_name'] : '';

			if ($upload_error !== UPLOAD_ERR_OK || $tmp_name === '') {
				throw new Exception('Upload failed. Please select a valid zip file.');
			}

			$ext = strtolower(pathinfo($_FILES['lang_zip']['name'], PATHINFO_EXTENSION));
			if ($ext !== 'zip') {
				throw new Exception('Only .zip files are accepted.');
			}

			$result = $this->translator->import_zip($tmp_name);
			if ($result['type'] !== 'success') {
				$this->set_response(
					array(
						'status'  => 'error',
						'message' => $result['msg'],
						'skipped' => isset($result['skipped']) ? $result['skipped'] : array(),
					),
					REST_Controller::HTTP_BAD_REQUEST
				);
				return;
			}

			$this->set_response(
				array(
					'status'   => 'success',
					'message'  => $result['msg'],
					'language' => $result['language'],
					'imported' => $result['imported'],
					'skipped'  => $result['skipped'],
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/translate/{lang}/files
	 */
	public function files_get($lang = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$status = $this->translator->get_files_status($lang);
			$files = array();
			foreach ($status as $base => $row) {
				$files[] = array_merge(array('file' => $base, 'label' => str_replace('_', ' ', $base)), $row);
			}
			$this->set_response(
				array(
					'status'   => 'success',
					'language' => $lang,
					'files'    => $files,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/translate/{lang}/files/{file}
	 */
	public function file_get($lang = null, $file = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$file = $this->_require_file($file);
			$data = $this->translator->get_file_entries($lang, $file);
			if ($data === false) {
				throw new Exception('NOT_FOUND');
			}
			$this->set_response(array('status' => 'success') + $data, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/translate/{lang}/files/{file}
	 * Body: { translations: { key: value, ... } }
	 */
	public function file_post($lang = null, $file = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$file = $this->_require_file($file);
			$input = $this->raw_json_input();
			if (! is_array($input) || ! isset($input['translations']) || ! is_array($input['translations'])) {
				throw new Exception('translations object is required');
			}

			$result = $this->translator->save_translations($lang, $file, $input['translations']);
			if ($result['type'] !== 'success') {
				throw new Exception($result['msg']);
			}

			$data = $this->translator->get_file_entries($lang, $file);
			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $result['msg'],
					'path'    => $result['path'],
				) + (is_array($data) ? $data : array()),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/translate/{lang}/files/{file}/reset
	 */
	public function file_reset_post($lang = null, $file = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$file = $this->_require_file($file);
			$result = $this->translator->reset_userdata_file($lang, $file);
			if ($result['type'] !== 'success') {
				throw new Exception($result['msg']);
			}
			$data = $this->translator->get_file_entries($lang, $file);
			$this->set_response(
				array(
					'status'  => 'success',
					'message' => $result['msg'],
				) + (is_array($data) ? $data : array()),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/translate/{lang}/search
	 */
	public function search_get($lang = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$q = $this->input->get('q');
			$only_missing = ($this->input->get('missing') === '1' || $this->input->get('missing') === 'true');
			$limit = (int) $this->input->get('limit');
			if ($limit <= 0) {
				$limit = 500;
			}

			$searched = (trim((string) $q) !== '' || $only_missing);
			$payload = array(
				'results'  => array(),
				'truncated' => false,
				'limit'    => $limit,
			);
			if ($searched) {
				$payload = $this->translator->search_language($lang, $q, $only_missing, $limit);
			}

			$this->set_response(
				array(
					'status'       => 'success',
					'language'     => $lang,
					'q'            => mb_substr(trim((string) $q), 0, 100),
					'only_missing' => $only_missing,
					'searched'     => $searched,
					'results'      => $payload['results'],
					'truncated'    => $payload['truncated'],
					'limit'        => $payload['limit'],
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * GET /api/admin/translate/{lang}/export
	 */
	public function export_get($lang = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$this->translator->export($lang);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	/**
	 * POST /api/admin/translate/{lang}/delete
	 */
	public function item_delete_post($lang = null)
	{
		try {
			$lang = $this->_require_language($lang);
			$result = $this->translator->delete_language($lang);
			if ($result['type'] !== 'success') {
				throw new Exception($result['msg']);
			}
			$this->set_response(
				array('status' => 'success', 'message' => $result['msg']),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error($e);
		}
	}

	private function _require_language($lang)
	{
		$lang = strtolower(trim((string) $lang));
		if (! $this->translator->is_valid_language_name($lang) || ! $this->translator->language_exists($lang)) {
			throw new Exception('NOT_FOUND');
		}
		return $lang;
	}

	private function _require_file($file)
	{
		$file = trim((string) $file);
		$file = preg_replace('/_lang\.php$/', '', $file);
		if ($this->translator->resolve_english_filename($file) === false) {
			throw new Exception('NOT_FOUND');
		}
		return $file;
	}

	private function _source_label($lang, array $info)
	{
		if ($lang === 'english') {
			return 'base';
		}
		if (! empty($info['has_official']) && ! empty($info['has_userdata'])) {
			return 'modified';
		}
		if (! empty($info['has_official'])) {
			return 'official';
		}
		return 'custom';
	}

	private function _error(Exception $e)
	{
		$code = $e->getMessage() === 'NOT_FOUND'
			? REST_Controller::HTTP_NOT_FOUND
			: REST_Controller::HTTP_BAD_REQUEST;
		$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
	}
}
