<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Event Logger
 * 
 * - Analytics events (pageviews, downloads) → Analytics_model
 * - Audit events (login, admin actions) → sitelogs table
 * - Search events → sitelogs table
 * 
 *
 */
class Event_Logger {
	
	protected $ci;
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('bots');
	}
	
	/**
	 * Main logging method - routes events to audit system
	 * 
	 * Note: 
	 * - Analytics events (pageview, download) should use log_pageview() or log_download()
	 * - Search events should use log_search()
	 * This method only handles audit events.
	 * 
	 * @param string $event_type Event type (e.g., 'login', 'resource-added')
	 * @param array $data Event data
	 * @return bool Success status
	 */
	public function log($event_type, $data = array())
	{
		// All events routed through this method are audit events
		return $this->log_audit($event_type, $data);
	}
	
	/**
	 * Log a pageview event (analytics)
	 * 
	 * @param string $study_id Study identifier
	 * @param string $session_id Optional session ID (from client-side)
	 * @param array $data Optional additional data (referrer, user_agent, etc.)
	 * @return bool Success status
	 */
	public function log_pageview($study_id, $session_id = null, $data = array())
	{
		// Quick validation before loading model
		if (!$this->validate_analytics_request('pageview', $data)) {
			return false;
		}
		
		return $this->ci->analytics_tracker->track_pageview($study_id, $session_id, $data);
	}
	
	/**
	 * Log a download event (analytics)
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name (not file_id, since files can be deleted)
	 * @param array $data Optional additional data (file_type, user_agent, etc.)
	 * @return bool Success status
	 */
	public function log_download($study_id, $file_name, $data = array())
	{
		// Quick validation before loading model
		if (!$this->validate_analytics_request('download', $data)) {
			return false;
		}
		
		return $this->ci->analytics_tracker->track_download($study_id, $file_name, $data);
	}
	
	/**
	 * Log a search query
	 * 
	 * @param string $query_text Search query text
	 * @param string $search_type Optional search type (e.g., 'study', 'variable')
	 * @param int $results_count Optional number of results
	 * @param array $additional_data Optional additional data
	 * @return bool Success status
	 */
	public function log_search($query_text, $search_type = null, $results_count = null, $additional_data = array())
	{
		if (!$this->validate_analytics_request('pageview', $additional_data)) {
			return false;
		}
		
		$username = '';
		
		if ($this->ci->ion_auth->logged_in()) {
			$user = $this->ci->ion_auth->current_user();
			if ($user) {
				$username = $user->email;
			}
		}
		
		// Prepare log data
		$log_data = array(
			'url' => substr($this->current_page_url(), 0, 255),
			'logtime' => date("U"),
			'ip' => $this->ci->input->ip_address(),
			'sessionid' => session_id(),
			'logtype' => 'search',
			'surveyid' => isset($additional_data['surveyid']) ? (int)$additional_data['surveyid'] : 0,
			'keyword' => substr((string)$query_text, 0, 300),
			'username' => $username,
			'section' => $search_type ? substr((string)$search_type, 0, 255) : null,
			'useragent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(strtolower($_SERVER['HTTP_USER_AGENT']), 0, 300) : null
		);
		
		return $this->ci->db->insert('sitelogs', $log_data);
	}
	
	/**
	 * Log an audit event (authentication, admin actions, security)
	 * 
	 * @param string $log_type Event type (e.g., 'login', 'resource-added')
	 * @param array $data Event data
	 *   - message: Optional message
	 *   - section: Optional section/context
	 *   - surveyid: Optional survey ID
	 *   - keyword: Optional additional info
	 *   - severity: Optional severity level ('info', 'warning', 'error', 'critical')
	 * @return bool Success status
	 */
	public function log_audit($log_type, $data = array())
	{		
		$username = '';
		
		if ($this->ci->ion_auth->logged_in()) {
			$user = $this->ci->ion_auth->current_user();
			if ($user) {
				$username = $user->email;
			}
		}
		
		// Override username if provided in data
		if (isset($data['username'])) {
			$username = $data['username'];
		}
		
		$log_data = array(
			'url' => isset($data['url']) ? substr($data['url'], 0, 255) : substr($this->current_page_url(), 0, 255),
			'logtime' => isset($data['logtime']) ? $data['logtime'] : date("U"),
			'ip' => isset($data['ip']) ? $data['ip'] : $this->ci->input->ip_address(),
			'sessionid' => isset($data['sessionid']) ? $data['sessionid'] : session_id(),
			'logtype' => $log_type,
			'surveyid' => isset($data['surveyid']) ? (int)$data['surveyid'] : 0,
			'keyword' => isset($data['keyword']) ? substr((string)$data['keyword'], 0, 300) : (isset($data['message']) ? substr((string)$data['message'], 0, 300) : null),
			'username' => $username,
			'section' => isset($data['section']) ? substr((string)$data['section'], 0, 255) : null,
			'useragent' => isset($data['useragent']) ? substr($data['useragent'], 0, 300) : (isset($_SERVER['HTTP_USER_AGENT']) ? substr(strtolower($_SERVER['HTTP_USER_AGENT']), 0, 300) : null)
		);
		
		return $this->ci->db->insert('sitelogs', $log_data);
	}
	
	
	/**
	 * Validate analytics request before processing
	 * 
	 * @param string $type Request type: 'pageview' or 'download'
	 * @param array $data Optional data array (can contain user_agent)
	 * @return bool True if request is valid and should be tracked, false otherwise
	 */
	private function validate_analytics_request($type = 'pageview', $data = array())
	{
		// Check if analytics tracking is enabled
		if (!$this->ci->config->item('analytics_enabled')) {
			return false;
		}
		
		// Filter HEAD requests - these are just probes, not actual requests
		if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD') {
			return false;
		}
		
		// Filter OPTIONS requests - CORS preflight requests
		if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
			return false;
		}
		
		// Filter Range requests - only for downloads (partial downloads or scrapers checking file size)
		if ($type === 'download' && isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
			return false;
		}
		
		// Get user agent if not provided
		$user_agent = isset($data['user_agent']) ? $data['user_agent'] : (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
		
		// Filter missing or empty user agents - suspicious, likely bots/scrapers
		if (empty($user_agent) || trim($user_agent) === '') {
			return false;
		}
		
		// Check bot filtering
		if ($this->ci->config->item('ignore_bot_logging') === TRUE) {
			if ($this->is_bot($user_agent)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Check if user agent is a bot
	 * 
	 * @param string $agent Optional user agent string
	 * @return bool True if bot detected
	 */
	public function is_bot($agent = null)
	{
		$ignore_array = $this->ci->config->item('bot_ignore');
		
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
	
	/**
	 * Get current page URL with query strings
	 * 
	 * @return string Current page URL
	 */
	private function current_page_url()
	{
		$get = $_GET;
		$querystring = array();
		
		foreach ($get as $key => $value) {
			if (is_array($value)) {
				$value_ = implode(',', $value);
			} else {
				$value_ = $value;
			}
			$querystring[] = "$key=$value_";
		}
		
		$url = uri_string();
		if (count($querystring) > 0) {
			$url .= '?' . implode('&', $querystring);
		}
		
		return $url;
	}
}

/* End of file Event_Logger.php */
/* Location: ./application/libraries/Event_Logger.php */
