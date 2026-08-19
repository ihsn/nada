<?php

require APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin API — study folder files for catalog edit (Managefiles filesystem).
 *
 * Study ref `{idno}` is IDNO by default; pass query `id_format=id` for numeric `surveys.id`.
 *
 * Routed URLs (same handlers):
 *   GET    /api/admin/catalog-files/{idno}
 *   GET    /api/admin/catalog/{idno}/files
 *   GET    …/files/download/{base64(relative/path)} — also /api/admin/catalog-files/{idno}/download/…
 *   DELETE …/files/{base64(relative/path)} — same path shape as listing `base64` / legacy encode
 *   POST   …/files/{base64(relative/path)}/delete — same as DELETE when clients cannot send DELETE
 *   POST   …/files — multipart field `file` (single upload; small files)
 *   POST   …/files/commit — JSON `{ "upload_id": "…" }` after `api/uploads` resumable flow completes
 *   POST   …/files/upload — Plupload-style chunked upload (`chunk`, `chunks`, `name`, body stream)
 */
class Catalog_files extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->helper(array('file', 'date'));
		$this->load->library('acl_manager');
		$this->load->library('catalog_admin');
		$this->load->model('Catalog_model');
		$this->load->model('Managefiles_model');
		$this->load->model('Survey_resource_model');
	}

	/**
	 * List files under the study catalog folder (recursive), with resource linkage when present.
	 */
	public function index_get($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$folderpath = $this->_study_folder_path($sid);
			if ($folderpath === false || ! is_dir($folderpath)) {
				throw new Exception('SURVEY_FOLDER_NOT_FOUND');
			}

			$data = $this->Managefiles_model->get_files_recursive($folderpath, $folderpath);
			$resources = $this->Managefiles_model->get_resource_paths_array($sid);
			if (! is_array($resources)) {
				$resources = array();
			}

			foreach ($data['files'] as $key => $file) {
				$data['files'][$key]['resource'] = $this->catalog_admin->match_resource_paths($resources, $file);
			}

			$ddi_full = $this->Catalog_model->get_survey_ddi_path($sid);
			$ddi_name   = ($ddi_full && is_string($ddi_full)) ? basename($ddi_full) : null;

			$files_out = array();
			foreach ($data['files'] as $file) {
				$rel_key = isset($file['relative']) && isset($file['name'])
					? $file['relative'] . '/' . $file['name']
					: $file['name'];
				$rel_key = unix_path(trim($rel_key, '/'));

				$res = $file['resource'];
				$resource_payload = false;
				if (is_array($res)) {
					$rid = null;
					$abs_match = isset($file['path'], $file['name'])
						? unix_path($file['path'] . '/' . $file['name'])
						: '';
					if ($abs_match !== '' && is_array($resources)) {
						foreach ($resources as $resource_id => $rr) {
							if (! empty($rr['filename']) && $rr['filename'] === $abs_match) {
								$rid = (int) $resource_id;
								break;
							}
						}
					}
					$resource_payload = array(
						'resource_id' => $rid,
						'ismicro'     => ! empty($res['ismicro']),
					);
				}

				$files_out[] = array(
					'name'           => $file['name'],
					'relative'       => isset($file['relative']) ? $file['relative'] : '',
					'relative_key'   => $rel_key,
					'size'           => $file['size'],
					'size_bytes'     => isset($file['date']) ? $this->_size_bytes_from_file_row($folderpath, $file) : null,
					'fileperms'      => $file['fileperms'],
					'date'           => $file['date'],
					'base64'         => base64_encode(urlencode($rel_key)),
					'is_ddi_locked'  => ($ddi_name !== null && $ddi_name !== '' && isset($file['name']) && $file['name'] === $ddi_name),
					'resource'       => $resource_payload,
				);
			}

			array_walk($files_out, 'unix_date_to_gmt', array('date'));

			$this->set_response(
				array(
					'status'       => 'success',
					'total'        => count($files_out),
					'files'        => $files_out,
					'ddi_filename' => $ddi_name,
					'upload'       => $this->_upload_capabilities_payload($idno),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * Stream a file from the study folder (relative path, same encoding as managefiles / list `base64`).
	 */
	public function download_get($idno = null, $encoded = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('view', $sid);

			$survey_folder = $this->_study_folder_path($sid);
			if (! $survey_folder || ! is_dir($survey_folder)) {
				throw new Exception('SURVEY_FOLDER_NOT_FOUND');
			}

			$fullpath = $this->_resolve_study_file_path($sid, $survey_folder, $encoded);
			if (! is_file($fullpath)) {
				throw new Exception('FILE_NOT_FOUND');
			}

			$this->load->helper('download');
			$this->db_logger->write_log('download', $fullpath, 'external-resource');
			force_download2($fullpath);
			die();
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * DELETE /api/admin/catalog/{idno}/files/{token}
	 *
	 * Token is base64(urlencode(relative/path)) — same as managefiles / list payload `base64`.
	 */
	public function index_delete($idno = null, $encoded = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$survey_folder = $this->_study_folder_path($sid);
			if (! $survey_folder || ! is_dir($survey_folder)) {
				throw new Exception('SURVEY_FOLDER_NOT_FOUND');
			}

			$fullpath = $this->_resolve_study_file_path($sid, $survey_folder, $encoded);
			if (! is_file($fullpath)) {
				throw new Exception('FILE_NOT_FOUND');
			}

			$this->_assert_not_ddi_file($sid, $fullpath);

			$rel = $this->_relative_key_from_fullpath($survey_folder, $fullpath);
			$this->Survey_resource_model->delete_file($sid, base64_encode(urlencode($rel)));

			$this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * POST /api/admin/catalog/{idno}/files/{token}/delete — same as {@see index_delete()}.
	 */
	public function files_delete_post($idno = null, $encoded = null)
	{
		$this->index_delete($idno, $encoded);
	}

	/**
	 * Move a completed `api/uploads` resumable file into this study’s catalog folder.
	 *
	 * POST /api/admin/catalog/{idno}/files/commit?id_format=id
	 * Body: { "upload_id": "<uuid>" }
	 *
	 * Requires the upload to be completed and owned by the current user (`_upload_owner_user_id` in upload metadata).
	 */
	public function commit_resumable_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$input = $this->raw_json_input();
			if (! is_array($input) || empty($input['upload_id'])) {
				throw new Exception('UPLOAD_ID_REQUIRED');
			}
			$upload_id = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $input['upload_id']);
			if ($upload_id === '') {
				throw new Exception('INVALID_UPLOAD_ID');
			}

			$this->load->library('Resumable_upload', null, 'uploader');
			$info = $this->uploader->get_completed_upload($upload_id);
			if (! is_array($info) || empty($info['file_path'])) {
				throw new Exception('UPLOAD_NOT_COMPLETE_OR_MISSING');
			}

			$meta = isset($info['metadata']) && is_array($info['metadata']) ? $info['metadata'] : array();
			$owner = isset($meta['_upload_owner_user_id']) ? (int) $meta['_upload_owner_user_id'] : 0;
			if ($owner <= 0 || $owner !== (int) $this->get_api_user_id()) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$basename = basename((string) $info['filename']);
			if ($basename === '' || $basename === '.' || $basename === '..') {
				throw new Exception('INVALID_FILENAME');
			}

			$this->_assert_catalog_upload_extension_allowed($basename);

			$survey_folder = $this->_study_folder_path($sid);
			if (! $survey_folder || ! is_dir($survey_folder)) {
				throw new Exception('SURVEY_FOLDER_NOT_FOUND');
			}

			$dest = unix_path($survey_folder . '/' . $basename);
			$dest_dir = unix_realpath(dirname($dest));
			if ($dest_dir === false || ! $this->_path_is_within($dest_dir, $survey_folder)) {
				throw new Exception('PATH_OUTSIDE_SURVEY_FOLDER');
			}

			$this->_assert_basename_not_ddi($sid, $basename);

			$src = (string) $info['file_path'];
			if (! is_file($src)) {
				throw new Exception('SOURCE_FILE_NOT_FOUND');
			}

			if (file_exists($dest)) {
				$this->_assert_not_ddi_file($sid, $dest);
				if (! @unlink($dest)) {
					throw new Exception('REPLACE_EXISTING_FAILED');
				}
			}

			$this->uploader->relocate_file($src, $dest);

			$this->db_logger->write_log('resource-upload', $dest, 'external-resource', $sid);

			$this->uploader->delete_upload($upload_id);

			$this->set_response(
				array(
					'status'             => 'success',
					'uploaded_file_name' => $basename,
					'full_path'          => $dest,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * POST multipart upload — form field `file` (single file).
	 */
	public function index_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			if (empty($_FILES['file'])) {
				throw new Exception('NO_FILE_UPLOADED');
			}

			$result = $this->Survey_resource_model->upload_file($sid, 'file', false);

			$this->set_response(
				array(
					'status'             => 'success',
					'uploaded_file_name' => $result['file_name'],
					'full_path'          => isset($result['full_path']) ? $result['full_path'] : null,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * Plupload-compatible chunked upload (same behaviour as admin/managefiles/{sid}/process_batch_uploads).
	 */
	public function process_batch_uploads_post($idno = null)
	{
		try {
			$user = $this->api_user();
			if (! $user) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}
			if ($this->acl_manager->get_admin_catalog_repository_scope($user) === false) {
				throw new AclAccessDeniedException('ACCESS-DENIED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$survey_path = $this->Managefiles_model->get_survey_path($sid);
			if ($survey_path === '' || ! file_exists($survey_path)) {
				throw new Exception('SURVEY_FOLDER_NOT_FOUND');
			}

			$this->_run_plupload_chunks($survey_path);
			die();
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->response(array('jsonrpc' => '2.0', 'error' => array('code' => 403, 'message' => 'ACCESS_DENIED'), 'id' => 'id'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->response(
				array('jsonrpc' => '2.0', 'error' => array('code' => 400, 'message' => $e->getMessage()), 'id' => 'id'),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	// --- helpers ---

	/**
	 * Absolute study folder path — same resolver as index_get / Managefiles.
	 *
	 * @param int $sid
	 * @return string|false
	 */
	private function _study_folder_path($sid)
	{
		$path = $this->Managefiles_model->get_survey_path($sid);
		if ($path === false || $path === '') {
			return false;
		}

		return resolve_catalog_path($path);
	}

	/**
	 * True when $path_real is $root_real or a descendant (uses realpath parent walk).
	 *
	 * @param string $path_real canonical path from realpath()
	 * @param string $root_real canonical survey folder from realpath()
	 */
	private function _path_is_within($path_real, $root_real)
	{
		$path_real = rtrim(unix_path($path_real), '/');
		$root_real = rtrim(unix_path($root_real), '/');

		if ($path_real === $root_real) {
			return true;
		}

		$current = $path_real;
		while ($current !== '' && $current !== dirname($current)) {
			if ($current === $root_real) {
				return true;
			}
			$current = dirname($current);
		}

		return false;
	}

	/**
	 * File token from route arg, ?t= query, or URI tail (handles base64 "/" splits).
	 *
	 * @param mixed $encoded
	 * @return string
	 */
	private function _extract_file_token($encoded)
	{
		if (is_array($encoded)) {
			$encoded = implode('/', $encoded);
		}

		$encoded = trim((string) $encoded);
		if ($encoded !== '') {
			return rawurldecode($encoded);
		}

		$t = $this->input->get('t');
		if (is_string($t) && trim($t) !== '') {
			return trim($t);
		}

		$uri = (string) $this->uri->uri_string();
		if (preg_match('#/files/download/([^?]+)#', $uri, $matches)) {
			return rawurldecode($matches[1]);
		}

		throw new Exception('PATH_NOT_SET');
	}

	private function _upload_capabilities_payload($idno)
	{
		$qs = '';
		if ($this->input->get('id_format') !== false && $this->input->get('id_format') !== null && $this->input->get('id_format') !== '') {
			$qs = '?' . http_build_query(array('id_format' => $this->input->get('id_format')));
		}

		$uploads_base = rtrim(site_url('api/uploads'), '/') . '/';

		return array(
			'multipart_field_name' => 'file',
			'simple_post'          => true,
			'resumable_upload'     => array(
				'supported'    => true,
				'protocol'     => 'api/uploads',
				'description'  => 'POST api/uploads/init, binary chunks to api/uploads/chunk/{id}, then POST …/files/commit with upload_id',
				'init_url'     => $uploads_base . 'init',
				'chunk_url_tpl'=> $uploads_base . 'chunk/{upload_id}',
				'status_url_tpl'=> $uploads_base . 'status/{upload_id}',
				'limits_url'   => $uploads_base . 'limits',
			),
			'chunked_upload'       => array(
				'supported'  => true,
				'protocol'   => 'plupload-chunks',
				'description'=> 'Legacy Plupload: POST …/files/upload with chunk, chunks, name',
			),
			'paths' => array(
				'post_multipart' => 'api/admin/catalog/' . rawurlencode((string) $idno) . '/files' . $qs,
				'post_commit'    => 'api/admin/catalog/' . rawurlencode((string) $idno) . '/files/commit' . $qs,
				'post_chunks'    => 'api/admin/catalog/' . rawurlencode((string) $idno) . '/files/upload' . $qs,
				'alt_catalog_files'=> 'api/admin/catalog-files/' . rawurlencode((string) $idno),
			),
		);
	}

	/**
	 * Match Survey_resource / catalog allowed extensions (`config.php` allowed_resource_types).
	 *
	 * @param string $filename
	 */
	private function _assert_catalog_upload_extension_allowed($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ($ext === '') {
			throw new Exception('FILE_TYPE_NOT_ALLOWED');
		}
		$allowed = $this->config->item('allowed_resource_types');
		$allowed = is_string($allowed) ? explode(',', $allowed) : array();
		$allowed = array_map('trim', $allowed);
		$allowed = array_map('strtolower', $allowed);
		if (! in_array($ext, $allowed, true)) {
			throw new Exception('FILE_TYPE_NOT_ALLOWED');
		}
	}

	private function _size_bytes_from_file_row($survey_root, $file)
	{
		$name = isset($file['name']) ? $file['name'] : '';
		$dir  = isset($file['path']) ? $file['path'] : $survey_root;
		$p    = unix_path($dir . '/' . $name);
		if (! @is_file($p)) {
			return null;
		}

		return @filesize($p);
	}

	private function _clean_relative_filepath($str)
	{
		$str = urldecode((string) $str);
		$str = str_replace('\\', '/', $str);
		$str = unix_path($str);
		$str = trim($str, '/');
		$parts = explode('/', $str);
		$out   = array();
		foreach ($parts as $p) {
			if ($p === '' || $p === '.') {
				continue;
			}
			if ($p === '..') {
				throw new Exception('INVALID_RELATIVE_PATH');
			}
			$out[] = $p;
		}

		return implode('/', $out);
	}

	private function _resolve_study_file_path($sid, $survey_folder, $encoded)
	{
		$survey_real = resolve_catalog_path($survey_folder);
		if ($survey_real === false || ! is_dir($survey_real)) {
			throw new Exception('SURVEY_FOLDER_NOT_FOUND');
		}

		$token = $this->_extract_file_token($encoded);
		$filepath = $this->_clean_relative_filepath($this->_decode_managefiles_token($token));
		if ($filepath === '' || preg_match('#(^/|[A-Za-z]:[\\\\/])#', $filepath)) {
			throw new Exception('INVALID_RELATIVE_PATH');
		}

		$candidate = unix_path($survey_real . '/' . $filepath);
		$file_real = unix_realpath($candidate);
		if ($file_real === false || ! is_file($file_real)) {
			throw new Exception('FILE_NOT_FOUND');
		}
		if (! $this->_path_is_within($file_real, $survey_real)) {
			throw new Exception('PATH_OUTSIDE_SURVEY_FOLDER');
		}

		return $file_real;
	}

	/**
	 * Decode base64(urlencode(relative/path)) token from managefiles / list API.
	 *
	 * @param string $token
	 * @return string
	 */
	private function _decode_managefiles_token($token)
	{
		$token = trim((string) $token);
		// Query strings may turn "+" into space; restore for base64.
		$token = str_replace(' ', '+', $token);

		$bin = base64_decode($token, true);
		if ($bin === false || $bin === '') {
			$bin = base64_decode($token);
		}
		if ($bin === false || $bin === '') {
			throw new Exception('INVALID_FILE_TOKEN');
		}

		return urldecode($bin);
	}

	private function _relative_key_from_fullpath($survey_folder, $fullpath)
	{
		$survey_folder = unix_path($survey_folder);
		$fullpath      = unix_path($fullpath);

		return ltrim(str_replace($survey_folder, '', $fullpath), '/');
	}

	private function _assert_not_ddi_file($sid, $fullpath)
	{
		$ddi_full = $this->Catalog_model->get_survey_ddi_path($sid);
		if (! $ddi_full || ! is_string($ddi_full)) {
			return;
		}
		$ddi_real = unix_realpath($ddi_full);
		$f_real   = unix_realpath($fullpath);
		if ($ddi_real !== false && $f_real !== false && $ddi_real === $f_real) {
			throw new Exception('DDI_FILE_LOCKED');
		}
	}

	/**
	 * Block replacing/creating the survey DDI file by basename (path may not exist yet).
	 */
	private function _assert_basename_not_ddi($sid, $basename)
	{
		$ddi_full = $this->Catalog_model->get_survey_ddi_path($sid);
		if (! $ddi_full || ! is_string($ddi_full)) {
			return;
		}
		if (strcasecmp(basename($ddi_full), basename((string) $basename)) === 0) {
			throw new Exception('DDI_FILE_LOCKED');
		}
	}

	/**
	 * @param string $resource_folder absolute survey folder
	 */
	private function _run_plupload_chunks($resource_folder)
	{
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');

		$targetDir = unix_path($resource_folder);

		@set_time_limit(15 * 60);

		$chunk  = isset($_REQUEST['chunk']) ? (int) $_REQUEST['chunk'] : 0;
		$chunks = isset($_REQUEST['chunks']) ? (int) $_REQUEST['chunks'] : 0;
		$fileName = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';

		if ($chunks < 2 && $fileName !== '' && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext      = strrpos($fileName, '.');
			$fileName_a = $ext ? substr($fileName, 0, $ext) : $fileName;
			$fileName_b = $ext ? substr($fileName, $ext) : '';
			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b)) {
				$count++;
			}
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		if (! file_exists($targetDir)) {
			@mkdir($targetDir);
		}

		$contentType = '';
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$contentType = $_SERVER['HTTP_CONTENT_TYPE'];
		}
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = $_SERVER['CONTENT_TYPE'];
		}

		if (strpos($contentType, 'multipart') !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk === 0 ? 'wb' : 'ab');
				if ($out) {
					$in = fopen($_FILES['file']['tmp_name'], 'rb');
					if ($in) {
						while ($buff = fread($in, 4096)) {
							fwrite($out, $buff);
						}
					} else {
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					}
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else {
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
				}
			} else {
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			}
		} else {
			$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk === 0 ? 'wb' : 'ab');
			if ($out) {
				$in = fopen('php://input', 'rb');
				if ($in) {
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}
				} else {
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				}
				fclose($in);
				fclose($out);
			} else {
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			}
		}

		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}
}
