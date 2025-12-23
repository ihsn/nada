<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Event Tracker Model
 * 
 * Handles tracking and validation of pageview and download events
 *
 */
class Analytics_event_tracker_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->config('bots');
	}
	
	/**
	 * Track a pageview event
	 * 
	 * @param string $study_id Study identifier
	 * @param string $session_id Client-generated session token
	 * @param array $data Additional data (referrer, user_agent, etc.)
	 * @return bool Success status
	 */
	public function track_pageview($study_id, $session_id = null, $data = array())
	{
		if (!$this->validate_request('pageview', $data)) {
			return false;
		}
		
		if (!isset($data['user_agent'])) {
			$data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		
		$ip = $this->input->ip_address();
		$hashed_ip = $this->hash_ip($ip);
		
		$event = array(
			'ts' => date('Y-m-d H:i:s'),
			'study_id' => $study_id,
			'session_id' => $session_id,
			'hashed_ip' => $hashed_ip,
			'user_agent' => $data['user_agent'] ? substr($data['user_agent'], 0, 200) : null,
			'referrer' => isset($data['referrer']) ? substr($data['referrer'], 0, 512) : null
		);
		
		return $this->db->insert('analytics_pageview_events', $event);
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
		// Filter HEAD requests - these are just probes, not actual requests
		if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD') {
			return false;
		}
		
		// Filter Range requests - only for downloads (partial downloads or scrapers checking file size)
		if ($type === 'download' && isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
			return false;
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
	 * @param string $study_id Study identifier
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
