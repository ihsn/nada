<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search Log Model
 * 
 * Handles search query logging for analytics
 *
 * @package		NADA
 * @subpackage	Models
 * @category	Search Logs
 */
class Search_log_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Insert a search log entry
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
		
		return $this->db->insert('search_logs', $data);
	}
	
	/**
	 * Get popular search queries
	 * 
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param int $limit Number of results
	 * @return array Popular queries
	 */
	public function get_popular_queries($start_date = null, $end_date = null, $limit = 100)
	{
		$this->db->select('query_text, COUNT(*) as count');
		$this->db->from('search_logs');
		
		if ($start_date) {
			$this->db->where('DATE(created_at) >=', $start_date);
		}
		
		if ($end_date) {
			$this->db->where('DATE(created_at) <=', $end_date);
		}
		
		$this->db->group_by('query_text');
		$this->db->order_by('count', 'DESC');
		$this->db->limit($limit);
		
		$query = $this->db->get();
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Get search logs with filters
	 * 
	 * @param array $filters Filter options
	 * @param int $limit Limit
	 * @param int $offset Offset
	 * @return array Search logs
	 */
	public function get_logs($filters = array(), $limit = 100, $offset = 0)
	{
		$this->db->select('*');
		$this->db->from('search_logs');
		
		// Apply filters
		if (isset($filters['search_type'])) {
			$this->db->where('search_type', $filters['search_type']);
		}
		
		if (isset($filters['user_id'])) {
			$this->db->where('user_id', $filters['user_id']);
		}
		
		if (isset($filters['username'])) {
			$this->db->where('username', $filters['username']);
		}
		
		if (isset($filters['query_text'])) {
			$this->db->like('query_text', $filters['query_text']);
		}
		
		if (isset($filters['start_date'])) {
			$this->db->where('created_at >=', $filters['start_date']);
		}
		
		if (isset($filters['end_date'])) {
			$this->db->where('created_at <=', $filters['end_date']);
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
	 * Count search logs with filters
	 * 
	 * @param array $filters Filter options
	 * @return int Count
	 */
	public function count_logs($filters = array())
	{
		$this->db->from('search_logs');
		
		// Apply same filters as get_logs
		if (isset($filters['search_type'])) {
			$this->db->where('search_type', $filters['search_type']);
		}
		
		if (isset($filters['user_id'])) {
			$this->db->where('user_id', $filters['user_id']);
		}
		
		if (isset($filters['username'])) {
			$this->db->where('username', $filters['username']);
		}
		
		if (isset($filters['query_text'])) {
			$this->db->like('query_text', $filters['query_text']);
		}
		
		if (isset($filters['start_date'])) {
			$this->db->where('created_at >=', $filters['start_date']);
		}
		
		if (isset($filters['end_date'])) {
			$this->db->where('created_at <=', $filters['end_date']);
		}
		
		return $this->db->count_all_results();
	}
	
	/**
	 * Get search statistics
	 * 
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Statistics
	 */
	public function get_statistics($start_date = null, $end_date = null)
	{
		$this->db->select('
			COUNT(*) as total_searches,
			COUNT(DISTINCT query_text) as unique_queries,
			COUNT(DISTINCT user_id) as unique_users,
			COUNT(DISTINCT session_id) as unique_sessions,
			AVG(results_count) as avg_results
		');
		$this->db->from('search_logs');
		
		if ($start_date) {
			$this->db->where('DATE(created_at) >=', $start_date);
		}
		
		if ($end_date) {
			$this->db->where('DATE(created_at) <=', $end_date);
		}
		
		$query = $this->db->get();
		
		if ($query && $query->num_rows() > 0) {
			return $query->row_array();
		}
		
		return array(
			'total_searches' => 0,
			'unique_queries' => 0,
			'unique_users' => 0,
			'unique_sessions' => 0,
			'avg_results' => 0
		);
	}
}

/* End of file Search_log_model.php */
/* Location: ./application/models/Search_log_model.php */


