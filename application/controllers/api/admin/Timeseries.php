<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/MY_REST_Controller.php';

/**
 * Admin Timeseries API (MongoDB indicator observations, DSD-driven).
 *
 * Base: /api/admin/timeseries — authenticated session or API key.
 * Study-scoped routes require {@see MY_REST_Controller::has_dataset_access} (view|edit) on the dataset, aligned with catalog edit.
 * DSD-wide maintenance (indexes, stats, rehash without a study) remains Ion Auth admin-only.
 * Public read-only mirror: /api/timeseries (see {@see Timeseries_public}).
 *
 * Study-scoped (idno = catalogue dataset idno; DSD via metadata.data_structure_reference idno):
 *   GET .../data/{idno}/count
 *   GET .../data/{idno} — paging: offset (or legacy skip), limit, sort; result: data, limit, offset, total, found.
 *        Filters: d[…], c[…], or legacy top-level (see Timeseries_mongo_model::build_observation_query_filter).
 *   POST .../data/{idno} — JSON array or { "data": [...] }
 *   POST .../data/import — multipart: required `idno` (study idno); CSV via `file` or completed resumable
 *        `upload_id` (from POST /api/uploads/*, not both). Optional `dsd_idno` (catalogue DSD idno) persists
 *        the study→DSD link like {@see data_attach_dsd_post} before import; optional `delimiter`, `mapping`
 *        (JSON string), `ensure_unique_index` (0|1). On success copies CSV into the study catalogue folder as
 *        {sid}_indicator_data.csv and upserts a {@see resources} row (resource_idno from config).
 *        Synchronous — large files should use the chunked flow below.
 *   POST .../data/upload — same fields as data/import; validates the header and stages the CSV (no data changes)
 *   POST .../data/{idno}/import-process — optional JSON { "max_time": 10–120 }; advances the staged import
 *        for a bounded time; repeat while result.has_more (409 while another call holds the lock)
 *   GET  .../data/{idno}/import-status — staged import progress (null when none)
 *   POST .../data/{idno}/rehash — optional JSON { "limit": n }
	 *   GET  .../data/{idno}/duplicates
	 *   GET  .../data/{idno}/schema
	 *   GET  .../data/{idno}/import-csv — stream canonical `{sid}_indicator_data.csv` from the study folder (if present)
	 *   POST .../data/{idno}/clear-data — delete all indicator observations, dimension-summary cache, and canonical import CSV for this study (DSD link unchanged)
 *
 * DSD-scoped:
 *   POST .../data-structures/{id}/indexes — optional JSON { "include_unique_key_hash": true }
 *   POST .../data-structures/{id}/rehash — optional JSON { "sid": n, "limit": n }
 *   GET  .../data-structures/{id}/duplicates ?sid=
 *   GET  .../data-structures/{id}/stats
 *
 * @see application/config/routes.php
 */
class Timeseries extends MY_REST_Controller {

	/** Maximum indicators per presentation/batch request. */
	const PRESENTATION_BATCH_MAX = 2000;

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->library('acl_manager');
		$this->load->model('Timeseries_mongo_model');
		$this->load->model('Timeseries_dsd_model');
		$this->load->model('Timeseries_value_count_model');
		$this->load->model('Timeseries_csv_import_model');
		$this->load->model('Study_presentation_model');
		$this->load->model('Survey_resource_model');
		$this->load->model('Catalog_model');
		$this->load->model('Dataset_model');
		$this->config->load('indicator_timeseries');
	}

	// ---------------------------------------------------------------------
	// Study-scoped: /api/admin/timeseries/data/{idno}/...
	// ---------------------------------------------------------------------

	/**
	 * GET .../data/{idno}/count
	 */
	public function data_count_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
				(int) $ctx['sid'],
				$ctx['components'],
				(array) $this->input->get()
			);
			$count  = $this->Timeseries_mongo_model->count_observations($ctx['dsd_id'], $filter);
			$this->set_response([
				'status' => 'success',
				'result' => ['count' => $count],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}
	 */
	public function data_get($idno = null)
	{
		try {
			$ctx    = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$query  = (array) $this->input->get();
			$filter = $this->Timeseries_mongo_model->build_observation_query_filter(
				(int) $ctx['sid'],
				$ctx['components'],
				$query
			);
			$limit  = $this->_query_limit();
			$offset = Timeseries_mongo_model::parse_observation_list_offset($query);
			$sort   = ['_ts_period_start' => 1];
			if ($this->get('sort') === 'desc') {
				$sort = ['_ts_period_start' => -1];
			}
			$rows = $this->Timeseries_mongo_model->find_observations($ctx['dsd_id'], $filter, [
				'limit' => $limit,
				'skip'  => $offset,
				'sort'  => $sort,
			]);
			$list   = $this->_bson_list_to_array($rows);
			$total  = $this->Timeseries_mongo_model->count_observations($ctx['dsd_id'], $filter);
			$found  = count($list);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'data'         => $list,
					'limit'        => $limit,
					'offset'       => $offset,
					'total'        => $total,
					'found'        => $found,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/import-csv
	 *
	 * Streams the configured indicator import CSV from the study catalogue folder.
	 */
	public function data_import_csv_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$fn = $this->Dataset_model->get_indicator_timeseries_import_csv_filename((int) $ctx['sid']);
			if ($fn === '') {
				throw new Exception('No indicator import filename is configured for this study.');
			}
			$surveyFolder = $this->Catalog_model->get_survey_path_full((int) $ctx['sid']);
			if (!$surveyFolder || ! is_dir($surveyFolder)) {
				throw new Exception('Study folder not found');
			}
			$path = unix_path($surveyFolder . '/' . $fn);
			if (! is_file($path)) {
				throw new Exception('Indicator import CSV not found on disk.');
			}
			$this->load->helper('download');
			force_download2($path);
			die();
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/clear-data
	 *
	 * Removes Mongo observations, value-count summaries, and the canonical CSV file; DSD link is unchanged.
	 */
	public function data_clear_data_post($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('edit', (int) $ctx['sid']);
			$this->_assert_no_running_import_job((int) $ctx['sid']);
			$this->Timeseries_mongo_model->delete_observations_for_sid((int) $ctx['dsd_id'], (int) $ctx['sid']);
			$this->_clear_value_counts_for_sid((int) $ctx['sid']);
			$this->_unlink_indicator_timeseries_import_csv_and_legacy((int) $ctx['sid']);
			$this->Timeseries_mongo_model->refresh_ts_data_count_for_sid((int) $ctx['sid']);
			$this->Dataset_model->clear_indicator_ts_sync_for_survey((int) $ctx['sid'], (int) $ctx['dsd_id']);
			$this->set_response([
				'status' => 'success',
				'result' => ['cleared' => true],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/attach-dsd
	 *
	 * Accepts either:
	 * - { "data_structure_id": 123 }
	 * - { "data_structure_reference": "AGENCY.NAME.VERSION" } (catalogue idno)
	 */
	public function data_attach_dsd_post($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode((string) $idno) : '';
			if ($idno === '') {
				throw new Exception('Dataset idno is required');
			}

			$sid = (int) $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$input = $this->_optional_json_body();
			$link = $this->_resolve_structure_link_input($input);

			$update = $this->_apply_structure_link_to_survey((int) $sid, $link);
			if ($link === null) {
				$this->_clear_value_counts_for_sid((int) $sid);
			} else {
				$ctx = $this->_context_from_idno($idno);
				$this->_sync_value_counts_for_context($ctx);
			}

			$this->Timeseries_mongo_model->refresh_ts_data_count_for_sid((int) $sid);

			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid' => $sid,
					'idno' => $this->Dataset_model->get_idno($sid),
					'data_structure_id' => $link ? (int) $link['id'] : null,
					'data_structure_reference' => $link ? (string) $link['idno'] : null,
					'ts_dimensions' => $update['ts_dimensions'] ?? null,
					'ts_sync_required' => (int) ($update['ts_sync_required'] ?? 0),
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno} — JSON batch
	 */
	public function data_post($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('edit', (int) $ctx['sid']);
			$input = $this->raw_json_input();
			if (!is_array($input)) {
				throw new Exception('JSON body required');
			}
			if (isset($input['data'])) {
				$list = $input['data'];
				if (!is_array($list) || !$this->_is_list($list)) {
					throw new Exception('"data" must be a JSON array');
				}
			} else {
				$list = $input;
				if (!is_array($list) || !$this->_is_list($list)) {
					throw new Exception('Body must be an array of rows or { "data": [ ... ] }');
				}
			}

			$include_unique = !isset($input['ensure_unique_index']) || !empty($input['ensure_unique_index']);
			if (!empty($this->input->get('ensure_unique_index')) || $this->input->get('ensure_unique_index') === '1') {
				$include_unique = true;
			}
			$this->Timeseries_mongo_model->ensure_indexes((int) $ctx['dsd_id'], $include_unique, (array) $ctx['components']);

			$docs     = [];
			$unparsed = ['count' => 0, 'examples' => []];
			foreach ($list as $idx => $row) {
				if (!is_array($row)) {
					throw new Exception('Invalid observation at index ' . $idx);
				}
				$fields = $this->Timeseries_mongo_model->filter_row_to_dsd_fields($row, $ctx['components']);
				$doc    = $this->Timeseries_mongo_model->build_observation_document(
					(int) $ctx['sid'],
					$ctx['idno'],
					(int) $ctx['dsd_id'],
					$fields,
					$ctx['components'],
					$ctx['structure']
				);
				$this->_track_unparsed_time_period($unparsed, $doc, $ctx['components']);
				$docs[] = $doc;
			}
			$inserted = $this->Timeseries_mongo_model->insert_observations_batch((int) $ctx['dsd_id'], $docs, false);
			$this->_sync_value_counts_for_context($ctx);
			$this->Timeseries_mongo_model->refresh_ts_data_count_for_sid((int) $ctx['sid']);
			$this->_log_unparsed_time_periods($unparsed, $ctx);
			$this->set_response([
				'status' => 'success',
				'result' => ['inserted' => $inserted, 'unparsed_time_periods' => $unparsed],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			if ($this->_exception_chain_matches_duplicate($e)) {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Duplicate observation (key_hash).',
				], REST_Controller::HTTP_CONFLICT);
				return;
			}
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/import — multipart: required `idno`; CSV via `file` or completed `upload_id`; optional `dsd_idno`, `delimiter`, `mapping`, `ensure_unique_index`
	 *
	 * Synchronous: the whole file is processed inside this request. Large files should use
	 * POST .../data/upload followed by repeated POST .../data/{idno}/import-process instead.
	 */
	public function data_import_post()
	{
		try {
			$params = $this->_import_request_params();
			$ctx    = $params['ctx'];
			$this->_assert_no_running_import_job((int) $ctx['sid']);

			$csvSource = $this->_resolve_csv_import_file_path();
			$path      = $csvSource['path'];
			$header    = $this->Timeseries_csv_import_model->read_header(
				$path,
				$params['delimiter'],
				$params['mapping'],
				$ctx['components']
			);

			set_time_limit(0);

			// A synchronous import supersedes any staged chunked import for this study.
			$this->_discard_import_job((int) $ctx['sid']);

			$this->Timeseries_mongo_model->ensure_indexes((int) $ctx['dsd_id'], $params['ensure_unique_index'], (array) $ctx['components']);
			$this->Timeseries_mongo_model->delete_observations_for_sid((int) $ctx['dsd_id'], (int) $ctx['sid']);
			$this->_clear_value_counts_for_sid((int) $ctx['sid']);

			$state = $this->Timeseries_csv_import_model->process_rows(
				$ctx,
				$path,
				$params['delimiter'],
				$header['rename'],
				$this->Timeseries_csv_import_model->initial_row_state($header['data_offset']),
				0
			);
			if ($state['inserted'] === 0) {
				throw new Exception('No data rows in CSV');
			}

			$resourceMeta = $this->_finalize_import($ctx, $state, $path, false);

			$this->set_response([
				'status' => 'success',
				'result' => [
					'inserted' => $state['inserted'],
					'lines_read'  => $state['lines_read'],
					'ignored_columns' => $header['ignored_columns'],
					'unparsed_time_periods' => $state['unparsed'],
					'import_source'   => $csvSource['source'],
					'upload_id'       => $csvSource['upload_id'],
					'resource_id'     => $resourceMeta ? (int) $resourceMeta['resource_id'] : null,
					'resource_idno'   => $resourceMeta ? (string) $resourceMeta['resource_idno'] : null,
					'resource_saved'  => $resourceMeta !== null,
				],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			if ($this->_exception_chain_matches_duplicate($e)) {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Duplicate observation (key_hash).',
				], REST_Controller::HTTP_CONFLICT);
				return;
			}
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/upload — stage a CSV for chunked import (step 1 of 2).
	 *
	 * Same multipart fields as POST .../data/import. Validates the header against the DSD and stores the
	 * file in the study folder; existing observations are not touched until the first import-process call.
	 * Then call POST .../data/{idno}/import-process until `has_more` is false.
	 */
	public function data_upload_post()
	{
		try {
			$params = $this->_import_request_params();
			$ctx    = $params['ctx'];
			$sid    = (int) $ctx['sid'];
			$this->_assert_no_running_import_job($sid);

			$csvSource = $this->_resolve_csv_import_file_path();
			$header    = $this->Timeseries_csv_import_model->read_header(
				$csvSource['path'],
				$params['delimiter'],
				$params['mapping'],
				$ctx['components']
			);

			$staged = $this->_staged_import_csv_path($sid);
			if ($staged === null) {
				throw new Exception('Study folder not found; cannot stage the import file');
			}
			@set_time_limit(0);
			if (is_file($staged)) {
				@unlink($staged);
			}
			if ($csvSource['source'] === 'file') {
				$moved = @move_uploaded_file($csvSource['path'], $staged);
			} else {
				// rename() can fail across volumes; fall back to copy (the upload is deleted below).
				$moved = @rename($csvSource['path'], $staged) || @copy($csvSource['path'], $staged);
			}
			if (!$moved || !is_file($staged)) {
				throw new Exception('Could not store the uploaded CSV in the study folder');
			}
			if ($csvSource['upload_id'] !== null) {
				$this->uploader->delete_upload($csvSource['upload_id']);
			}

			$job = $this->Timeseries_csv_import_model->create_job($sid, [
				'idno'                => $ctx['idno'],
				'dsd_id'              => (int) $ctx['dsd_id'],
				'file_size'           => (int) filesize($staged),
				'delimiter'           => $params['delimiter'],
				'rename'              => $header['rename'],
				'ignored_columns'     => $header['ignored_columns'],
				'data_offset'         => (int) $header['data_offset'],
				'ensure_unique_index' => $params['ensure_unique_index'],
				'import_source'       => $csvSource['source'],
			]);

			$this->set_response([
				'status' => 'success',
				'result' => $this->Timeseries_csv_import_model->public_progress($job),
				'links'  => [
					'process' => site_url('api/admin/timeseries/data/' . rawurlencode($ctx['idno']) . '/import-process'),
					'status'  => site_url('api/admin/timeseries/data/' . rawurlencode($ctx['idno']) . '/import-status'),
				],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/import-process — run the staged import for a bounded time (step 2 of 2).
	 *
	 * Optional JSON { "max_time": seconds } (10–120, default 30). Each call advances the import
	 * (replace existing rows → insert CSV rows → finalize) and returns progress; repeat while
	 * `result.has_more` is true. 409 when another call is still working on this study.
	 */
	public function data_import_process_post($idno = null)
	{
		$sid   = 0;
		$token = null;
		try {
			$ctx = $this->_context_from_idno($idno);
			$sid = (int) $ctx['sid'];
			$this->has_dataset_access('edit', $sid);

			$input   = $this->_optional_json_body();
			$maxTime = isset($input['max_time']) ? (int) $input['max_time'] : 30;
			$maxTime = max(10, min(120, $maxTime));

			$model = $this->Timeseries_csv_import_model;
			$job   = $model->get_job($sid);
			if (!$job) {
				throw new Exception('No staged import found for this study; upload a CSV first');
			}
			if (!in_array($job['status'], Timeseries_csv_import_model::ACTIVE_STATUSES, true)) {
				$this->set_response(['status' => 'success', 'result' => $model->public_progress($job)], REST_Controller::HTTP_OK);
				return;
			}

			$job = $model->acquire_lock($sid, $maxTime + 300);
			if (!$job) {
				$this->set_response([
					'status'  => 'error',
					'message' => 'Import is being processed by another request; retry shortly.',
					'result'  => $model->public_progress($model->get_job($sid)),
				], REST_Controller::HTTP_CONFLICT);
				return;
			}
			$token = $job['lock_token'];

			@set_time_limit(0);
			// Keep going if the client disconnects, so saved progress always matches inserted rows.
			ignore_user_abort(true);

			if ((int) $job['dsd_id'] !== (int) $ctx['dsd_id']) {
				throw new Exception('The study\'s data structure changed after the CSV was uploaded; upload it again');
			}
			$staged = $this->_staged_import_csv_path($sid);
			if ($staged === null || !is_file($staged)) {
				throw new Exception('Staged import file is missing; upload the CSV again');
			}

			$deadline  = microtime(true) + $maxTime;
			$remaining = function () use ($deadline) {
				return (int) max(1, ceil($deadline - microtime(true)));
			};

			$status = $job['status'];

			if ($status === 'ready') {
				$this->Timeseries_mongo_model->ensure_indexes((int) $ctx['dsd_id'], !empty($job['ensure_unique_index']), (array) $ctx['components']);
				$this->_clear_value_counts_for_sid($sid);
				$status = 'deleting';
				$model->save_job($sid, $token, ['status' => $status, 'started_at' => time()]);
			}

			if ($status === 'deleting' && microtime(true) < $deadline) {
				if ($model->delete_observations_batched((int) $ctx['dsd_id'], $sid, $remaining())) {
					$status = 'in_progress';
					$model->save_job($sid, $token, ['status' => $status]);
				}
			}

			if ($status === 'in_progress' && microtime(true) < $deadline) {
				$rowState = [
					'byte_offset'    => (int) $job['byte_offset'],
					'lines_read'     => (int) $job['lines_read'],
					'inserted'       => (int) $job['inserted'],
					'unparsed'       => $job['unparsed'],
					'tally'          => $job['tally'],
					'tally_overflow' => $job['tally_overflow'],
					'eof'            => false,
				];
				$rowState = $model->process_rows(
					$ctx,
					$staged,
					(string) $job['delimiter'],
					$job['rename'],
					$rowState,
					$remaining(),
					function (array $s) use ($model, $sid, $token) {
						return $model->save_job($sid, $token, [
							'byte_offset'    => $s['byte_offset'],
							'lines_read'     => $s['lines_read'],
							'inserted'       => $s['inserted'],
							'unparsed'       => $s['unparsed'],
							'tally'          => $s['tally'],
							'tally_overflow' => $s['tally_overflow'],
						]);
					}
				);
				$save = [
					'byte_offset' => $rowState['byte_offset'],
					'lines_read'  => $rowState['lines_read'],
				];
				if ($rowState['eof']) {
					if ($rowState['inserted'] === 0) {
						throw new Exception('No data rows in CSV');
					}
					// Finalize in the next call so it gets a full time budget.
					$status = 'finalizing';
					$save['status'] = $status;
				}
				$model->save_job($sid, $token, $save);
			} elseif ($status === 'finalizing') {
				$resourceMeta = $this->_finalize_import($ctx, $job, $staged, true);
				$model->save_job($sid, $token, [
					'status'         => 'completed',
					'completed_at'   => time(),
					'resource_saved' => $resourceMeta !== null,
				]);
			}

			$model->release_lock($sid, $token);
			$token = null;

			$this->set_response([
				'status' => 'success',
				'result' => $model->public_progress($model->get_job($sid)),
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$message = $this->_exception_chain_matches_duplicate($e)
				? 'Duplicate observation (key_hash): the CSV contains more than one row for the same series and time period.'
				: $e->getMessage();
			if ($sid > 0 && $token !== null) {
				log_message('error', "Timeseries chunked import failed (sid {$sid}): " . $e->getMessage());
				$this->Timeseries_csv_import_model->release_lock($sid, $token, ['status' => 'failed', 'error' => $message]);
			}
			$this->_error_response(new Exception($message));
		}
	}

	/**
	 * GET .../data/{idno}/import-status — progress of the staged/chunked import (null when none).
	 */
	public function data_import_status_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$job = $this->Timeseries_csv_import_model->get_job((int) $ctx['sid']);
			$this->set_response([
				'status' => 'success',
				'result' => $this->Timeseries_csv_import_model->public_progress($job),
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/rehash
	 */
	public function data_rehash_post($idno = null)
	{
		try {
			$ctx    = $this->_context_from_idno($idno);
			$this->has_dataset_access('edit', (int) $ctx['sid']);
			$input  = $this->_optional_json_body();
			$limit  = null;
			if (isset($input['limit']) && $input['limit'] !== '') {
				$limit = (int) $input['limit'];
			}
			$updated = $this->Timeseries_mongo_model->rehash_documents(
				(int) $ctx['dsd_id'],
				['sid' => (int) $ctx['sid']],
				$limit > 0 ? $limit : null
			);
			$this->_sync_value_counts_for_context($ctx);
			if ($limit === null || $limit <= 0) {
				$this->Dataset_model->clear_indicator_ts_sync_for_survey((int) $ctx['sid'], (int) $ctx['dsd_id']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['updated' => $updated],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/duplicates
	 */
	public function data_duplicates_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$dups = $this->Timeseries_mongo_model->find_duplicate_key_hashes((int) $ctx['dsd_id'], ['sid' => (int) $ctx['sid']]);
			$this->set_response([
				'status' => 'success',
				'result' => ['duplicates' => $dups],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/schema
	 */
	public function data_schema_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$tsRow = $this->db->select('ts_dimensions, ts_sync_required')
				->get_where('surveys', ['id' => (int) $ctx['sid']])
				->row_array();
			$csvFn = $this->Dataset_model->get_indicator_timeseries_import_csv_filename((int) $ctx['sid']);
			$surveyFolder = $this->Catalog_model->get_survey_path_full((int) $ctx['sid']);
			$csvPresent = $surveyFolder && $csvFn !== '' && is_file(unix_path($surveyFolder . '/' . $csvFn));
			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid'           => (int) $ctx['sid'],
					'idno'          => $ctx['idno'],
					'dsd_id'        => (int) $ctx['dsd_id'],
					'data_structure'=> $ctx['structure'],
					'components'    => $ctx['components'],
					'collection'      => $this->Timeseries_mongo_model->get_collection_name((int) $ctx['dsd_id']),
					'ts_dimensions' => $tsRow['ts_dimensions'] ?? null,
					'ts_sync_required' => isset($tsRow['ts_sync_required']) ? (int) $tsRow['ts_sync_required'] : 0,
					'indicator_import_csv_filename' => $csvFn !== '' ? $csvFn : null,
					'indicator_import_csv_present'  => (bool) $csvPresent,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/presentation
	 *
	 * Default view settings (study_presentation) plus the observed code options per column,
	 * for the admin "Default view" editor.
	 */
	public function data_presentation_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$this->set_response([
				'status' => 'success',
				'result' => array_merge(
					$this->_presentation_result((int) $ctx['sid'], $ctx['idno']),
					[
						'time_period_component' => $this->Timeseries_mongo_model->get_component_name_for_column_type($ctx['components'], 'time_period'),
						'filter_options' => $this->Timeseries_value_count_model->get_filter_options(
							(int) $ctx['sid'],
							(int) $ctx['dsd_id'],
							$ctx['components']
						),
					]
				),
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/presentation — body {"data_presentation": {...} | null}
	 *
	 * Replaces the default view settings as a whole; an empty or null value removes them.
	 */
	public function data_presentation_post($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode((string) $idno) : '';
			if ($idno === '') {
				throw new Exception('Dataset idno is required');
			}
			$sid = (int) $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);

			$input = $this->_optional_json_body();
			$settings = $this->_presentation_from_input(array_key_exists('data_presentation', $input) ? $input['data_presentation'] : null);
			if (!empty($settings)) {
				$this->Study_presentation_model->validate($settings);
			}
			$this->Study_presentation_model->replace($sid, $settings, $this->get_api_user_id());

			$this->set_response([
				'status' => 'success',
				'result' => $this->_presentation_result($sid, $idno),
			], REST_Controller::HTTP_OK);
		} catch (ValidationException $e) {
			$this->set_response([
				'status' => 'failed',
				'message' => $e->getMessage(),
				'errors' => $e->GetValidationErrors(),
			], REST_Controller::HTTP_BAD_REQUEST);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * DELETE .../data/{idno}/presentation — remove the default view settings.
	 */
	public function data_presentation_delete($idno = null)
	{
		try {
			$idno = $idno !== null ? rawurldecode((string) $idno) : '';
			if ($idno === '') {
				throw new Exception('Dataset idno is required');
			}
			$sid = (int) $this->get_sid_from_idno($idno);
			$this->has_dataset_access('edit', $sid);
			$this->Study_presentation_model->delete($sid);

			$this->set_response([
				'status' => 'success',
				'result' => $this->_presentation_result($sid, $idno),
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../presentation/batch — body {"items": [{"idno": "…", "data_presentation": {...} | null}, …]}
	 *
	 * Replaces the default view settings of several indicators. All items are checked first (indicator
	 * exists, edit access, schema); if any fails nothing is saved and the errors are listed per item.
	 */
	public function presentation_batch_post()
	{
		try {
			$input = $this->_optional_json_body();
			$items = isset($input['items']) && is_array($input['items']) ? array_values($input['items']) : null;
			if ($items === null || empty($items)) {
				throw new Exception('items must be a non-empty array');
			}
			if (count($items) > self::PRESENTATION_BATCH_MAX) {
				throw new Exception('Too many items; the maximum per request is ' . self::PRESENTATION_BATCH_MAX);
			}

			$planned = [];
			$errors = [];
			foreach ($items as $i => $item) {
				$idno = is_array($item) && isset($item['idno']) ? trim((string) $item['idno']) : '';
				try {
					if ($idno === '') {
						throw new Exception('idno is required');
					}
					$sid = (int) $this->get_sid_from_idno($idno);
					$this->has_dataset_access('edit', $sid);
					$settings = $this->_presentation_from_input(array_key_exists('data_presentation', $item) ? $item['data_presentation'] : null);
					if (!empty($settings)) {
						$this->Study_presentation_model->validate($settings);
					}
					$planned[] = ['sid' => $sid, 'settings' => $settings];
				} catch (ValidationException $e) {
					$errors[] = ['index' => $i, 'idno' => $idno, 'message' => $e->getMessage(), 'errors' => $e->GetValidationErrors()];
				} catch (Exception $e) {
					$errors[] = ['index' => $i, 'idno' => $idno, 'message' => $e->getMessage()];
				}
			}

			if (!empty($errors)) {
				$this->set_response([
					'status' => 'failed',
					'message' => 'No changes saved: ' . count($errors) . ' of ' . count($items) . ' items are invalid',
					'errors' => $errors,
				], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}

			$user_id = $this->get_api_user_id();
			$saved = 0;
			$removed = 0;
			$this->db->trans_start();
			foreach ($planned as $p) {
				$this->Study_presentation_model->replace($p['sid'], $p['settings'], $user_id);
				if (empty($p['settings'])) {
					$removed++;
				} else {
					$saved++;
				}
			}
			$this->db->trans_complete();
			if ($this->db->trans_status() === false) {
				throw new Exception('Saving the default view settings failed; no changes were saved');
			}

			$this->set_response([
				'status' => 'success',
				'result' => ['saved' => $saved, 'removed' => $removed],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data/{idno}/value-counts-summary
	 */
	public function data_value_counts_summary_get($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('view', (int) $ctx['sid']);
			$summary = $this->Timeseries_value_count_model->get_summary((int) $ctx['sid'], (int) $ctx['dsd_id']);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid' => (int) $ctx['sid'],
					'dsd_id' => (int) $ctx['dsd_id'],
					'summary' => $summary,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data/{idno}/sync-counts
	 */
	public function data_sync_counts_post($idno = null)
	{
		try {
			$ctx = $this->_context_from_idno($idno);
			$this->has_dataset_access('edit', (int) $ctx['sid']);
			set_time_limit(0);
			$inserted = $this->_sync_value_counts_for_context($ctx);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'sid' => (int) $ctx['sid'],
					'dsd_id' => (int) $ctx['dsd_id'],
					'inserted' => (int) $inserted,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// ---------------------------------------------------------------------
	// DSD-scoped: /api/admin/timeseries/data-structures/{id}/...
	// ---------------------------------------------------------------------

	/**
	 * POST .../data-structures/{id}/indexes
	 */
	public function structure_indexes_post($dsd_id = null)
	{
		try {
			$this->require_access('data_structure', 'edit');
			$dsd_id = (int) $dsd_id;
			if ($dsd_id <= 0) {
				throw new Exception('Invalid data structure id');
			}
			$bundle = $this->Timeseries_dsd_model->load_dsd_bundle($dsd_id);
			if ($bundle === null) {
				throw new Exception('Data structure not found');
			}
			$input = $this->_optional_json_body();
			$include_unique = true;
			if (array_key_exists('include_unique_key_hash', $input)) {
				$include_unique = !empty($input['include_unique_key_hash']);
			}
			set_time_limit(0);
			$this->Timeseries_mongo_model->ensure_indexes($dsd_id, $include_unique, (array) $bundle['components']);
			$this->set_response([
				'status' => 'success',
				'result' => ['data_structure_id' => $dsd_id, 'indexes' => 'ok'],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST .../data-structures/{id}/rehash
	 */
	public function structure_rehash_post($dsd_id = null)
	{
		try {
			$dsd_id = (int) $dsd_id;
			if ($dsd_id <= 0) {
				throw new Exception('Invalid data structure id');
			}
			if ($this->Timeseries_dsd_model->load_dsd_bundle($dsd_id) === null) {
				throw new Exception('Data structure not found');
			}
			$input  = $this->_optional_json_body();
			$filter = [];
			if (!empty($input['sid'])) {
				$filter['sid'] = (int) $input['sid'];
			}
			if (!empty($filter['sid'])) {
				$sidF = (int) $filter['sid'];
				$this->has_dataset_access('edit', $sidF);
				$rowS = $this->db->select('data_structure_id')->get_where('surveys', ['id' => $sidF])->row_array();
				if (!$rowS || empty($rowS['data_structure_id']) || (int) $rowS['data_structure_id'] !== $dsd_id) {
					throw new Exception('Study is not linked to this data structure');
				}
			} else {
				$this->require_access('data_structure', 'edit');
			}
			$limit = null;
			if (isset($input['limit']) && $input['limit'] !== '') {
				$limit = (int) $input['limit'];
			}
			$updated = $this->Timeseries_mongo_model->rehash_documents($dsd_id, $filter, $limit > 0 ? $limit : null);
			$fullRun = ($limit === null || $limit <= 0);
			if ($fullRun) {
				if (!empty($filter['sid'])) {
					$sidF = (int) $filter['sid'];
					$rowS = $this->db->select('data_structure_id')->get_where('surveys', ['id' => $sidF])->row_array();
					if ($rowS && !empty($rowS['data_structure_id']) && (int) $rowS['data_structure_id'] === $dsd_id) {
						$this->Dataset_model->clear_indicator_ts_sync_for_survey($sidF, $dsd_id);
					}
				} else {
					$this->Dataset_model->clear_indicator_ts_sync_for_all_surveys_on_dsd($dsd_id);
				}
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['updated' => $updated],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data-structures/{id}/duplicates
	 */
	public function structure_duplicates_get($dsd_id = null)
	{
		try {
			$dsd_id = (int) $dsd_id;
			if ($dsd_id <= 0) {
				throw new Exception('Invalid data structure id');
			}
			if ($this->Timeseries_dsd_model->load_dsd_bundle($dsd_id) === null) {
				throw new Exception('Data structure not found');
			}
			$filter = [];
			$sid = $this->input->get('sid');
			if ($sid !== null && $sid !== '' && is_numeric($sid)) {
				$sidInt = (int) $sid;
				$this->has_dataset_access('view', $sidInt);
				$rowS = $this->db->select('data_structure_id')->get_where('surveys', ['id' => $sidInt])->row_array();
				if (!$rowS || empty($rowS['data_structure_id']) || (int) $rowS['data_structure_id'] !== $dsd_id) {
					throw new Exception('Study is not linked to this data structure');
				}
				$filter['sid'] = $sidInt;
			} else {
				$this->require_access('data_structure', 'view');
			}
			$dups = $this->Timeseries_mongo_model->find_duplicate_key_hashes($dsd_id, $filter);
			$this->set_response([
				'status' => 'success',
				'result' => ['duplicates' => $dups],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET .../data-structures/{id}/stats
	 */
	public function structure_stats_get($dsd_id = null)
	{
		try {
			$this->require_access('data_structure', 'view');
			$dsd_id = (int) $dsd_id;
			if ($dsd_id <= 0) {
				throw new Exception('Invalid data structure id');
			}
			if ($this->Timeseries_dsd_model->load_dsd_bundle($dsd_id) === null) {
				throw new Exception('Data structure not found');
			}
			$coll = $this->Timeseries_mongo_model->get_collection($dsd_id);
			$count = $coll->countDocuments([]);
			$idx = [];
			foreach ($coll->listIndexes() as $info) {
				$idx[] = $info->getName();
			}
			$this->set_response([
				'status' => 'success',
				'result' => [
					'data_structure_id' => $dsd_id,
					'collection'        => $this->Timeseries_mongo_model->get_collection_name($dsd_id),
					'document_count'    => $count,
					'index_names'       => $idx,
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// ---------------------------------------------------------------------
	// Internals
	// ---------------------------------------------------------------------

	/**
	 * JSON body optional (empty stream => []).
	 *
	 * @return array
	 */
	/**
	 * API result for one study's default view settings.
	 */
	private function _presentation_result($sid, $idno)
	{
		$row = $this->Study_presentation_model->get_row((int) $sid);
		return [
			'sid' => (int) $sid,
			'idno' => (string) $idno,
			'data_presentation' => $row && !empty($row['settings']) ? $row['settings'] : null,
			'changed' => $row && $row['changed'] !== null ? (int) $row['changed'] : null,
			'changed_by' => $row && !empty($row['changed_by_name']) ? (string) $row['changed_by_name'] : null,
		];
	}

	/**
	 * Settings array from a request value: an object, or null/empty to remove the settings.
	 */
	private function _presentation_from_input($value)
	{
		if ($value === null) {
			return [];
		}
		if (!is_array($value)) {
			throw new Exception('data_presentation must be an object or null');
		}
		return $value;
	}

	private function _optional_json_body()
	{
		$raw = trim((string) $this->input->raw_input_stream);
		if ($raw === '') {
			return [];
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			throw new Exception('Invalid JSON body');
		}
		return $decoded;
	}

	/**
	 * Resolve either numeric DSD id or string idno from request payload.
	 * Supports detach via { detach: true } or { data_structure_reference: null }.
	 *
	 * @param array $input
	 * @return array{id:int,idno:string}|null
	 */
	private function _resolve_structure_link_input(array $input)
	{
		$this->load->model('Data_structure_model');

		if (!empty($input['detach'])) {
			return null;
		}

		$has_id = array_key_exists('data_structure_id', $input);
		$has_ref = array_key_exists('data_structure_reference', $input);
		if ($has_ref && ($input['data_structure_reference'] === null || trim((string) $input['data_structure_reference']) === '')) {
			return null;
		}

		if (!$has_id && !$has_ref) {
			throw new Exception('Provide one of: data_structure_id, data_structure_reference, or detach=true.');
		}

		$row_by_id = null;
		$row_by_ref = null;

		if ($has_id) {
			$raw_id = $input['data_structure_id'];
			if ($raw_id === null || $raw_id === '') {
				throw new Exception('data_structure_id cannot be empty.');
			}
			if (!is_numeric($raw_id)) {
				throw new Exception('data_structure_id must be numeric.');
			}
			$did = (int) $raw_id;
			if ($did <= 0) {
				throw new Exception('data_structure_id must be greater than zero.');
			}
			$row_by_id = $this->Data_structure_model->get_structure_by_id($did, false);
			if (!$row_by_id) {
				throw new Exception('Data structure not found for data_structure_id: ' . $did);
			}
		}

		if ($has_ref) {
			$raw_ref = $input['data_structure_reference'];
			if ($raw_ref === null || trim((string) $raw_ref) === '') {
				throw new Exception('data_structure_reference cannot be empty.');
			}
			$ref = trim((string) $raw_ref);
			$row_by_ref = $this->Data_structure_model->get_structure_by_idno($ref);
			if (!$row_by_ref) {
				throw new Exception("Data structure not found for data_structure_reference '{$ref}'.");
			}
		}

		if ($row_by_id && $row_by_ref && (int) $row_by_id['id'] !== (int) $row_by_ref['id']) {
			throw new Exception('data_structure_id and data_structure_reference refer to different data structures.');
		}

		$row = $row_by_id ?: $row_by_ref;
		return [
			'id'      => (int)    $row['id'],
			'idno'    => (string) $row['idno'],
			'agency'  => (string) ($row['agency']  ?? ''),
			'name'    => (string) ($row['name']    ?? ''),
			'version' => (string) ($row['version'] ?? ''),
		];
	}

	/**
	 * Persist study→DSD link on {@see surveys} (same as attach-dsd).
	 * When the linked structure id changes or the study is detached, indicator Mongo rows,
	 * value-count cache, and the canonical import CSV file are removed for a consistent state.
	 *
	 * @param int $sid surveys.id
	 * @param array{id:int,idno:string}|null $link null detaches
	 * @return array<string,mixed> applied column values (metadata encoded)
	 */
	private function _apply_structure_link_to_survey($sid, $link)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			throw new Exception('Invalid survey id');
		}
		$row = $this->Dataset_model->get_row_detailed($sid);
		if (!$row) {
			throw new Exception('Study not found');
		}
		$oldDsdId = isset($row['data_structure_id']) && $row['data_structure_id'] !== null && $row['data_structure_id'] !== ''
			? (int) $row['data_structure_id'] : 0;
		$newDsdId = $link ? (int) $link['id'] : 0;

		if ($oldDsdId !== $newDsdId) {
			$this->_purge_indicator_timeseries_on_structure_unlink_or_switch($sid, $oldDsdId, $newDsdId, $link);
		}

		$metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : [];
		if ($link === null) {
			$metadata['data_structure_reference'] = null;
		} else {
			$metadata['data_structure_reference'] = [
				'idno'    => (string) ($link['idno']    ?? ''),
				'agency'  => (string) ($link['agency']  ?? ''),
				'name'    => (string) ($link['name']    ?? ''),
				'version' => (string) ($link['version'] ?? ''),
			];
		}

		$update = [
			'metadata' => $this->Dataset_model->encode_metadata($metadata),
			'data_structure_id' => $link ? (int) $link['id'] : null,
			'changed' => date('U'),
		];
		if ($link === null) {
			$update['ts_dimensions'] = null;
			$update['ts_frequency']  = null;
			$update['ts_sync_required'] = 0;
		} else {
			$update['ts_dimensions'] = $this->Dataset_model->build_ts_dimensions_csv_for_structure_id((int) $link['id']);
			$update['ts_frequency']  = $this->Dataset_model->build_ts_frequency_for_structure_id((int) $link['id']);
			$update['ts_sync_required'] = 1;
		}

		$this->db->where('id', $sid);
		$ok = $this->db->update('surveys', $update);
		if ($ok === false) {
			$error = $this->db->error();
			$msg = is_array($error) ? implode(', ', $error) : 'Database update failed';
			throw new Exception($msg);
		}

		$this->events->emit('db.after.update', 'surveys', $sid, 'refresh');
		return $update;
	}

	/**
	 * Remove Mongo observations, value-count rows, and on-disk import CSV when the study
	 * switches or drops its linked DSD.
	 *
	 * @param int               $sid
	 * @param int               $oldDsdId prior surveys.data_structure_id (0 if none)
	 * @param int               $newDsdId target DSD id when linking (0 when detaching)
	 * @param array|null        $link null when detaching
	 */
	private function _purge_indicator_timeseries_on_structure_unlink_or_switch($sid, $oldDsdId, $newDsdId, $link)
	{
		$sid = (int) $sid;
		$oldDsdId = (int) $oldDsdId;
		$newDsdId = (int) $newDsdId;

		if ($oldDsdId > 0) {
			try {
				$this->Timeseries_mongo_model->delete_observations_for_sid($oldDsdId, $sid);
			} catch (Exception $e) {
				log_message('error', 'Indicator timeseries purge (old DSD ' . $oldDsdId . '): ' . $e->getMessage());
			}
		}

		if ($link !== null && $newDsdId > 0 && $newDsdId !== $oldDsdId) {
			try {
				$this->Timeseries_mongo_model->delete_observations_for_sid($newDsdId, $sid);
			} catch (Exception $e) {
				log_message('error', 'Indicator timeseries purge (new DSD ' . $newDsdId . '): ' . $e->getMessage());
			}
		}

		$this->_clear_value_counts_for_sid($sid);
		$this->_unlink_indicator_timeseries_import_csv_and_legacy($sid);
	}

	/**
	 * Delete canonical `{sid}_indicator_data.csv` and legacy `ts_csv_latest.csv` if present.
	 *
	 * @param int $sid
	 */
	private function _unlink_indicator_timeseries_import_csv_and_legacy($sid)
	{
		$sid = (int) $sid;
		if ($sid <= 0) {
			return;
		}
		$surveyFolder = $this->Catalog_model->get_survey_path_full($sid);
		if ($surveyFolder === null || $surveyFolder === '' || !is_dir($surveyFolder)) {
			return;
		}
		$surveyFolder = unix_path($surveyFolder);
		$fn = $this->Dataset_model->get_indicator_timeseries_import_csv_filename($sid);
		if ($fn !== '') {
			$p = unix_path($surveyFolder . '/' . $fn);
			if (is_file($p)) {
				@unlink($p);
			}
		}
		$ridno = trim((string) $this->config->item('timeseries_csv_resource_idno'));
		if ($ridno === '') {
			$ridno = 'ts_csv_latest';
		}
		$legacy = unix_path($surveyFolder . '/' . $ridno . '.csv');
		if (is_file($legacy)) {
			@unlink($legacy);
		}
		$this->_discard_import_job($sid);
	}

	/**
	 * @return array{sid:int,idno:string,dsd_id:int,structure:array,components:array}
	 */
	private function _context_from_idno($idno)
	{
		$idno = $idno !== null ? rawurldecode((string) $idno) : '';
		if ($idno === '') {
			throw new Exception('Dataset idno is required');
		}
		$sid = $this->get_sid_from_idno($idno);
		$ctx = $this->Timeseries_dsd_model->resolve_dsd_for_sid((int) $sid);
		if ($ctx === null) {
			throw new Exception('No data structure linked for this dataset (set metadata.data_structure_reference to the global DSD idno)');
		}
		$row = $this->Dataset_model->get_row((int) $sid);
		$idno_out = $row && !empty($row['idno']) ? (string) $row['idno'] : (string) $idno;
		return [
			'sid'        => (int) $sid,
			'idno'       => $idno_out,
			'dsd_id'     => (int) $ctx['dsd_id'],
			'structure'  => $ctx['structure'],
			'components' => $ctx['components'],
		];
	}

	private function _query_limit()
	{
		$def = (int) $this->config->item('indicator_timeseries_default_page_size');
		$max = (int) $this->config->item('indicator_timeseries_max_page_size');
		if ($def < 1) {
			$def = 100;
		}
		if ($max < 1) {
			$max = 500;
		}
		$lim = $this->input->get('limit');
		if ($lim === null || $lim === '') {
			return $def;
		}
		$lim = (int) $lim;
		if ($lim < 1) {
			return $def;
		}
		return min($lim, $max);
	}

	private function _bson_list_to_array(array $rows)
	{
		$out = [];
		foreach ($rows as $doc) {
			$out[] = json_decode(json_encode($doc), true);
		}
		return $out;
	}

	private function _is_list(array $a)
	{
		if (function_exists('array_is_list')) {
			return array_is_list($a);
		}
		$i = 0;
		foreach ($a as $k => $_) {
			if ($k !== $i) {
				return false;
			}
			$i++;
		}
		return true;
	}

	/**
	 * Resolve CSV path for POST .../data/import from multipart file or a completed resumable upload.
	 *
	 * @return array{path:string,source:string,upload_id:?string}
	 * @throws Exception
	 */
	private function _resolve_csv_import_file_path()
	{
		$upload_id_raw = $this->input->post('upload_id');
		$upload_id     = is_string($upload_id_raw) ? trim($upload_id_raw) : '';
		$has_file      = !empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']);

		if ($upload_id !== '' && $has_file) {
			throw new Exception('Provide either upload_id or file, not both');
		}

		if ($upload_id !== '') {
			$this->load->library('Resumable_upload', null, 'uploader');

			$metadata = $this->uploader->get_upload_metadata($upload_id);
			if (!$metadata) {
				throw new Exception('INVALID_UPLOAD_ID');
			}
			if ($metadata['status'] !== 'completed') {
				throw new Exception('UPLOAD_NOT_COMPLETED');
			}

			$final_file = $this->uploader->get_final_file_path($upload_id);
			if (!$final_file || !is_file($final_file)) {
				throw new Exception('FILE_NOT_FOUND_FOR_UPLOAD_ID');
			}

			$ext = strtolower(pathinfo($final_file, PATHINFO_EXTENSION));
			if ($ext !== 'csv') {
				throw new Exception('Only CSV files are supported for timeseries import');
			}

			return [
				'path'      => (string) $final_file,
				'source'    => 'upload_id',
				'upload_id' => $upload_id,
			];
		}

		if ($has_file) {
			return [
				'path'      => (string) $_FILES['file']['tmp_name'],
				'source'    => 'file',
				'upload_id' => null,
			];
		}

		throw new Exception('Missing CSV: provide multipart field "file" or completed resumable upload_id');
	}

	private function _exception_chain_matches_duplicate(Exception $e)
	{
		$ex = $e;
		while ($ex instanceof Exception) {
			if ($this->_is_duplicate_key_message($ex->getMessage())) {
				return true;
			}
			$prev = $ex->getPrevious();
			$ex = ($prev instanceof Exception) ? $prev : null;
		}
		return false;
	}

	private function _is_duplicate_key_message($msg)
	{
		$m = strtolower((string) $msg);
		return strpos($m, 'e11000') !== false
			|| strpos($m, 'duplicate key') !== false
			|| (strpos($m, 'duplicate') !== false && strpos($m, 'index') !== false);
	}

	private function _error_response(Exception $e)
	{
		$msg = $e->getMessage();
		$code = REST_Controller::HTTP_BAD_REQUEST;
		if ($msg === 'IDNO-NOT-FOUND' || strpos($msg, 'not found') !== false) {
			$code = REST_Controller::HTTP_NOT_FOUND;
		}
		if ($msg === 'ACCESS-DENIED'
			|| stripos($msg, 'Access denied for resource') !== false) {
			$code = REST_Controller::HTTP_FORBIDDEN;
		}
		$this->set_response([
			'status'  => 'error',
			'message' => $msg === 'INVALID_JSON_INPUT' ? 'Invalid JSON body' : $msg,
		], $code);
	}

	/**
	 * Copy CSV into the study catalogue folder and upsert {@see resources} (fixed resource_idno).
	 *
	 * @param array{sid:int,dsd_id:int,...} $ctx
	 * @param string $tmpPath uploaded temp path
	 * @return array{resource_id:int,resource_idno:string,filename:string}|null
	 */
	private function _upsert_timeseries_csv_resource(array $ctx, $tmpPath)
	{
		$this->load->helper('file');
		$sid = (int) $ctx['sid'];
		$tmpPath = (string) $tmpPath;
		if ($sid <= 0 || $tmpPath === '' || !is_file($tmpPath)) {
			log_message('error', 'Timeseries CSV resource: invalid tmp path or sid');
			return null;
		}

		$ridno = trim((string) $this->config->item('timeseries_csv_resource_idno'));
		if ($ridno === '') {
			$ridno = 'ts_csv_latest';
		}
		if (!$this->Survey_resource_model->validate_idno_format($ridno)) {
			log_message('error', 'Timeseries CSV resource: invalid timeseries_csv_resource_idno config: ' . $ridno);
			return null;
		}

		$dctypeCode = trim((string) $this->config->item('timeseries_csv_resource_dctype'));
		if ($dctypeCode === '' || !in_array($dctypeCode, ['dat', 'dat/micro'], true)) {
			$dctypeCode = 'dat/micro';
		}
		$dctypeLabel = $this->Survey_resource_model->get_dctype_label_by_code($dctypeCode);

		$surveyFolder = $this->Catalog_model->get_survey_path_full($sid);
		if ($surveyFolder === null || $surveyFolder === '' || !is_dir($surveyFolder)) {
			log_message('error', 'Timeseries CSV resource: survey folder not found for sid ' . $sid);
			return null;
		}
		$surveyFolder = unix_path($surveyFolder);
		$basename     = $this->Dataset_model->get_indicator_timeseries_import_csv_filename($sid);
		if ($basename === '') {
			log_message('error', 'Timeseries CSV resource: invalid sid for filename');
			return null;
		}
		$destFull     = unix_path($surveyFolder . '/' . $basename);
		$alreadyInPlace = realpath($tmpPath) !== false && realpath($tmpPath) === realpath($destFull);
		if (!$alreadyInPlace && !@copy($tmpPath, $destFull)) {
			log_message('error', 'Timeseries CSV resource: copy to survey folder failed');
			return null;
		}

		$title = 'Indicator timeseries data (CSV)';
		$meta  = $this->Survey_resource_model->get_file_metadata($sid, $basename);

		$options = [
			'survey_id'      => $sid,
			'title'          => $title,
			'dctype'         => $dctypeLabel,
			'filename'       => $basename,
			'resource_idno'  => $ridno,
		];
		if (is_array($meta)) {
			if (!empty($meta['filesize'])) {
				$options['filesize'] = (int) $meta['filesize'];
			}
			if (!empty($meta['mime_type'])) {
				$options['dcformat'] = (string) $meta['mime_type'];
			}
		}

		$userId = $this->session->userdata('user_id');
		if ($userId) {
			$options['changed_by'] = (int) $userId;
		}

		$existing = $this->Survey_resource_model->get_resource_by_idno($sid, $ridno);
		if ($existing) {
			$resourceId = (int) $existing['resource_id'];
			$options['resource_id'] = $resourceId;
			$this->Survey_resource_model->validate_resource($options, false);
			$this->Survey_resource_model->update($resourceId, $options);
			return [
				'resource_id'   => $resourceId,
				'resource_idno' => $ridno,
				'filename'      => $basename,
			];
		}

		if ($userId) {
			$options['created_by'] = (int) $userId;
		}
		$this->Survey_resource_model->validate_resource($options, true);
		$newId = (int) $this->Survey_resource_model->insert($options);
		if ($newId <= 0) {
			log_message('error', 'Timeseries CSV resource: insert failed');
			return null;
		}
		return [
			'resource_id'   => $newId,
			'resource_idno' => $ridno,
			'filename'      => $basename,
		];
	}

	/**
	 * Rebuild app-db value counts for one study + linked DSD from Mongo observations.
	 *
	 * @param array{sid:int,dsd_id:int,components:array} $ctx
	 * @return int inserted rows
	 */
	private function _sync_value_counts_for_context(array $ctx)
	{
		$rows = $this->Timeseries_mongo_model->build_value_counts_for_sid(
			(int) $ctx['dsd_id'],
			(int) $ctx['sid'],
			(array) $ctx['components']
		);
		return $this->Timeseries_value_count_model->replace_counts_for_sid(
			(int) $ctx['sid'],
			$rows,
			(int) $ctx['dsd_id']
		);
	}

	/**
	 * Count an imported row whose time period could not be standardized (it cannot be found by
	 * from/to or time-period filters); keep up to 10 distinct example values.
	 *
	 * @param array $tracker ['count' => int, 'examples' => string[]]
	 * @param array $doc     from build_observation_document
	 * @param array $components
	 */
	private function _track_unparsed_time_period(array &$tracker, array $doc, array $components)
	{
		if ($doc['_ts_period_start'] !== null) {
			return;
		}
		$name = $this->Timeseries_mongo_model->get_component_name_for_column_type($components, 'time_period');
		if ($name === null) {
			return;
		}
		$tracker['count']++;
		$raw = isset($doc[$name]) ? (string) $doc[$name] : '';
		if (count($tracker['examples']) < 10 && !in_array($raw, $tracker['examples'], true)) {
			$tracker['examples'][] = $raw;
		}
	}

	/**
	 * @param array $tracker from _track_unparsed_time_period
	 * @param array $ctx
	 */
	private function _log_unparsed_time_periods(array $tracker, array $ctx)
	{
		if ($tracker['count'] > 0) {
			log_message('error', "Timeseries import {$ctx['idno']}: {$tracker['count']} rows have an unrecognized time period, e.g. "
				. implode(', ', array_map(function ($v) { return "'" . $v . "'"; }, $tracker['examples'])));
		}
	}

	/**
	 * Delete all cached value counts for one study.
	 *
	 * @param int $sid
	 */
	private function _clear_value_counts_for_sid($sid)
	{
		$this->Timeseries_value_count_model->replace_counts_for_sid((int) $sid, []);
	}

	// -------------------------------------------------------------------------
	// CSV import helpers (sync + chunked)
	// -------------------------------------------------------------------------

	/**
	 * Shared multipart params for POST .../data/import and .../data/upload. Applies `dsd_idno` (study→DSD
	 * link) before resolving the context.
	 *
	 * @return array{ctx:array,delimiter:string,mapping:array,ensure_unique_index:bool}
	 */
	private function _import_request_params()
	{
		$postIdno = $this->input->post('idno');
		$idnoIn   = ($postIdno !== null && trim((string) $postIdno) !== '') ? trim((string) $postIdno) : '';
		if ($idnoIn === '') {
			throw new Exception('Dataset idno is required (multipart field "idno")');
		}
		$sid = (int) $this->get_sid_from_idno($idnoIn);
		$this->has_dataset_access('edit', $sid);

		$dsdIdno = $this->input->post('dsd_idno');
		$dsdIdno = ($dsdIdno !== null && trim((string) $dsdIdno) !== '') ? trim((string) $dsdIdno) : '';
		if ($dsdIdno !== '') {
			$this->load->model('Data_structure_model');
			$dsdRow = $this->Data_structure_model->get_structure_by_idno($dsdIdno);
			if (!$dsdRow) {
				throw new Exception("Data structure not found for dsd_idno '{$dsdIdno}'.");
			}
			$this->_apply_structure_link_to_survey($sid, [
				'id'   => (int) $dsdRow['id'],
				'idno' => (string) $dsdRow['idno'],
			]);
		}

		$delimiter = $this->input->post('delimiter');
		$delimiter = ($delimiter !== null && $delimiter !== '') ? (string) $delimiter : ',';
		if ($delimiter === '\\t') {
			$delimiter = "\t";
		}

		$mapping      = [];
		$mapping_json = $this->input->post('mapping');
		if ($mapping_json !== null && $mapping_json !== '') {
			$mapping = json_decode((string) $mapping_json, true);
			if (!is_array($mapping)) {
				throw new Exception('Invalid JSON in "mapping" form field');
			}
		}

		return [
			'ctx'                 => $this->_context_from_idno($idnoIn),
			'delimiter'           => $delimiter,
			'mapping'             => $mapping,
			'ensure_unique_index' => $this->input->post('ensure_unique_index') !== '0',
		];
	}

	/**
	 * Staged CSV for a chunked import: `{sid}_indicator_data.pending.csv` in the study folder.
	 * Renamed to the canonical `{sid}_indicator_data.csv` when the import completes.
	 *
	 * @return string|null null when the study folder does not exist
	 */
	private function _staged_import_csv_path($sid)
	{
		$folder = $this->Catalog_model->get_survey_path_full((int) $sid);
		if ($folder === null || $folder === '' || !is_dir($folder)) {
			return null;
		}
		return unix_path(unix_path($folder) . '/' . (int) $sid . '_indicator_data.pending.csv');
	}

	/**
	 * Reject when a chunked import is currently being processed for the study (lock held).
	 */
	private function _assert_no_running_import_job($sid)
	{
		$job = $this->Timeseries_csv_import_model->get_job((int) $sid);
		if ($job
			&& in_array($job['status'], Timeseries_csv_import_model::ACTIVE_STATUSES, true)
			&& (int) $job['lock_until'] >= time()) {
			throw new Exception('An import is currently running for this study; wait for it to finish');
		}
	}

	/**
	 * Remove the study's chunked-import job and staged file.
	 */
	private function _discard_import_job($sid)
	{
		$this->Timeseries_csv_import_model->delete_job((int) $sid);
		$staged = $this->_staged_import_csv_path((int) $sid);
		if ($staged !== null && is_file($staged)) {
			@unlink($staged);
		}
	}

	/**
	 * After all rows are inserted: value counts, study counters, sync flag, canonical CSV + resource row.
	 *
	 * Value counts come from the tally built while streaming rows; they are recomputed in MongoDB only
	 * when the tally overflowed or the stored row count does not match what this import inserted.
	 *
	 * @param array  $ctx
	 * @param array  $state    inserted, unparsed, tally, tally_overflow
	 * @param string $csvPath  the imported file
	 * @param bool   $isStaged true: move $csvPath into place; false: copy it (temp upload)
	 * @return array|null resource meta from {@see _upsert_timeseries_csv_resource()}
	 */
	private function _finalize_import(array $ctx, array $state, $csvPath, $isStaged)
	{
		$sid   = (int) $ctx['sid'];
		$dsdId = (int) $ctx['dsd_id'];

		$this->_log_unparsed_time_periods($state['unparsed'], $ctx);

		$stored = $this->Timeseries_mongo_model->count_observations($dsdId, [
			'$or' => [['sid' => $sid], ['sid' => (string) $sid]],
		]);
		if (empty($state['tally_overflow']) && $stored === (int) $state['inserted']) {
			$this->Timeseries_value_count_model->replace_counts_for_sid(
				$sid,
				$this->Timeseries_mongo_model->value_count_rows_from_tally($dsdId, (array) $state['tally']),
				$dsdId
			);
		} else {
			if ($stored !== (int) $state['inserted']) {
				log_message('error', "Timeseries import {$ctx['idno']}: inserted {$state['inserted']} rows but {$stored} are stored; recounting values from MongoDB");
			}
			$this->_sync_value_counts_for_context($ctx);
		}

		$this->Timeseries_mongo_model->refresh_ts_data_count_for_sid($sid);
		$this->Dataset_model->clear_indicator_ts_sync_for_survey($sid, $dsdId);

		$resourcePath = $csvPath;
		if ($isStaged) {
			$folder    = $this->Catalog_model->get_survey_path_full($sid);
			$canonical = unix_path(unix_path($folder) . '/' . $this->Dataset_model->get_indicator_timeseries_import_csv_filename($sid));
			if (is_file($canonical)) {
				@unlink($canonical);
			}
			if (@rename($csvPath, $canonical)) {
				$resourcePath = $canonical;
			} else {
				log_message('error', "Timeseries import {$ctx['idno']}: could not move staged CSV into place");
			}
		}

		$resourceMeta = null;
		if ($resourcePath !== '' && is_file($resourcePath)) {
			try {
				$resourceMeta = $this->_upsert_timeseries_csv_resource($ctx, $resourcePath);
			} catch (Exception $persistEx) {
				log_message('error', 'Timeseries CSV resource upsert failed: ' . $persistEx->getMessage());
			}
		}
		if ($isStaged && $resourcePath !== $csvPath && is_file($csvPath)) {
			@unlink($csvPath);
		}
		return $resourceMeta;
	}
}
