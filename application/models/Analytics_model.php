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
			
			// Calculate totals on-the-fly: legacy (year=0, month=0) + current monthly aggregates
			// Note: year=0, month=0 records remain static from legacy migration and are NOT updated
			
			// Use a single efficient SQL query to combine legacy and current totals
			$db_driver = $this->db->dbdriver;
			
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
			
			if ($query->num_rows() === 0) {
				$this->db->trans_complete();
				$result['success'] = true;
				return $result;
			}
			
			$all_rows = array();
			foreach ($query->result_array() as $row) {
				$all_rows[] = array(
					'study_id' => $row['study_id'],
					'pageviews' => (int)$row['pageviews'],
					'downloads' => (int)$row['downloads']
				);
			}
			
			$total_rows = count($all_rows);
			$updated = 0;
			
			if ($total_rows === 0) {
				$this->db->trans_complete();
				$result['success'] = true;
				return $result;
			}
			
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
		
		// Build list of year-month pairs for last N months
		$now = new DateTime();
		for ($i = $months - 1; $i >= 0; $i--) {
			$d = clone $now;
			$d->modify("-{$i} month");
			$year = (int)$d->format('Y');
			$month = (int)$d->format('n');
			$label = $d->format('M y'); // e.g., "Jan 25"
			
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
		
		// Validate limit
		$limit = max(1, min(500, (int)$limit));
		
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
		$this->db->limit($limit, $offset);
		
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
		
		// Validate limit
		$limit = max(1, min(500, (int)$limit));
		
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
		$this->db->limit($limit, $offset);
		
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
		
		// Determine date function based on database driver
		$db_driver = $this->db->dbdriver;
		$date_func = ($db_driver === 'sqlsrv' || $db_driver === 'mssql') ? 'CAST(ts AS DATE)' : 'DATE(ts)';
		
		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_pageview_events WHERE 1=1";
		$count_params = array();
		
		if ($date_from) {
			$count_sql .= " AND {$date_func} >= ?";
			$count_params[] = $date_from;
		}
		if ($date_to) {
			$count_sql .= " AND {$date_func} <= ?";
			$count_params[] = $date_to;
		}
		if ($study_id) {
			$count_sql .= " AND study_id = ?";
			$count_params[] = $study_id;
		}
		
		$count_query = $this->db->query($count_sql, $count_params);
		$total = $count_query && $count_query->num_rows() > 0 ? (int)$count_query->row()->total : 0;
		
		// Build data query
		$this->db->select('id, ts, study_id, session_id, user_agent, referrer, hashed_ip');
		$this->db->from('analytics_pageview_events');
		
		// Apply filters for data query
		if ($date_from) {
			$this->db->where("{$date_func} >= ", $this->db->escape($date_from), false);
		}
		if ($date_to) {
			$this->db->where("{$date_func} <= ", $this->db->escape($date_to), false);
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
		
		// Determine date function based on database driver
		$db_driver = $this->db->dbdriver;
		$date_func = ($db_driver === 'sqlsrv' || $db_driver === 'mssql') ? 'CAST(ts AS DATE)' : 'DATE(ts)';
		
		// Build count query using raw SQL to avoid query builder state issues
		$count_sql = "SELECT COUNT(*) as total FROM analytics_download_events WHERE 1=1";
		$count_params = array();
		
		if ($date_from) {
			$count_sql .= " AND {$date_func} >= ?";
			$count_params[] = $date_from;
		}
		if ($date_to) {
			$count_sql .= " AND {$date_func} <= ?";
			$count_params[] = $date_to;
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
		$this->db->select('id, ts, study_id, file_name, file_type, user_agent, hashed_ip');
		$this->db->from('analytics_download_events');
		
		// Apply filters for data query
		if ($date_from) {
			$this->db->where("{$date_func} >= ", $this->db->escape($date_from), false);
		}
		if ($date_to) {
			$this->db->where("{$date_func} <= ", $this->db->escape($date_to), false);
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
	 * Find previous months that should be finalized but aren't
	 * 
	 * @return array Array of months that need finalization
	 */
	private function find_unfinalized_previous_months()
	{
		$current_year = (int)date('Y');
		$current_month = (int)date('m');
		
		$unfinalized = array();
		
		// Check last 12 months (excluding current month)
		for ($i = 1; $i <= 12; $i++) {
			$check_date = date('Y-m', strtotime("-{$i} month"));
			list($year, $month) = explode('-', $check_date);
			$year = (int)$year;
			$month = (int)$month;
			
			// Check if this month has data but is not finalized
			$this->db->select('COUNT(*) as count, MAX(finalized) as max_finalized');
			$this->db->from('analytics_monthly_studies');
			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$query = $this->db->get();
			
			if ($query && $query->num_rows() > 0) {
				$row = $query->row();
				// If has data but not finalized (or mixed finalized states)
				if ($row->count > 0 && (!$row->max_finalized || $row->max_finalized == 0)) {
					$unfinalized[] = array(
						'year' => $year,
						'month' => $month,
						'date' => $check_date
					);
				}
			}
		}
		
		// Sort by date (oldest first)
		usort($unfinalized, function($a, $b) {
			return strcmp($a['date'], $b['date']);
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
		
		// If no current step, start with daily
		if (!$current_step || $current_step === '') {
			$missing_dates = $this->find_missing_daily_aggregates();
			if (!empty($missing_dates)) {
				return array(
					'step' => 'daily',
					'item' => $missing_dates[0],
					'total' => count($missing_dates),
					'processed' => 0
				);
			}
			// No daily missing, move to monthly
			$current_step = 'monthly';
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
		
		// Process monthly aggregates (current month)
		if ($current_step === 'monthly') {
			$current_year = (int)date('Y');
			$current_month = (int)date('m');
			return array(
				'step' => 'monthly',
				'item' => "{$current_year}-{$current_month}",
				'year' => $current_year,
				'month' => $current_month
			);
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
				case 'daily':
					$date = $next_action['item'];
					
					// Aggregate pageviews
					$result_pv = $this->aggregate_pageviews_daily($date);
					
					// Aggregate downloads
					$result_dl = $this->aggregate_downloads_daily($date);
					
					if ($result_pv['success'] && $result_dl['success']) {
						$total = $next_action['total'];
						$processed = ($status['processed_items'] ?? 0) + 1;
						$remaining = $total - $processed;
						
						$result['has_more'] = $remaining > 0;
						$result['progress'] = $total > 0 ? round(($processed / $total) * 100) : 0;
						$result['message'] = "Processed {$date}. {$remaining} date(s) remaining.";
						
						// Update status
						$this->update_aggregation_status(array(
							'current_step' => $remaining > 0 ? 'daily' : 'monthly',
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
						// After monthly aggregation, check for unfinalized previous months
						$unfinalized_months = $this->find_unfinalized_previous_months();
						$next_step = !empty($unfinalized_months) ? 'month_end' : 'cleanup';
						
						$result['has_more'] = true;
						$result['progress'] = 20; // Approximate
						$result['message'] = "Monthly aggregates updated for {$year}-{$month}";
						
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
						
						$result['has_more'] = $has_more_months;
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

