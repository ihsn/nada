<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Resumable File Upload API
 * 
 * Generic API for uploading very large files with resume capability.
 * Supports chunked uploads with automatic resume on failure.
 * 
 * For complete documentation, see: docs/uploads.md
 * 
 * @package NADA
 * @subpackage API
 */
class Uploads extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('Resumable_upload', null, 'uploader');
		// Authenticated catalog editors use resumable uploads (not necessarily site admins).
		$this->is_authenticated_or_die();
	}
	
	/**
	 * Initialize a new resumable upload
	 * 
	 * POST /api/uploads/init
	 * 
	 * Request body:
	 * {
	 *   "filename": "large-file.zip",
	 *   "total_size": 10737418240,
	 *   "total_chunks": 1024,
	 *   "chunk_size": 10485760,
	 *   "metadata": {} // optional
	 * }
	 */
	function init_post()
	{
		try {
			$this->_require_upload_starter();
			$input = $this->raw_json_input();
			
			if (empty($input)) {
				$input = $this->input->post(null, true);
			}
			
			// Validate required fields
			if (empty($input['filename'])) {
				throw new Exception("INVALID_INPUT: filename is required");
			}
			
			if (empty($input['total_size']) || !is_numeric($input['total_size'])) {
				throw new Exception("INVALID_INPUT: total_size is required and must be numeric");
			}
			
			if (empty($input['total_chunks']) || !is_numeric($input['total_chunks'])) {
				throw new Exception("INVALID_INPUT: total_chunks is required and must be numeric");
			}
			
			if (empty($input['chunk_size']) || !is_numeric($input['chunk_size'])) {
				throw new Exception("INVALID_INPUT: chunk_size is required and must be numeric");
			}
			
			$metadata = isset($input['metadata']) ? $input['metadata'] : array();
			if (! is_array($metadata)) {
				$metadata = array();
			}
			$uid = $this->get_api_user_id();
			if ($uid) {
				$metadata['_upload_owner_user_id'] = (int) $uid;
			}

			// Initialize upload
			$upload_id = $this->uploader->init_upload(
				$input['filename'],
				(int)$input['total_size'],
				(int)$input['total_chunks'],
				(int)$input['chunk_size'],
				$metadata
			);
			
			$response = array(
				'status' => 'success',
				'upload_id' => $upload_id,
				'upload_url' => site_url('api/uploads/chunk/' . $upload_id),
				'status_url' => site_url('api/uploads/status/' . $upload_id),
				'max_chunk_size' => $this->uploader->get_max_chunk_size()
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Upload a chunk
	 * 
	 * POST /api/uploads/chunk/{upload_id}
	 * POST /api/uploads/chunk (for auto-init with first chunk)
	 * 
	 * Parameters can be provided via (priority order):
	 * 1. HTTP Headers: X-Upload-Chunk-Number, X-Upload-Filename, X-Upload-Total-Size, etc.
	 * 2. JSON Body: {"chunk_number": 0, "filename": "...", ...} (if Content-Type: application/json)
	 * 3. POST Data: multipart/form-data or application/x-www-form-urlencoded
	 * 
	 * Chunk data can be sent as:
	 * - Raw binary: Content-Type: application/octet-stream (body contains binary data)
	 * - Multipart: Content-Type: multipart/form-data (chunk in 'chunk' field)
	 * 
	 * If upload_id is not provided and chunk_number is 0, will auto-initialize the upload
	 */
	function chunk_post($upload_id = null)
	{
		try {
			@set_time_limit(0);
			@ini_set('max_execution_time', '0');

			$chunk_number = $this->get_parameter('chunk_number', 'X-Upload-Chunk-Number');
			
			if ($chunk_number === null || $chunk_number === '' || !is_numeric($chunk_number)) {
				throw new Exception("INVALID_INPUT: chunk_number is required");
			}
			
			$chunk_number = (int)$chunk_number;
			
			// Get chunk_size from client (required for validation)
			$chunk_size = $this->get_parameter('chunk_size', 'X-Upload-Chunk-Size');
			
			if ($chunk_size === null || $chunk_size === '' || !is_numeric($chunk_size)) {
				throw new Exception("INVALID_INPUT: chunk_size is required");
			}
			
			$chunk_size = (int)$chunk_size;
			
			// Auto-initialize if upload_id not provided and this is the first chunk
			if (empty($upload_id) && $chunk_number == 0) {
				$upload_id = $this->auto_init_upload();
			}
			
			if (empty($upload_id)) {
				throw new Exception("INVALID_INPUT: upload_id is required (or provide init parameters for first chunk)");
			}
			$this->_require_upload_owner($upload_id);
			
			// Read chunk data from request body
			// Check Content-Type to determine how to read chunk data
			$content_type = $this->input->server('CONTENT_TYPE');
			$is_multipart = $content_type && strpos(strtolower($content_type), 'multipart/form-data') !== false;
			$is_json = $content_type && strpos(strtolower($content_type), 'application/json') !== false;
			
			$chunk_data = null;
			
			if ($is_multipart) {
				// Multipart: chunk is in $_FILES
				if (isset($_FILES['chunk']) && is_uploaded_file($_FILES['chunk']['tmp_name'])) {
					$chunk_data = file_get_contents($_FILES['chunk']['tmp_name']);
				}
			} elseif (!$is_json) {
				// Raw binary: read from raw_input_stream (application/octet-stream)
				$chunk_data = $this->input->raw_input_stream;
			}
			
			if (empty($chunk_data)) {
				throw new Exception("INVALID_INPUT: chunk data is required");
			}
			
			// Upload chunk
			$result = $this->uploader->upload_chunk($upload_id, $chunk_number, $chunk_data, $chunk_size);
			
			$response = array(
				'status' => 'success',
				'upload_status' => $result['status'],
				'uploaded_chunks' => $result['uploaded_chunks'],
				'total_chunks' => $result['total_chunks'],
				'progress' => round($result['progress'] * 100, 2)
			);
			
			// If complete, add file info
			if ($result['status'] == 'complete') {
				$metadata = $this->uploader->get_upload_metadata($upload_id);
				$final_file = $this->uploader->get_final_file_path($upload_id);
				
				$response['file_path'] = $final_file;
				$response['filename'] = $metadata['filename'];
				$response['file_size'] = filesize($final_file);
				$response['download_url'] = site_url('api/uploads/download/' . $upload_id);
			}
			
			// If this was auto-initialized, include upload_id in response
			if ($chunk_number == 0) {
				$response['upload_id'] = $upload_id;
				$response['status_url'] = site_url('api/uploads/status/' . $upload_id);
			}
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Get upload status
	 * 
	 * GET /api/uploads/status/{upload_id}
	 */
	function status_get($upload_id = null)
	{
		try {
			if (empty($upload_id)) {
				throw new Exception("INVALID_INPUT: upload_id is required");
			}
			$this->_require_upload_owner($upload_id);
			
			$metadata = $this->uploader->get_upload_metadata($upload_id);
			
			if (!$metadata) {
				throw new Exception("UPLOAD_NOT_FOUND");
			}
			
			$uploaded_chunks = $this->uploader->get_uploaded_chunks($upload_id);
			$progress = count($uploaded_chunks) / $metadata['total_chunks'];
			
			$response = array(
				'status' => 'success',
				'upload_id' => $upload_id,
				'filename' => $metadata['filename'],
				'total_size' => $metadata['total_size'],
				'total_chunks' => $metadata['total_chunks'],
				'uploaded_chunks' => $uploaded_chunks,
				'progress' => round($progress * 100, 2),
				'upload_status' => $metadata['status'],
				'created_at' => $metadata['created_at'],
				'updated_at' => $metadata['updated_at']
			);
			
			if ($metadata['status'] == 'completed') {
				$final_file = $this->uploader->get_final_file_path($upload_id);
				
				$response['completed_at'] = $metadata['completed_at'];
				$response['file_path'] = $final_file;
				$response['file_size'] = filesize($final_file);
				$response['download_url'] = site_url('api/uploads/download/' . $upload_id);
			}
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Download completed file
	 * 
	 * GET /api/uploads/download/{upload_id}
	 */
	function download_get($upload_id = null)
	{
		try {
			if (empty($upload_id)) {
				throw new Exception("INVALID_INPUT: upload_id is required");
			}
			$this->_require_upload_owner($upload_id);
			
			$metadata = $this->uploader->get_upload_metadata($upload_id);
			
			if (!$metadata) {
				throw new Exception("UPLOAD_NOT_FOUND");
			}
			
			if ($metadata['status'] != 'completed') {
				throw new Exception("UPLOAD_NOT_COMPLETED");
			}
			
			$final_file = $this->uploader->get_final_file_path($upload_id);
			
			if (!file_exists($final_file)) {
				throw new Exception("FILE_NOT_FOUND");
			}
			
			// Set headers for file download
			$this->load->helper('download');
			force_download2($final_file);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Cancel/Delete upload
	 * 
	 * DELETE /api/uploads/{upload_id}
	 */
	function index_delete($upload_id = null)
	{
		try {
			if (empty($upload_id)) {
				throw new Exception("INVALID_INPUT: upload_id is required");
			}
			$this->_require_upload_owner($upload_id);
			
			$result = $this->uploader->delete_upload($upload_id);
			
			if (!$result) {
				throw new Exception("FAILED_TO_DELETE_UPLOAD");
			}
			
			$response = array(
				'status' => 'success',
				'message' => 'Upload deleted successfully'
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

    //alias for index_delete
    function delete_delete($upload_id = null){
        return $this->index_delete($upload_id);
    }

	/**
	 * POST /api/uploads/delete/{upload_id}
	 * Alias for DELETE when that verb is blocked.
	 */
	function delete_post($upload_id = null)
	{
		return $this->index_delete($upload_id);
	}

	/**
	 * Cleanup expired uploads
	 * 
	 * POST /api/uploads/cleanup
	 * 
	 * Removes expired incomplete uploads based on expiry_hours configuration
	 */
	function cleanup_post()
	{
		try {
			$owner_user_id = $this->is_admin() ? null : (int) $this->get_api_user_id();
			$stats = $this->uploader->cleanup_expired_uploads($owner_user_id);
			
			$response = array(
				'status' => 'success',
				'checked' => $stats['checked'],
				'deleted' => $stats['deleted'],
				'errors' => $stats['errors'],
				'message' => 'Cleanup completed successfully'
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * List active uploads (optional)
	 * 
	 * GET /api/uploads
	 */
	function index_get()
	{
		try {
			$uploads = $this->uploader->list_uploads();
			if (!$this->is_admin()) {
				$uid = (int) $this->get_api_user_id();
				$own = array();
				foreach ($uploads as $row) {
					if (empty($row['upload_id'])) {
						continue;
					}
					$meta = $this->uploader->get_upload_metadata($row['upload_id']);
					$owner = ($meta && isset($meta['_upload_owner_user_id'])) ? (int) $meta['_upload_owner_user_id'] : 0;
					if ($owner === $uid) {
						$own[] = $row;
					}
				}
				$uploads = $own;
			}
			
			$response = array(
				'status' => 'success',
				'total' => count($uploads),
				'uploads' => $uploads
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	/**
	 * Extract error code from exception message
	 * 
	 * @param string $message
	 * @return string
	 */
	private function get_error_code($message)
	{
		if (strpos($message, ':') !== false) {
			return trim(explode(':', $message)[0]);
		}
		
		// Map common messages to error codes
		$error_map = array(
			'UPLOAD_NOT_FOUND' => 'UPLOAD_NOT_FOUND',
			'UPLOAD_COMPLETED' => 'UPLOAD_COMPLETED',
			'UPLOAD_CANCELLED' => 'UPLOAD_CANCELLED',
			'CHUNK_OUT_OF_RANGE' => 'CHUNK_OUT_OF_RANGE',
			'CHUNK_OUT_OF_ORDER' => 'CHUNK_OUT_OF_ORDER',
			'CHUNK_SIZE_MISMATCH' => 'CHUNK_SIZE_MISMATCH',
			'FILE_TYPE_NOT_ALLOWED' => 'FILE_TYPE_NOT_ALLOWED',
			'FILE_TOO_LARGE' => 'FILE_TOO_LARGE',
			'INVALID_INPUT' => 'INVALID_INPUT',
			'ACCESS-DENIED' => 'ACCESS-DENIED'
		);
		
		foreach ($error_map as $code => $pattern) {
			if (stripos($message, $code) !== false) {
				return $code;
			}
		}
		
		return 'UNKNOWN_ERROR';
	}
	
	/**
	 * Get parameter value with priority: Headers → Raw JSON → POST
	 * 
	 * @param string $param_name Parameter name (for JSON/POST)
	 * @param string $header_name Header name (e.g., 'X-Upload-Filename')
	 * @return mixed Parameter value or null if not found
	 */
	private function get_parameter($param_name, $header_name = null)
	{
		$value = null;
		
		// Priority 1: HTTP Headers
		if ($header_name) {
			$header_value = $this->input->server('HTTP_' . str_replace('-', '_', strtoupper($header_name)));
			if ($header_value !== null && $header_value !== '') {
				$value = $header_value;
			}
		}
		
		// Priority 2: Raw JSON body (if Content-Type is application/json)
		// Note: Only parse JSON if Content-Type indicates JSON, to avoid interfering with binary data
		if ($value === null) {
			$content_type = $this->input->server('CONTENT_TYPE');
			if ($content_type && strpos(strtolower($content_type), 'application/json') !== false) {
				try {
					$json_input = $this->raw_json_input();
					if ($json_input && isset($json_input[$param_name])) {
						$value = $json_input[$param_name];
					}
				} catch (Exception $e) {
					// If JSON parsing fails, continue to next priority
				}
			}
		}
		
		// Priority 3: POST data (works with multipart/form-data and application/x-www-form-urlencoded)
		if ($value === null) {
			$post_value = $this->input->post($param_name);
			if ($post_value !== null && $post_value !== '') {
				$value = $post_value;
			}
		}
		
		return $value;
	}
	
	/**
	 * Auto-initialize upload from chunk request parameters
	 * 
	 * Parameters read from: Headers → Raw JSON → POST
	 * 
	 * @return string upload_id
	 */
	private function auto_init_upload()
	{
		$this->_require_upload_starter();
		$filename = $this->get_parameter('filename', 'X-Upload-Filename');
		$total_size = $this->get_parameter('total_size', 'X-Upload-Total-Size');
		$total_chunks = $this->get_parameter('total_chunks', 'X-Upload-Total-Chunks');
		$chunk_size = $this->get_parameter('chunk_size', 'X-Upload-Chunk-Size');
		
		// Validate required fields
		if (empty($filename)) {
			throw new Exception("INVALID_INPUT: filename is required for auto-initialization");
		}
		
		if (empty($total_size) || !is_numeric($total_size)) {
			throw new Exception("INVALID_INPUT: total_size is required and must be numeric for auto-initialization");
		}
		
		if (empty($total_chunks) || !is_numeric($total_chunks)) {
			throw new Exception("INVALID_INPUT: total_chunks is required and must be numeric for auto-initialization");
		}
		
		if (empty($chunk_size) || !is_numeric($chunk_size)) {
			throw new Exception("INVALID_INPUT: chunk_size is required and must be numeric for auto-initialization");
		}
		
		$metadata = array();
		$metadata_input = $this->get_parameter('metadata', 'X-Upload-Metadata');
		
		if (!empty($metadata_input)) {
			if (is_string($metadata_input)) {
				$metadata = json_decode($metadata_input, true);
				if ($metadata === null) {
					$metadata = array();
				}
			} elseif (is_array($metadata_input)) {
				$metadata = $metadata_input;
			}
		}

		$uid = $this->get_api_user_id();
		if ($uid) {
			$metadata['_upload_owner_user_id'] = (int) $uid;
		}

		// Initialize upload
		$upload_id = $this->uploader->init_upload(
			$filename,
			(int)$total_size,
			(int)$total_chunks,
			(int)$chunk_size,
			$metadata
		);
		
		return $upload_id;
	}


	/**
	 * 
	 * Return server limits for uploads
	 * 
	 */
	function limits_get()
	{
		try {
			$limits = array(
				'max_file_size' => $this->uploader->get_max_file_size(),
				'max_chunk_size' => $this->uploader->get_max_chunk_size(),
				'max_chunk_size_h' => $this->uploader->get_max_chunk_size(true), // max allowed by PHP settings
				'upload_expiry_hours' => $this->uploader->get_expiry_hours(),
				'allowed_file_types' => $this->uploader->get_allowed_file_types(),
				//php post and upload limits (for reference)
				'post_max_size' => $this->uploader->get_post_max_size(),
				'upload_max_filesize' => $this->uploader->get_upload_max_filesize()
			);
			
			$response = array(
				'status' => 'success',
				'limits' => $limits
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$output = array(
				'status' => 'error',
				'error_code' => $this->get_error_code($e->getMessage()),
				'message' => $e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * Starting an upload needs a catalog, table, deposit, or configuration permission.
	 * A signed-in account with none of those cannot create one.
	 */
	private function _require_upload_starter()
	{
		if ($this->_caller_may_start_upload()) {
			return;
		}

		throw new Exception('ACCESS-DENIED');
	}

	/**
	 * Status, download, chunk, and delete are limited to the upload owner.
	 * A site admin may use any upload.
	 */
	private function _require_upload_owner($upload_id)
	{
		if ($this->is_admin()) {
			return;
		}

		$metadata = $this->uploader->get_upload_metadata($upload_id);
		if (!$metadata) {
			throw new Exception('UPLOAD_NOT_FOUND');
		}

		$owner = isset($metadata['_upload_owner_user_id']) ? (int) $metadata['_upload_owner_user_id'] : 0;
		if ($owner < 1 || $owner !== (int) $this->get_api_user_id()) {
			throw new Exception('ACCESS-DENIED');
		}
	}

	/**
	 * @return bool
	 */
	private function _caller_may_start_upload()
	{
		if ($this->is_admin()) {
			return true;
		}

		$user = $this->ion_auth->get_user((int) $this->get_api_user_id());
		if (empty($user) || empty($user->id)) {
			return false;
		}

		$global = array(
			array('study', 'edit'),
			array('study', 'create'),
			array('configurations', 'edit'),
			array('table', 'edit'),
			array('datadeposit', 'edit'),
		);
		foreach ($global as $pair) {
			if ($this->acl_manager->user_has_access($pair[0], $pair[1], $user)) {
				return true;
			}
		}

		$roles = $this->acl_manager->get_user_roles($user->id);
		foreach ($this->acl_manager->get_roles_permissions(array_keys($roles)) as $perm) {
			$resource = isset($perm['resource']) ? (string) $perm['resource'] : '';
			$privileges = isset($perm['permissions']) ? (array) $perm['permissions'] : array();
			if (preg_match('/^.+-study$/', $resource)
				&& (in_array('edit', $privileges, true) || in_array('create', $privileges, true))) {
				return true;
			}
		}

		$uid = (int) $user->id;
		$slugs = array_merge(
			$this->acl_manager->repositories_acl_repository_slugs_for_user_permission_prefix($uid, 'study_'),
			$this->acl_manager->repositories_acl_repository_slugs_for_user_permission_prefix($uid, 'collection_')
		);
		foreach (array_unique($slugs) as $slug) {
			if ($this->acl_manager->user_has_access('study', 'edit', $user, $slug)
				|| $this->acl_manager->user_has_access('study', 'create', $user, $slug)) {
				return true;
			}
		}

		return false;
	}
}

