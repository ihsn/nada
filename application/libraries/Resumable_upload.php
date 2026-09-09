<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resumable Upload Library
 * 
 * Handles chunked file uploads with resume capability
 * Uses file-based metadata storage
 */
class Resumable_upload {
	
	private $temp_path;
	private $max_size;
	private $chunk_size;
	private $expiry_hours;
	private $allowed_types;
	private $max_chunk_size;
	
	public function __construct()
	{
		$CI =& get_instance();
		$CI->config->load('uploads');
		
		$this->temp_path = $CI->config->item('resumable_upload_temp_path');
		$this->max_size = $CI->config->item('resumable_upload_max_size');
		$this->chunk_size = $CI->config->item('resumable_upload_chunk_size');
		$this->expiry_hours = $CI->config->item('resumable_upload_expiry_hours');
		$this->allowed_types = $CI->config->item('allowed_resource_types');
		
		// Calculate maximum chunk size based on PHP limits
		$this->max_chunk_size = $this->get_php_max_chunk_size();
		
		if (!$this->temp_path || trim($this->temp_path) == '') {
			throw new Exception("RESUMABLE_UPLOAD_TEMP_PATH_NOT_SET");
		}
		
		// If not absolute path, use relative to FCPATH
		if (!file_exists($this->temp_path)) {
			$this->temp_path = FCPATH . $this->temp_path;
		}
		
		$this->temp_path = unix_path($this->temp_path);
		
		// Ensure temp directory exists
		if (!file_exists($this->temp_path)) {
			if (!@mkdir($this->temp_path, 0755, true)) {
				throw new Exception("FAILED_TO_CREATE_TEMP_DIRECTORY: " . $this->temp_path);
			}
		}
	}
	
	/**
	 * Initialize a new upload session
	 * 
	 * @param string $filename Original filename
	 * @param int $total_size Total file size in bytes
	 * @param int $total_chunks Total number of chunks
	 * @param int $chunk_size Size of each chunk in bytes
	 * @param array $metadata Optional metadata
	 * @return string upload_id
	 */
	public function init_upload($filename, $total_size, $total_chunks, $chunk_size, $metadata = array())
	{
		// Validate inputs
		if (empty($filename)) {
			throw new Exception("INVALID_INPUT: filename is required");
		}
		
		if ($total_size <= 0) {
			throw new Exception("INVALID_INPUT: total_size must be positive");
		}
		
		if ($total_chunks <= 0) {
			throw new Exception("INVALID_INPUT: total_chunks must be positive");
		}
		
		if ($chunk_size <= 0) {
			throw new Exception("INVALID_INPUT: chunk_size must be positive");
		}
		
		// Validate chunk size against PHP limits
		if ($chunk_size > $this->max_chunk_size) {
			throw new Exception("CHUNK_SIZE_EXCEEDS_PHP_LIMIT: Maximum chunk size is " . $this->format_bytes($this->max_chunk_size) . " (PHP post_max_size: " . ini_get('post_max_size') . ", upload_max_filesize: " . ini_get('upload_max_filesize') . ")");
		}
		
		// Validate file size limit
		if ($this->max_size > 0 && $total_size > $this->max_size) {
			throw new Exception("FILE_TOO_LARGE: Maximum size is " . $this->max_size . " bytes");
		}
		
		// Store original filename before sanitization
		$original_filename = basename($filename);
		
		// Sanitize filename for safe storage
		$filename = $this->sanitize_filename($filename);
		
		// Validate file type (check per-upload restriction first, then global)
		if (!$this->validate_file_type($filename, $metadata)) {
			throw new Exception("FILE_TYPE_NOT_ALLOWED");
		}
		
		// Generate upload ID (UUID v4)
		$upload_id = $this->generate_upload_id();
		
		// Create upload directory (chunks are appended to a growing file; no per-chunk files)
		$upload_path = $this->get_upload_path($upload_id);
		
		if (!@mkdir($upload_path, 0755, true)) {
			throw new Exception("FAILED_TO_CREATE_UPLOAD_DIRECTORY");
		}
		
		// Create metadata
		$metadata_data = array(
			'upload_id' => $upload_id,
			'filename' => $filename,
			'original_filename' => $original_filename,
			'total_size' => (int)$total_size,
			'total_chunks' => (int)$total_chunks,
			'chunk_size' => (int)$chunk_size,
			'created_at' => time(),
			'updated_at' => time(),
			'completed_at' => null,
			'status' => 'in_progress',
			'uploaded_chunks' => array(),
			'append_mode' => true,
			'assembled_bytes' => 0,
			'metadata' => $metadata
		);
		
		// Save metadata
		$this->save_upload_metadata($upload_id, $metadata_data);
		
		// Automatic cleanup of expired uploads (incremental)
		// Delete more files per call to clean up faster
		$this->cleanup_expired_uploads_auto(5);
		
		return $upload_id;
	}
	
	/**
	 * Get upload metadata
	 * 
	 * @param string $upload_id
	 * @return array|false Metadata array or false if not found
	 */
	public function get_upload_metadata($upload_id)
	{
		$metadata = $this->read_upload_metadata_raw($upload_id);
		if (!$metadata) {
			return false;
		}

		$this->heal_completed_status($upload_id, $metadata);
		
		return $metadata;
	}
	
	/**
	 * Save upload metadata
	 * 
	 * @param string $upload_id
	 * @param array $metadata
	 * @return bool
	 */
	public function save_upload_metadata($upload_id, $metadata)
	{
		$metadata_path = $this->get_metadata_path($upload_id);
		$upload_path = dirname($metadata_path);
		
		// Ensure directory exists
		if (!file_exists($upload_path)) {
			if (!@mkdir($upload_path, 0755, true)) {
				throw new Exception("FAILED_TO_CREATE_UPLOAD_DIRECTORY");
			}
		}
		
		$metadata_json = json_encode($metadata, JSON_PRETTY_PRINT);
		
		// Atomic write: write to temp file, then rename
		$temp_file = $metadata_path . '.tmp';
		if (@file_put_contents($temp_file, $metadata_json) === false) {
			throw new Exception("FAILED_TO_WRITE_METADATA");
		}
		
		if (!@rename($temp_file, $metadata_path)) {
			@unlink($temp_file);
			throw new Exception("FAILED_TO_SAVE_METADATA");
		}
		
		return true;
	}
	
	/**
	 * Upload a chunk
	 * 
	 * @param string $upload_id
	 * @param int $chunk_number
	 * @param string $chunk_data Binary chunk data
	 * @param int $client_chunk_size Chunk size as reported by client (for validation)
	 * @return array Status information
	 */
	public function upload_chunk($upload_id, $chunk_number, $chunk_data, $client_chunk_size = null)
	{
		$this->lift_execution_limits();

		$lock = $this->lock_upload($upload_id);
		try {
			// Re-read after lock so concurrent requests cannot clobber metadata
			$metadata = $this->read_upload_metadata_raw($upload_id);
			if (!$metadata) {
				throw new Exception("UPLOAD_NOT_FOUND");
			}

			if ($metadata['status'] == 'completed') {
				return $this->chunk_status_payload($metadata);
			}

			if ($metadata['status'] == 'cancelled') {
				throw new Exception("UPLOAD_CANCELLED");
			}

			if ($chunk_number < 0 || $chunk_number >= $metadata['total_chunks']) {
				throw new Exception("CHUNK_OUT_OF_RANGE");
			}

			$actual_size = strlen($chunk_data);

			$expected_size = $metadata['chunk_size'];
			if ($chunk_number == $metadata['total_chunks'] - 1) {
				$expected_size = $metadata['total_size'] - ($chunk_number * $metadata['chunk_size']);
			}

			if ($client_chunk_size !== null && $client_chunk_size != $actual_size) {
				throw new Exception("CHUNK_SIZE_MISMATCH: Client reported " . $client_chunk_size . " bytes, but actual data is " . $actual_size . " bytes");
			}

			$append_mode = $this->is_append_mode($metadata);

			if ($actual_size > $expected_size) {
				throw new Exception("CHUNK_SIZE_MISMATCH: Expected max " . $expected_size . " bytes, got " . $actual_size);
			}

			if ($append_mode) {
				if ($actual_size != $expected_size) {
					throw new Exception("CHUNK_SIZE_MISMATCH: Expected exactly " . $expected_size . " bytes for chunk " . $chunk_number . ", got " . $actual_size);
				}
			} elseif ($chunk_number < $metadata['total_chunks'] - 1) {
				$min_size = (int)($expected_size * 0.9);
				if ($actual_size < $min_size) {
					throw new Exception("CHUNK_SIZE_MISMATCH: Expected at least " . $min_size . " bytes for chunk " . $chunk_number . ", got " . $actual_size);
				}
			}

			$uploaded = $this->normalized_uploaded_chunks($upload_id, $metadata);
			$next_expected = $this->next_expected_chunk($uploaded);

			if (in_array($chunk_number, $uploaded, true)) {
				$metadata['uploaded_chunks'] = $uploaded;
				return $this->chunk_status_payload($metadata);
			}

			if ($append_mode && $chunk_number !== $next_expected) {
				throw new Exception("CHUNK_OUT_OF_ORDER: expected chunk " . $next_expected . ", got " . $chunk_number . ". Chunks must be uploaded sequentially.");
			}

			if ($append_mode) {
				$this->append_chunk_to_partial($upload_id, $chunk_data, $metadata);
			} else {
				$this->write_legacy_chunk_file($upload_id, $chunk_number, $chunk_data);
			}

			$uploaded[] = $chunk_number;
			sort($uploaded, SORT_NUMERIC);
			$metadata['uploaded_chunks'] = $uploaded;
			$metadata['updated_at'] = time();

			$is_complete = count($uploaded) == $metadata['total_chunks'];
			if ($is_complete) {
				$ignore_abort = ignore_user_abort(true);
				try {
					$this->finalize_assembled_file($upload_id, $metadata);
					$metadata['status'] = 'completed';
					$metadata['completed_at'] = time();
					$this->save_upload_metadata($upload_id, $metadata);
					$this->delete_chunks_directory($upload_id);
				} finally {
					ignore_user_abort($ignore_abort);
				}
			} else {
				$this->save_upload_metadata($upload_id, $metadata);
			}

			if ($chunk_number == 0 || ($chunk_number % 10 == 0)) {
				$this->cleanup_expired_uploads_auto(5);
			}

			return $this->chunk_status_payload($metadata);
		} finally {
			$this->unlock_upload($lock);
		}
	}
	
	/**
	 * Get list of uploaded chunks
	 * 
	 * @param string $upload_id
	 * @return array Array of chunk numbers
	 */
	public function get_uploaded_chunks($upload_id)
	{
		$metadata = $this->read_upload_metadata_raw($upload_id);
		return $this->normalized_uploaded_chunks($upload_id, $metadata ? $metadata : array());
	}
	
	/**
	 * Check if upload is complete
	 * 
	 * @param string $upload_id
	 * @return bool
	 */
	public function is_upload_complete($upload_id)
	{
		$metadata = $this->get_upload_metadata($upload_id);
		if (!$metadata) {
			return false;
		}

		if ($metadata['status'] == 'completed') {
			return true;
		}
		
		$uploaded_chunks = $this->normalized_uploaded_chunks($upload_id, $metadata);
		
		return count($uploaded_chunks) == $metadata['total_chunks'];
	}
	
	/**
	 * Combine leftover per-chunk files into the final file (legacy uploads only).
	 *
	 * New uploads append into a partial file as chunks arrive.
	 * 
	 * @param string $upload_id
	 * @return string Path to final file
	 */
	public function combine_chunks($upload_id)
	{
		$this->lift_execution_limits();

		$metadata = $this->get_upload_metadata($upload_id);
		if (!$metadata) {
			throw new Exception("UPLOAD_NOT_FOUND");
		}

		$this->stream_combine_legacy_chunks($upload_id, $metadata);

		$metadata['status'] = 'completed';
		$metadata['completed_at'] = time();
		$metadata['assembled_bytes'] = (int) $metadata['total_size'];
		$this->save_upload_metadata($upload_id, $metadata);
		$this->delete_chunks_directory($upload_id);

		return $this->get_final_file_path($upload_id, $metadata['filename']);
	}

	/**
	 * Move or duplicate a completed upload onto $destination without reading
	 * the whole file into memory. Prefers rename (when $prefer_move), then
	 * hardlink, then a streamed copy.
	 *
	 * @param string $source
	 * @param string $destination
	 * @param bool $prefer_move When true, rename the source onto $destination
	 * @return bool
	 */
	public function relocate_file($source, $destination, $prefer_move = true)
	{
		$this->lift_execution_limits();

		if (!is_file($source)) {
			throw new Exception("SOURCE_FILE_NOT_FOUND");
		}

		$destination = unix_path($destination);
		$dest_dir = dirname($destination);
		if (!is_dir($dest_dir)) {
			if (!@mkdir($dest_dir, 0755, true)) {
				throw new Exception("FAILED_TO_CREATE_DESTINATION_DIRECTORY");
			}
		}

		if (unix_path($source) === $destination) {
			return true;
		}

		if ($prefer_move && @rename($source, $destination)) {
			return true;
		}

		if (@link($source, $destination)) {
			return true;
		}

		if ($this->stream_copy_file($source, $destination)) {
			return true;
		}

		throw new Exception("FILE_RELOCATE_FAILED");
	}
	
	/**
	 * Delete upload (cleanup)
	 * 
	 * @param string $upload_id
	 * @return bool
	 */
	public function delete_upload($upload_id)
	{
		$upload_path = $this->get_upload_path($upload_id);
		
		if (!file_exists($upload_path)) {
			return true; // Already deleted
		}
		
		return $this->delete_directory($upload_path);
	}
	
	/**
	 * List all active uploads
	 * 
	 * @return array Array of upload information
	 */
	public function list_uploads()
	{
		$uploads = array();
		
		if (!file_exists($this->temp_path)) {
			return $uploads;
		}
		
		$dirs = @scandir($this->temp_path);
		
		if ($dirs === false) {
			return $uploads;
		}
		
		foreach ($dirs as $dir) {
			if ($dir == '.' || $dir == '..') {
				continue;
			}
			
			$upload_path = unix_path($this->temp_path . '/' . $dir);
			if (!is_dir($upload_path)) {
				continue;
			}
			
			$metadata = $this->get_upload_metadata($dir);
			if ($metadata) {
				$uploaded_chunks = $this->get_uploaded_chunks($dir);
				$progress = count($uploaded_chunks) / $metadata['total_chunks'];
				
				// Calculate expiry time: for completed uploads use completed_at, otherwise updated_at
				$check_time = ($metadata['status'] == 'completed' && isset($metadata['completed_at'])) 
					? $metadata['completed_at'] 
					: $metadata['updated_at'];
				$expires_at = $check_time + ($this->expiry_hours * 3600);
				
				$uploads[] = array(
					'upload_id' => $dir,
					'filename' => $metadata['filename'],
					'original_filename' => $metadata['original_filename'],
					'total_size' => $metadata['total_size'],
					'total_size_formatted' => $this->format_bytes($metadata['total_size']),
					'progress' => round($progress * 100, 2),
					'status' => $metadata['status'],
					'created_at' => date('Y-m-d H:i:s', $metadata['created_at']),
					'updated_at' => date('Y-m-d H:i:s', $metadata['updated_at']),
					'expires_at' => date('Y-m-d H:i:s', $expires_at)
				);
			}
		}
		
		return $uploads;
	}
	
	/**
	 * Automatic cleanup of expired uploads (incremental)
	 * 
	 * Uses opendir/readdir to process a limited number of expired uploads
	 * without loading all directory entries into memory. Designed to be called
	 * automatically during normal operations (e.g., when upload starts/completes).
	 * 
	 * @param int $max_deletions Maximum number of expired uploads to delete (default: 5)
	 * @return array Statistics
	 */
	public function cleanup_expired_uploads_auto($max_deletions = 5)
	{
		$stats = array(
			'checked' => 0,
			'deleted' => 0,
			'errors' => 0
		);
		
		if (!file_exists($this->temp_path)) {
			return $stats;
		}
		
		$expiry_time = time() - ($this->expiry_hours * 3600);
		$handle = @opendir($this->temp_path);
		
		if ($handle === false) {
			return $stats;
		}
		
		// Read entries one at a time using stream-based approach
		while (($dir = readdir($handle)) !== false) {
			// Stop if we've deleted enough
			if ($stats['deleted'] >= $max_deletions) {
				break;
			}
			
			if ($dir == '.' || $dir == '..') {
				continue;
			}
			
			$upload_path = unix_path($this->temp_path . '/' . $dir);
			
			if (!is_dir($upload_path)) {
				continue;
			}
			
			$stats['checked']++;
			
			// Check metadata
			$metadata_path = unix_path($upload_path . '/metadata.json');
			if (!file_exists($metadata_path)) {
				// No metadata, delete if old enough
				if (@filemtime($upload_path) < $expiry_time) {
					if ($this->delete_directory($upload_path)) {
						$stats['deleted']++;
					} else {
						$stats['errors']++;
					}
				}
				continue;
			}
			
			$metadata = $this->get_upload_metadata($dir);
			if (!$metadata) {
				continue;
			}
			
			// Delete expired uploads (complete or incomplete)
			// For completed uploads, use completed_at if available, otherwise updated_at
			$check_time = ($metadata['status'] == 'completed' && isset($metadata['completed_at'])) 
				? $metadata['completed_at'] 
				: $metadata['updated_at'];
			
			if ($check_time < $expiry_time) {
				if ($this->delete_upload($dir)) {
					$stats['deleted']++;
				} else {
					$stats['errors']++;
				}
			}
		}
		
		closedir($handle);
		return $stats;
	}
	
	/**
	 * Cleanup expired uploads
	 * 
	 * @return array Statistics
	 */
	public function cleanup_expired_uploads()
	{
		$stats = array(
			'checked' => 0,
			'deleted' => 0,
			'errors' => 0
		);
		
		if (!file_exists($this->temp_path)) {
			return $stats;
		}
		
		$expiry_time = time() - ($this->expiry_hours * 3600);
		$dirs = @scandir($this->temp_path);
		
		if ($dirs === false) {
			return $stats;
		}
		
		foreach ($dirs as $dir) {
			if ($dir == '.' || $dir == '..') {
				continue;
			}
			
			$upload_path = unix_path($this->temp_path . '/' . $dir);
			
			if (!is_dir($upload_path)) {
				continue;
			}
			
			$stats['checked']++;
			
			// Check metadata
			$metadata_path = unix_path($upload_path . '/metadata.json');
			if (!file_exists($metadata_path)) {
				// No metadata, delete if old enough
				if (@filemtime($upload_path) < $expiry_time) {
					if ($this->delete_directory($upload_path)) {
						$stats['deleted']++;
					} else {
						$stats['errors']++;
					}
				}
				continue;
			}
			
			$metadata = $this->get_upload_metadata($dir);
			if (!$metadata) {
				continue;
			}
			
			// Delete expired uploads (complete or incomplete)
			// For completed uploads, use completed_at if available, otherwise updated_at
			$check_time = ($metadata['status'] == 'completed' && isset($metadata['completed_at'])) 
				? $metadata['completed_at'] 
				: $metadata['updated_at'];
			
			if ($check_time < $expiry_time) {
				if ($this->delete_upload($dir)) {
					$stats['deleted']++;
				} else {
					$stats['errors']++;
				}
			}
		}
		
		return $stats;
	}
	
	/**
	 * Generate UUID v4
	 * 
	 * @return string
	 */
	private function lift_execution_limits()
	{
		@ignore_user_abort(true);
		@set_time_limit(0);
		@ini_set('max_execution_time', '0');
	}

	private function read_upload_metadata_raw($upload_id)
	{
		$metadata_path = $this->get_metadata_path($upload_id);

		if (!file_exists($metadata_path)) {
			return false;
		}

		$metadata_json = @file_get_contents($metadata_path);
		if ($metadata_json === false) {
			return false;
		}

		$metadata = @json_decode($metadata_json, true);
		if ($metadata === null) {
			return false;
		}

		return $metadata;
	}

	private function is_append_mode($metadata)
	{
		if (!empty($metadata['append_mode'])) {
			return true;
		}

		return !$this->has_legacy_chunk_files($metadata['upload_id']);
	}

	private function has_legacy_chunk_files($upload_id)
	{
		$chunks = $this->scan_chunk_files($upload_id);
		return !empty($chunks);
	}

	private function scan_chunk_files($upload_id)
	{
		$upload_path = $this->get_upload_path($upload_id);
		$chunks_dir = unix_path($upload_path . '/chunks');

		if (!file_exists($chunks_dir)) {
			return array();
		}

		$chunks = array();
		$files = @scandir($chunks_dir);
		if ($files === false) {
			return array();
		}

		foreach ($files as $file) {
			if (preg_match('/^chunk_(\d+)\.part$/', $file, $matches)) {
				$chunks[] = (int)$matches[1];
			}
		}

		sort($chunks);
		return $chunks;
	}

	private function normalized_uploaded_chunks($upload_id, $metadata)
	{
		$chunks = array();
		if (!empty($metadata['uploaded_chunks']) && is_array($metadata['uploaded_chunks'])) {
			foreach ($metadata['uploaded_chunks'] as $n) {
				$chunks[] = (int)$n;
			}
		}

		foreach ($this->scan_chunk_files($upload_id) as $n) {
			$chunks[] = (int)$n;
		}

		$chunks = array_values(array_unique($chunks));
		sort($chunks, SORT_NUMERIC);
		return $chunks;
	}

	private function next_expected_chunk($uploaded_chunks)
	{
		$expected = 0;
		foreach ($uploaded_chunks as $n) {
			if ((int)$n === $expected) {
				$expected++;
			} else {
				break;
			}
		}
		return $expected;
	}

	private function chunk_status_payload($metadata)
	{
		$uploaded = isset($metadata['uploaded_chunks']) ? $metadata['uploaded_chunks'] : array();
		$total = isset($metadata['total_chunks']) ? (int)$metadata['total_chunks'] : 0;
		$is_complete = isset($metadata['status']) && $metadata['status'] == 'completed';

		return array(
			'status' => $is_complete ? 'complete' : 'partial',
			'uploaded_chunks' => $uploaded,
			'total_chunks' => $total,
			'progress' => $total > 0 ? count($uploaded) / $total : 0
		);
	}

	private function lock_upload($upload_id)
	{
		$upload_path = $this->get_upload_path($upload_id);
		if (!is_dir($upload_path)) {
			if (!@mkdir($upload_path, 0755, true)) {
				throw new Exception("FAILED_TO_CREATE_UPLOAD_DIRECTORY");
			}
		}

		$lock_path = unix_path($upload_path . '/upload.lock');
		$handle = @fopen($lock_path, 'c');
		if (!$handle) {
			throw new Exception("FAILED_TO_LOCK_UPLOAD");
		}

		if (!flock($handle, LOCK_EX)) {
			fclose($handle);
			throw new Exception("FAILED_TO_LOCK_UPLOAD");
		}

		return $handle;
	}

	private function unlock_upload($handle)
	{
		if (!$handle) {
			return;
		}
		@flock($handle, LOCK_UN);
		@fclose($handle);
	}

	private function get_partial_file_path($upload_id, $metadata)
	{
		$upload_path = $this->get_upload_path($upload_id);
		return unix_path($upload_path . '/' . $metadata['filename'] . '.partial');
	}

	private function file_size_or_zero($path)
	{
		if (!is_file($path)) {
			return 0;
		}

		$size = @filesize($path);
		return $size === false ? 0 : $size;
	}

	private function append_chunk_to_partial($upload_id, $chunk_data, &$metadata)
	{
		$partial = $this->get_partial_file_path($upload_id, $metadata);
		$expected = isset($metadata['assembled_bytes']) ? (int)$metadata['assembled_bytes'] : 0;

		$out = @fopen($partial, 'c+b');
		if (!$out) {
			throw new Exception("FAILED_TO_WRITE_CHUNK");
		}

		if (!flock($out, LOCK_EX)) {
			fclose($out);
			throw new Exception("FAILED_TO_LOCK_UPLOAD");
		}

		try {
			$stat = fstat($out);
			$current = isset($stat['size']) ? (int)$stat['size'] : 0;

			if ($current > $expected) {
				if (!ftruncate($out, $expected)) {
					throw new Exception("FAILED_TO_RECOVER_PARTIAL_FILE");
				}
				$current = $expected;
			}

			if ($current < $expected) {
				throw new Exception("ASSEMBLED_FILE_CORRUPT: expected " . $expected . " bytes, file has " . $current);
			}

			if (fseek($out, $expected) !== 0) {
				throw new Exception("FAILED_TO_SEEK_PARTIAL_FILE");
			}

			$written = fwrite($out, $chunk_data);
			if ($written === false || $written != strlen($chunk_data)) {
				throw new Exception("FAILED_TO_WRITE_CHUNK");
			}

			fflush($out);
		} finally {
			flock($out, LOCK_UN);
			fclose($out);
		}

		$metadata['assembled_bytes'] = $expected + strlen($chunk_data);
	}

	private function write_legacy_chunk_file($upload_id, $chunk_number, $chunk_data)
	{
		$chunk_path = $this->get_chunk_path($upload_id, $chunk_number);
		$chunks_dir = dirname($chunk_path);

		if (!file_exists($chunks_dir)) {
			if (!@mkdir($chunks_dir, 0755, true)) {
				throw new Exception("FAILED_TO_CREATE_CHUNKS_DIRECTORY");
			}
		}

		$temp_chunk = $chunk_path . '.tmp';
		if (@file_put_contents($temp_chunk, $chunk_data) === false) {
			throw new Exception("FAILED_TO_WRITE_CHUNK");
		}

		if (!@rename($temp_chunk, $chunk_path)) {
			@unlink($temp_chunk);
			throw new Exception("FAILED_TO_SAVE_CHUNK");
		}
	}

	private function finalize_assembled_file($upload_id, &$metadata)
	{
		$final_file = unix_path($this->get_upload_path($upload_id) . '/' . $metadata['filename']);
		$partial = $this->get_partial_file_path($upload_id, $metadata);

		if (is_file($partial)) {
			$partial_size = $this->file_size_or_zero($partial);
			if ($partial_size != $metadata['total_size']) {
				throw new Exception("FILE_SIZE_MISMATCH: Expected " . $metadata['total_size'] . ", got " . $partial_size);
			}

			if (is_file($final_file) && unix_path($final_file) !== unix_path($partial)) {
				@unlink($final_file);
			}

			if (!@rename($partial, $final_file)) {
				throw new Exception("FAILED_TO_FINALIZE_FILE");
			}
		} elseif ($this->has_legacy_chunk_files($upload_id)) {
			$this->stream_combine_legacy_chunks($upload_id, $metadata);
		} else {
			$final_size = $this->file_size_or_zero($final_file);
			if ($final_size != $metadata['total_size']) {
				throw new Exception("FILE_SIZE_MISMATCH: Expected " . $metadata['total_size'] . ", got " . $final_size);
			}
		}

		$metadata['assembled_bytes'] = (int)$metadata['total_size'];
	}

	private function stream_combine_legacy_chunks($upload_id, $metadata)
	{
		$final_file = unix_path($this->get_upload_path($upload_id) . '/' . $metadata['filename']);
		$temp_file = $final_file . '.tmp';
		$buffer_size = 1048576;

		$out = @fopen($temp_file, 'wb');
		if (!$out) {
			throw new Exception("FAILED_TO_CREATE_FINAL_FILE");
		}

		$total_size = 0;
		try {
			for ($i = 0; $i < $metadata['total_chunks']; $i++) {
				$chunk_path = $this->get_chunk_path($upload_id, $i);
				if (!file_exists($chunk_path)) {
					throw new Exception("CHUNK_NOT_FOUND: chunk_" . $i);
				}

				$in = @fopen($chunk_path, 'rb');
				if (!$in) {
					throw new Exception("FAILED_TO_READ_CHUNK: chunk_" . $i);
				}

				while (!feof($in)) {
					$buffer = fread($in, $buffer_size);
					if ($buffer === false) {
						fclose($in);
						throw new Exception("FAILED_TO_READ_CHUNK: chunk_" . $i);
					}
					if ($buffer === '') {
						break;
					}

					$written = fwrite($out, $buffer);
					if ($written === false || $written != strlen($buffer)) {
						fclose($in);
						throw new Exception("FAILED_TO_WRITE_CHUNK_DATA: chunk_" . $i);
					}
					$total_size += $written;
				}

				fclose($in);
			}

			fflush($out);
		} catch (Exception $e) {
			fclose($out);
			@unlink($temp_file);
			throw $e;
		}

		fclose($out);

		if ($total_size != $metadata['total_size']) {
			@unlink($temp_file);
			throw new Exception("FILE_SIZE_MISMATCH: Expected " . $metadata['total_size'] . ", got " . $total_size);
		}

		if (is_file($final_file)) {
			@unlink($final_file);
		}

		if (!@rename($temp_file, $final_file)) {
			@unlink($temp_file);
			throw new Exception("FAILED_TO_FINALIZE_FILE");
		}
	}

	private function delete_chunks_directory($upload_id)
	{
		$chunks_dir = unix_path($this->get_upload_path($upload_id) . '/chunks');
		if (file_exists($chunks_dir)) {
			$this->delete_directory($chunks_dir);
		}
	}

	private function heal_completed_status($upload_id, &$metadata)
	{
		if (!$metadata || !isset($metadata['status']) || $metadata['status'] == 'completed') {
			return;
		}

		if (empty($metadata['filename']) || empty($metadata['total_size'])) {
			return;
		}

		$lock_path = unix_path($this->get_upload_path($upload_id) . '/upload.lock');
		$lock = @fopen($lock_path, 'c');
		if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
			if ($lock) {
				fclose($lock);
			}
			return;
		}

		try {
			$fresh = $this->read_upload_metadata_raw($upload_id);
			if ($fresh) {
				$metadata = $fresh;
			}
			if (isset($metadata['status']) && $metadata['status'] == 'completed') {
				return;
			}

			$final_file = unix_path($this->get_upload_path($upload_id) . '/' . $metadata['filename']);
			$partial = $this->get_partial_file_path($upload_id, $metadata);

			if (is_file($partial) && $this->file_size_or_zero($partial) == $metadata['total_size']) {
				if (is_file($final_file)) {
					@unlink($final_file);
				}
				if (!@rename($partial, $final_file)) {
					return;
				}
			}

			if (!is_file($final_file) || $this->file_size_or_zero($final_file) != $metadata['total_size']) {
				return;
			}

			$metadata['status'] = 'completed';
			$metadata['completed_at'] = time();
			$metadata['assembled_bytes'] = (int)$metadata['total_size'];
			$metadata['uploaded_chunks'] = $this->normalized_uploaded_chunks($upload_id, $metadata);

			$this->save_upload_metadata($upload_id, $metadata);
			$this->delete_chunks_directory($upload_id);
		} catch (Exception $e) {
			// Leave caller with whatever status we could recover
		} finally {
			$this->unlock_upload($lock);
		}
	}

	private function stream_copy_file($source, $destination)
	{
		$in = @fopen($source, 'rb');
		if (!$in) {
			return false;
		}

		$out = @fopen($destination, 'wb');
		if (!$out) {
			fclose($in);
			return false;
		}

		$ok = true;
		while (!feof($in)) {
			$buffer = fread($in, 1048576);
			if ($buffer === false) {
				$ok = false;
				break;
			}
			if ($buffer === '') {
				break;
			}
			$written = fwrite($out, $buffer);
			if ($written === false || $written != strlen($buffer)) {
				$ok = false;
				break;
			}
		}

		fclose($in);
		fclose($out);

		if (!$ok) {
			@unlink($destination);
			return false;
		}

		return true;
	}

	private function generate_upload_id()
	{
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
		
		return sprintf('%08s-%04s-%04s-%04s-%12s',
			bin2hex(substr($data, 0, 4)),
			bin2hex(substr($data, 4, 2)),
			bin2hex(substr($data, 6, 2)),
			bin2hex(substr($data, 8, 2)),
			bin2hex(substr($data, 10, 6))
		);
	}
	
	/**
	 * Recursively delete directory
	 * 
	 * @param string $dir
	 * @return bool
	 */
	private function delete_directory($dir)
	{
		if (!file_exists($dir)) {
			return true;
		}
		
		if (!is_dir($dir)) {
			return @unlink($dir);
		}
		
		$files = @scandir($dir);
		if ($files === false) {
			return false;
		}
		
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			
			$file_path = unix_path($dir . '/' . $file);
			
			if (is_dir($file_path)) {
				if (!$this->delete_directory($file_path)) {
					return false;
				}
			} else {
				if (!@unlink($file_path)) {
					return false;
				}
			}
		}
		
		return @rmdir($dir);
	}
	
	/**
	 * Get upload directory path
	 * 
	 * @param string $upload_id
	 * @return string
	 */
	private function get_upload_path($upload_id)
	{
		return unix_path($this->temp_path . '/' . $upload_id);
	}
	
	/**
	 * Get metadata file path
	 * 
	 * @param string $upload_id
	 * @return string
	 */
	private function get_metadata_path($upload_id)
	{
		$upload_path = $this->get_upload_path($upload_id);
		return unix_path($upload_path . '/metadata.json');
	}
	
	/**
	 * Get chunk file path
	 * 
	 * @param string $upload_id
	 * @param int $chunk_number
	 * @return string
	 */
	private function get_chunk_path($upload_id, $chunk_number)
	{
		$upload_path = $this->get_upload_path($upload_id);
		$chunks_dir = unix_path($upload_path . '/chunks');
		return unix_path($chunks_dir . '/chunk_' . $chunk_number . '.part');
	}
	
	/**
	 * Get final file path
	 * 
	 * @param string $upload_id
	 * @param string $filename
	 * @return string
	 */
	public function get_final_file_path($upload_id, $filename = null)
	{
		$upload_path = $this->get_upload_path($upload_id);
		
		if ($filename === null) {
			$metadata = $this->get_upload_metadata($upload_id);
			if (!$metadata) {
				return false;
			}
			$filename = $metadata['filename'];
		}
		
		return unix_path($upload_path . '/' . $filename);
	}
	
	/**
	 * Get completed upload file information (for internal use)
	 * 
	 * Validates that the upload is completed before returning file information.
	 * Use this function when other parts of the application need to access
	 * the completed file.
	 * 
	 * @param string $upload_id
	 * @return array|false Array with full file information, or false if not found/not completed
	 */
	public function get_completed_upload($upload_id)
	{
		$metadata = $this->get_upload_metadata($upload_id);
		
		if (!$metadata) {
			return false;
		}
		
		if ($metadata['status'] != 'completed') {
			return false;
		}
		
		$final_file = $this->get_final_file_path($upload_id);
		
		// Verify file actually exists
		if (!file_exists($final_file)) {
			return false;
		}
		
		$file_extension = strtolower(pathinfo($metadata['filename'], PATHINFO_EXTENSION));
		
		$file_info = array(
			'upload_id' => $upload_id,
			'file_path' => $final_file,
			'filename' => $metadata['filename'],
			'original_filename' => isset($metadata['original_filename']) ? $metadata['original_filename'] : $metadata['filename'],
			'file_extension' => $file_extension,
			'file_type' => $file_extension,
			'file_size' => filesize($final_file),
			'total_size' => $metadata['total_size'],
			'created_at' => $metadata['created_at'],
			'updated_at' => $metadata['updated_at'],
			'completed_at' => isset($metadata['completed_at']) ? $metadata['completed_at'] : null,
			'status' => $metadata['status'],
			'metadata' => isset($metadata['metadata']) ? $metadata['metadata'] : array()
		);
		
		return $file_info;
	}
	
	/**
	 * Sanitize filename for safe storage
	 * 
	 * @param string $filename
	 * @return string
	 */
	private function sanitize_filename($filename)
	{
		// Remove path components
		$filename = basename($filename);
		
		// Remove any null bytes
		$filename = str_replace("\0", '', $filename);
		
		// Remove directory traversal attempts
		$filename = str_replace(['../', '..\\', '..'], '', $filename);
		
		// Replace dangerous characters
		$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
		
		// Limit length
		if (strlen($filename) > 255) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$name = pathinfo($filename, PATHINFO_FILENAME);
			$filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
		}
		
		return $filename;
	}
	
	/**
	 * Validate file extension against allowed types
	 * 
	 * Checks per-upload restriction (metadata['allowed_types']) first,
	 * then falls back to global allowed_resource_types setting.
	 * 
	 * @param string $filename
	 * @param array $metadata Optional metadata containing per-upload restrictions
	 * @return bool
	 */
	private function validate_file_type($filename, $metadata = array())
	{
		// Check for per-upload file type restriction in metadata
		$allowed_types = null;
		if (isset($metadata['allowed_types']) && !empty($metadata['allowed_types'])) {
			$allowed_types = $metadata['allowed_types'];
		}
		
		// Fall back to global setting if no per-upload restriction
		if ($allowed_types === null) {
			$allowed_types = $this->allowed_types;
		}
		
		// If still empty, all types allowed
		if (empty($allowed_types) || trim($allowed_types) == '') {
			return true;
		}
		
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$allowed = array_map('trim', explode(',', strtolower($allowed_types)));
		
		return in_array($extension, $allowed);
	}
	
	/**
	 * Get maximum chunk size based on PHP limits
	 * 
	 * Returns the smaller of post_max_size and upload_max_filesize
	 * 
	 * @return int Maximum chunk size in bytes
	 */
	public function get_max_chunk_size($human_readable = false)
	{
		if ($human_readable) {
			return $this->format_bytes($this->max_chunk_size);
		}
		return $this->max_chunk_size;
	}

	/**
	 * Get maximum file size allowed for uploads
	 * 
	 * @return int Maximum file size in bytes
	 */
	public function get_max_file_size($human_readable = false)
	{
		if ($human_readable) {
			return $this->format_bytes($this->max_size);
		}
		return $this->max_size;
	}

	/**
	 * Get upload expiry time in hours
	 * 
	 * @return int Expiry hours
	 */
	public function get_expiry_hours()
	{
		return $this->expiry_hours;
	}

	/**
	 * Get allowed file types
	 * 
	 * @return string Comma-separated list of allowed file extensions
	 */
	public function get_allowed_file_types()
	{
		return $this->allowed_types;
	}

	/**
	 * Get maximum upload limit enforced by PHP settings
	 * 
	 * Returns the smaller of post_max_size and upload_max_filesize
	 * 
	 * @return int Maximum upload size in bytes
	 */
	public function get_max_upload_limit($human_readable = false)
	{
		if ($human_readable) {
			return $this->format_bytes($this->max_chunk_size);
		}	
	
		return $this->get_php_max_chunk_size();
	}

	/**
	 * Get PHP post_max_size setting in bytes
	 * 
	 * @return int post_max_size in bytes
	 */
	public function get_post_max_size()
	{
		return ini_get('post_max_size');
	}

	/**
	 * Get PHP upload_max_filesize setting in bytes
	 * 
	 * @return int upload_max_filesize in bytes
	 */
	public function get_upload_max_filesize()
	{
		return ini_get('upload_max_filesize');
	}
	
	/**
	 * Get maximum chunk size based on PHP limits
	 * 
	 * Returns the smaller of post_max_size and upload_max_filesize
	 * 
	 * @return int Maximum chunk size in bytes
	 */
	private function get_php_max_chunk_size()
	{
		$post_max_size = $this->convert_ini_size_to_bytes(ini_get('post_max_size'));
		$upload_max_filesize = $this->convert_ini_size_to_bytes(ini_get('upload_max_filesize'));
		
		// Use the smaller of the two limits
		// post_max_size must be >= upload_max_filesize, but we'll be safe
		return min($post_max_size, $upload_max_filesize);
	}
	
	/**
	 * Convert PHP ini size string to bytes
	 * 
	 * Handles formats like: "10M", "100M", "1G", "10485760" (bytes)
	 * 
	 * @param string $size_str
	 * @return int Size in bytes
	 */
	private function convert_ini_size_to_bytes($size_str)
	{
		if (empty($size_str) || $size_str == '0') {
			return 0;
		}
		
		$size_str = trim($size_str);
		$last_char = strtolower(substr($size_str, -1));
		
		// Extract numeric value
		$value = (float)$size_str;
		
		// Apply multiplier based on suffix
		switch ($last_char) {
			case 'g':
				$value *= 1024 * 1024 * 1024;
				break;
			case 'm':
				$value *= 1024 * 1024;
				break;
			case 'k':
				$value *= 1024;
				break;
			default:
				// No suffix, already in bytes
				break;
		}
		
		return (int)$value;
	}
	
	/**
	 * Format bytes to human-readable string
	 * 
	 * @param int $bytes
	 * @return string
	 */
	private function format_bytes($bytes)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, 2) . ' ' . $units[$pow];
	}
}

