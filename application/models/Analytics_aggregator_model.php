<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Aggregator Model
 *
 * Handles daily aggregation routines for pageviews and downloads, including
 * chunked processing helpers to avoid timeouts and helper utilities to find
 * missing aggregates.
 */
class Analytics_aggregator_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Aggregate pageview events to daily study aggregates.
	 *
	 * @param string $date Date in Y-m-d format (default: yesterday)
	 * @return array Result summary
	 */
	public function aggregate_pageviews_daily($date = null)
	{
		if ($date === null) {
			$date = date('Y-m-d', strtotime('-1 day'));
		}

		$ts_start = $date . ' 00:00:00';
		$ts_end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

		$this->db->trans_start();

		// Delete existing aggregates for this date
		$this->db->where('date', $date);
		$this->db->delete('analytics_daily_studies');

		// Insert aggregated data
		$sql = "
			INSERT INTO analytics_daily_studies (date, study_id, pageviews, unique_visitors)
			SELECT
				? as date,
				study_id,
				COUNT(*) as pageviews,
				COUNT(DISTINCT session_id) as unique_visitors
			FROM analytics_pageview_events
			WHERE ts >= ? AND ts < ?
				AND session_id IS NOT NULL
			GROUP BY study_id
		";

		$result = $this->db->query($sql, array($date, $ts_start, $ts_end));

		$this->db->trans_complete();

		return array(
			'date' => $date,
			'success' => ($result !== false && $this->db->trans_status() !== false),
			'affected_rows' => $this->db->affected_rows()
		);
	}

	/**
	 * Calculate unique visitors for a specific study and date.
	 *
	 * @param string $date Date in Y-m-d format
	 * @param string $study_id Study identifier
	 * @return int Number of unique visitors
	 */
	public function calculate_unique_visitors($date, $study_id)
	{
		$ts_start = $date . ' 00:00:00';
		$ts_end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

		$this->db->select('COUNT(DISTINCT session_id) as unique_visitors');
		$this->db->where('ts >=', $ts_start);
		$this->db->where('ts <', $ts_end);
		$this->db->where('study_id', $study_id);
		$this->db->where('session_id IS NOT NULL', null, false);
		$query = $this->db->get('analytics_pageview_events');

		if ($query && $query->num_rows() > 0) {
			$row = $query->row();
			return (int)$row->unique_visitors;
		}

		return 0;
	}

	/**
	 * Aggregate download events to daily file aggregates.
	 *
	 * @param string $date Date in Y-m-d format (default: yesterday)
	 * @return array Result
	 */
	public function aggregate_downloads_daily($date = null)
	{
		if ($date === null) {
			$date = date('Y-m-d', strtotime('-1 day'));
		}

		$ts_start = $date . ' 00:00:00';
		$ts_end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

		$this->db->trans_start();

		// Delete existing aggregates for this date
		$this->db->where('date', $date);
		$this->db->delete('analytics_daily_files');

		// Insert
		$sql = "
			INSERT INTO analytics_daily_files (date, study_id, file_name, downloads)
			SELECT
				? as date,
				study_id,
				file_name,
				COUNT(*) as downloads
			FROM analytics_download_events
			WHERE ts >= ? AND ts < ?
			GROUP BY study_id, file_name
		";

		$result = $this->db->query($sql, array($date, $ts_start, $ts_end));

		$this->db->trans_complete();

		// Update study-level download totals
		$this->update_study_downloads_daily($date);

		return array(
			'date' => $date,
			'success' => ($result !== false && $this->db->trans_status() !== false),
			'affected_rows' => $this->db->affected_rows()
		);
	}

	/**
	 * Update study-level download totals from file aggregates.
	 *
	 * @param string $date Date in Y-m-d format
	 * @return bool Success status
	 */
	private function update_study_downloads_daily($date)
	{
		$db_driver = $this->db->dbdriver;
		$is_sqlsrv = ($db_driver === 'sqlsrv');

		if ($is_sqlsrv) {
			$sql = "
				UPDATE ads
				SET ads.downloads = file_totals.total_downloads
				FROM analytics_daily_studies ads
				JOIN (
					SELECT
						date,
						study_id,
						SUM(downloads) as total_downloads
					FROM analytics_daily_files
					WHERE date = ?
					GROUP BY date, study_id
				) AS file_totals ON ads.date = file_totals.date AND ads.study_id = file_totals.study_id
			";
		} else {
			$sql = "
				UPDATE analytics_daily_studies ads
				INNER JOIN (
					SELECT
						date,
						study_id,
						SUM(downloads) as total_downloads
					FROM analytics_daily_files
					WHERE date = ?
					GROUP BY date, study_id
				) as file_totals ON ads.date = file_totals.date AND ads.study_id = file_totals.study_id
				SET ads.downloads = file_totals.total_downloads
			";
		}

		return $this->db->query($sql, array($date)) !== false;
	}

	/**
	 * Chunked daily pageview aggregation to avoid timeouts.
	 */
	public function aggregate_pageviews_daily_chunked($date = null, $offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		if ($date === null) {
			$date = date('Y-m-d', strtotime('-1 day'));
		}

		$start_time = microtime(true);
		$start_memory = memory_get_usage(true);

		$ts_start = $date . ' 00:00:00';
		$ts_end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

		$total_studies_query = $this->db->query("
			SELECT COUNT(DISTINCT study_id) as total
			FROM analytics_pageview_events
			WHERE ts >= ? AND ts < ? AND session_id IS NOT NULL
		", array($ts_start, $ts_end));

		$total_studies = 0;
		if ($total_studies_query && $total_studies_query->num_rows() > 0) {
			$row = $total_studies_query->row();
			$total_studies = (int)$row->total;
		}

		if ($total_studies === 0) {
			return array(
				'date' => $date,
				'success' => true,
				'processed' => 0,
				'total_studies' => 0,
				'offset' => 0,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		//get studies
		$this->db->distinct();
		$this->db->select('study_id');
		$this->db->from('analytics_pageview_events');
		$this->db->where('ts >=', $ts_start);
		$this->db->where('ts <', $ts_end);
		$this->db->where('session_id IS NOT NULL', null, false);
		$this->db->order_by('study_id');
		$this->db->limit($limit, $offset);
		$studies_query = $this->db->get();

		if (!$studies_query || $studies_query->num_rows() === 0) {
			return array(
				'date' => $date,
				'success' => true,
				'processed' => $offset,
				'total_studies' => $total_studies,
				'offset' => $offset,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$study_ids = array();
		foreach ($studies_query->result() as $row) {
			$study_ids[] = $row->study_id;
		}

		$placeholders = implode(',', array_fill(0, count($study_ids), '?'));

		// Use DELETE + INSERT
		$this->db->trans_start();

		// Delete existing aggregates for this date and study_ids
		$this->db->where('date', $date);
		$this->db->where_in('study_id', $study_ids);
		$this->db->delete('analytics_daily_studies');

		// Insert aggregated data
		$sql = "
			INSERT INTO analytics_daily_studies (date, study_id, pageviews, unique_visitors)
			SELECT
				? as date,
				study_id,
				COUNT(*) as pageviews,
				COUNT(DISTINCT session_id) as unique_visitors
			FROM analytics_pageview_events
			WHERE ts >= ? AND ts < ?
				AND session_id IS NOT NULL
				AND study_id IN ($placeholders)
			GROUP BY study_id
		";

		$params = array_merge(array($date, $ts_start, $ts_end), $study_ids);
		$result = $this->db->query($sql, $params);

		$this->db->trans_complete();

		$processed = $offset + count($study_ids);
		$has_more = ($processed < $total_studies) && ((microtime(true) - $start_time) < ($max_time_seconds - 2));

		return array(
			'date' => $date,
			'success' => ($result !== false && $this->db->trans_status() !== false),
			'processed' => $processed,
			'total_studies' => $total_studies,
			'offset' => $processed,
			'has_more' => $has_more,
			'execution_time' => round(microtime(true) - $start_time, 2),
			'memory_used_mb' => round((memory_get_usage(true) - $start_memory) / 1024 / 1024, 2)
		);
	}

	/**
	 * Chunked daily download aggregation to avoid timeouts.
	 */
	public function aggregate_downloads_daily_chunked($date = null, $offset = 0, $limit = 100, $max_time_seconds = 25)
	{
		if ($date === null) {
			$date = date('Y-m-d', strtotime('-1 day'));
		}

		$start_time = microtime(true);
		$start_memory = memory_get_usage(true);

		$ts_start = $date . ' 00:00:00';
		$ts_end   = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';

		if ($this->db->dbdriver === 'sqlsrv') {
			$total_files_query = $this->db->query("
				SELECT COUNT(DISTINCT CAST(study_id AS NVARCHAR(50)) + '|' + file_name) as total
				FROM analytics_download_events
				WHERE ts >= ? AND ts < ?
			", array($ts_start, $ts_end));
		} else {
			$total_files_query = $this->db->query("
				SELECT COUNT(DISTINCT CONCAT(study_id, '|', file_name)) as total
				FROM analytics_download_events
				WHERE ts >= ? AND ts < ?
			", array($ts_start, $ts_end));
		}

		$total_files = 0;
		if ($total_files_query && $total_files_query->num_rows() > 0) {
			$row = $total_files_query->row();
			$total_files = (int)$row->total;
		}

		if ($total_files === 0) {
			$this->update_study_downloads_daily($date);
			return array(
				'date' => $date,
				'success' => true,
				'processed' => 0,
				'total_files' => 0,
				'offset' => 0,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		//get files
		$this->db->distinct();
		$this->db->select('study_id, file_name');
		$this->db->from('analytics_download_events');
		$this->db->where('ts >=', $ts_start);
		$this->db->where('ts <', $ts_end);
		$this->db->order_by('study_id, file_name');
		$this->db->limit($limit, $offset);
		$files_query = $this->db->get();

		if (!$files_query || $files_query->num_rows() === 0) {
			$this->update_study_downloads_daily($date);
			return array(
				'date' => $date,
				'success' => true,
				'processed' => $offset,
				'total_files' => $total_files,
				'offset' => $offset,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$file_combinations = array();
		$study_ids = array();
		$file_names = array();
		foreach ($files_query->result() as $row) {
			$file_combinations[] = array(
				'study_id' => $row->study_id,
				'file_name' => $row->file_name
			);
			$study_ids[] = $row->study_id;
			$file_names[] = $row->file_name;
		}

		// Use DELETE + INSERT pattern (works for both MySQL and SQL Server)
		$this->db->trans_start();

		// Delete existing aggregates for this date and file combinations
		$this->db->where('date', $date);
		$this->db->where_in('study_id', $study_ids);
		$this->db->where_in('file_name', $file_names);
		$this->db->delete('analytics_daily_files');

		// Build WHERE clause for file combinations
		$where_conditions = array();
		$params = array($date, $ts_start, $ts_end);
		foreach ($file_combinations as $file_combo) {
			$where_conditions[] = "(study_id = ? AND file_name = ?)";
			$params[] = $file_combo['study_id'];
			$params[] = $file_combo['file_name'];
		}
		$where_clause = implode(' OR ', $where_conditions);

		// Insert aggregated data for all file combinations in batch
		$sql = "
			INSERT INTO analytics_daily_files (date, study_id, file_name, downloads)
			SELECT
				? as date,
				study_id,
				file_name,
				COUNT(*) as downloads
			FROM analytics_download_events
			WHERE ts >= ? AND ts < ?
				AND ({$where_clause})
			GROUP BY study_id, file_name
		";

		$result = $this->db->query($sql, $params);

		$this->db->trans_complete();

		$processed_count = ($result !== false && $this->db->trans_status() !== false) ? count($file_combinations) : 0;

		$processed = $offset + $processed_count;
		$has_more = ($processed < $total_files) && ((microtime(true) - $start_time) < ($max_time_seconds - 2));

		if (!$has_more) {
			$this->update_study_downloads_daily($date);
		}

		return array(
			'date' => $date,
			'success' => ($result !== false && $this->db->trans_status() !== false),
			'processed' => $processed,
			'total_files' => $total_files,
			'offset' => $processed,
			'has_more' => $has_more,
			'execution_time' => round(microtime(true) - $start_time, 2),
			'memory_used_mb' => round((memory_get_usage(true) - $start_memory) / 1024 / 1024, 2)
		);
	}

	/**
	 * Roll up daily aggregates to monthly aggregates.
	 *
	 * @param int $year Year (default: current year)
	 * @param int $month Month (default: previous month)
	 * @return array Result summary
	 */
	public function aggregate_daily_to_monthly($year = null, $month = null)
	{
		if ($year === null || $month === null) {
			$prev_month = date('Y-m', strtotime('first day of last month'));
			list($year, $month) = explode('-', $prev_month);
			$year = (int)$year;
			$month = (int)$month;
		} else {
			$year = (int)$year;
			$month = (int)$month;
		}

		// Check if month is already finalized
		$this->db->select('finalized');
		$this->db->from('analytics_monthly_studies');
		$this->db->where('year', $year);
		$this->db->where('month', $month);
		$this->db->limit(1);
		$check_query = $this->db->get();

		if ($check_query && $check_query->num_rows() > 0) {
			$row = $check_query->row();
			if (!empty($row->finalized) && (int)$row->finalized === 1) {
				return array(
					'year' => $year,
					'month' => $month,
					'success' => true,
					'skipped' => true,
					'reason' => 'Month is already finalized',
					'studies_affected' => 0,
					'files_affected' => 0
				);
			}
		}

		$first_day = sprintf('%04d-%02d-01', $year, $month);
		$last_day  = date('Y-m-t', strtotime($first_day));

		// Use DELETE + INSERT
		$this->db->trans_start();

		// Delete existing monthly aggregates for this year/month
		$this->db->where('year', $year);
		$this->db->where('month', $month);
		$this->db->delete('analytics_monthly_studies');

		$this->db->where('year', $year);
		$this->db->where('month', $month);
		$this->db->delete('analytics_monthly_files');

		// Insert aggregated data for studies
		$sql_studies = "
			INSERT INTO analytics_monthly_studies (year, month, study_id, pageviews, unique_visitors, downloads, finalized)
			SELECT
				? as year,
				? as month,
				study_id,
				SUM(pageviews) as pageviews,
				SUM(unique_visitors) as unique_visitors,
				SUM(downloads) as downloads,
				0 as finalized
			FROM analytics_daily_studies
			WHERE date >= ? AND date <= ?
			GROUP BY study_id
		";

		// Insert aggregated data for files
		$sql_files = "
			INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads, finalized)
			SELECT
				? as year,
				? as month,
				study_id,
				file_name,
				SUM(downloads) as downloads,
				0 as finalized
			FROM analytics_daily_files
			WHERE date >= ? AND date <= ?
			GROUP BY study_id, file_name
		";

		$result_studies = $this->db->query($sql_studies, array($year, $month, $first_day, $last_day));
		$result_files = $this->db->query($sql_files, array($year, $month, $first_day, $last_day));

		$this->db->trans_complete();

		return array(
			'year' => $year,
			'month' => $month,
			'success' => ($result_studies !== false && $result_files !== false && $this->db->trans_status() !== false),
			'skipped' => false,
			'studies_affected' => $this->db->affected_rows(),
			'files_affected' => $this->db->affected_rows()
		);
	}

	/**
	 * Finalize a month - mark monthly aggregates as finalized.
	 */
	public function finalize_month($year, $month)
	{
		$result = array(
			'success' => false,
			'studies_finalized' => 0,
			'files_finalized' => 0,
			'errors' => array()
		);

		try {
			$finalized_at = date('Y-m-d H:i:s');

			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$this->db->set('finalized', 1);
			$this->db->set('finalized_at', $finalized_at);
			$update_studies = $this->db->update('analytics_monthly_studies');

			if ($update_studies) {
				$result['studies_finalized'] = $this->db->affected_rows();
			} else {
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to finalize studies: " . $error_msg;
			}

			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$update_files = $this->db->update('analytics_monthly_files', array(
				'finalized' => 1,
				'finalized_at' => $finalized_at
			));

			if ($update_files) {
				$result['files_finalized'] = $this->db->affected_rows();
			} else {
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to finalize files: " . $error_msg;
			}

			$result['success'] = ($update_studies !== false && $update_files !== false);

		} catch (Exception $e) {
			$result['errors'][] = "Exception: " . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Clean up raw events older than retention period.
	 * Only deletes raw events for months that are already monthly-aggregated and marked as finalized,
	 * so we never delete data that might still be needed for aggregation.
	 */
	public function cleanup_raw_events($retention_days = 60)
	{
		$result = array(
			'deleted_pageviews' => 0,
			'deleted_downloads' => 0,
			'errors' => array()
		);

		try {
			// Use start-of-day so ts < cutoff_ts hits the index directly
			$cutoff_ts = date('Y-m-d', strtotime("-{$retention_days} days")) . ' 00:00:00';

			// Delete pageview events only where ts < cutoff AND (year, month) is finalized
			$sql_pv = "
				DELETE FROM analytics_pageview_events
				WHERE ts < ?
				AND EXISTS (
					SELECT 1 FROM analytics_monthly_studies m
					WHERE m.year = YEAR(analytics_pageview_events.ts)
					AND m.month = MONTH(analytics_pageview_events.ts)
					AND m.finalized = 1
					AND (m.year != 0 OR m.month != 0)
				)
			";
			$delete_pageviews = $this->db->query($sql_pv, array($cutoff_ts));

			if ($delete_pageviews !== false) {
				$result['deleted_pageviews'] = $this->db->affected_rows();
			} else {
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to delete pageview events: " . $error_msg;
			}

			// Delete download events only where ts < cutoff AND (year, month) is finalized
			$sql_dl = "
				DELETE FROM analytics_download_events
				WHERE ts < ?
				AND EXISTS (
					SELECT 1 FROM analytics_monthly_studies m
					WHERE m.year = YEAR(analytics_download_events.ts)
					AND m.month = MONTH(analytics_download_events.ts)
					AND m.finalized = 1
					AND (m.year != 0 OR m.month != 0)
				)
			";
			$delete_downloads = $this->db->query($sql_dl, array($cutoff_ts));

			if ($delete_downloads !== false) {
				$result['deleted_downloads'] = $this->db->affected_rows();
			} else {
				$db_error = $this->db->error();
				$error_msg = is_array($db_error) ? ($db_error['message'] ?? 'Unknown database error') : (is_string($db_error) ? $db_error : 'Unknown database error');
				$result['errors'][] = "Failed to delete download events: " . $error_msg;
			}

		} catch (Exception $e) {
			$result['errors'][] = "Exception: " . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Clean up daily aggregates for finalized months.
	 * Daily tables use a 'date' column (Y-m-d), so we delete by date range for the given year/month.
	 */
	public function cleanup_daily_aggregates($year = null, $month = null)
	{
		$result = array(
			'deleted_daily_studies' => 0,
			'deleted_daily_files' => 0,
			'errors' => array()
		);

		try {
			if ($year === null || $month === null) {
				$prev_month = date('Y-m', strtotime('first day of last month'));
				list($year, $month) = explode('-', $prev_month);
				$year = (int)$year;
				$month = (int)$month;
			} else {
				$year = (int)$year;
				$month = (int)$month;
			}

			$this->db->select('finalized');
			$this->db->from('analytics_monthly_studies');
			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$this->db->limit(1);
			$row = $this->db->get()->row();
			if (!$row) {
				$result['errors'][] = "Month {$year}-{$month} has no monthly data. Cannot delete daily aggregates.";
				return $result;
			}
			if ((int)$row->finalized !== 1) {
				$result['errors'][] = "Month {$year}-{$month} is not finalized. Cannot delete daily aggregates.";
				return $result;
			}

			// Keep daily rows for the immediately previous calendar month.
			// The 7-day chart reads from analytics_daily_studies, so on the first days
			// of a new month the chart window still spans the previous month. Deleting
			// those rows immediately would leave gaps in the chart.
			// The orphan-cleanup pass in find_unfinalized_previous_months() will pick
			// these rows up and delete them one month later, once they are no longer
			// needed by the rolling window.
			$current_year  = (int)date('Y');
			$current_month = (int)date('n');
			$prev_year  = $current_month === 1 ? $current_year - 1 : $current_year;
			$prev_month_num = $current_month === 1 ? 12 : $current_month - 1;
			if ($year === $prev_year && $month === $prev_month_num) {
				$result['retained'] = true;
				return $result;
			}

			// Daily tables have 'date' (DATE), not year/month. Use first and last day of month.
			$first_day = sprintf('%04d-%02d-01', $year, $month);
			$last_day = date('Y-m-t', strtotime($first_day));

			$this->db->where('date >=', $first_day);
			$this->db->where('date <=', $last_day);
			$this->db->delete('analytics_daily_studies');
			$result['deleted_daily_studies'] = $this->db->affected_rows();

			$this->db->where('date >=', $first_day);
			$this->db->where('date <=', $last_day);
			$this->db->delete('analytics_daily_files');
			$result['deleted_daily_files'] = $this->db->affected_rows();

		} catch (Exception $e) {
			$result['errors'][] = "Exception: " . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Chunked monthly rollup aggregation to avoid timeouts.
	 */
	public function aggregate_daily_to_monthly_chunked($year = null, $month = null, $offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		if ($year === null || $month === null) {
			$prev_month = date('Y-m', strtotime('first day of last month'));
			list($year, $month) = explode('-', $prev_month);
			$year = (int)$year;
			$month = (int)$month;
		}

		$start_time = microtime(true);
		$start_memory = memory_get_usage(true);

		$first_day = sprintf('%04d-%02d-01', $year, $month);
		$last_day  = date('Y-m-t', strtotime($first_day));

		$total_studies_query = $this->db->query("
			SELECT COUNT(DISTINCT study_id) as total
			FROM analytics_daily_studies
			WHERE date >= ? AND date <= ?
		", array($first_day, $last_day));

		$total_studies = 0;
		if ($total_studies_query && $total_studies_query->num_rows() > 0) {
			$row = $total_studies_query->row();
			$total_studies = (int)$row->total;
		}

		if ($total_studies === 0) {
			$this->db->trans_start();

			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$this->db->delete('analytics_monthly_files');

			$sql_files = "
				INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads, finalized)
				SELECT
					? as year,
					? as month,
					study_id,
					file_name,
					SUM(downloads) as downloads,
					0 as finalized
				FROM analytics_daily_files
				WHERE date >= ? AND date <= ?
				GROUP BY study_id, file_name
			";

			$result_files = $this->db->query($sql_files, array($year, $month, $first_day, $last_day));
			$this->db->trans_complete();

			return array(
				'year' => $year,
				'month' => $month,
				'success' => ($result_files !== false && $this->db->trans_status() !== false),
				'processed' => 0,
				'total_studies' => 0,
				'offset' => 0,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$this->db->distinct();
		$this->db->select('study_id');
		$this->db->from('analytics_daily_studies');
		$this->db->where('date >=', $first_day);
		$this->db->where('date <=', $last_day);
		$this->db->order_by('study_id');
		$this->db->limit($limit);
		$this->db->offset($offset);
		$studies_query = $this->db->get();

		if (!$studies_query || $studies_query->num_rows() === 0) {
			$this->db->trans_start();

			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$this->db->delete('analytics_monthly_files');

			$sql_files = "
				INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads, finalized)
				SELECT
					? as year,
					? as month,
					study_id,
					file_name,
					SUM(downloads) as downloads,
					0 as finalized
				FROM analytics_daily_files
				WHERE date >= ? AND date <= ?
				GROUP BY study_id, file_name
			";

			$result_files = $this->db->query($sql_files, array($year, $month, $first_day, $last_day));
			$this->db->trans_complete();

			return array(
				'year' => $year,
				'month' => $month,
				'success' => ($result_files !== false && $this->db->trans_status() !== false),
				'processed' => $offset,
				'total_studies' => $total_studies,
				'offset' => $offset,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$study_ids = array();
		foreach ($studies_query->result() as $row) {
			$study_ids[] = $row->study_id;
		}

		$placeholders = implode(',', array_fill(0, count($study_ids), '?'));

		$this->db->trans_start();

		$this->db->where('year', $year);
		$this->db->where('month', $month);
		$this->db->where_in('study_id', $study_ids);
		$this->db->delete('analytics_monthly_studies');

		$sql_studies = "
			INSERT INTO analytics_monthly_studies (year, month, study_id, pageviews, unique_visitors, downloads, finalized)
			SELECT
				? as year,
				? as month,
				study_id,
				SUM(pageviews) as pageviews,
				SUM(unique_visitors) as unique_visitors,
				SUM(downloads) as downloads,
				0 as finalized
			FROM analytics_daily_studies
			WHERE date >= ? AND date <= ?
				AND study_id IN ($placeholders)
			GROUP BY study_id
		";

		$params = array_merge(array($year, $month, $first_day, $last_day), $study_ids);
		$result_studies = $this->db->query($sql_studies, $params);

		if ($offset === 0) {
			$this->db->where('year', $year);
			$this->db->where('month', $month);
			$this->db->delete('analytics_monthly_files');

			$sql_files = "
				INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads, finalized)
				SELECT
					? as year,
					? as month,
					study_id,
					file_name,
					SUM(downloads) as downloads,
					0 as finalized
				FROM analytics_daily_files
				WHERE date >= ? AND date <= ?
				GROUP BY study_id, file_name
			";

			$result_files = $this->db->query($sql_files, array($year, $month, $first_day, $last_day));
		}

		$this->db->trans_complete();

		$processed = $offset + count($study_ids);
		$has_more = ($processed < $total_studies) && ((microtime(true) - $start_time) < ($max_time_seconds - 2));

		return array(
			'year' => $year,
			'month' => $month,
			'success' => ($result_studies !== false && $this->db->trans_status() !== false),
			'processed' => $processed,
			'total_studies' => $total_studies,
			'offset' => $processed,
			'has_more' => $has_more,
			'execution_time' => round(microtime(true) - $start_time, 2),
			'memory_used_mb' => round((memory_get_usage(true) - $start_memory) / 1024 / 1024, 2)
		);
	}

	/**
	 * Chunked legacy totals update.
	 */
	public function update_legacy_totals_chunked($offset = 0, $limit = 50, $max_time_seconds = 25)
	{
		$start_time = microtime(true);
		$start_memory = memory_get_usage(true);

		$this->db->select('COUNT(DISTINCT study_id) as total');
		$this->db->from('analytics_monthly_studies');
		$this->db->where('year != 0 AND month != 0', null, false);
		$total_studies_query = $this->db->get();
		$total_studies = $total_studies_query && $total_studies_query->num_rows() > 0 ? (int)$total_studies_query->row()->total : 0;

		if ($total_studies == 0) {
			return array(
				'success' => true,
				'processed' => 0,
				'total_studies' => 0,
				'offset' => 0,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$this->db->distinct();
		$this->db->select('study_id');
		$this->db->from('analytics_monthly_studies');
		$this->db->where('year != 0 AND month != 0', null, false);
		$this->db->order_by('study_id');
		$this->db->limit($limit, $offset);
		$studies_query = $this->db->get();

		if (!$studies_query || $studies_query->num_rows() === 0) {
			$this->db->trans_start();

			$this->db->where('year', 0);
			$this->db->where('month', 0);
			$this->db->delete('analytics_monthly_files');

			$sql_files = "
				INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads)
				SELECT 
					0 as year,
					0 as month,
					study_id,
					file_name,
					SUM(downloads) as downloads
				FROM analytics_monthly_files
				WHERE year != 0 AND month != 0
				GROUP BY study_id, file_name
			";

			$result_files = $this->db->query($sql_files);
			$this->db->trans_complete();

			return array(
				'success' => ($result_files !== false && $this->db->trans_status() !== false),
				'processed' => $offset,
				'total_studies' => $total_studies,
				'offset' => $offset,
				'has_more' => false,
				'execution_time' => round(microtime(true) - $start_time, 2)
			);
		}

		$study_ids = array();
		foreach ($studies_query->result() as $row) {
			$study_ids[] = $row->study_id;
		}

		$placeholders = implode(',', array_fill(0, count($study_ids), '?'));

		$this->db->trans_start();

		$this->db->where('year', 0);
		$this->db->where('month', 0);
		$this->db->where_in('study_id', $study_ids);
		$this->db->delete('analytics_monthly_studies');

		$sql_studies = "
			INSERT INTO analytics_monthly_studies (year, month, study_id, pageviews, unique_visitors, downloads)
			SELECT 
				0 as year,
				0 as month,
				study_id,
				SUM(pageviews) as pageviews,
				MAX(unique_visitors) as unique_visitors,
				SUM(downloads) as downloads
			FROM analytics_monthly_studies
			WHERE year != 0 AND month != 0
				AND study_id IN ($placeholders)
			GROUP BY study_id
		";

		$result_studies = $this->db->query($sql_studies, $study_ids);

		if ($offset === 0) {
			$this->db->where('year', 0);
			$this->db->where('month', 0);
			$this->db->delete('analytics_monthly_files');

			$sql_files = "
				INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads)
				SELECT 
					0 as year,
					0 as month,
					study_id,
					file_name,
					SUM(downloads) as downloads
				FROM analytics_monthly_files
				WHERE year != 0 AND month != 0
				GROUP BY study_id, file_name
			";

			$result_files = $this->db->query($sql_files);
		}

		$this->db->trans_complete();

		$processed = $offset + count($study_ids);
		$has_more = ($processed < $total_studies) && ((microtime(true) - $start_time) < ($max_time_seconds - 2));

		return array(
			'success' => ($result_studies !== false && $this->db->trans_status() !== false),
			'processed' => $processed,
			'total_studies' => $total_studies,
			'offset' => $processed,
			'has_more' => $has_more,
			'execution_time' => round(microtime(true) - $start_time, 2),
			'memory_used_mb' => round((memory_get_usage(true) - $start_memory) / 1024 / 1024, 2)
		);
	}

	/**
	 * Update legacy totals (year=0, month=0) for a study or all.
	 */
	public function update_legacy_totals($study_id = null)
	{
		$this->db->trans_start();

		$this->db->where('year', 0);
		$this->db->where('month', 0);
		if ($study_id !== null) {
			$this->db->where('study_id', $study_id);
		}
		$this->db->delete('analytics_monthly_studies');

		$this->db->where('year', 0);
		$this->db->where('month', 0);
		if ($study_id !== null) {
			$this->db->where('study_id', $study_id);
		}
		$this->db->delete('analytics_monthly_files');

		$sql_studies = "
			INSERT INTO analytics_monthly_studies (year, month, study_id, pageviews, unique_visitors, downloads)
			SELECT 
				0 as year,
				0 as month,
				study_id,
				SUM(pageviews) as pageviews,
				MAX(unique_visitors) as unique_visitors,
				SUM(downloads) as downloads
			FROM analytics_monthly_studies
			WHERE year != 0 AND month != 0
		";

		if ($study_id !== null) {
			$sql_studies .= " AND study_id = ?";
		}

		$sql_studies .= " GROUP BY study_id";

		if ($study_id !== null) {
			$result_studies = $this->db->query($sql_studies, array($study_id));
		} else {
			$result_studies = $this->db->query($sql_studies);
		}

		$sql_files = "
			INSERT INTO analytics_monthly_files (year, month, study_id, file_name, downloads)
			SELECT 
				0 as year,
				0 as month,
				study_id,
				file_name,
				SUM(downloads) as downloads
			FROM analytics_monthly_files
			WHERE year != 0 AND month != 0
		";

		if ($study_id !== null) {
			$sql_files .= " AND study_id = ?";
		}

		$sql_files .= " GROUP BY study_id, file_name";

		if ($study_id !== null) {
			$result_files = $this->db->query($sql_files, array($study_id));
		} else {
			$result_files = $this->db->query($sql_files);
		}

		$this->db->trans_complete();

		return ($result_studies !== false && $result_files !== false && $this->db->trans_status() !== false);
	}

	/**
	 * Find dates with raw events but missing daily aggregates.
	 */
	public function find_missing_daily_aggregates($start_date = null, $end_date = null)
	{
		// Determine end date (default: yesterday)
		if ($end_date === null) {
			$end_date = date('Y-m-d', strtotime('-1 day'));
		}

		$end_ts = date('Y-m-d', strtotime($end_date . ' +1 day')) . ' 00:00:00';

		// Determine start date
		if ($start_date === null) {
			// Find latest date in daily aggregates
			$this->db->select_max('date');
			$query = $this->db->get('analytics_daily_studies');

			if ($query && $query->num_rows() > 0) {
				$row = $query->row();
				if ($row->date) {
					// Start from day after latest aggregate
					$start_date = date('Y-m-d', strtotime($row->date . ' +1 day'));
				} else {
					// No aggregates exist, find first raw event date
					$first_event_query = $this->db->query(
						"SELECT CAST(MIN(ts) AS DATE) as first_date FROM analytics_pageview_events WHERE ts < ?",
						array($end_ts)
					);

					if ($first_event_query && $first_event_query->num_rows() > 0) {
						$first_row = $first_event_query->row();
						$start_date = $first_row->first_date ? $first_row->first_date : date('Y-m-d', strtotime('-60 days'));
					} else {
						// No raw events, use 60 days ago as default
						$start_date = date('Y-m-d', strtotime('-60 days'));
					}
				}
			} else {
				// No aggregates exist, find first raw event date
				$first_event_query = $this->db->query(
					"SELECT CAST(MIN(ts) AS DATE) as first_date FROM analytics_pageview_events WHERE ts < ?",
					array($end_ts)
				);

				if ($first_event_query && $first_event_query->num_rows() > 0) {
					$first_row = $first_event_query->row();
					$start_date = $first_row->first_date ? $first_row->first_date : date('Y-m-d', strtotime('-60 days'));
				} else {
					// No raw events, use 60 days ago as default
					$start_date = date('Y-m-d', strtotime('-60 days'));
				}
			}
		}

		// Validate date range
		$start_timestamp = strtotime($start_date);
		$end_timestamp = strtotime($end_date);

		if ($start_timestamp > $end_timestamp) {
			return array(); // No dates to process
		}

		$start_ts = $start_date . ' 00:00:00';

		// Find dates with raw events but no daily aggregates
		// Get all dates with raw events in the range
		$raw_dates_query = $this->db->query(
			"SELECT DISTINCT CAST(ts AS DATE) as event_date
			FROM analytics_pageview_events
			WHERE ts >= ? AND ts < ?
			ORDER BY CAST(ts AS DATE)",
			array($start_ts, $end_ts)
		);

		if (!$raw_dates_query || $raw_dates_query->num_rows() === 0) {
			return array(); // No raw events in range
		}

		$raw_dates = array();
		foreach ($raw_dates_query->result() as $row) {
			$raw_dates[] = $row->event_date;
		}

		// Get all dates that already have daily aggregates
		$this->db->distinct();
		$this->db->select('date');
		$this->db->where('date >=', $start_date);
		$this->db->where('date <=', $end_date);
		$aggregate_query = $this->db->get('analytics_daily_studies');

		$aggregate_dates = array();
		if ($aggregate_query) {
			foreach ($aggregate_query->result() as $row) {
				$aggregate_dates[] = $row->date;
			}
		}

		// Find missing dates (dates with raw events but no aggregates)
		$missing_dates = array_diff($raw_dates, $aggregate_dates);
		sort($missing_dates);

		// Exclude dates that fall in finalized months (no need to run daily for those)
		$this->db->select('year, month');
		$this->db->distinct();
		$this->db->from('analytics_monthly_studies');
		$this->db->where('finalized', 1);
		$this->db->where('(year != 0 OR month != 0)', null, false);
		$finalized_query = $this->db->get();
		$finalized_keys = array();
		if ($finalized_query) {
			foreach ($finalized_query->result_array() as $row) {
				$finalized_keys[(int)$row['year'] . '-' . (int)$row['month']] = true;
			}
		}

		$filtered = array();
		foreach ($missing_dates as $d) {
			$y = (int)date('Y', strtotime($d));
			$m = (int)date('n', strtotime($d));
			if (isset($finalized_keys[$y . '-' . $m])) {
				continue;
			}
			$filtered[] = $d;
		}

		return array_values($filtered);
	}
}

/* End of file Analytics_aggregator_model.php */
/* Location: ./application/models/Analytics_aggregator_model.php */
