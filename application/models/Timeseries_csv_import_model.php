<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Indicator timeseries CSV import engine.
 *
 * Large CSVs cannot be imported inside one HTTP request (proxy / FastCGI timeouts). Like the data
 * tables API (Data_table_mongo_model::import_csv_chunked), the file is staged first and then
 * processed by repeated calls, each working for a bounded time from a saved byte offset.
 *
 * Job state lives in the Mongo collection {@see JOBS_COLLECTION}, one document per study (_id = sid).
 *
 * Phases (job.status):
 *   ready        file staged and header validated; existing observations untouched
 *   deleting     indexes ensured; removing the study's previous observations in bounded batches
 *   in_progress  inserting CSV rows from job.byte_offset
 *   finalizing   all rows inserted; value counts / resource / counters pending
 *   completed | failed
 */
class Timeseries_csv_import_model extends CI_Model
{
	const JOBS_COLLECTION = 'timeseries_import_jobs';

	/** Statuses that still need process calls. */
	const ACTIVE_STATUSES = ['ready', 'deleting', 'in_progress', 'finalizing'];

	/** Value-count tallies larger than this (JSON bytes) are dropped and recomputed in MongoDB at finalize. */
	const MAX_TALLY_JSON_BYTES = 4000000;

	/** Documents deleted per deleteMany during the deleting phase. */
	const DELETE_BATCH = 10000;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Timeseries_mongo_model');
	}

	// -------------------------------------------------------------------------
	// Header
	// -------------------------------------------------------------------------

	/**
	 * Read the header row and map CSV columns to DSD component names.
	 *
	 * @param string $path
	 * @param string $delimiter
	 * @param array  $mapping    CSV header => DSD name ('' drops the column)
	 * @param array  $components
	 * @return array{rename:array<int,string>,ignored_columns:string[],data_offset:int}
	 * @throws Exception when no column matches or required DSD columns are missing
	 */
	public function read_header($path, $delimiter, array $mapping, array $components)
	{
		$fh = fopen($path, 'rb');
		if ($fh === false) {
			throw new Exception('Could not read uploaded file');
		}
		$header = fgetcsv($fh, 0, $delimiter);
		$dataOffset = (int) ftell($fh);
		fclose($fh);
		if ($header === false || empty($header)) {
			throw new Exception('CSV has no header row');
		}
		$header = array_map(function ($h) {
			return trim((string) $h);
		}, $header);

		$rename  = [];
		$ignored = [];
		foreach ($header as $i => $col) {
			$to = isset($mapping[$col]) ? trim((string) $mapping[$col]) : $col;
			if ($to === '') {
				continue;
			}
			$canonical = $this->Timeseries_mongo_model->resolve_dsd_component_name($to, $components);
			if ($canonical === null) {
				$ignored[] = $col !== '' ? $col : ('column ' . ($i + 1));
				continue;
			}
			$rename[(int) $i] = $canonical;
		}
		$ignored = array_values(array_unique($ignored));
		if (empty($rename)) {
			$hint = $ignored !== [] ? ' (ignored headers: ' . implode(', ', array_slice($ignored, 0, 20)) . ')' : '';
			throw new Exception('No CSV columns match a DSD field.' . $hint);
		}
		$this->Timeseries_mongo_model->assert_csv_import_mapped_fields_cover_required_dsd(
			array_values(array_unique(array_values($rename))),
			$components
		);

		return [
			'rename'          => $rename,
			'ignored_columns' => $ignored,
			'data_offset'     => $dataOffset,
		];
	}

	// -------------------------------------------------------------------------
	// Rows
	// -------------------------------------------------------------------------

	/**
	 * Fresh row-processing state for {@see process_rows()}.
	 *
	 * @param int $data_offset byte offset of the first data row
	 */
	public function initial_row_state($data_offset)
	{
		return [
			'byte_offset'           => (int) $data_offset,
			'lines_read'            => 0,
			'inserted'              => 0,
			'unparsed'              => ['count' => 0, 'examples' => []],
			'tally'                 => [],
			'tally_overflow'        => false,
			'eof'                   => false,
		];
	}

	/**
	 * Stream CSV rows from $state['byte_offset'] and insert them in batches.
	 *
	 * Stops at end of file, or once $max_seconds have elapsed (0 = no limit). After every flushed
	 * batch $on_flush($state) is called with the offset just past the last inserted row, so callers
	 * can persist progress; returning false from it stops processing (e.g. the job lock was lost).
	 *
	 * @param array         $ctx       sid, idno, dsd_id, structure, components
	 * @param string        $path
	 * @param string        $delimiter
	 * @param array         $rename    column index => DSD component name
	 * @param array         $state     from {@see initial_row_state()} or a previous call
	 * @param int           $max_seconds
	 * @param callable|null $on_flush
	 * @return array updated state
	 */
	public function process_rows(array $ctx, $path, $delimiter, array $rename, array $state, $max_seconds = 0, ?callable $on_flush = null)
	{
		$started = microtime(true);
		$mongo   = $this->Timeseries_mongo_model;
		$sid     = (int) $ctx['sid'];
		$dsdId   = (int) $ctx['dsd_id'];
		$idno    = $ctx['idno'];

		$plan       = $mongo->build_observation_plan($ctx['components'], $ctx['structure']);
		$countNames = $mongo->get_value_count_component_names($ctx['components']);
		$timeName   = $mongo->get_component_name_for_column_type($ctx['components'], 'time_period');
		$chunkSize  = $this->batch_size();

		$fh = fopen($path, 'rb');
		if ($fh === false) {
			throw new Exception('Could not read import file');
		}
		if (fseek($fh, (int) $state['byte_offset']) !== 0) {
			fclose($fh);
			throw new Exception('Could not seek in import file');
		}

		$docs      = [];
		$tally     = $state['tally_overflow'] ? null : $state['tally'];
		$keepGoing = true;

		try {
			while (true) {
				if ($max_seconds > 0 && (microtime(true) - $started) >= $max_seconds) {
					break;
				}
				$row = fgetcsv($fh, 0, $delimiter);
				if ($row === false) {
					$state['eof'] = true;
					break;
				}
				$state['lines_read']++;
				$rowEnd = (int) ftell($fh);

				if ($this->row_all_empty($row)) {
					if ($docs === []) {
						$state['byte_offset'] = $rowEnd;
					}
					continue;
				}

				// Header was already mapped to canonical DSD names, so no per-row name resolution.
				$fields = [];
				foreach ($rename as $i => $name) {
					$v = isset($row[$i]) ? trim((string) $row[$i]) : '';
					$fields[$name] = $v === '' ? null : $v;
				}
				$doc = $mongo->build_observation_document_with_plan($sid, $idno, $dsdId, $fields, $plan);

				if ($timeName !== null && $doc['_ts_period_start'] === null) {
					$state['unparsed']['count']++;
					$raw = isset($doc[$timeName]) ? (string) $doc[$timeName] : '';
					if (count($state['unparsed']['examples']) < 10 && !in_array($raw, $state['unparsed']['examples'], true)) {
						$state['unparsed']['examples'][] = $raw;
					}
				}
				if ($tally !== null) {
					$mongo->tally_value_counts($tally, $doc, $countNames);
				}

				$docs[] = $doc;
				if (count($docs) >= $chunkSize) {
					$keepGoing = $this->flush($dsdId, $docs, $state, $rowEnd, $tally, $on_flush);
					if (!$keepGoing) {
						break;
					}
				}
			}
			if ($keepGoing && $docs !== []) {
				$this->flush($dsdId, $docs, $state, (int) ftell($fh), $tally, $on_flush);
			}
		} finally {
			fclose($fh);
		}

		return $state;
	}

	/**
	 * @return bool false when $on_flush asked to stop
	 */
	private function flush($dsdId, array &$docs, array &$state, $offsetAfter, &$tally, ?callable $on_flush)
	{
		$state['inserted'] += $this->Timeseries_mongo_model->insert_observations_batch($dsdId, $docs, false);
		$docs = [];
		$state['byte_offset'] = (int) $offsetAfter;
		if ($tally !== null) {
			$state['tally'] = $tally;
		}
		if ($on_flush === null) {
			return true;
		}
		return $on_flush($state) !== false;
	}

	private function row_all_empty(array $row)
	{
		foreach ($row as $cell) {
			if (trim((string) $cell) !== '') {
				return false;
			}
		}
		return true;
	}

	private function batch_size()
	{
		$chunkSize = (int) $this->config->item('indicator_timeseries_bulk_batch_size');
		if ($chunkSize < 1) {
			$chunkSize = 5000;
		}
		$maxBulk = (int) $this->config->item('indicator_timeseries_max_bulk_insert');
		if ($maxBulk < 1) {
			$maxBulk = 5000;
		}
		return min($chunkSize, $maxBulk);
	}

	/**
	 * Delete up to the time budget's worth of the study's observations, in batches.
	 *
	 * @return bool true when no observations remain for the study
	 */
	public function delete_observations_batched($dsd_id, $sid, $max_seconds)
	{
		$started = microtime(true);
		$coll    = $this->Timeseries_mongo_model->get_collection($dsd_id);
		$filter  = ['$or' => [['sid' => (int) $sid], ['sid' => (string) $sid]]];
		while (true) {
			$ids = [];
			foreach ($coll->find($filter, ['projection' => ['_id' => 1], 'limit' => self::DELETE_BATCH]) as $d) {
				$ids[] = $d['_id'];
			}
			if ($ids === []) {
				return true;
			}
			$coll->deleteMany(['_id' => ['$in' => $ids]]);
			if ($max_seconds > 0 && (microtime(true) - $started) >= $max_seconds) {
				return false;
			}
		}
	}

	// -------------------------------------------------------------------------
	// Job state
	// -------------------------------------------------------------------------

	/** @return \MongoDB\Collection */
	private function jobs()
	{
		$m = $this->Timeseries_mongo_model;
		return $m->get_db_connection()->{$m->get_mongo_db_name()}->{self::JOBS_COLLECTION};
	}

	private function read_options()
	{
		return ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
	}

	/**
	 * @param int $sid
	 * @return array|null
	 */
	public function get_job($sid)
	{
		$job = $this->jobs()->findOne(['_id' => (int) $sid], $this->read_options());
		return $job ? $this->decode_job($job) : null;
	}

	/**
	 * Replace the study's job with a new one in status "ready".
	 *
	 * @param int   $sid
	 * @param array $fields
	 * @return array
	 */
	public function create_job($sid, array $fields)
	{
		$now = time();
		$job = array_merge($fields, [
			'_id'        => (int) $sid,
			'sid'        => (int) $sid,
			'status'     => 'ready',
			'error'      => null,
			'lock_until' => 0,
			'lock_token' => null,
			'created_at' => $now,
			'updated_at' => $now,
		]);
		$job = $this->encode_job(array_merge($job, $this->initial_row_state((int) $fields['data_offset'])));
		$this->jobs()->replaceOne(['_id' => (int) $sid], $job, ['upsert' => true]);
		return $this->get_job($sid);
	}

	public function delete_job($sid)
	{
		$this->jobs()->deleteOne(['_id' => (int) $sid]);
	}

	/**
	 * Take the job's processing lock if it is active and not held by another request.
	 *
	 * @param int $sid
	 * @param int $seconds lock lifetime (must exceed one process call)
	 * @return array|null job (with lock_token) or null when busy / not active
	 */
	public function acquire_lock($sid, $seconds)
	{
		$now   = time();
		$token = bin2hex(random_bytes(8));
		$job   = $this->jobs()->findOneAndUpdate(
			[
				'_id'        => (int) $sid,
				'status'     => ['$in' => self::ACTIVE_STATUSES],
				'lock_until' => ['$lt' => $now],
			],
			['$set' => ['lock_until' => $now + (int) $seconds, 'lock_token' => $token]],
			array_merge($this->read_options(), [
				'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
			])
		);
		return $job ? $this->decode_job($job) : null;
	}

	/**
	 * Persist fields while holding the lock.
	 *
	 * @return bool false when the lock is no longer ours (another request took over)
	 */
	public function save_job($sid, $token, array $fields)
	{
		$fields['updated_at'] = time();
		$res = $this->jobs()->updateOne(
			['_id' => (int) $sid, 'lock_token' => (string) $token],
			['$set' => $this->encode_job($fields)]
		);
		return $res->getMatchedCount() > 0;
	}

	public function release_lock($sid, $token, array $fields = [])
	{
		$fields['lock_until'] = 0;
		$fields['lock_token'] = null;
		$fields['updated_at'] = time();
		$this->jobs()->updateOne(
			['_id' => (int) $sid, 'lock_token' => (string) $token],
			['$set' => $this->encode_job($fields)]
		);
	}

	/**
	 * Tally and column map are stored as JSON strings: codes can contain "." / "$" (invalid Mongo
	 * keys) and column indexes must survive as integers.
	 */
	private function encode_job(array $fields)
	{
		if (array_key_exists('tally', $fields)) {
			$json = json_encode((object) $fields['tally'], JSON_UNESCAPED_UNICODE);
			if ($json === false || strlen($json) > self::MAX_TALLY_JSON_BYTES) {
				$fields['tally_overflow'] = true;
				$json = '{}';
			}
			$fields['tally_json'] = $json;
			unset($fields['tally']);
		}
		if (array_key_exists('rename', $fields)) {
			$fields['rename_json'] = json_encode((object) $fields['rename'], JSON_UNESCAPED_UNICODE);
			unset($fields['rename']);
		}
		return $fields;
	}

	private function decode_job(array $job)
	{
		$job['tally'] = [];
		if (isset($job['tally_json']) && is_string($job['tally_json'])) {
			$t = json_decode($job['tally_json'], true);
			$job['tally'] = is_array($t) ? $t : [];
		}
		unset($job['tally_json']);
		$job['rename'] = [];
		if (isset($job['rename_json']) && is_string($job['rename_json'])) {
			$r = json_decode($job['rename_json'], true);
			if (is_array($r)) {
				foreach ($r as $i => $name) {
					$job['rename'][(int) $i] = (string) $name;
				}
			}
		}
		unset($job['rename_json']);
		$job['tally_overflow'] = !empty($job['tally_overflow']);
		return $job;
	}

	/**
	 * Client-facing progress summary (no internal fields).
	 *
	 * @param array|null $job
	 * @return array|null
	 */
	public function public_progress($job)
	{
		if (!$job) {
			return null;
		}
		$size   = isset($job['file_size']) ? (int) $job['file_size'] : 0;
		$offset = isset($job['byte_offset']) ? (int) $job['byte_offset'] : 0;
		$status = (string) $job['status'];
		$pct    = 0;
		if ($status === 'completed' || $status === 'finalizing') {
			$pct = 100;
		} elseif ($status === 'in_progress' && $size > 0) {
			$pct = (int) min(99, floor($offset * 100 / $size));
		}
		return [
			'status'          => $status,
			'has_more'        => in_array($status, self::ACTIVE_STATUSES, true),
			'progress_percent'=> $pct,
			'bytes_processed' => $offset,
			'file_size'       => $size,
			'lines_read'      => isset($job['lines_read']) ? (int) $job['lines_read'] : 0,
			'inserted'        => isset($job['inserted']) ? (int) $job['inserted'] : 0,
			'ignored_columns' => isset($job['ignored_columns']) ? array_values((array) $job['ignored_columns']) : [],
			'unparsed_time_periods' => isset($job['unparsed']) ? $job['unparsed'] : ['count' => 0, 'examples' => []],
			'error'           => isset($job['error']) ? $job['error'] : null,
			'locked'          => isset($job['lock_until']) && (int) $job['lock_until'] >= time(),
			'created_at'      => isset($job['created_at']) ? (int) $job['created_at'] : null,
			'updated_at'      => isset($job['updated_at']) ? (int) $job['updated_at'] : null,
			'completed_at'    => isset($job['completed_at']) ? (int) $job['completed_at'] : null,
			'resource_saved'  => isset($job['resource_saved']) ? (bool) $job['resource_saved'] : null,
		];
	}
}
