<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Status Model
 * 
 * Manages aggregation job status tracking and progress monitoring
 * Handles concurrency control and stale run detection
 */
class Analytics_status_model extends CI_Model {
	
	// Status constants
	const STATUS_IDLE = 'idle';
	const STATUS_RUNNING = 'running';
	const STATUS_COMPLETED = 'completed';
	const STATUS_FAILED = 'failed';
	
	const STALE_TIMEOUT_MINUTES = 7;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Get current aggregation status
	 * 
	 * @return array Status information
	 */
	public function get_current_status()
	{
		$this->db->select('*');
		$this->db->from('analytics_aggregation_status');
		$this->db->order_by('id', 'DESC');
		$this->db->limit(1);
		$query = $this->db->get();
		
		if ($query && $query->num_rows() > 0) {
			$row = $query->row_array();
			
			// Check for stale runs (running but last updated > 5 minutes ago)
			if ($row['status'] === self::STATUS_RUNNING && $row['last_updated_at']) {
				if ($this->is_stale_run($row)) {
					// Mark as failed (stale)
					$minutes_ago = $this->get_minutes_since_update($row['last_updated_at']);
					$this->mark_as_stale($row['id'], $minutes_ago);
					$row['status'] = self::STATUS_FAILED;
					$row['error_message'] = 'Run timed out (no update for ' . round($minutes_ago) . ' minutes)';
				}
			}
			
			return $row;
		}
		
		// No status found, return default
		return $this->get_default_status();
	}
	
	/**
	 * Get the most recent completed aggregation run (for "last run" display).
	 *
	 * @return array|null Array with 'completed_at', 'started_at'; or null if no completed run
	 */
	public function get_last_completed_run()
	{
		$this->db->select('completed_at, started_at');
		$this->db->from('analytics_aggregation_status');
		$this->db->where('status', self::STATUS_COMPLETED);
		$this->db->where('completed_at IS NOT NULL', null, false);
		$this->db->order_by('id', 'DESC');
		$this->db->limit(1);
		$query = $this->db->get();
		
		if ($query && $query->num_rows() > 0) {
			return $query->row_array();
		}
		return null;
	}
	
	/**
	 * Initialize aggregation status for a new run
	 * 
	 * @param string $context 'cli' or 'web'
	 * @param int $user_id Optional user ID for web context
	 * @return bool Success
	 */
	public function create_new_run($context = 'cli', $user_id = null)
	{
		$status = $this->get_current_status();
		
		// Check if already running
		if ($status['status'] === self::STATUS_RUNNING) {
			// Check if stale
			if ($status['last_updated_at']) {
				$minutes_ago = $this->get_minutes_since_update($status['last_updated_at']);
				
				if ($minutes_ago > self::STALE_TIMEOUT_MINUTES) {
					// Stale, mark as failed and start new
					$this->db->where('id', $status['id']);
					$this->db->update('analytics_aggregation_status', array(
						'status' => self::STATUS_FAILED,
						'error_message' => 'Previous run timed out',
						'completed_at' => date('Y-m-d H:i:s')
					));
				} else {
					// Still running, can't start new
					return false;
				}
			}
		}
		
		// Update or insert status
		if ($status['id']) {
			$this->db->where('id', $status['id']);
			$this->db->update('analytics_aggregation_status', array(
				'status' => self::STATUS_RUNNING,
				'current_step' => null,
				'current_item' => null,
				'total_items' => 0,
				'processed_items' => 0,
				'progress_percent' => 0,
				'message' => 'Starting aggregation...',
				'started_at' => date('Y-m-d H:i:s'),
				'completed_at' => null,
				'last_updated_at' => date('Y-m-d H:i:s'),
				'error_message' => null,
				'context' => $context,
				'user_id' => $user_id
			));
		} else {
			$this->db->insert('analytics_aggregation_status', array(
				'status' => self::STATUS_RUNNING,
				'current_step' => null,
				'current_item' => null,
				'total_items' => 0,
				'processed_items' => 0,
				'progress_percent' => 0,
				'message' => 'Starting aggregation...',
				'started_at' => date('Y-m-d H:i:s'),
				'last_updated_at' => date('Y-m-d H:i:s'),
				'context' => $context,
				'user_id' => $user_id
			));
		}
		
		return true;
	}
	
	/**
	 * Update aggregation status progress
	 * 
	 * @param array $data Status data to update
	 * @return bool Success
	 */
	public function update_progress($data)
	{
		$status = $this->get_current_status();
		
		if (!$status['id']) {
			return false;
		}
		
		$update_data = array(
			'last_updated_at' => date('Y-m-d H:i:s')
		);
		
		if (isset($data['current_step'])) {
			$update_data['current_step'] = $data['current_step'];
		}
		if (isset($data['current_item'])) {
			$update_data['current_item'] = $data['current_item'];
		}
		if (isset($data['total_items'])) {
			$update_data['total_items'] = $data['total_items'];
		}
		if (isset($data['processed_items'])) {
			$update_data['processed_items'] = $data['processed_items'];
		}
		if (isset($data['progress_percent'])) {
			$update_data['progress_percent'] = $data['progress_percent'];
		}
		if (isset($data['message'])) {
			$update_data['message'] = $data['message'];
		}
		if (isset($data['error_message'])) {
			$update_data['error_message'] = $data['error_message'];
			$update_data['status'] = self::STATUS_FAILED;
			$update_data['completed_at'] = date('Y-m-d H:i:s');
		}
		
		$this->db->where('id', $status['id']);
		return $this->db->update('analytics_aggregation_status', $update_data);
	}
	
	/**
	 * Mark aggregation as completed
	 * 
	 * @return bool Success
	 */
	public function mark_completed()
	{
		$status = $this->get_current_status();
		
		if (!$status['id']) {
			return false;
		}
		
		$this->db->where('id', $status['id']);
		return $this->db->update('analytics_aggregation_status', array(
			'status' => self::STATUS_COMPLETED,
			'progress_percent' => 100,
			'message' => 'Aggregation completed successfully',
			'completed_at' => date('Y-m-d H:i:s'),
			'last_updated_at' => date('Y-m-d H:i:s')
		));
	}
	
	/**
	 * Mark aggregation as stopped by user
	 * 
	 * @return bool Success
	 */
	public function mark_stopped()
	{
		$status = $this->get_current_status();
		
		if (!$status['id']) {
			return false;
		}
		
		$this->db->where('id', $status['id']);
		return $this->db->update('analytics_aggregation_status', array(
			'status' => self::STATUS_IDLE,
			'current_step' => null,
			'current_item' => null,
			'message' => 'Stopped by user',
			'completed_at' => date('Y-m-d H:i:s'),
			'last_updated_at' => date('Y-m-d H:i:s')
		));
	}
	
	/**
	 * Touch last_updated_at without triggering stale detection.
	 * Call this at the START of any step that may run for several minutes
	 * (e.g. cleanup, sync_counters) so the stale window runs from when the
	 * step began, not from when the previous step completed.
	 *
	 * Uses a direct query to bypass the stale check inside get_current_status().
	 *
	 * @return bool Success
	 */
	public function touch_heartbeat()
	{
		if ($this->db->dbdriver === 'sqlsrv') {
			return $this->db->query(
				"UPDATE TOP(1) analytics_aggregation_status SET last_updated_at = ? WHERE [status] = ?",
				array(date('Y-m-d H:i:s'), self::STATUS_RUNNING)
			);
		}
		return $this->db->query(
			"UPDATE analytics_aggregation_status SET last_updated_at = ? WHERE status = ? ORDER BY id DESC LIMIT 1",
			array(date('Y-m-d H:i:s'), self::STATUS_RUNNING)
		);
	}

	/**
	 * Mark aggregation as failed
	 * 
	 * @param string $error_message Error message
	 * @return bool Success
	 */
	public function mark_failed($error_message)
	{
		$status = $this->get_current_status();
		
		if (!$status['id']) {
			return false;
		}
		
		$this->db->where('id', $status['id']);
		return $this->db->update('analytics_aggregation_status', array(
			'status' => self::STATUS_FAILED,
			'error_message' => $error_message,
			'completed_at' => date('Y-m-d H:i:s'),
			'last_updated_at' => date('Y-m-d H:i:s')
		));
	}
	
	/**
	 * Check if a run is currently active
	 * 
	 * @return bool True if running
	 */
	public function is_running()
	{
		$status = $this->get_current_status();
		return $status['status'] === self::STATUS_RUNNING;
	}
	
	/**
	 * Check if current run can be started (no other run is active)
	 * 
	 * @return bool True if can start
	 */
	public function can_start_new_run()
	{
		$status = $this->get_current_status();
		
		if ($status['status'] !== self::STATUS_RUNNING) {
			return true;
		}
		
		// Check if stale
		if ($status['last_updated_at']) {
			$minutes_ago = $this->get_minutes_since_update($status['last_updated_at']);
			return $minutes_ago > self::STALE_TIMEOUT_MINUTES;
		}
		
		return false;
	}
	
	/**
	 * Get default status structure
	 * 
	 * @return array Default status
	 */
	private function get_default_status()
	{
		return array(
			'id' => null,
			'status' => self::STATUS_IDLE,
			'current_step' => null,
			'current_item' => null,
			'total_items' => 0,
			'processed_items' => 0,
			'progress_percent' => 0,
			'message' => 'Ready',
			'started_at' => null,
			'completed_at' => null,
			'last_updated_at' => null,
			'error_message' => null,
			'context' => 'cli',
			'user_id' => null
		);
	}
	
	/**
	 * Check if a run record is stale
	 * 
	 * @param array $status_row Status record
	 * @return bool True if stale
	 */
	private function is_stale_run($status_row)
	{
		if (!isset($status_row['last_updated_at']) || !$status_row['last_updated_at']) {
			return false;
		}
		
		$minutes_ago = $this->get_minutes_since_update($status_row['last_updated_at']);
		return $minutes_ago > self::STALE_TIMEOUT_MINUTES;
	}
	
	/**
	 * Get minutes since last update
	 * 
	 * @param string $timestamp Last updated timestamp
	 * @return float Minutes since update
	 */
	private function get_minutes_since_update($timestamp)
	{
		$last_updated = strtotime($timestamp);
		$now = time();
		return ($now - $last_updated) / 60;
	}
	
	/**
	 * Mark a run as stale/timed out
	 * 
	 * @param int $id Status record ID
	 * @param float $minutes_ago Minutes since last update
	 * @return bool Success
	 */
	private function mark_as_stale($id, $minutes_ago)
	{
		$this->db->where('id', $id);
		return $this->db->update('analytics_aggregation_status', array(
			'status' => self::STATUS_FAILED,
			'error_message' => 'Run timed out (no update for ' . round($minutes_ago) . ' minutes)',
			'completed_at' => date('Y-m-d H:i:s')
		));
	}
}

/* End of file Analytics_status_model.php */
/* Location: ./application/models/Analytics_status_model.php */
