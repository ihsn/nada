<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Tracker Library
 * 
 *
 */
class Analytics_tracker {
	
	protected $CI;
	protected $event_tracker;
	protected $analytics_model;
	
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model('Analytics_event_tracker_model');
		$this->CI->load->model('Analytics_model');
		$this->event_tracker = $this->CI->Analytics_event_tracker_model;
		$this->analytics_model = $this->CI->Analytics_model;
	}
	
	/**
	 * Track a pageview event
	 * 
	 * @param string $study_id Study identifier
	 * @param string $session_id Optional session ID (from client-side)
	 * @param array $data Optional additional data (referrer, user_agent, etc.)
	 * @return bool Success status
	 */
	public function track_pageview($study_id, $session_id = null, $data = array())
	{
		if (!$this->CI->config->item('analytics_enabled')) {
			return false;
		}
		return $this->event_tracker->track_pageview($study_id, $session_id, $data);
	}
	
	/**
	 * Track a download event
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name (not file_id, since files can be deleted)
	 * @param array $data Optional additional data (file_type, user_agent, etc.)
	 * @return bool Success status
	 */
	public function track_download($study_id, $file_name, $data = array())
	{
		if (!$this->CI->config->item('analytics_enabled')) {
			return false;
		}
		return $this->event_tracker->track_download($study_id, $file_name, $data);
	}
	
	/**
	 * Get study statistics
	 * 
	 * @param string $study_id Study identifier
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Daily statistics
	 */
	public function get_study_daily_stats($study_id, $start_date, $end_date)
	{
		return $this->analytics_model->get_study_daily_stats($study_id, $start_date, $end_date);
	}
	
	/**
	 * Get study monthly statistics
	 * 
	 * @param string $study_id Study identifier
	 * @param int $year Year
	 * @param int $month Month (optional)
	 * @return array Monthly statistics
	 */
	public function get_study_monthly_stats($study_id, $year, $month = null)
	{
		return $this->analytics_model->get_study_monthly_stats($study_id, $year, $month);
	}
	
	/**
	 * Get study totals (all-time)
	 * 
	 * @param string $study_id Study identifier
	 * @return array|null Totals or null if not found
	 */
	public function get_study_totals($study_id)
	{
		return $this->analytics_model->get_study_totals($study_id);
	}
	
	/**
	 * Get file statistics
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Daily statistics
	 */
	public function get_file_daily_stats($study_id, $file_name, $start_date, $end_date)
	{
		return $this->analytics_model->get_file_daily_stats($study_id, $file_name, $start_date, $end_date);
	}
	
	/**
	 * Get file monthly statistics
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @param int $year Year
	 * @param int $month Month (optional)
	 * @return array Monthly statistics
	 */
	public function get_file_monthly_stats($study_id, $file_name, $year, $month = null)
	{
		return $this->analytics_model->get_file_monthly_stats($study_id, $file_name, $year, $month);
	}
	
	/**
	 * Get file totals (all-time)
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @return int|null Total downloads or null if not found
	 */
	public function get_file_totals($study_id, $file_name)
	{
		return $this->analytics_model->get_file_totals($study_id, $file_name);
	}
}

/* End of file Analytics_tracker.php */
/* Location: ./application/libraries/Analytics_tracker.php */

