<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Event Tracker Model
 * 
 * Handles tracking and validation of pageview and download events
 *
 */
class Analytics_event_tracker_model extends CI_Model {

	const PAGEVIEW_INSERTED = 'inserted';
	const PAGEVIEW_DUPLICATE = 'duplicate';
	const PAGEVIEW_REJECTED = 'rejected';
	
	public function __construct()
	{
		parent::__construct();
		$this->load->config('bots');
		$this->load->config('analytics');
	}
	
	/**
	 * Track a pageview event
	 * 
	 * @param int $study_id Study identifier
	 * @param string $session_id Client-generated session token
	 * @param array $data Additional data (referrer, user_agent, page_url, section, etc.)
	 * @return string inserted|duplicate|rejected
	 */
	public function track_pageview($study_id, $session_id = null, $data = array())
	{
		if (!$this->validate_request('pageview', $data)) {
			return self::PAGEVIEW_REJECTED;
		}
		
		if (!isset($data['user_agent'])) {
			$data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}

		$page_url = $this->normalize_page_url(isset($data['page_url']) ? $data['page_url'] : null);
		$section = $this->normalize_section(isset($data['section']) ? $data['section'] : null);
		$is_client_pageview = $this->is_client_pageview($data);

		if ($is_client_pageview) {
			if (!$this->is_valid_session_id($session_id)) {
				return self::PAGEVIEW_REJECTED;
			}

			if ($section === null && $page_url === null) {
				return self::PAGEVIEW_REJECTED;
			}

			if ($this->is_recent_pageview_duplicate($study_id, $session_id, $page_url, $section)) {
				log_message('debug', "Pageview dedupe: Duplicate prevented for study {$study_id}");
				return self::PAGEVIEW_DUPLICATE;
			}
		}
		
		$event = array(
			'ts' => date('Y-m-d H:i:s'),
			'study_id' => $study_id,
			'session_id' => $session_id,
			'page_url' => $page_url,
			'section' => $section,
			'user_agent' => $data['user_agent'] ? substr($data['user_agent'], 0, 200) : null,
			'referrer' => isset($data['referrer']) ? substr($data['referrer'], 0, 512) : null
		);
		
		if (!$this->db->insert('analytics_pageview_events', $event)) {
			return self::PAGEVIEW_REJECTED;
		}

		return self::PAGEVIEW_INSERTED;
	}
	
	/**
	 * Validate a request against all filtering rules
	 * 
	 * @param string $type Request type: 'pageview' or 'download'
	 * @param array $data Optional data array (can contain user_agent)
	 * @return bool True if request is valid and should be tracked, false otherwise
	 */
	public function validate_request($type = 'pageview', $data = array())
	{
		// For downloads, only track GET requests. Filter out HEAD, POST, PUT, DELETE, OPTIONS, etc.
		if ($type === 'download') {
			$request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
			if ($request_method !== 'GET') {
				return false;
			}
			
			// Filter Range requests - partial downloads or scrapers checking file size
			if (isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
				return false;
			}
		}
		
		// Filter HEAD requests for pageviews - these are just probes, not actual requests
		if ($type === 'pageview') {
			if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD') {
				return false;
			}
		}
		
		// Get user agent if not provided
		if (!isset($data['user_agent'])) {
			$data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		
		// Filter missing or empty user agents - suspicious, likely bots/scrapers
		if (empty($data['user_agent']) || trim($data['user_agent']) === '') {
			return false;
		}
		
		// Check bot filtering
		if ($this->config->item('ignore_bot_logging') === TRUE) {
			if ($this->is_bot($data['user_agent'])) {
				return false;
			}
		}
		
		// Check connection status - if connection is already aborted, don't track
		if (function_exists('connection_aborted') && connection_aborted()) {
			return false;
		}
		
		// Check connection status - if connection is not normal, don't track
		if (function_exists('connection_status') && connection_status() !== CONNECTION_NORMAL) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Validate download request
	 * 
	 * @param array $data Optional
	 * @return bool
	 */
	public function validate_download_request($data = array())
	{
		return $this->validate_request('download', $data);
	}
	
	/**
	 * Track a download event
	 * 
	 * @param int $study_id Study identifier
	 * @param string $file_name File name 
	 * @param array $data Additional data (file_type, user_agent, etc.)
	 * @return bool Success status
	 */
	public function track_download($study_id, $file_name, $data = array())
	{
		if (!$this->validate_download_request($data)) {
			return false;
		}
		
		if (!isset($data['user_agent'])) {
			$data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		
		$ip = $this->input->ip_address();
		$hashed_ip = $this->hash_ip($ip);
		
		// Check for duplicate within deduplication window
		if ($this->is_recent_duplicate($study_id, $file_name, $hashed_ip, $data['user_agent'])) {
			log_message('debug', "Download dedupe: Duplicate prevented for {$study_id}/{$file_name}");
			return false;
		}
		
		$event = array(
			'ts' => date('Y-m-d H:i:s'),
			'study_id' => $study_id,
			'file_name' => $file_name,
			'file_type' => isset($data['file_type']) ? substr($data['file_type'], 0, 50) : null,
			'hashed_ip' => $hashed_ip,
			'user_agent' => substr($data['user_agent'], 0, 200)
		);
		
		return $this->db->insert('analytics_download_events', $event);
	}
	
	/**
	 * Check if a pageview was recently tracked (within deduplication window)
	 *
	 * @param int $study_id Study identifier
	 * @param string $session_id Client session token
	 * @param string|null $page_url Page URL path
	 * @param string|null $section Study tab/section name
	 * @return bool True if duplicate found within dedup window, false otherwise
	 */
	private function is_recent_pageview_duplicate($study_id, $session_id, $page_url, $section)
	{
		$dedupe_window = $this->get_dedupe_window_minutes('pageview');
		if ($dedupe_window <= 0) {
			return false;
		}

		$cutoff_time = date('Y-m-d H:i:s', strtotime("-{$dedupe_window} minutes"));

		$this->db->where('study_id', $study_id);
		$this->db->where('session_id', $session_id);
		$this->db->where('ts >=', $cutoff_time);

		if ($section !== null) {
			$this->db->where('section', $section);
		} else {
			$this->db->where('page_url', $page_url);
			$this->db->where('section IS NULL', null, false);
		}

		$this->db->limit(1);

		$result = $this->db->get('analytics_pageview_events')->row_array();

		return !empty($result);
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	private function is_client_pageview($data)
	{
		if (empty($data['source'])) {
			return false;
		}

		return in_array($data['source'], array('builtin', 'ajax'), true);
	}

	/**
	 * @param mixed $session_id
	 * @return bool
	 */
	private function is_valid_session_id($session_id)
	{
		if (!is_string($session_id) || $session_id === '') {
			return false;
		}

		return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $session_id);
	}

	/**
	 * @param mixed $page_url
	 * @return string|null
	 */
	private function normalize_page_url($page_url)
	{
		if (!is_string($page_url)) {
			return null;
		}

		$page_url = trim($page_url);
		if ($page_url === '') {
			return null;
		}

		return substr($page_url, 0, 512);
	}

	/**
	 * @param mixed $section
	 * @return string|null
	 */
	private function normalize_section($section)
	{
		if (!is_string($section)) {
			return null;
		}

		$section = trim($section);
		if ($section === '') {
			return null;
		}

		return substr($section, 0, 100);
	}

	/**
	 * Check if a download was recently tracked (within deduplication window)
	 * 
	 * Prevents duplicate tracking of:
	 * - Browser retries after failed downloads
	 * - Accidental double-clicks
	 * - Rapid consecutive download attempts
	 * 
	 * @param int $study_id Study identifier
	 * @param string $file_name File name
	 * @param string $hashed_ip Hashed IP address
	 * @param string $user_agent User agent string
	 * @return bool True if duplicate found within dedup window, false otherwise
	 */
	private function is_recent_duplicate($study_id, $file_name, $hashed_ip, $user_agent)
	{
		$dedupe_window = $this->get_dedupe_window_minutes('download');
		if ($dedupe_window <= 0) {
			return false;
		}
		
		// Calculate cutoff time
		$cutoff_time = date('Y-m-d H:i:s', strtotime("-{$dedupe_window} minutes"));
		
		// Truncate user_agent to match what's stored in DB
		$user_agent_truncated = substr($user_agent, 0, 200);
		
		// Query for recent duplicate
		$this->db->where('study_id', $study_id);
		$this->db->where('file_name', $file_name);
		$this->db->where('hashed_ip', $hashed_ip);
		$this->db->where('user_agent', $user_agent_truncated);
		$this->db->where('ts >=', $cutoff_time);
		$this->db->limit(1);
		
		$result = $this->db->get('analytics_download_events')->row_array();
		
		return !empty($result);
	}
	
	/**
	 * @param string $type pageview|download
	 * @return int Minutes
	 */
	private function get_dedupe_window_minutes($type)
	{
		if ($type === 'pageview') {
			$window = $this->config->item('analytics_pageview_dedupe_window_minutes');
		} else {
			$window = $this->config->item('analytics_download_dedupe_window_minutes');
		}

		if ($window === null || $window === '') {
			$window = $this->config->item('analytics_dedupe_window_minutes');
		}

		return (int) $window;
	}

	/**
	 * Hash IP address for privacy
	 * 
	 * @param string $ip IP address
	 * @return string Hashed IP (SHA-256)
	 */
	private function hash_ip($ip)
	{
		if (empty($ip)) {
			return null;
		}
		return hash('sha256', $ip);
	}
	
	/**
	 * Check if user agent is a bot
	 * 
	 * @param string $agent User agent string (optional, uses $_SERVER if not provided)
	 * @return bool
	 */
	public function is_bot($agent = null)
	{
		$ignore_array = $this->config->item('bot_ignore');
		
		if (!$ignore_array || !is_array($ignore_array)) {
			return false;
		}
		
		if (!$agent) {
			$agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
		} else {
			$agent = strtolower($agent);
		}
		
		foreach ($ignore_array as $bot) {
			if (trim($bot) == '') {
				continue;
			}
			
			if (stripos($agent, trim(strtolower($bot))) !== false) {
				return true;
			}
		}
		
		return false;
	}
}

/* End of file Analytics_event_tracker_model.php */
/* Location: ./application/models/Analytics_event_tracker_model.php */
