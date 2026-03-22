<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Model
 * 
 * Handles tracking and aggregation of pageviews and downloads
 *
 */
class Analytics_model extends CI_Model {

	private $status_manager;
	private $aggregator;
	
	public function __construct()
	{
		parent::__construct();
		$this->load->config('bots');
		$this->load->model('Analytics_status_model');
		$this->load->model('Analytics_aggregator_model');
		$this->status_manager = $this->Analytics_status_model;
		$this->aggregator = $this->Analytics_aggregator_model;
	}
	

	/**
	 * Aggregate pageview events to daily study aggregates
	 * 
	 * @param string $date Date in Y-m-d format (default: yesterday)
	 * @return array Result summary
	 */
	public function aggregate_pageviews_daily($date = null)
	{
		return $this->aggregator->aggregate_pageviews_daily($date);
	}
	
	/**
	 * Calculate unique visitors for a specific study and date
	 * 
	 * @param string $date Date in Y-m-d format
	 * @param string $study_id Study identifier
	 * @return int Number of unique visitors
	 */
	public function calculate_unique_visitors($date, $study_id)
	{
		return $this->aggregator->calculate_unique_visitors($date, $study_id);
	}
	
	
	/**
	 * Aggregate download events to daily file aggregates
	 * 
	 * @param string $date Date in Y-m-d format (default: yesterday)
	 * @return array Result
	 */
	public function aggregate_downloads_daily($date = null)
	{
		return $this->aggregator->aggregate_downloads_daily($date);
	}
	
	/**
	 * Update study-level download totals from file aggregates
	 * 
	 * @param string $date Date in Y-m-d format
	 * @return bool Success status
	 */
	// Daily download totals are maintained in Analytics_aggregator_model
		
	
	/**
	 * Roll up daily aggregates to monthly aggregates
	 * 
	 * @param int $year Year (default: current year)
	 * @param int $month Month (default: previous month)
	 * @return array Result summary
	 */
	public function aggregate_daily_to_monthly($year = null, $month = null)
	{
		return $this->aggregator->aggregate_daily_to_monthly($year, $month);
	}
	
	/**
	 * Finalize a month - mark monthly aggregates as finalized
	 * 
	 * Once finalized, monthly aggregates cannot be re-aggregated.
	 * This protects historical data from accidental corruption.
	 * 
	 * @param int $year Year
	 * @param int $month Month (1-12)
	 * @return array Result with 'success', 'studies_finalized', 'files_finalized'
	 */
	public function finalize_month($year, $month)
	{
		return $this->aggregator->finalize_month($year, $month);
	}
	
	/**
	 * Clean up raw events older than retention period
	 * 
	 * Deletes raw events from analytics_pageview_events and analytics_download_events
	 * that are older than the specified retention days.
	 * 
	 * @param int $retention_days Number of days to keep raw events (default: 60)
	 * @return array Result with 'deleted_pageviews', 'deleted_downloads', 'errors'
	 */
	public function cleanup_raw_events($retention_days = 60)
	{
		return $this->aggregator->cleanup_raw_events($retention_days);
	}
	
	/**
	 * Clean up daily aggregates for finalized months
	 * 
	 * Deletes daily aggregates (analytics_daily_studies and analytics_daily_files)
	 * for months that have been finalized. Only keeps daily aggregates for the
	 * current month (non-finalized).
	 * 
	 * @param int $year Year to cleanup (default: previous month)
	 * @param int $month Month to cleanup (default: previous month)
	 * @return array Result with 'deleted_daily_studies', 'deleted_daily_files', 'errors'
	 */
	public function cleanup_daily_aggregates($year = null, $month = null)
	{
		return $this->aggregator->cleanup_daily_aggregates($year, $month);
	}

	
	/**
	 * Chunked daily pageview aggregation
	 * Processes studies in batches to avoid timeouts
	 * 
	 * @param string $date Date in Y-m-d format
	 * @param int $offset Starting offset for studies
	 * @param int $limit Number of studies to process per chunk
	 * @param int $max_time_seconds Maximum execution time (default: 25 seconds)
	 * @return array Result with progress information
	 */
	public function aggregate_pageviews_daily_chunked($date = null, $offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		return $this->aggregator->aggregate_pageviews_daily_chunked($date, $offset, $limit, $max_time_seconds);
	}
	
	/**
	 * Chunked daily download aggregation
	 * Processes file combinations in batches to avoid timeouts
	 * 
	 * @param string $date Date in Y-m-d format
	 * @param int $offset Starting offset for files
	 * @param int $limit Number of file combinations to process per chunk
	 * @param int $max_time_seconds Maximum execution time (default: 25 seconds)
	 * @return array Result with progress information
	 */
	public function aggregate_downloads_daily_chunked($date = null, $offset = 0, $limit = 100, $max_time_seconds = 25)
	{
		return $this->aggregator->aggregate_downloads_daily_chunked($date, $offset, $limit, $max_time_seconds);
	}
	
	/**
	 * Chunked monthly rollup aggregation
	 * Processes studies in batches to avoid timeouts
	 * 
	 * @param int $year Year
	 * @param int $month Month (1-12)
	 * @param int $offset Starting offset for studies
	 * @param int $limit Number of studies to process per chunk
	 * @param int $max_time_seconds Maximum execution time (default: 25 seconds)
	 * @return array Result with progress information
	 */
	public function aggregate_daily_to_monthly_chunked($year = null, $month = null, $offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		return $this->aggregator->aggregate_daily_to_monthly_chunked($year, $month, $offset, $limit, $max_time_seconds);
	}
	
	/**
	 * Chunked legacy totals update
	 * Processes studies in batches to avoid timeouts
	 * 
	 * @param int $offset Starting offset for studies
	 * @param int $limit Number of studies to process per chunk
	 * @param int $max_time_seconds Maximum execution time (default: 25 seconds)
	 * @return array Result with progress information
	 */
	public function update_legacy_totals_chunked($offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		return $this->aggregator->update_legacy_totals_chunked($offset, $limit, $max_time_seconds);
	}
	
	
	/**
	 * Update legacy totals (year=0, month=0) for a study
	 * 
	 * @param string $study_id Study identifier (optional, updates all if null)
	 * @return bool Success status
	 */
	public function update_legacy_totals($study_id = null)
	{
		return $this->aggregator->update_legacy_totals($study_id);
	}
	
	
	/**
	 * Get daily statistics for a study
	 * 
	 * @param string $study_id Study identifier
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Daily statistics
	 */
	public function get_study_daily_stats($study_id, $start_date, $end_date)
	{
		$this->db->select('date, pageviews, unique_visitors, downloads');
		$this->db->where('study_id', $study_id);
		$this->db->where('date >=', $start_date);
		$this->db->where('date <=', $end_date);
		$this->db->order_by('date', 'ASC');
		$query = $this->db->get('analytics_daily_studies');
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Get monthly statistics for a study
	 * 
	 * @param string $study_id Study identifier
	 * @param int $year Year (optional)
	 * @param int $month Month (optional)
	 * @return array Monthly statistics
	 */
	public function get_study_monthly_stats($study_id, $year = null, $month = null)
	{
		$this->db->select('year, month, pageviews, unique_visitors, downloads');
		$this->db->where('study_id', $study_id);
		
		if ($year !== null) {
			$this->db->where('year', $year);
		}
		
		if ($month !== null) {
			$this->db->where('month', $month);
		}
		
		// Exclude legacy totals (year=0, month=0) unless specifically requested
		if ($year === null || $year != 0) {
			$this->db->where('(year != 0 OR month != 0)', null, false);
		}
		
		$this->db->order_by('year', 'ASC');
		$this->db->order_by('month', 'ASC');
		$query = $this->db->get('analytics_monthly_studies');
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Get all-time totals for a study (from legacy totals)
	 * 
	 * @param string $study_id Study identifier
	 * @return array|null Study totals or null if not found
	 */
	public function get_study_totals($study_id)
	{
		$this->db->select('pageviews, unique_visitors, downloads');
		$this->db->where('study_id', $study_id);
		$this->db->where('year', 0);
		$this->db->where('month', 0);
		$query = $this->db->get('analytics_monthly_studies');
		
		if ($query && $query->num_rows() > 0) {
			return $query->row_array();
		}
		
		return null;
	}
	
	
	/**
	 * Get daily statistics for a file
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Daily statistics
	 */
	public function get_file_daily_stats($study_id, $file_name, $start_date, $end_date)
	{
		$this->db->select('date, study_id, downloads');
		$this->db->where('study_id', $study_id);
		$this->db->where('file_name', $file_name);
		$this->db->where('date >=', $start_date);
		$this->db->where('date <=', $end_date);
		$this->db->order_by('date', 'ASC');
		$query = $this->db->get('analytics_daily_files');
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Get monthly statistics for a file
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @param int $year Year (optional)
	 * @param int $month Month (optional)
	 * @return array Monthly statistics
	 */
	public function get_file_monthly_stats($study_id, $file_name, $year = null, $month = null)
	{
		$this->db->select('year, month, study_id, downloads');
		$this->db->where('study_id', $study_id);
		$this->db->where('file_name', $file_name);
		
		if ($year !== null) {
			$this->db->where('year', $year);
		}
		
		if ($month !== null) {
			$this->db->where('month', $month);
		}
		
		// Exclude legacy totals unless specifically requested
		if ($year === null || $year != 0) {
			$this->db->where('(year != 0 OR month != 0)', null, false);
		}
		
		$this->db->order_by('year', 'ASC');
		$this->db->order_by('month', 'ASC');
		$query = $this->db->get('analytics_monthly_files');
		
		if ($query) {
			return $query->result_array();
		}
		
		return array();
	}
	
	/**
	 * Get all-time totals for a file (from legacy totals)
	 * 
	 * @param string $study_id Study identifier
	 * @param string $file_name File name
	 * @return int|null Total downloads or null if not found
	 */
	public function get_file_totals($study_id, $file_name)
	{
		$this->db->select('downloads');
		$this->db->where('study_id', $study_id);
		$this->db->where('file_name', $file_name);
		$this->db->where('year', 0);
		$this->db->where('month', 0);
		$query = $this->db->get('analytics_monthly_files');
		
		if ($query && $query->num_rows() > 0) {
			$row = $query->row();
			return (int)$row->downloads;
		}
		
		return null;
	}
	
	
	/**
	 * Migrate legacy totals from surveys table to analytics_monthly_studies
	 * 
	 * One-time migration: Reads total_views and total_downloads from surveys table
	 * and creates all-time totals (year=0, month=0) in analytics_monthly_studies.
	 * 
	 * Note: This method is primarily used by the database migration.
	 * For one-time migration, use: php index.php cli/database_migration migrate
	 * 
	 * @param string|null $study_id Optional study ID (migrates all if omitted)
	 * @return array Result with 'success' (bool), 'migrated' (int), and 'errors' (array)
	 */
	public function migrate_legacy_totals_from_surveys($study_id = null)
	{
		$result = array(
			'success' => false,
			'migrated' => 0,
			'errors' => array()
		);
		
		try {
			// Get surveys with non-zero totals
			$this->db->select('s.id as study_id, s.total_views, s.total_downloads');
			$this->db->from('surveys s');
			$this->db->where('(s.total_views > 0 OR s.total_downloads > 0)', null, false);
			
			if ($study_id !== null) {
				$this->db->where('s.id', $study_id);
			}
			
			$query = $this->db->get();
			
			if (!$query) {
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to fetch surveys: " . $error_msg;
				return $result;
			}
			
			if ($query->num_rows() === 0) {
				$result['success'] = true;
				return $result;
			}
			
			$migrated = 0;
			foreach ($query->result_array() as $row) {
				// Check if all-time totals already exist for this study
				$this->db->where('year', 0);
				$this->db->where('month', 0);
				$this->db->where('study_id', $row['study_id']);
				$existing = $this->db->get('analytics_monthly_studies')->row();
				
				$data = array(
					'year' => 0,
					'month' => 0,
					'study_id' => $row['study_id'],
					'pageviews' => (int)$row['total_views'],
					'unique_visitors' => 0, // We don't have this data from surveys table
					'downloads' => (int)$row['total_downloads']
				);
				
				if ($existing) {
					// Update existing row - overwrite with legacy totals
					$this->db->where('year', 0);
					$this->db->where('month', 0);
					$this->db->where('study_id', $row['study_id']);
					$update_result = $this->db->update('analytics_monthly_studies', array(
						'pageviews' => (int)$row['total_views'],
						'downloads' => (int)$row['total_downloads']
					));
				} else {
					// Insert new row
					$update_result = $this->db->insert('analytics_monthly_studies', $data);
				}
				
				if ($update_result) {
					$migrated++;
				} else {
					$db_error = $this->db->error();
					$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
					$result['errors'][] = "Failed to migrate study_id {$row['study_id']}: " . $error_msg;
				}
			}
			
			$result['migrated'] = $migrated;
			$result['success'] = (count($result['errors']) === 0);
			
		} catch (Exception $e) {
			$result['errors'][] = "Exception: " . $e->getMessage();
		}
		
		return $result;
	}
	
	
	/**
	 * Sync study counters from analytics aggregates to surveys table
	 * 
	 * Updates surveys.total_views and surveys.total_downloads by calculating
	 * current totals from monthly aggregates (year != 0, month != 0) and adding
	 * them to legacy totals (year=0, month=0) which remain static from migration.
	 * 
	 * Note: year=0, month=0 records are NOT updated - they remain static from
	 * the legacy migration. Current totals are calculated on-the-fly.
	 * 
	 * - Processing in batches to limit memory usage
	 * - Using batch updates to reduce query count
	 * - Using transactions for better performance
	 * 
	 * @param string|null $study_id Optional study ID to sync (syncs all if omitted)
	 * @param int $batch_size Number of records to process per batch (default: 500)
	 * @return array Result array with 'success' (bool), 'updated' (int), and 'errors' (array)
	 */
	public function sync_counters($study_id = null, $batch_size = 500)
	{
		$result = array(
			'success' => false,
			'updated' => 0,
			'errors' => array()
		);
		
		$batch_size = max(100, min(1000, (int)$batch_size)); // Clamp between 100 and 1000
		
		try {
			// Start transaction for better performance
			$this->db->trans_start();
			
			// Calculate totals: legacy (year=0, month=0) + finalized/past monthly aggregates
			// (excluding the current month) + current month from daily.
			//
			// Why exclude current month from monthly:
			// Daily rows for a non-finalized month are NOT deleted after the monthly rollup,
			// so both the monthly row and the daily rows exist simultaneously. To avoid
			// double-counting we use one source per month: monthly for all past months,
			// daily for the in-progress current month (which always has the freshest data
			// including today's events that haven't been rolled to monthly yet).
			$db_driver = $this->db->dbdriver;
			$current_year  = (int)date('Y');
			$current_month = (int)date('n');
			
			if ($db_driver === 'mysqli') {
				// MySQL: Use COALESCE and LEFT JOIN
				$sql = "
					SELECT 
						COALESCE(legacy.study_id, current.study_id) as study_id,
						COALESCE(legacy.pageviews, 0) + COALESCE(current.pageviews, 0) as pageviews,
						COALESCE(legacy.downloads, 0) + COALESCE(current.downloads, 0) as downloads
					FROM (
						SELECT study_id, pageviews, downloads
						FROM analytics_monthly_studies
						WHERE year = 0 AND month = 0
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
					) legacy
					LEFT JOIN (
						SELECT 
							study_id,
							SUM(pageviews) as pageviews,
							SUM(downloads) as downloads
						FROM analytics_monthly_studies
						WHERE year != 0 AND month != 0
							AND NOT (year = {$current_year} AND month = {$current_month})
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
						GROUP BY study_id
					) current ON legacy.study_id = current.study_id
					
					UNION
					
					SELECT 
						current.study_id,
						COALESCE(legacy.pageviews, 0) + COALESCE(current.pageviews, 0) as pageviews,
						COALESCE(legacy.downloads, 0) + COALESCE(current.downloads, 0) as downloads
					FROM (
						SELECT 
							study_id,
							SUM(pageviews) as pageviews,
							SUM(downloads) as downloads
						FROM analytics_monthly_studies
						WHERE year != 0 AND month != 0
							AND NOT (year = {$current_year} AND month = {$current_month})
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
						GROUP BY study_id
					) current
					LEFT JOIN (
						SELECT study_id, pageviews, downloads
						FROM analytics_monthly_studies
						WHERE year = 0 AND month = 0
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
					) legacy ON legacy.study_id = current.study_id
					WHERE legacy.study_id IS NULL
				";
			} else {
				// SQL Server: Use FULL OUTER JOIN
				$sql = "
					SELECT 
						COALESCE(legacy.study_id, current.study_id) as study_id,
						COALESCE(legacy.pageviews, 0) + COALESCE(current.pageviews, 0) as pageviews,
						COALESCE(legacy.downloads, 0) + COALESCE(current.downloads, 0) as downloads
					FROM (
						SELECT study_id, pageviews, downloads
						FROM analytics_monthly_studies
						WHERE year = 0 AND month = 0
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
					) legacy
					FULL OUTER JOIN (
						SELECT 
							study_id,
							SUM(pageviews) as pageviews,
							SUM(downloads) as downloads
						FROM analytics_monthly_studies
						WHERE year != 0 AND month != 0
							AND NOT (year = {$current_year} AND month = {$current_month})
				";
				if ($study_id !== null) {
					$sql .= " AND study_id = " . $this->db->escape($study_id);
				}
				$sql .= "
						GROUP BY study_id
					) current ON legacy.study_id = current.study_id
				";
			}
			
			$query = $this->db->query($sql);
			
			if (!$query) {
				$this->db->trans_rollback();
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to fetch analytics data: " . $error_msg;
				return $result;
			}
			
			$totals_map = array();
			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$sid = (int)$row['study_id'];  // use $sid to avoid overwriting the $study_id filter parameter
					$totals_map[$sid] = array(
						'pageviews' => (int)$row['pageviews'],
						'downloads' => (int)$row['downloads']
					);
				}
			}

			// Add current-month daily aggregates. Daily rows for non-finalized months are NOT
			// deleted after the monthly rollup, so they always reflect the latest data including
			// today's in-progress events. The monthly query above excludes the current month
			// entirely to prevent double-counting.
			// Finalized months are intentionally excluded — once a month is finalized, any
			// late events tracked for that period are ignored by design.
			$current_first_day = sprintf('%04d-%02d-01', $current_year, $current_month);
			$current_last_day  = date('Y-m-t', strtotime($current_first_day));
			$daily_sql = "
				SELECT study_id, SUM(pageviews) as pageviews, SUM(downloads) as downloads
				FROM analytics_daily_studies
				WHERE date >= ? AND date <= ?
			";
			$params = array($current_first_day, $current_last_day);
			if ($study_id !== null) {  // $study_id is still the original filter parameter here
				$daily_sql .= " AND study_id = ?";
				$params[] = $study_id;
			}
			$daily_sql .= " GROUP BY study_id";
			$daily_query = $this->db->query($daily_sql, $params);
			if ($daily_query && $daily_query->num_rows() > 0) {
				foreach ($daily_query->result_array() as $row) {
					$sid = (int)$row['study_id'];
					$pv = (int)$row['pageviews'];
					$dl = (int)$row['downloads'];
					if (!isset($totals_map[$sid])) {
						$totals_map[$sid] = array('pageviews' => 0, 'downloads' => 0);
					}
					$totals_map[$sid]['pageviews'] += $pv;
					$totals_map[$sid]['downloads'] += $dl;
				}
			}

			if (empty($totals_map)) {
				$this->db->trans_complete();
				$result['success'] = true;
				return $result;
			}

			$all_rows = array();
			foreach ($totals_map as $study_id => $totals) {
				$all_rows[] = array(
					'study_id' => $study_id,
					'pageviews' => $totals['pageviews'],
					'downloads' => $totals['downloads']
				);
			}
			
			$total_rows = count($all_rows);
			$updated = 0;
			
			// Process in batches to limit memory and improve performance
			for ($offset = 0; $offset < $total_rows; $offset += $batch_size) {
				$batch = array_slice($all_rows, $offset, $batch_size);
				
				if (empty($batch)) {
					break;
				}
				
				// Build batch update using CASE statements (works with query builder)
				$study_ids = array();
				$cases_views = array();
				$cases_downloads = array();
				
				foreach ($batch as $row) {
					$study_id_escaped = $this->db->escape($row['study_id']);
					$pageviews = (int)$row['pageviews'];
					$downloads = (int)$row['downloads'];
					
					$study_ids[] = $study_id_escaped;
					$cases_views[] = "WHEN id = {$study_id_escaped} THEN {$pageviews}";
					$cases_downloads[] = "WHEN id = {$study_id_escaped} THEN {$downloads}";
				}
				
				if (empty($study_ids)) {
					continue;
				}
				
				// Build the batch update query
				$case_views_sql = implode(' ', $cases_views);
				$case_downloads_sql = implode(' ', $cases_downloads);
				$study_ids_sql = implode(',', $study_ids);
				
				$sql = "
					UPDATE surveys
					SET 
						total_views = CASE {$case_views_sql} ELSE total_views END,
						total_downloads = CASE {$case_downloads_sql} ELSE total_downloads END
					WHERE id IN ({$study_ids_sql})
				";
				
				$update_result = $this->db->query($sql);
				
				if ($update_result !== false) {
					$updated += $this->db->affected_rows();
				} else {
					$db_error = $this->db->error();
					$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
					$result['errors'][] = "Failed to update batch (offset {$offset}): " . $error_msg;
				}
			}
			
			// Complete transaction
			$this->db->trans_complete();
			
			if ($this->db->trans_status() === false) {
				$result['errors'][] = "Transaction failed";
			}
			
			$result['updated'] = $updated;
			$result['success'] = (count($result['errors']) === 0);
			
		} catch (Exception $e) {
			$this->db->trans_rollback();
			$result['errors'][] = "Exception: " . $e->getMessage();
		}
		
		return $result;
	}
	
	
	
	/**
	 * Check if legacy migration is needed.
	 *
	 * Returns true if analytics_monthly_studies has no year=0, month=0 rows,
	 * meaning legacy counts from the surveys table have not yet been seeded into
	 * the analytics system.
	 *
	 * @return bool
	 */
	public function needs_legacy_migration()
	{
		// Backup needed: analytics_legacy_counts is still empty
		$backup_query = $this->db->query("SELECT COUNT(*) as cnt FROM analytics_legacy_counts");
		if ($backup_query) {
			$backup_count = (int)$backup_query->row()->cnt;
			if ($backup_count === 0) {
				// Check if surveys table has any non-zero legacy counts worth backing up
				$survey_query = $this->db->query(
					"SELECT COUNT(*) as cnt FROM surveys WHERE COALESCE(total_views, 0) > 0 OR COALESCE(total_downloads, 0) > 0"
				);
				if ($survey_query && (int)$survey_query->row()->cnt > 0) {
					return true;
				}
			}
		}

		// Seed needed: analytics_monthly_studies has no year=0, month=0 rows
		// but analytics_legacy_counts has data waiting to be seeded
		$legacy_query = $this->db->query("SELECT COUNT(*) as cnt FROM analytics_legacy_counts");
		$legacy_count = $legacy_query ? (int)$legacy_query->row()->cnt : 0;
		if ($legacy_count > 0) {
			$seeded_query = $this->db->query("SELECT COUNT(*) as cnt FROM analytics_monthly_studies WHERE year = 0 AND month = 0");
			$seeded_count = $seeded_query ? (int)$seeded_query->row()->cnt : 0;
			if ($seeded_count < $legacy_count) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Migrate legacy counts from surveys table into the analytics system.
	 *
	 * Step 1 – Backup: if analytics_legacy_counts is empty, copy total_views and
	 *          total_downloads from the surveys table into analytics_legacy_counts.
	 * Step 2 – Seed: insert a year=0, month=0 row into analytics_monthly_studies
	 *          for every survey that is present in analytics_legacy_counts but
	 *          does not already have a legacy-total row there.
	 *
	 * Both operations are bulk SQL, safe for catalogs with tens-of-thousands of
	 * surveys, and idempotent – repeated calls will not double-count.
	 *
	 * @return array Result with 'success' (bool), 'backed_up' (int rows),
	 *               'migrated' (int rows), 'skipped' (int rows already present)
	 */
	public function migrate_legacy_counts()
	{
		$result = array(
			'success'  => false,
			'backed_up' => 0,
			'migrated' => 0,
			'skipped'  => 0,
			'errors'   => array()
		);

		try {
			$db_driver = $this->db->dbdriver;

			// ----------------------------------------------------------------
			// Step 1: Backup legacy totals if not already done
			// ----------------------------------------------------------------
			$count_query = $this->db->query("SELECT COUNT(*) as cnt FROM analytics_legacy_counts");
			$legacy_count = $count_query ? (int)$count_query->row()->cnt : 0;

			if ($legacy_count === 0) {
				// Bulk-insert all surveys that have non-zero legacy counts
				$sql = "INSERT INTO analytics_legacy_counts (survey_id, total_views, total_downloads)
				        SELECT id,
				               COALESCE(total_views, 0),
				               COALESCE(total_downloads, 0)
				        FROM surveys
				        WHERE COALESCE(total_views, 0) > 0 OR COALESCE(total_downloads, 0) > 0";
				$this->db->query($sql);
				$result['backed_up'] = $this->db->affected_rows();
			} else {
				$result['skipped'] = $legacy_count;
			}

			// ----------------------------------------------------------------
			// Step 2: Seed year=0, month=0 rows in analytics_monthly_studies
			// for any survey missing a legacy-total entry (idempotent)
			// ----------------------------------------------------------------
			$sql = "INSERT INTO analytics_monthly_studies
			            (year, month, study_id, pageviews, unique_visitors, downloads, finalized)
			        SELECT 0, 0, lc.survey_id, lc.total_views, 0, lc.total_downloads, 1
			        FROM analytics_legacy_counts lc
			        WHERE NOT EXISTS (
			            SELECT 1 FROM analytics_monthly_studies ams
			            WHERE ams.year = 0 AND ams.month = 0 AND ams.study_id = lc.survey_id
			        )";
			$this->db->query($sql);
			$result['migrated'] = $this->db->affected_rows();

			$result['success'] = true;

		} catch (Exception $e) {
			$result['errors'][] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Find dates that have raw events but no daily aggregates
	 * 
	 * Returns an array of dates (Y-m-d format) that need daily aggregation.
	 * Checks from the latest daily aggregate date (or first raw event date) to yesterday.
	 * 
	 * @param string $start_date Optional start date (Y-m-d). If null, uses latest daily aggregate + 1 day
	 * @param string $end_date Optional end date (Y-m-d). If null, uses yesterday
	 * @return array Array of dates that need processing, or empty array if none found
	 */
	public function find_missing_daily_aggregates($start_date = null, $end_date = null)
	{
		return $this->aggregator->find_missing_daily_aggregates($start_date, $end_date);
	}
	
	/**
	 * Get aggregated monthly totals for the last N months
	 * 
	 * Returns sum of pageviews, unique_visitors, and downloads across all studies
	 * for each of the last N months, ordered chronologically.
	 * 
	 * @param int $months Number of months to retrieve (default: 12)
	 * @return array Array of monthly totals with year, month, label, and metrics
	 */
	public function get_monthly_totals($months = 12)
	{
		$months = max(1, min(24, (int)$months)); // clamp 1-24
		$result = array();
		
		// Build list of year-month pairs for last N months (use first day of month to avoid day overflow, e.g. Jan 31 - 2 months -> Dec 1)
		$now = new DateTime();
		$now->setDate((int)$now->format('Y'), (int)$now->format('n'), 1);
		$now->setTime(0, 0, 0);
		for ($i = $months - 1; $i >= 0; $i--) {
			$d = clone $now;
			$d->modify("-{$i} month");
			$year = (int)$d->format('Y');
			$month = (int)$d->format('n');
			$label = $d->format('M Y'); // e.g., "Jan 2025"
			
			// Sum totals for this month across all studies
			$this->db->select('SUM(pageviews) as pageviews, SUM(unique_visitors) as unique_visitors, SUM(downloads) as downloads');
			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$query = $this->db->get('analytics_monthly_studies');
			
			if ($query && $query->num_rows() > 0) {
				$row = $query->row();
				$result[] = array(
					'year' => $year,
					'month' => $month,
					'label' => $label,
					'pageviews' => (int)$row->pageviews,
					'unique_visitors' => (int)$row->unique_visitors,
					'downloads' => (int)$row->downloads
				);
			} else {
				// No data for this month - return zeros
				$result[] = array(
					'year' => $year,
					'month' => $month,
					'label' => $label,
					'pageviews' => 0,
					'unique_visitors' => 0,
					'downloads' => 0
				);
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Get monthly study aggregates with pagination and filtering
	 * 
	 * @param array $filters Filter parameters (year, month, study_id)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @return array Result with data, total, and pagination info
	 */
	public function get_monthly_studies($filters = array(), $limit = 50, $offset = 0)
	{
		$year = isset($filters['year']) ? $filters['year'] : null;
		$month = isset($filters['month']) ? $filters['month'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		
		// Validate limit (0 = no limit for exports)
		$limit = (int)$limit;
		if ($limit > 0) { $limit = min(500, $limit); }
		
		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_monthly_studies WHERE 1=1";
		$count_params = array();
		
		// Exclude all-time totals (year=0, month=0) unless specifically requested
		if ($year === null && $month === null) {
			$count_sql .= " AND (year != 0 OR month != 0)";
		}
		
		// Apply filters for count
		if ($year !== null) {
			$count_sql .= " AND year = ?";
			$count_params[] = (int)$year;
		}
		if ($month !== null) {
			$count_sql .= " AND month = ?";
			$count_params[] = (int)$month;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		
		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;
		
		// Now build the data query (reset query builder first)
		$this->db->reset_query();
		$this->db->select('surveys.title, surveys.nation, surveys.year_start as study_year, surveys.total_views, surveys.total_downloads, year, month, study_id, pageviews, unique_visitors, downloads, finalized, finalized_at');
		$this->db->join('surveys', 'analytics_monthly_studies.study_id = surveys.id', 'left');
		$this->db->from('analytics_monthly_studies');
		
		// Re-apply filters for data query
		if ($year === null && $month === null) {
			$this->db->where('(year != 0 OR month != 0)', null, false);
		}
		if ($year !== null) {
			$this->db->where('year', (int)$year);
		}
		if ($month !== null) {
			$this->db->where('month', (int)$month);
		}
		if ($study_id) {
			$this->db->where('study_id', $study_id);
		}
		
		// Apply sorting
		$this->db->order_by('year', 'DESC');
		$this->db->order_by('month', 'DESC');
		$this->db->order_by('study_id', 'ASC');
		
		// Apply pagination
		if ($limit > 0) { $this->db->limit($limit, $offset); }
		
		$query = $this->db->get();
		
		$data = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
		}
		
		return array(
			'data' => $data,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get monthly file aggregates with pagination and filtering
	 * 
	 * @param array $filters Filter parameters (year, month, study_id)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @return array Result with data, total, and pagination info
	 */
	public function get_monthly_files($filters = array(), $limit = 50, $offset = 0)
	{
		$year = isset($filters['year']) ? $filters['year'] : null;
		$month = isset($filters['month']) ? $filters['month'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		
		// Validate limit (0 = no limit for exports)
		$limit = (int)$limit;
		if ($limit > 0) { $limit = min(500, $limit); }
		
		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_monthly_files WHERE 1=1";
		$count_params = array();
		
		// Exclude all-time totals (year=0, month=0) unless specifically requested
		if ($year === null && $month === null) {
			$count_sql .= " AND (year != 0 OR month != 0)";
		}
		
		// Apply filters for count
		if ($year !== null) {
			$count_sql .= " AND year = ?";
			$count_params[] = (int)$year;
		}
		if ($month !== null) {
			$count_sql .= " AND month = ?";
			$count_params[] = (int)$month;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		
		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;
		
		// Now build the data query (reset query builder first)
		$this->db->reset_query();
		$this->db->select('year, month, study_id, file_name, downloads, finalized, surveys.title, surveys.nation, surveys.year_start as study_year');
		$this->db->join('surveys', 'analytics_monthly_files.study_id = surveys.id', 'left');
		$this->db->from('analytics_monthly_files');
		
		// Re-apply filters for data query
		if ($year === null && $month === null) {
			$this->db->where('(year != 0 OR month != 0)', null, false);
		}
		if ($year !== null) {
			$this->db->where('year', (int)$year);
		}
		if ($month !== null) {
			$this->db->where('month', (int)$month);
		}
		if ($study_id) {
			$this->db->where('study_id', $study_id);
		}
		
		// Apply sorting
		$this->db->order_by('year', 'DESC');
		$this->db->order_by('month', 'DESC');
		$this->db->order_by('study_id', 'ASC');
		$this->db->order_by('downloads', 'DESC');
		
		// Apply pagination
		if ($limit > 0) { $this->db->limit($limit, $offset); }
		
		$query = $this->db->get();
		
		$data = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
		}
		
		return array(
			'data' => $data,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get daily study aggregates with pagination and filtering
	 *
	 * @param array $filters Filter parameters (date_from, date_to, study_id)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @return array Result with data, total, and pagination info
	 */
	public function get_daily_studies($filters = array(), $limit = 50, $offset = 0)
	{
		$date_from = isset($filters['date_from']) ? $filters['date_from'] : null;
		$date_to = isset($filters['date_to']) ? $filters['date_to'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		
		$limit = (int)$limit;
		if ($limit > 0) { $limit = min(500, $limit); }
		
		$count_sql = "SELECT COUNT(*) as total FROM analytics_daily_studies WHERE 1=1";
		$count_params = array();
		if ($date_from) {
			$count_sql .= " AND date >= ?";
			$count_params[] = $date_from;
		}
		if ($date_to) {
			$count_sql .= " AND date <= ?";
			$count_params[] = $date_to;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;
		
		$this->db->reset_query();
		$this->db->select('analytics_daily_studies.date, analytics_daily_studies.study_id, analytics_daily_studies.pageviews, analytics_daily_studies.unique_visitors, analytics_daily_studies.downloads, surveys.title, surveys.nation, surveys.year_start as study_year');
		$this->db->join('surveys', 'analytics_daily_studies.study_id = surveys.id', 'left');
		$this->db->from('analytics_daily_studies');
		if ($date_from) {
			$this->db->where('analytics_daily_studies.date >=', $date_from);
		}
		if ($date_to) {
			$this->db->where('analytics_daily_studies.date <=', $date_to);
		}
		if ($study_id) {
			$this->db->where('analytics_daily_studies.study_id', $study_id);
		}
		$this->db->order_by('analytics_daily_studies.date', 'DESC');
		$this->db->order_by('analytics_daily_studies.study_id', 'ASC');
		if ($limit > 0) { $this->db->limit($limit, $offset); }
		$query = $this->db->get();
		
		$data = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
		}
		
		return array(
			'data' => $data,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get daily file aggregates with pagination and filtering
	 *
	 * @param array $filters Filter parameters (date_from, date_to, study_id)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @return array Result with data, total, and pagination info
	 */
	public function get_daily_files($filters = array(), $limit = 50, $offset = 0)
	{
		$date_from = isset($filters['date_from']) ? $filters['date_from'] : null;
		$date_to = isset($filters['date_to']) ? $filters['date_to'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		
		$limit = (int)$limit;
		if ($limit > 0) { $limit = min(500, $limit); }
		
		$count_sql = "SELECT COUNT(*) as total FROM analytics_daily_files WHERE 1=1";
		$count_params = array();
		if ($date_from) {
			$count_sql .= " AND date >= ?";
			$count_params[] = $date_from;
		}
		if ($date_to) {
			$count_sql .= " AND date <= ?";
			$count_params[] = $date_to;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;
		
		$this->db->reset_query();
		$this->db->select('analytics_daily_files.date, analytics_daily_files.study_id, analytics_daily_files.file_name, analytics_daily_files.downloads, surveys.title, surveys.nation, surveys.year_start as study_year');
		$this->db->join('surveys', 'analytics_daily_files.study_id = surveys.id', 'left');
		$this->db->from('analytics_daily_files');
		if ($date_from) {
			$this->db->where('analytics_daily_files.date >=', $date_from);
		}
		if ($date_to) {
			$this->db->where('analytics_daily_files.date <=', $date_to);
		}
		if ($study_id) {
			$this->db->where('analytics_daily_files.study_id', $study_id);
		}
		$this->db->order_by('analytics_daily_files.date', 'DESC');
		$this->db->order_by('analytics_daily_files.study_id', 'ASC');
		$this->db->order_by('analytics_daily_files.downloads', 'DESC');
		if ($limit > 0) { $this->db->limit($limit, $offset); }
		$query = $this->db->get();
		
		$data = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
		}
		
		return array(
			'data' => $data,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get raw pageview events with pagination and filtering
	 * 
	 * @param array $filters Filter parameters (date_from, date_to, study_id)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @param string $sort_by Field to sort by
	 * @param string $sort_order Sort order (asc/desc)
	 * @return array Result with data, total, and pagination info
	 */
	public function get_raw_pageviews($filters = array(), $limit = 50, $offset = 0, $sort_by = 'ts', $sort_order = 'desc')
	{
		$date_from = isset($filters['date_from']) ? $filters['date_from'] : null;
		$date_to = isset($filters['date_to']) ? $filters['date_to'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		
		// Validate limit
		$limit = max(1, min(500, (int)$limit));

		$ts_from = $date_from ? ($date_from . ' 00:00:00') : null;
		$ts_to   = $date_to   ? (date('Y-m-d', strtotime($date_to . ' +1 day')) . ' 00:00:00') : null;

		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_pageview_events WHERE 1=1";
		$count_params = array();

		if ($ts_from) {
			$count_sql .= " AND ts >= ?";
			$count_params[] = $ts_from;
		}
		if ($ts_to) {
			$count_sql .= " AND ts < ?";
			$count_params[] = $ts_to;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}

		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;

		// Build data query
		$this->db->select('analytics_pageview_events.id, analytics_pageview_events.ts, analytics_pageview_events.study_id, analytics_pageview_events.session_id, analytics_pageview_events.user_agent, analytics_pageview_events.referrer, hashed_ip, surveys.title as study_title');
		$this->db->from('analytics_pageview_events');
		$this->db->join('surveys', 'analytics_pageview_events.study_id = surveys.id', 'left');

		// Apply filters for data query
		if ($ts_from) {
			$this->db->where('ts >=', $ts_from);
		}
		if ($ts_to) {
			$this->db->where('ts <', $ts_to);
		}
		if ($study_id) {
			$this->db->where('study_id', $study_id);
		}

		// Apply sorting
		$valid_sort_fields = array('ts', 'study_id', 'session_id');
		if (in_array($sort_by, $valid_sort_fields)) {
			$this->db->order_by($sort_by, strtoupper($sort_order));
		} else {
			$this->db->order_by('ts', 'DESC');
		}
		
		// Apply pagination
		$this->db->limit($limit, $offset);
		
		$query = $this->db->get();
		
		$events = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$events[] = $row;
			}
		}
		
		return array(
			'data' => $events,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get raw download events with pagination and filtering
	 * 
	 * @param array $filters Filter parameters (date_from, date_to, study_id, file_type)
	 * @param int $limit Number of records per page
	 * @param int $offset Pagination offset
	 * @param string $sort_by Field to sort by
	 * @param string $sort_order Sort order (asc/desc)
	 * @return array Result with data, total, and pagination info
	 */
	public function get_raw_downloads($filters = array(), $limit = 50, $offset = 0, $sort_by = 'ts', $sort_order = 'desc')
	{
		$date_from = isset($filters['date_from']) ? $filters['date_from'] : null;
		$date_to = isset($filters['date_to']) ? $filters['date_to'] : null;
		$study_id = isset($filters['study_id']) ? $filters['study_id'] : null;
		$file_type = isset($filters['file_type']) ? $filters['file_type'] : null;
		
		// Validate limit
		$limit = max(1, min(500, (int)$limit));
		
		$ts_from = $date_from ? ($date_from . ' 00:00:00') : null;
		$ts_to   = $date_to   ? (date('Y-m-d', strtotime($date_to . ' +1 day')) . ' 00:00:00') : null;

		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_download_events WHERE 1=1";
		$count_params = array();

		if ($ts_from) {
			$count_sql .= " AND ts >= ?";
			$count_params[] = $ts_from;
		}
		if ($ts_to) {
			$count_sql .= " AND ts < ?";
			$count_params[] = $ts_to;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		if ($file_type) {
			$count_sql .= " AND file_type = ?";
			$count_params[] = $file_type;
		}

		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;

		// Build data query
		$this->db->select('analytics_download_events.id, analytics_download_events.ts, analytics_download_events.study_id, analytics_download_events.file_name, analytics_download_events.file_type, analytics_download_events.user_agent, analytics_download_events.hashed_ip, surveys.title as study_title');
		$this->db->from('analytics_download_events');
		$this->db->join('surveys', 'analytics_download_events.study_id = surveys.id', 'left');

		// Apply filters for data query
		if ($ts_from) {
			$this->db->where('ts >=', $ts_from);
		}
		if ($ts_to) {
			$this->db->where('ts <', $ts_to);
		}
		if ($study_id) {
			$this->db->where('study_id', $study_id);
		}
		if ($file_type) {
			$this->db->where('file_type', $file_type);
		}
		
		// Apply sorting
		$valid_sort_fields = array('ts', 'study_id', 'file_name', 'file_type');
		if (in_array($sort_by, $valid_sort_fields)) {
			$this->db->order_by($sort_by, strtoupper($sort_order));
		} else {
			$this->db->order_by('ts', 'DESC');
		}
		
		// Apply pagination
		$this->db->limit($limit, $offset);
		
		$query = $this->db->get();
		
		$events = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$events[] = $row;
			}
		}
		
		return array(
			'data' => $events,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'has_more' => ($offset + $limit) < $total
		);
	}
	
	/**
	 * Get current aggregation status
	 * 
	 * @return array Status information
	 */
	public function get_aggregation_status()
	{
		return $this->status_manager->get_current_status();
	}
	
	/**
	 * Get the most recent completed aggregation run (for "last run" display).
	 *
	 * @return array|null Array with 'completed_at', 'started_at'; or null if no completed run
	 */
	public function get_last_completed_aggregation()
	{
		return $this->status_manager->get_last_completed_run();
	}
	
	/**
	 * Initialize aggregation status for a new run
	 * 
	 * @param string $context 'cli' or 'web'
	 * @param int $user_id Optional user ID for web context
	 * @return bool Success
	 */
	public function init_aggregation_status($context = 'cli', $user_id = null)
	{
		return $this->status_manager->create_new_run($context, $user_id);
	}
	
	/**
	 * Update aggregation status
	 * 
	 * @param array $data Status data to update
	 * @return bool Success
	 */
	public function update_aggregation_status($data)
	{
		return $this->status_manager->update_progress($data);
	}
	
	/**
	 * Mark aggregation as completed
	 * 
	 * @return bool Success
	 */
	public function complete_aggregation_status()
	{
		return $this->status_manager->mark_completed();
	}
	
	/**
	 * Stop/reset aggregation status
	 * 
	 * @return bool Success
	 */
	public function stop_aggregation_status()
	{
		return $this->status_manager->mark_stopped();
	}
	
	/**
	 * Find months that have daily aggregates but need monthly rollup.
	 * Used to roll up daily → monthly for backlog (e.g. after 2 years of no aggregation).
	 * Returns months up to and including current month, ordered oldest first.
	 * Excludes past months that already have rows in analytics_monthly_studies.
	 * Always includes the current month when it has daily data so new daily data is picked up (re-roll each run).
	 *
	 * @param int|null $exclude_year If set, exclude this year/month from result (month we just rolled this run, to avoid infinite loop)
	 * @param int|null $exclude_month
	 * @return array Array of arrays with 'year', 'month', 'date' (Y-m)
	 */
	private function find_months_needing_monthly_rollup($exclude_year = null, $exclude_month = null)
	{
		$current_year = (int)date('Y');
		$current_month = (int)date('n');
		$end_of_current = date('Y-m-t', strtotime($current_year . '-' . $current_month . '-01'));

		$this->db->select('YEAR(date) as year, MONTH(date) as month');
		$this->db->distinct();
		$this->db->from('analytics_daily_studies');
		$this->db->where('date <=', $end_of_current);
		$this->db->order_by('year', 'ASC');
		$this->db->order_by('month', 'ASC');
		$query = $this->db->get();
		if (!$query || $query->num_rows() === 0) {
			return array();
		}

		// Exclude past months that already have monthly data (already rolled up).
		// Always include the current month so we re-roll it and pick up new daily data (e.g. new days aggregated since last run).
		$this->db->select('year, month');
		$this->db->from('analytics_monthly_studies');
		$this->db->where('(year != 0 OR month != 0)', null, false);
		$existing_query = $this->db->get();
		$existing_keys = array();
		if ($existing_query) {
			foreach ($existing_query->result_array() as $row) {
				$existing_keys[(int)$row['year'] . '-' . (int)$row['month']] = true;
			}
		}

		$months = array();
		foreach ($query->result_array() as $row) {
			$year = (int)$row['year'];
			$month = (int)$row['month'];
			$is_current_month = ($year === $current_year && $month === $current_month);
			// Skip only if month already has monthly rows AND is not the current month (current month we always re-roll)
			if (isset($existing_keys[$year . '-' . $month]) && !$is_current_month) {
				continue;
			}
			$months[] = array(
				'year' => $year,
				'month' => $month,
				'date' => sprintf('%04d-%02d', $year, $month)
			);
		}

		// Exclude the month we just rolled (same run) so we don't loop forever re-processing it
		if ($exclude_year !== null && $exclude_month !== null) {
			$months = array_values(array_filter($months, function ($m) use ($exclude_year, $exclude_month) {
				return !($m['year'] === $exclude_year && $m['month'] === $exclude_month);
			}));
		}
		return $months;
	}

	/**
	 * Find past months (before current) that have monthly data but are not finalized.
	 * Used for month-end: finalize and cleanup daily aggregates. Includes all such months, not just last 12.
	 *
	 * @return array Array of arrays with 'year', 'month', 'date' (Y-m), oldest first
	 */
	private function find_unfinalized_previous_months()
	{
		$current_year = (int)date('Y');
		$current_month = (int)date('m');

		// 1. Months that are not yet finalized
		$this->db->select('year, month');
		$this->db->distinct();
		$this->db->from('analytics_monthly_studies');
		$this->db->where('(year < ' . $current_year . ' OR (year = ' . $current_year . ' AND month < ' . $current_month . '))', null, false);
		$this->db->where('(year != 0 OR month != 0)', null, false);
		$this->db->group_start();
		$this->db->where('finalized', 0);
		$this->db->or_where('finalized IS NULL', null, false);
		$this->db->group_end();
		$this->db->order_by('year', 'ASC');
		$this->db->order_by('month', 'ASC');
		$query = $this->db->get();

		$seen = array();
		$unfinalized = array();
		if ($query) {
			foreach ($query->result_array() as $row) {
				$year = (int)$row['year'];
				$month = (int)$row['month'];
				$key = $year . '-' . $month;
				$seen[$key] = true;
				$unfinalized[] = array(
					'year' => $year,
					'month' => $month,
					'date' => sprintf('%04d-%02d', $year, $month)
				);
			}
		}

		// 2. Finalized months that still have orphaned daily rows (cleanup didn't run).
		//    These need a cleanup pass even though they're already finalized.
		//    Exclude the immediately previous calendar month: cleanup_daily_aggregates()
		//    intentionally retains its rows for the 7-day chart. Queuing it here would
		//    cause an infinite month_end loop (finalize → retain → queue → finalize...).
		$current_first_day_o = sprintf('%04d-%02d-01', $current_year, $current_month);
		$prev_year_o  = $current_month === 1 ? $current_year - 1 : $current_year;
		$prev_month_o = $current_month === 1 ? 12 : $current_month - 1;
		$prev_first_day_o = sprintf('%04d-%02d-01', $prev_year_o, $prev_month_o);
		$prev_last_day_o  = date('Y-m-t', strtotime($prev_first_day_o));
		$orphan_query = $this->db->query("
			SELECT YEAR(date) as year, MONTH(date) as month
			FROM analytics_daily_studies
			WHERE date < ?
			  AND NOT (date >= ? AND date <= ?)
			GROUP BY YEAR(date), MONTH(date)
		", array(
			$current_first_day_o,
			$prev_first_day_o,
			$prev_last_day_o
		));

		if ($orphan_query) {
			foreach ($orphan_query->result_array() as $row) {
				$year  = (int)$row['year'];
				$month = (int)$row['month'];
				$key   = $year . '-' . $month;
				if (!isset($seen[$key])) {
					$seen[$key] = true;
					$unfinalized[] = array(
						'year' => $year,
						'month' => $month,
						'date' => sprintf('%04d-%02d', $year, $month)
					);
				}
			}
		}

		// Sort oldest first so cleanup proceeds in order
		usort($unfinalized, function ($a, $b) {
			return $a['year'] !== $b['year'] ? $a['year'] - $b['year'] : $a['month'] - $b['month'];
		});

		return $unfinalized;
	}

	/**
	 * Determine next action based on current status
	 * 
	 * @param array $status Current status
	 * @return array Next action information
	 */
	private function determine_next_action($status)
	{
		$current_step = $status['current_step'];

		// First step: migrate legacy counts if not yet done
		if (!$current_step || $current_step === '' || $current_step === 'migrate_legacy') {
			if ($this->needs_legacy_migration()) {
				return array(
					'step' => 'migrate_legacy',
					'item' => 'legacy_counts'
				);
			}
			// Migration already done (or no legacy data), move to daily
			$current_step = 'daily';
		}

		// Process daily aggregates
		if ($current_step === 'daily') {
			$missing_dates = $this->find_missing_daily_aggregates();
			if (!empty($missing_dates)) {
				return array(
					'step' => 'daily',
					'item' => $missing_dates[0],
					'total' => count($missing_dates),
					'processed' => $status['processed_items'] ?? 0
				);
			}
			// Daily done, move to monthly
			$current_step = 'monthly';
		}
		
		// Process monthly aggregates: roll daily → monthly for every month that has daily data and is not finalized (backlog-safe)
		if ($current_step === 'monthly') {
			$months_needing_rollup = $this->find_months_needing_monthly_rollup();
			if (!empty($months_needing_rollup)) {
				$first = $months_needing_rollup[0];
				return array(
					'step' => 'monthly',
					'item' => $first['date'],
					'year' => $first['year'],
					'month' => $first['month'],
					'total_months' => count($months_needing_rollup)
				);
			}
			// No months need rollup, move to month_end
			$current_step = 'month_end';
		}
		
		// Check for unfinalized previous months
		if ($current_step === 'month_end') {
			$unfinalized_months = $this->find_unfinalized_previous_months();
			if (!empty($unfinalized_months)) {
				$month_to_finalize = $unfinalized_months[0];
				return array(
					'step' => 'month_end',
					'item' => $month_to_finalize['date'],
					'year' => $month_to_finalize['year'],
					'month' => $month_to_finalize['month']
				);
			}
			// No more months to finalize, move to cleanup
			$current_step = 'cleanup';
		}
		
		// Cleanup raw events
		if ($current_step === 'cleanup') {
			return array(
				'step' => 'cleanup',
				'item' => 'raw_events_60_days'
			);
		}
		
		// Sync counters (year=0, month=0 should remain static from legacy migration, not updated)
		if ($current_step === 'sync') {
			return array(
				'step' => 'sync',
				'item' => 'survey_counters'
			);
		}
		
		// All done
		return array(
			'step' => 'complete',
			'item' => null
		);
	}
	
	/**
	 * Process one aggregation step/item (unified method for both CLI and web)
	 * 
	 * @param string $context 'cli' or 'web'
	 * @param int $user_id Optional user ID for web context
	 * @return array Result with step, item, has_more, progress, message
	 */
	public function process_aggregation_step($context = 'cli', $user_id = null)
	{
		// Get current status
		$status = $this->get_aggregation_status();
		
		// If not running, initialize
		if ($status['status'] !== 'running') {
			if (!$this->init_aggregation_status($context, $user_id)) {
				return array(
					'current_step' => null,
					'current_item' => null,
					'has_more' => false,
					'progress' => 0,
					'message' => 'Another aggregation is already running',
					'error' => true
				);
			}
			$status = $this->get_aggregation_status();
		}
		
		// Determine next action
		$next_action = $this->determine_next_action($status);
		
		if ($next_action['step'] === 'complete') {
			// All done
			$this->complete_aggregation_status();
			return array(
				'current_step' => 'complete',
				'current_item' => null,
				'has_more' => false,
				'progress' => 100,
				'message' => 'Aggregation completed successfully'
			);
		}
		
		$result = array(
			'step' => $next_action['step'],
			'item' => $next_action['item'],
			'has_more' => true,
			'progress' => 0,
			'message' => ''
		);
		
		try {
			// Execute the action
			switch ($next_action['step']) {
				case 'migrate_legacy':
					$migrate_result = $this->migrate_legacy_counts();

					if ($migrate_result['success']) {
						$msg = 'Legacy migration complete.';
						if ($migrate_result['backed_up'] > 0) {
							$msg .= " Backed up {$migrate_result['backed_up']} survey(s)";
						}
						if ($migrate_result['migrated'] > 0) {
							$msg .= ", seeded {$migrate_result['migrated']} legacy total(s) into analytics.";
						} elseif ($migrate_result['skipped'] > 0) {
							$msg .= " ({$migrate_result['skipped']} already migrated).";
						}

						$result['has_more'] = true;
						$result['progress'] = 5;
						$result['message'] = $msg;

						$this->update_aggregation_status(array(
							'current_step'    => 'daily',
							'current_item'    => 'legacy_counts',
							'progress_percent' => 5,
							'message'         => $msg
						));
					} else {
						$err = implode('; ', $migrate_result['errors']);
						throw new Exception("Legacy migration failed: {$err}");
					}
					break;

				case 'daily':
					$date = $next_action['item'];
					
					// Aggregate pageviews
					$result_pv = $this->aggregate_pageviews_daily($date);
					
					// Aggregate downloads
					$result_dl = $this->aggregate_downloads_daily($date);
					
					if ($result_pv['success'] && $result_dl['success']) {
						// $total = count of still-missing dates INCLUDING the one just processed.
						// Use ($total - 1) for remaining — do NOT use cumulative processed_items from DB
						// because find_missing_daily_aggregates() shrinks on every call while
						// processed_items grows, causing remaining to go negative halfway through.
						$total = $next_action['total'];
						$remaining = $total - 1;
						$daily_done = ($remaining <= 0);
						$processed = ($status['processed_items'] ?? 0) + 1;
						
						// Always return has_more=true until we call complete_aggregation_status(); otherwise UI stops polling and DB stays "running"
						$result['has_more'] = true;
						// Cap daily-phase progress so UI doesn't think job is complete (100% only when sync done)
						$result['progress'] = $daily_done ? 15 : ($total > 0 ? max(1, round((1 - $remaining / $total) * 15)) : 0);
						$result['message'] = $daily_done
							? "Daily aggregation complete. Rolling up monthly next."
							: "Processed {$date}. {$remaining} date(s) remaining.";
						
$this->update_aggregation_status(array(
							'current_step' => $daily_done ? 'monthly' : 'daily',
							'current_item' => $date,
							'total_items' => $total,
							'processed_items' => $processed,
							'progress_percent' => $result['progress'],
							'message' => $result['message']
						));
					} else {
						throw new Exception("Failed to process daily aggregation for {$date}");
					}
					break;
					
				case 'monthly':
					$year = $next_action['year'];
					$month = $next_action['month'];
					
					$result_monthly = $this->aggregate_daily_to_monthly($year, $month);
					
					if ($result_monthly['success']) {
						// Check if more months need daily→monthly rollup (exclude the month we just rolled so we don't loop forever)
						$remaining_rollup = $this->find_months_needing_monthly_rollup($year, $month);
						$next_step = !empty($remaining_rollup) ? 'monthly' : 'month_end';
						
						$result['has_more'] = true;
						$result['progress'] = 20; // Approximate
						$result['message'] = "Monthly aggregates updated for {$year}-{$month}." . (!empty($remaining_rollup) ? ' ' . count($remaining_rollup) . ' month(s) remaining to roll up.' : '');
						
						$this->update_aggregation_status(array(
							'current_step' => $next_step,
							'current_item' => "{$year}-{$month}",
							'progress_percent' => $result['progress'],
							'message' => $result['message']
						));
					} else {
						throw new Exception("Failed to process monthly aggregation for {$year}-{$month}");
					}
					break;
					
				case 'month_end':
					$year = $next_action['year'];
					$month = $next_action['month'];
					
					// Finalize the specified month
					$finalize_result = $this->finalize_month($year, $month);
					
					if ($finalize_result['success']) {
						// Delete daily aggregates for finalized month
						$cleanup_result = $this->cleanup_daily_aggregates($year, $month);
						
						// Check if there are more months to finalize
						$remaining_unfinalized = $this->find_unfinalized_previous_months();
						$has_more_months = !empty($remaining_unfinalized);
						
						// Always keep has_more=true - only the 'sync' step signals completion.
						// Returning false here stops JS polling before cleanup/sync steps run.
						$result['has_more'] = true;
						$result['progress'] = $has_more_months ? 40 : 60;
						$result['message'] = "Month-end processing completed for {$year}-{$month}";
						
						$next_step = $has_more_months ? 'month_end' : 'cleanup';
						$this->update_aggregation_status(array(
							'current_step' => $next_step,
							'current_item' => "{$year}-{$month}",
							'progress_percent' => $result['progress'],
							'message' => $result['message']
						));
					} else {
						throw new Exception("Failed to finalize month {$year}-{$month}");
					}
					break;
					
				case 'cleanup':
					$this->status_manager->touch_heartbeat();
					$cleanup_result = $this->cleanup_raw_events(60);
					
					$result['has_more'] = true;
					$result['progress'] = 60;
					$result['message'] = "Raw events cleanup completed";
					
					$this->update_aggregation_status(array(
						'current_step' => 'sync',
						'current_item' => 'raw_events',
						'progress_percent' => $result['progress'],
						'message' => $result['message']
					));
					break;
					
				case 'sync':
					$this->status_manager->touch_heartbeat();
					$sync_result = $this->sync_counters(null, 500);
					
					if ($sync_result['success']) {
						$result['has_more'] = false;
						$result['progress'] = 100;
						$result['message'] = "Counters synced. Aggregation completed.";
						
						$this->complete_aggregation_status();
					} else {
						throw new Exception("Failed to sync counters");
					}
					break;
			}
			
			return $result;
			
		} catch (Exception $e) {
			// Update status with error
			$this->update_aggregation_status(array(
				'error_message' => $e->getMessage(),
				'message' => 'Error: ' . $e->getMessage()
			));
			
			return array(
				'current_step' => $next_action['step'],
				'current_item' => $next_action['item'],
				'has_more' => false,
				'progress' => $status['progress_percent'] ?? 0,
				'message' => 'Error: ' . $e->getMessage(),
				'error' => true
			);
		}
	}
}

/* End of file Analytics_model.php */
/* Location: ./application/models/Analytics_model.php */

