<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Log Model
 * 
 * Handles audit log entries for authentication, admin actions, and security events
 *
 * @package		NADA
 * @subpackage	Models
 * @category	Audit Logs
 */
class Audit_log_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Insert an audit log entry
	 * 
	 * @param array $data Log data
	 * @return bool Success status
	 */
	public function insert($data)
	{
		// Ensure created_at is set
		if (!isset($data['created_at'])) {
			$data['created_at'] = date('Y-m-d H:i:s');
		}
		
		// Ensure severity has valid value
		if (isset($data['severity'])) {
			$valid_severities = array('info', 'warning', 'error', 'critical');
			if (!in_array($data['severity'], $valid_severities)) {
				$data['severity'] = 'info';
			}
		} else {
			$data['severity'] = 'info';
		}
		
		// Convert details array to JSON if needed
		if (isset($data['details']) && is_array($data['details'])) {
			$data['details'] = json_encode($data['details']);
		}
		
		return $this->db->insert('audit_logs', $data);
	}
	
	/**
	 * Get audit logs with filters
	 * 
	 * @param array $filters Filter options
	 * @param int $limit Limit
	 * @param int $offset Offset
	 * @return array Audit logs
	 */
	public function get_logs($filters = array(), $limit = 100, $offset = 0)
	{
		$this->db->select('*');
		$this->db->from('audit_logs');
		
		// Apply filters
		if (isset($filters['log_type'])) {
			$this->db->where('log_type', $filters['log_type']);
		}
		
		if (isset($filters['category'])) {
			$this->db->where('category', $filters['category']);
		}
		
		if (isset($filters['user_id'])) {
			$this->db->where('user_id', $filters['user_id']);
		}
		
		if (isset($filters['username'])) {
			$this->db->where('username', $filters['username']);
		}
		
		if (isset($filters['start_date'])) {
			$this->db->where('created_at >=', $filters['start_date']);
		}
		
		if (isset($filters['end_date'])) {
			$this->db->where('created_at <=', $filters['end_date']);
		}
		
		if (isset($filters['severity'])) {
			$this->db->where('severity', $filters['severity']);
		}
		
		if (isset($filters['survey_id'])) {
			$this->db->where('survey_id', $filters['survey_id']);
		}
		
		// Order by created_at descending
		$this->db->order_by('created_at', 'DESC');
		
		// Apply limit and offset
		$this->db->limit($limit, $offset);
		
		$query = $this->db->get();
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Count audit logs with filters
	 * 
	 * @param array $filters Filter options
	 * @return int Count
	 */
	public function count_logs($filters = array())
	{
		$this->db->from('audit_logs');
		
		// Apply same filters as get_logs
		if (isset($filters['log_type'])) {
			$this->db->where('log_type', $filters['log_type']);
		}
		
		if (isset($filters['category'])) {
			$this->db->where('category', $filters['category']);
		}
		
		if (isset($filters['user_id'])) {
			$this->db->where('user_id', $filters['user_id']);
		}
		
		if (isset($filters['username'])) {
			$this->db->where('username', $filters['username']);
		}
		
		if (isset($filters['start_date'])) {
			$this->db->where('created_at >=', $filters['start_date']);
		}
		
		if (isset($filters['end_date'])) {
			$this->db->where('created_at <=', $filters['end_date']);
		}
		
		if (isset($filters['severity'])) {
			$this->db->where('severity', $filters['severity']);
		}
		
		if (isset($filters['survey_id'])) {
			$this->db->where('survey_id', $filters['survey_id']);
		}
		
		return $this->db->count_all_results();
	}
	
	/**
	 * Get failed login attempts for a user/IP
	 * 
	 * @param string $username Username/email
	 * @param string $ip_address IP address (optional)
	 * @param int $hours Number of hours to look back
	 * @return int Count of failed attempts
	 */
	public function get_failed_login_attempts($username, $ip_address = null, $hours = 24)
	{
		$this->db->where('log_type', 'login_failed');
		$this->db->where('category', 'authentication');
		$this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$hours} hours")));
		
		if ($username) {
			$this->db->where('username', $username);
		}
		
		if ($ip_address) {
			$this->db->where('ip_address', $ip_address);
		}
		
		return $this->db->count_all_results('audit_logs');
	}
}

/* End of file Audit_log_model.php */
/* Location: ./application/models/Audit_log_model.php */
