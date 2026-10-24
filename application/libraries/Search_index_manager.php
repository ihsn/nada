<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search index change tracking.
 *
 * Tracks one index: the index engine (search_index_engine), whichever engine serves the searches. No rows are written
 * while it is none. Solr/OpenSearch process queue rows inline; nada-ai pulls them through the search-index API.
 *
 * search_index_meta (one row) says which engine and which nada-ai index the rows in search_index_state and
 * search_index_queue describe. The tables are cleared when that stops being true:
 *   - on the first catalog change after the index engine changed (so switching away and back with no change in
 *     between keeps them), or on nada-ai's first call once the index engine is nada_ai;
 *   - when nada-ai reports a new generation of its study index (the index was recreated);
 *   - when the NADA-AI URL changes (another nada-ai, another index), like an engine change.
 * nada-ai's calls (queue, ack, state reports, diffs) are accepted only while the rows describe nada-ai's index.
 */
class Search_index_manager
{
	const OBJECT_SURVEY   = 'survey';
	const OBJECT_CITATION = 'citation';

	const ENGINE_NADA_AI = 'nada_ai';

	const CLASS_FULL     = 'upsert_full';
	const CLASS_PARTIAL  = 'upsert_partial';
	const CLASS_VARIABLES = 'variables';
	const CLASS_DELETE   = 'delete';

	const STATUS_PENDING  = 'pending';
	const STATUS_FAILED   = 'failed';
	const STATUS_INDEXED  = 'indexed';
	/**
	 * Not a stored state: a delete ack (and a bulk 'deleted') removes the
	 * search_index_state row, since nothing is left to track once an object is
	 * gone from both the catalog and the index. Used as the ack/bulk result label only.
	 */
	const STATUS_DELETED  = 'deleted';

	/** search_index_queue.priority: catalog changes before the rows a sync queued. */
	const PRIORITY_LIVE = 0;
	const PRIORITY_SYNC = 1;

	/** Most rows one batch ack may carry. */
	const ACK_BATCH_MAX = 100;

	/** @var CI_Controller */
	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('search_index');
	}

	/** Whether any object type is tracked: the queue is readable when either studies or citations are. */
	public function tracking_enabled()
	{
		return $this->tracking_enabled_for(self::OBJECT_SURVEY) || $this->tracking_enabled_for(self::OBJECT_CITATION);
	}

	/** Whether changes to one object type are queued: while an index engine is set (Search_engine_resolver::tracked()). */
	public function tracking_enabled_for($object_type)
	{
		$this->ci->load->library('search_engine_resolver');
		return $this->ci->search_engine_resolver->tracked($object_type === self::OBJECT_CITATION ? 'citation' : 'survey');
	}

	/** The engine that serves searches: database | solr | opensearch | nada_ai (see Search_engine_resolver). */
	public function current_engine()
	{
		$this->ci->load->library('search_engine_resolver');
		return $this->ci->search_engine_resolver->engine();
	}

	/** The engine whose index is tracked: none | solr | opensearch | nada_ai (see Search_engine_resolver). */
	public function index_engine()
	{
		$this->ci->load->library('search_engine_resolver');
		return $this->ci->search_engine_resolver->index_engine();
	}

	/**
	 * @param string     $table     surveys | citations
	 * @param int|string $object_id
	 * @param string     $action    Events action
	 * @param bool       $is_delete
	 */
	public function handle_event($table, $object_id, $action = 'atomic', $is_delete = false)
	{
		$object_type = $this->normalize_object_type($table);
		if ($object_type === null) {
			return;
		}

		if (!$this->tables_ready()) {
			return;
		}
		// a change the rows of another engine would not record: they no longer describe that engine's index
		$this->follow_index_engine();
		if (!$this->tracking_enabled_for($object_type)) {
			return;
		}

		$object_id = (int) $object_id;
		if ($object_id <= 0) {
			return;
		}

		$change_class = $is_delete ? self::CLASS_DELETE : $this->map_action($action);
		$object_key   = $this->resolve_object_key($object_type, $object_id);
		$row          = $this->enqueue($object_type, $object_id, $object_key, $change_class);
		if ($row) {
			$this->process_inline($row);
		}
	}

	/**
	 * @return array queue row
	 */
	public function enqueue($object_type, $object_id, $object_key, $change_class)
	{
		$now = time();
		$existing = $this->get_queue_row($object_type, $object_id);

		if ($existing) {
			$keep_class = $this->coalesce_class($existing['change_class'], $change_class);
			$key        = ($object_key !== '' && $object_key !== null)
				? $object_key
				: $existing['object_key'];
			$this->ci->db->where('id', (int) $existing['id']);
			$this->ci->db->update('search_index_queue', array(
				'object_key'   => $key,
				'change_class' => $keep_class,
				'status'       => self::STATUS_PENDING,
				'attempts'     => 0,
				'last_error'   => null,
				'changed'      => $now,
				'priority'     => self::PRIORITY_LIVE,
			));
			$row = $this->get_queue_by_id((int) $existing['id']);
		} else {
			$this->ci->db->insert('search_index_queue', array(
				'object_type'  => $object_type,
				'object_id'    => (int) $object_id,
				'object_key'   => $object_key,
				'change_class' => $change_class,
				'status'       => self::STATUS_PENDING,
				'attempts'     => 0,
				'changed'      => $now,
				'priority'     => self::PRIORITY_LIVE,
			));
			$insert_id = (int) $this->ci->db->insert_id();
			$row = $insert_id > 0
				? $this->get_queue_by_id($insert_id)
				: $this->get_queue_row($object_type, $object_id);
		}

		$this->upsert_state($object_type, (int) $object_id, $row['object_key'], self::STATUS_PENDING, $now);
		return $row;
	}

	/**
	 * One page of queue rows for nada-ai, catalog changes first (priority), then oldest first. $exclude_ids are rows the
	 * caller already holds in this pass (it could not ack them), so the page reaches past them.
	 *
	 * @param int[] $exclude_ids
	 * @return array{items: array, total: int, limit: int, tracking_enabled: bool, reason?: string}
	 */
	public function list_queue($status = self::STATUS_PENDING, $limit = 50, $object_type = null, array $exclude_ids = array())
	{
		$defaults = (int) $this->ci->config->item('search_index_queue_default_limit') ?: 50;
		$max      = (int) $this->ci->config->item('search_index_queue_max_limit') ?: 100;
		$limit    = (int) $limit;
		if ($limit <= 0) {
			$limit = $defaults;
		}
		$limit = min($limit, $max);

		try {
			$this->require_nada_ai_rows();
		} catch (Search_index_exception $e) {
			return array(
				'items'            => array(),
				'total'            => 0,
				'limit'            => $limit,
				'tracking_enabled' => false,
				'reason'           => $this->refusal_reason($e->getMessage()),
			);
		}

		if (!in_array($status, array(self::STATUS_PENDING, self::STATUS_FAILED), true)) {
			$status = self::STATUS_PENDING;
		}

		$where  = 'q.status = ?';
		$params = array($status);
		if ($object_type === self::OBJECT_SURVEY || $object_type === self::OBJECT_CITATION) {
			$where   .= ' AND q.object_type = ?';
			$params[] = $object_type;
		}
		$total = (int) $this->ci->db->query("SELECT COUNT(*) AS n FROM search_index_queue q WHERE {$where}", $params)->row_array()['n'];

		$exclude_ids = array_slice(array_values(array_filter(array_map('intval', $exclude_ids), function ($id) {
			return $id > 0;
		})), 0, 1000);
		if (!empty($exclude_ids)) {
			$where .= ' AND q.id NOT IN (' . implode(',', $exclude_ids) . ')';
		}

		// data_type: surveys.type, or the type last seen for a study that is gone (a delete)
		$sql = "
			SELECT q.*, COALESCE(s.type, st.data_type) AS data_type
			FROM search_index_queue q
			LEFT JOIN surveys s ON q.object_type = 'survey' AND s.id = q.object_id
			LEFT JOIN search_index_state st ON st.object_type = q.object_type AND st.object_id = q.object_id
			WHERE {$where}
			ORDER BY q.priority ASC, q.changed ASC, q.id ASC
		";
		$rows = $this->ci->db->query($sql . $this->paginate_clause($limit, 0), $params)->result_array();

		$items = array();
		foreach ($rows as $row) {
			$items[] = $this->format_queue_item($row);
		}

		return array(
			'items'            => $items,
			'total'            => $total,
			'limit'            => $limit,
			'tracking_enabled' => true,
		);
	}

	/** Why nada-ai's call is refused, for the empty queue page. */
	private function refusal_reason($code)
	{
		switch ($code) {
			case 'INDEX_ENGINE_NOT_NADA_AI':
				return 'not_tracking_for_nada_ai';
			case 'INDEX_GENERATION_MISMATCH':
				return 'other_nada_ai';
			default:
				return 'tracking_disabled';
		}
	}

	/**
	 * @return array applied result
	 * @throws Search_index_exception
	 */
	public function ack($id, $result, $changed, $error = null)
	{
		$this->require_nada_ai_rows();

		if ($result !== self::STATUS_INDEXED && $result !== self::STATUS_FAILED) {
			throw new Search_index_exception('INVALID_RESULT', 400);
		}

		$row = $this->get_queue_by_id((int) $id);
		if (!$row) {
			throw new Search_index_exception('QUEUE_NOT_FOUND', 404);
		}
		if ((int) $row['changed'] !== (int) $changed) {
			throw new Search_index_exception('QUEUE_CHANGED', 409);
		}

		return $this->ack_apply($row, $result, $error);
	}

	/**
	 * Ack several queue rows in one call. Each row is applied on its own: one that changed or is gone does not stop the
	 * others.
	 *
	 * @param array $items each: {id, result, changed, error?}
	 * @return array{applied: int, results: array} one entry per item, in order: {id, applied, result?|error?}
	 */
	public function ack_batch(array $items)
	{
		$this->require_nada_ai_rows();
		if (empty($items)) {
			throw new Search_index_exception('ITEMS_REQUIRED', 400);
		}
		if (count($items) > self::ACK_BATCH_MAX) {
			throw new Search_index_exception('TOO_MANY_ITEMS', 400);
		}

		$results = array();
		$applied = 0;
		foreach ($items as $item) {
			$id      = isset($item['id']) ? (int) $item['id'] : 0;
			$result  = isset($item['result']) ? (string) $item['result'] : '';
			$changed = isset($item['changed']) ? (int) $item['changed'] : null;
			$error   = isset($item['error']) ? (string) $item['error'] : null;

			if ($id <= 0) {
				$results[] = array('id' => $id, 'applied' => false, 'error' => 'ID_REQUIRED');
				continue;
			}
			if ($result !== self::STATUS_INDEXED && $result !== self::STATUS_FAILED) {
				$results[] = array('id' => $id, 'applied' => false, 'error' => 'INVALID_RESULT');
				continue;
			}
			if ($changed === null) {
				$results[] = array('id' => $id, 'applied' => false, 'error' => 'CHANGED_REQUIRED');
				continue;
			}
			$row = $this->get_queue_by_id($id);
			if (!$row) {
				$results[] = array('id' => $id, 'applied' => false, 'error' => 'QUEUE_NOT_FOUND');
				continue;
			}
			if ((int) $row['changed'] !== $changed) {
				$results[] = array('id' => $id, 'applied' => false, 'error' => 'QUEUE_CHANGED');
				continue;
			}
			$done = $this->ack_apply($row, $result, $error);
			$results[] = array('id' => $id, 'applied' => true, 'result' => $done['result']);
			$applied++;
		}

		return array('applied' => $applied, 'results' => $results);
	}

	/**
	 * Queue what nada-ai's index needs to match the catalog, as rows of priority PRIORITY_SYNC:
	 *   - delete:          tracked objects that are gone from the catalog (and whose key no live row has);
	 *   - upsert_full:     catalog objects with no 'indexed' row;
	 *   - upsert_partial:  with $refresh_indexed, every 'indexed' object (its published status and filters are rewritten
	 *                      without embedding): after nada-ai reported an existing index (import).
	 * Objects that already have a queue row are left as they are, so running it again while the work is processed does
	 * not queue anything twice (or turn queued partial updates into full ones).
	 *
	 * $data_type narrows studies to one surveys.type (citations have none); $remove_only queues the deletes only.
	 *
	 * @param string $object_type survey | citation | all
	 * @return array{survey?: array, citation?: array} per object type: {delete, upsert_full, upsert_partial}
	 */
	public function sync($object_type = 'all', $refresh_indexed = false, $data_type = null, $remove_only = false)
	{
		$this->require_nada_ai_rows();

		$types = ($object_type === 'all')
			? array(self::OBJECT_SURVEY, self::OBJECT_CITATION)
			: array($this->normalize_object_type($object_type));
		if (in_array(null, $types, true)) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}

		$now    = time();
		$counts = array();
		$this->ci->db->trans_start();
		$data_type = ($data_type !== null && $data_type !== '') ? (string) $data_type : null;
		foreach ($types as $type) {
			if ($data_type !== null && $type !== self::OBJECT_SURVEY) {
				continue;
			}
			$counts[$type] = $this->queue_sync_rows($type, $now, (bool) $refresh_indexed, $data_type, (bool) $remove_only);
		}
		// the objects just queued are no longer known to be indexed
		$this->ci->db->query(
			"UPDATE search_index_state SET status = ?, changed = ?, last_error = NULL
			WHERE EXISTS (SELECT 1 FROM search_index_queue q
				WHERE q.object_type = search_index_state.object_type
				AND q.object_id = search_index_state.object_id
				AND q.priority = ? AND q.changed = ?)",
			array(self::STATUS_PENDING, $now, self::PRIORITY_SYNC, $now)
		);
		$this->ci->db->trans_complete();

		log_message('info', 'Search_index sync queued: ' . json_encode($counts));
		return $counts;
	}

	/** @return array{delete: int, upsert_full: int, upsert_partial: int} */
	private function queue_sync_rows($type, $now, $refresh_indexed, $data_type, $remove_only)
	{
		$catalog = ($type === self::OBJECT_SURVEY) ? 'surveys' : 'citations';
		$key     = ($type === self::OBJECT_SURVEY) ? 'idno' : 'uuid';
		$columns = 'object_type, object_id, object_key, change_class, status, attempts, changed, priority';
		$not_queued = "NOT EXISTS (SELECT 1 FROM search_index_queue q2 WHERE q2.object_type = ? AND q2.object_id = %s)";
		$counts  = array('delete' => 0, 'upsert_full' => 0, 'upsert_partial' => 0);

		// one study type: the catalog's type for live rows, the type last seen for rows that are gone
		$live_type  = $data_type !== null ? ' AND c.type = ?' : '';
		$state_type = $data_type !== null ? ' AND st.data_type = ?' : '';
		$typed = function (array $params) use ($data_type) {
			if ($data_type !== null) {
				$params[] = $data_type;
			}
			return $params;
		};

		$this->ci->db->query(
			"INSERT INTO search_index_queue ({$columns})
			SELECT st.object_type, st.object_id, st.object_key, ?, ?, 0, ?, ?
			FROM search_index_state st
			WHERE st.object_type = ? AND " . $this->stale_condition($type) . "
				AND " . sprintf($not_queued, 'st.object_id') . $state_type,
			$typed(array(self::CLASS_DELETE, self::STATUS_PENDING, $now, self::PRIORITY_SYNC, $type, $type))
		);
		$counts['delete'] = (int) $this->ci->db->affected_rows();

		if ($remove_only) {
			return $counts;
		}

		if ($refresh_indexed) {
			$this->ci->db->query(
				"INSERT INTO search_index_queue ({$columns})
				SELECT ?, c.id, c.{$key}, ?, ?, 0, ?, ?
				FROM {$catalog} c
				INNER JOIN search_index_state st ON st.object_type = ? AND st.object_id = c.id
				WHERE st.status = ? AND " . sprintf($not_queued, 'c.id') . $live_type,
				$typed(array($type, self::CLASS_PARTIAL, self::STATUS_PENDING, $now, self::PRIORITY_SYNC,
					$type, self::STATUS_INDEXED, $type))
			);
			$counts['upsert_partial'] = (int) $this->ci->db->affected_rows();
		}

		$this->ci->db->query(
			"INSERT INTO search_index_queue ({$columns})
			SELECT ?, c.id, c.{$key}, ?, ?, 0, ?, ?
			FROM {$catalog} c
			LEFT JOIN search_index_state st ON st.object_type = ? AND st.object_id = c.id
			WHERE (st.object_id IS NULL OR st.status != ?) AND " . sprintf($not_queued, 'c.id') . $live_type,
			$typed(array($type, self::CLASS_FULL, self::STATUS_PENDING, $now, self::PRIORITY_SYNC,
				$type, self::STATUS_INDEXED, $type))
		);
		$counts['upsert_full'] = (int) $this->ci->db->affected_rows();

		return $counts;
	}

	/**
	 * Start the tracking of nada-ai's index afresh, for an index that was just emptied (Delete index, Delete and
	 * rebuild): the rows are cleared at once instead of when NADA next reads a new generation from /info, and with
	 * $queue everything is queued.
	 *
	 * @return array the queued counts, as sync() returns them (empty without $queue)
	 */
	public function rebuild($queue = true)
	{
		$this->require_nada_ai_rows();
		$this->reset_tracking(self::ENGINE_NADA_AI, null);
		// a cached /info still names the emptied index: its generation must not be taken for the new one's
		$this->ci->load->library('nada_ai_link');
		$this->ci->nada_ai_link->forget_info();
		return $queue ? $this->sync('all') : array();
	}

	/**
	 * What changed lately, for the dashboard: the catalog changes waiting to be indexed (newest first; rows a sync
	 * queued are only counted, not listed, so a long sync does not crowd them out), and the latest results recorded.
	 * Each item carries the study's or citation's title.
	 *
	 * @return array{waiting: array, sync_waiting: int, results: array}
	 */
	public function recent_changes($limit = 10)
	{
		$limit = max(1, min((int) $limit, 50));
		$out = array('waiting' => array(), 'sync_waiting' => 0, 'results' => array());
		if (!$this->tables_ready() || !$this->rows_describe_index_engine()) {
			return $out;
		}

		$title = "COALESCE(s.title, c.title)";
		$joins = "
			LEFT JOIN surveys s ON x.object_type = 'survey' AND s.id = x.object_id
			LEFT JOIN citations c ON x.object_type = 'citation' AND c.id = x.object_id";

		$rows = $this->ci->db->query(
			"SELECT x.object_type, x.object_id, x.object_key, x.change_class, x.status, x.changed, x.last_error,
				{$title} AS title
			FROM search_index_queue x {$joins}
			WHERE x.priority = ?
			ORDER BY x.changed DESC" . $this->paginate_clause($limit, 0),
			array(self::PRIORITY_LIVE)
		)->result_array();
		foreach ($rows as $r) {
			$out['waiting'][] = $this->recent_item($r, $r['status'] === self::STATUS_FAILED ? 'failed' : 'waiting');
		}

		$out['sync_waiting'] = (int) $this->ci->db->from('search_index_queue')
			->where('status', self::STATUS_PENDING)->where('priority', self::PRIORITY_SYNC)->count_all_results();

		$rows = $this->ci->db->query(
			"SELECT x.object_type, x.object_id, x.object_key, NULL AS change_class, x.status, x.changed, x.last_error,
				{$title} AS title
			FROM search_index_state x {$joins}
			WHERE x.status IN (?, ?)
			ORDER BY x.changed DESC" . $this->paginate_clause($limit, 0),
			array(self::STATUS_INDEXED, self::STATUS_FAILED)
		)->result_array();
		foreach ($rows as $r) {
			$out['results'][] = $this->recent_item($r, $r['status']);
		}

		return $out;
	}

	private function recent_item(array $r, $status)
	{
		return array(
			'object_type' => $r['object_type'],
			'object_id'   => (int) $r['object_id'],
			'object_key'  => $r['object_key'],
			'title'       => $r['title'],
			'change'      => $r['change_class'],
			'status'      => $status,
			'at'          => (int) $r['changed'],
			'error'       => $r['last_error'],
		);
	}

	/**
	 * Queue a variables-only update of these studies (their variables are synced again; the study is not
	 * re-indexed). For the variables the index is missing.
	 *
	 * @param int[] $sids
	 * @return int how many were queued
	 */
	public function queue_variables(array $sids)
	{
		if (!$this->tracking_enabled() || !$this->tables_ready()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}
		$this->follow_index_engine();
		$sids = array_values(array_unique(array_filter(array_map('intval', $sids))));
		if (empty($sids)) {
			return 0;
		}
		$rows = $this->ci->db->select('id, idno')->where_in('id', $sids)->get('surveys')->result_array();
		foreach ($rows as $row) {
			$queued = $this->enqueue(self::OBJECT_SURVEY, (int) $row['id'], $row['idno'], self::CLASS_VARIABLES);
			$this->process_inline($queued);
		}
		return count($rows);
	}

	public function requeue_object($object_type, $object_id)
	{
		if (!$this->tracking_enabled()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}
		if (!$this->tables_ready()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}

		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}
		$object_id = (int) $object_id;
		if ($object_id <= 0) {
			throw new Search_index_exception('OBJECT_ID_REQUIRED', 400);
		}

		$this->follow_index_engine();
		$live_key = $this->lookup_live_key($object_type, $object_id);
		if ($live_key !== null) {
			$row = $this->enqueue($object_type, $object_id, $live_key, self::CLASS_FULL);
			$this->process_inline($row);
			return $this->format_queue_item($row);
		}

		$state = $this->get_state($object_type, $object_id);
		$key   = $state ? $state['object_key'] : (string) $object_id;
		$row   = $this->enqueue($object_type, $object_id, $key, self::CLASS_DELETE);
		$this->process_inline($row);
		return $this->format_queue_item($row);
	}

	/**
	 * @return int number reset
	 */
	public function requeue_failed()
	{
		if (!$this->tracking_enabled()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}
		if (!$this->tables_ready()) {
			return 0;
		}
		$this->follow_index_engine();

		// the state of those objects is no longer 'failed' either: they are queued again
		$this->ci->db->query(
			"UPDATE search_index_state SET status = ?, last_error = NULL
			WHERE EXISTS (SELECT 1 FROM search_index_queue q
				WHERE q.object_type = search_index_state.object_type
				AND q.object_id = search_index_state.object_id
				AND q.status = ?)",
			array(self::STATUS_PENDING, self::STATUS_FAILED)
		);

		$this->ci->db->where('status', self::STATUS_FAILED);
		$this->ci->db->update('search_index_queue', array(
			'status'     => self::STATUS_PENDING,
			'attempts'   => 0,
			'last_error' => null,
			'changed'    => time(),
		));
		return (int) $this->ci->db->affected_rows();
	}

	public function status()
	{
		$engine  = $this->current_engine();
		$enabled = $this->tracking_enabled() && $this->tables_ready();
		$meta    = $enabled ? $this->get_meta() : null;
		if ($meta && $meta['engine'] === self::ENGINE_NADA_AI) {
			$meta = $this->check_index_generation($meta);
		}

		$queue = array('pending' => 0, 'failed' => 0, 'sync_pending' => 0, 'oldest_pending_at' => null, 'last_result_at' => null);
		$state = array(
			'indexed' => 0,
			'pending' => 0,
			'failed'  => 0,
		);

		if ($enabled && $this->rows_describe_index_engine()) {
			$queue['pending'] = $this->count_queue(self::STATUS_PENDING);
			$queue['failed']  = $this->count_queue(self::STATUS_FAILED);
			$queue['sync_pending'] = (int) $this->ci->db->from('search_index_queue')
				->where('status', self::STATUS_PENDING)->where('priority', self::PRIORITY_SYNC)->count_all_results();
			$oldest = $this->ci->db->select_min('changed', 'oldest')->where('status', self::STATUS_PENDING)
				->get('search_index_queue')->row_array();
			$queue['oldest_pending_at'] = ($oldest && $oldest['oldest'] !== null) ? (int) $oldest['oldest'] : null;
			// when a result was last recorded (an ack or a state report writes the state row's time): shows whether
			// results are still being recorded while the queue is worked through
			$last = $this->ci->db->select_max('changed', 'last_result')
				->where_in('status', array(self::STATUS_INDEXED, self::STATUS_FAILED))
				->get('search_index_state')->row_array();
			$queue['last_result_at'] = ($last && $last['last_result'] !== null) ? (int) $last['last_result'] : null;
			$state['indexed'] = $this->count_state(self::STATUS_INDEXED);
			$state['pending'] = $this->count_state(self::STATUS_PENDING);
			$state['failed']  = $this->count_state(self::STATUS_FAILED);
		}

		return array(
			'search_engine'     => $engine,
			'index_engine'      => $this->index_engine(),
			'tracking_enabled'  => $enabled,
			'tracked'           => $meta ? $this->format_meta($meta) : null,
			'queue'             => $queue,
			'state'             => $state,
		);
	}

	/**
	 * How many catalog entries of $object_type exist vs. their
	 * search_index_state breakdown — the "records in DB vs in index" summary
	 * a dashboard needs, scoped to one object_type (unlike status() above,
	 * which is a global total across every tracked object_type).
	 *
	 * @return array{object_type: string, catalog_total: int, state: array{indexed:int,pending:int,failed:int}}
	 */
	public function object_type_summary($object_type)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}

		$table = ($object_type === self::OBJECT_SURVEY) ? 'surveys' : 'citations';
		$catalog_total = (int) $this->ci->db->from($table)->count_all_results();

		$state = array('indexed' => 0, 'pending' => 0, 'failed' => 0);
		if ($this->tables_ready() && $this->rows_describe_index_engine()) {
			$state['indexed'] = $this->count_state(self::STATUS_INDEXED, $object_type);
			$state['pending'] = $this->count_state(self::STATUS_PENDING, $object_type);
			$state['failed']  = $this->count_state(self::STATUS_FAILED, $object_type);
		}

		return array(
			'object_type'   => $object_type,
			'catalog_total' => $catalog_total,
			'state'         => $state,
		);
	}

	/**
	 * Per-data-type breakdown of catalog vs. index coverage — surveys.type
	 * ('survey' aka microdata, geospatial, document, timeseries, ...), not
	 * NADA's coarse object_type bucket that lumps all of these together (see
	 * object_type_summary() above, which is what this breaks down further).
	 * Citations have no finer type to break down, so they come back as a
	 * single 'citation' row, for symmetry.
	 *
	 * @return array<int, array{data_type: string, catalog_total: int, indexed: int, missing: int, stale: int, errors: int}>
	 */
	public function type_breakdown($object_type = self::OBJECT_SURVEY)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}

		if ($object_type === self::OBJECT_CITATION) {
			$summary = $this->object_type_summary(self::OBJECT_CITATION);
			return array(array(
				'data_type'     => 'citation',
				'catalog_total' => $summary['catalog_total'],
				'indexed'       => $summary['state']['indexed'],
				'missing'       => max(0, $summary['catalog_total'] - $summary['state']['indexed']),
				'stale'         => 0,
				'errors'        => 0,
			));
		}

		$rows = array();

		if (!$this->tables_ready() || !$this->rows_describe_index_engine()) {
			$sql = "SELECT type AS data_type, COUNT(*) AS catalog_total FROM surveys GROUP BY type";
			foreach ($this->ci->db->query($sql)->result_array() as $r) {
				$type = ($r['data_type'] !== null && $r['data_type'] !== '') ? $r['data_type'] : 'unknown';
				$rows[$type] = array(
					'data_type'     => $type,
					'catalog_total' => (int) $r['catalog_total'],
					'indexed'       => 0,
					'missing'       => (int) $r['catalog_total'],
					'stale'         => 0,
					'errors'        => 0,
				);
			}
			ksort($rows);
			return array_values($rows);
		}

		$sql = "
			SELECT s.type AS data_type,
				COUNT(*) AS catalog_total,
				SUM(CASE WHEN st.status = 'indexed' THEN 1 ELSE 0 END) AS indexed_total,
				SUM(CASE WHEN st.object_id IS NULL OR st.status != 'indexed' THEN 1 ELSE 0 END) AS missing_total,
				SUM(CASE WHEN st.last_error IS NOT NULL THEN 1 ELSE 0 END) AS error_total
			FROM surveys s
			LEFT JOIN search_index_state st
				ON st.object_type = 'survey' AND st.object_id = s.id
			GROUP BY s.type
		";
		foreach ($this->ci->db->query($sql)->result_array() as $r) {
			$type = ($r['data_type'] !== null && $r['data_type'] !== '') ? $r['data_type'] : 'unknown';
			$rows[$type] = array(
				'data_type'     => $type,
				'catalog_total' => (int) $r['catalog_total'],
				'indexed'       => (int) $r['indexed_total'],
				'missing'       => (int) $r['missing_total'],
				'stale'         => 0,
				'errors'        => (int) $r['error_total'],
			);
		}

		$stale_sql = "
			SELECT COALESCE(st.data_type, 'unknown') AS data_type, COUNT(*) AS stale_total
			FROM search_index_state st
			WHERE st.object_type = 'survey' AND st.status = 'indexed' AND " . $this->stale_condition(self::OBJECT_SURVEY) . "
			GROUP BY COALESCE(st.data_type, 'unknown')
		";
		foreach ($this->ci->db->query($stale_sql)->result_array() as $r) {
			$type = $r['data_type'];
			if (!isset($rows[$type])) {
				$rows[$type] = array(
					'data_type'     => $type,
					'catalog_total' => 0,
					'indexed'       => 0,
					'missing'       => 0,
					'stale'         => 0,
					'errors'        => 0,
				);
			}
			$rows[$type]['stale'] = (int) $r['stale_total'];
		}

		ksort($rows);
		return array_values($rows);
	}

	/**
	 * Directly upsert search_index_state for a batch of items.
	 *
	 * For content indexed via an admin/bulk operation (not a DB change event),
	 * there is no pre-existing search_index_queue row to ack against — this is
	 * the write path for that case: given (object_type, object_id, status),
	 * upsert state directly ('deleted' removes the state row). The id (sid /
	 * citation id) is the key, as in the index: also for an object gone from the
	 * catalog, so the stale diff finds it. object_key (idno / uuid) is
	 * informative: NADA reuses an idno when a study is deleted and created again.
	 * Never touches search_index_queue; a genuine queue-driven change for the
	 * same object is unaffected and still processed normally later.
	 *
	 * @param array $items each: {object_type, object_id, object_key?, status, error?}
	 * @return array {applied: int, results: array} one result entry per item, in order
	 */
	public function bulk_set_state(array $items)
	{
		$this->require_nada_ai_rows();

		$allowed_status = array(self::STATUS_INDEXED, self::STATUS_FAILED, self::STATUS_DELETED);
		$now = time();
		$results = array();

		foreach ($items as $item) {
			$object_type = isset($item['object_type']) ? $this->normalize_object_type($item['object_type']) : null;
			$object_id   = isset($item['object_id']) ? (int) $item['object_id'] : 0;
			$status      = isset($item['status']) ? (string) $item['status'] : '';

			if ($object_type === null) {
				$results[] = array('object_id' => $object_id, 'applied' => false, 'error' => 'INVALID_OBJECT_TYPE');
				continue;
			}
			if ($object_id <= 0) {
				$results[] = array('object_id' => $object_id, 'applied' => false, 'error' => 'OBJECT_ID_REQUIRED');
				continue;
			}
			if (!in_array($status, $allowed_status, true)) {
				$results[] = array('object_id' => $object_id, 'applied' => false, 'error' => 'INVALID_STATUS');
				continue;
			}

			if ($status === self::STATUS_DELETED) {
				$this->delete_state($object_type, $object_id);
				$results[] = array('object_id' => $object_id, 'applied' => true, 'status' => $status);
				continue;
			}

			// a queued change is newer than what the report describes: its ack sets the state
			if ($this->get_queue_row($object_type, $object_id)) {
				$results[] = array('object_id' => $object_id, 'applied' => false, 'error' => 'QUEUED');
				continue;
			}

			$object_key = isset($item['object_key']) ? trim((string) $item['object_key']) : '';
			if ($object_key === '') {
				$object_key = (string) $this->lookup_live_key($object_type, $object_id);
			}
			$last_error = ($status === self::STATUS_FAILED && isset($item['error'])) ? (string) $item['error'] : null;
			$this->upsert_state($object_type, $object_id, $object_key, $status, $now, $last_error);
			$results[] = array('object_id' => $object_id, 'applied' => true, 'status' => $status);
		}

		$applied = 0;
		foreach ($results as $r) {
			if ($r['applied']) {
				$applied++;
			}
		}

		return array('applied' => $applied, 'results' => $results);
	}

	/**
	 * Catalog entries of $object_type with no 'indexed' search_index_state row —
	 * i.e. never indexed, or indexed then failed/superseded since.
	 *
	 * Published status is not considered here — a row's `published` flag is
	 * tracked and reported separately (see the schema), but this index-sync
	 * diff only cares whether the row *exists* in the catalog, not whether
	 * it's currently publicly visible. Unpublished/draft rows are indexed the
	 * same as published ones.
	 *
	 * ``$data_type``, when given, narrows to one surveys.type value (microdata
	 * aka 'survey', geospatial, document, timeseries, ...) — ignored for
	 * citations, which have no finer type. See type_breakdown() for the
	 * per-type counts this lets a caller then act on one type at a time.
	 *
	 * ``$has_error``, when true, narrows to rows with a recorded last_error —
	 * i.e. genuinely attempted and failed, as opposed to simply never having
	 * been attempted yet (which is most of "missing" on a fresh backfill).
	 *
	 * @return array{items: array{object_id: int, idno: string, type: string, last_error: string|null}[], total: int}
	 */
	public function diff_missing($object_type, $limit = 100, $offset = 0, $data_type = null, $has_error = false)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}
		$this->require_nada_ai_rows();
		$limit  = max(1, min((int) $limit, 1000));
		$offset = max(0, (int) $offset);
		$data_type = ($data_type !== null && $data_type !== '') ? (string) $data_type : null;
		$error_filter = $has_error ? ' AND st.last_error IS NOT NULL' : '';

		if ($object_type === self::OBJECT_SURVEY) {
			$type_filter = ($data_type !== null) ? ' AND s.type = ?' : '';
			$params = ($data_type !== null) ? array($data_type) : array();
			$sql = "
				SELECT s.id AS object_id, s.idno AS object_key, s.type AS type, st.last_error AS last_error
				FROM surveys s
				LEFT JOIN search_index_state st
					ON st.object_type = 'survey' AND st.object_id = s.id
				WHERE (st.object_id IS NULL OR st.status != 'indexed'){$type_filter}{$error_filter}
				ORDER BY s.id ASC
			";
			$count_sql = "
				SELECT COUNT(*) AS n
				FROM surveys s
				LEFT JOIN search_index_state st
					ON st.object_type = 'survey' AND st.object_id = s.id
				WHERE (st.object_id IS NULL OR st.status != 'indexed'){$type_filter}{$error_filter}
			";
		} else {
			$params = array();
			$sql = "
				SELECT c.id AS object_id, c.uuid AS object_key, 'citation' AS type, st.last_error AS last_error
				FROM citations c
				LEFT JOIN search_index_state st
					ON st.object_type = 'citation' AND st.object_id = c.id
				WHERE (st.object_id IS NULL OR st.status != 'indexed'){$error_filter}
				ORDER BY c.id ASC
			";
			$count_sql = "
				SELECT COUNT(*) AS n
				FROM citations c
				LEFT JOIN search_index_state st
					ON st.object_type = 'citation' AND st.object_id = c.id
				WHERE (st.object_id IS NULL OR st.status != 'indexed'){$error_filter}
			";
		}

		$total = (int) $this->ci->db->query($count_sql, $params)->row_array()['n'];
		$rows  = $this->ci->db->query($sql . $this->paginate_clause($limit, $offset), $params)->result_array();

		return array(
			'items' => array_map(function ($r) {
				return array(
					'object_id'  => (int) $r['object_id'],
					'idno'       => $r['object_key'],
					'type'       => $r['type'],
					'last_error' => isset($r['last_error']) ? $r['last_error'] : null,
				);
			}, $rows),
			'total' => $total,
		);
	}

	/**
	 * search_index_state rows of $object_type marked 'indexed' whose catalog
	 * row is gone entirely (see stale_condition()) — these should be removed
	 * from the index, by object_id.
	 *
	 * Published status is not considered — see diff_missing()'s docblock for
	 * why. An unpublished row is not "stale"; only a genuinely deleted one is.
	 *
	 * ``$data_type``, when given, narrows to one search_index_state.data_type
	 * value — ignored for citations (see diff_missing()'s docblock).
	 *
	 * @return array{items: array{object_id: int, idno: string, type: string|null}[], total: int}
	 */
	public function diff_stale($object_type, $limit = 100, $offset = 0, $data_type = null)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}
		$this->require_nada_ai_rows();
		$limit  = max(1, min((int) $limit, 1000));
		$offset = max(0, (int) $offset);
		$data_type = ($data_type !== null && $data_type !== '' && $object_type === self::OBJECT_SURVEY) ? (string) $data_type : null;

		$type_filter = ($data_type !== null) ? ' AND st.data_type = ?' : '';
		$params = ($data_type !== null) ? array($object_type, $data_type) : array($object_type);

		$stale = $this->stale_condition($object_type);

		$sql = "
			SELECT st.object_id AS object_id, st.object_key AS object_key, st.data_type AS data_type
			FROM search_index_state st
			WHERE st.object_type = ?
				AND st.status = 'indexed'
				AND {$stale}{$type_filter}
			ORDER BY st.object_id ASC
		";
		$count_sql = "
			SELECT COUNT(*) AS n
			FROM search_index_state st
			WHERE st.object_type = ?
				AND st.status = 'indexed'
				AND {$stale}{$type_filter}
		";

		$total = (int) $this->ci->db->query($count_sql, $params)->row_array()['n'];
		$rows  = $this->ci->db->query(
			$sql . $this->paginate_clause($limit, $offset),
			$params
		)->result_array();

		return array(
			'items' => array_map(function ($r) {
				return array('object_id' => (int) $r['object_id'], 'idno' => $r['object_key'], 'type' => $r['data_type']);
			}, $rows),
			'total' => $total,
		);
	}

	/**
	 * SQL condition on search_index_state `st`: the row is stale — its catalog row is gone. The index is keyed by the
	 * same id, so a study deleted and created again with the same idno (a new id) is two objects: the old one is
	 * stale, the new one missing.
	 */
	private function stale_condition($object_type)
	{
		if ($object_type === self::OBJECT_SURVEY) {
			return "NOT EXISTS (SELECT 1 FROM surveys live WHERE live.id = st.object_id)";
		}

		return "NOT EXISTS (SELECT 1 FROM citations live WHERE live.id = st.object_id)";
	}

	/** MySQL/SQL Server both back this app (see migrations) — LIMIT/OFFSET syntax differs between them. */
	private function paginate_clause($limit, $offset)
	{
		$limit  = (int) $limit;
		$offset = (int) $offset;
		if ($this->ci->db->dbdriver === 'sqlsrv') {
			return " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
		}
		return " LIMIT {$limit} OFFSET {$offset}";
	}

	/**
	 * surveys.type ('survey', 'geospatial', 'document', 'timeseries', ...) for
	 * the given object — null if not a survey object, or the row is gone.
	 */
	private function resolve_data_type($object_type, $object_id)
	{
		if ($object_type !== self::OBJECT_SURVEY) {
			return null;
		}
		$row = $this->ci->db->select('type')
			->from('surveys')
			->where('id', (int) $object_id)
			->get()
			->row_array();
		return $row ? $row['type'] : null;
	}

	public function format_queue_item(array $row)
	{
		return array(
			'id'             => (int) $row['id'],
			'object_type'    => $row['object_type'],
			'object_id'      => (int) $row['object_id'],
			'object_key'     => $row['object_key'],
			'change_class'   => $row['change_class'],
			'status'         => $row['status'],
			'attempts'       => (int) $row['attempts'],
			'last_error'     => $row['last_error'],
			'changed'        => (int) $row['changed'],
			'priority'       => isset($row['priority']) ? (int) $row['priority'] : self::PRIORITY_LIVE,
			'data_type'      => isset($row['data_type']) && $row['data_type'] !== '' ? $row['data_type'] : null,
			'fetch_document' => $row['change_class'] !== self::CLASS_DELETE,
		);
	}

	private function ack_apply(array $row, $result, $error = null)
	{
		$applied = $this->ack_apply_row($row, $result, $error);
		if (isset($row['priority']) && (int) $row['priority'] === self::PRIORITY_SYNC) {
			$this->note_sync_progress();
		}
		return $applied;
	}

	/** A sync is finished when none of the rows it queued is pending any more. */
	private function note_sync_progress()
	{
		$left = (int) $this->ci->db->from('search_index_queue')
			->where('status', self::STATUS_PENDING)->where('priority', self::PRIORITY_SYNC)->count_all_results();
		if ($left === 0) {
			$this->ci->db->where('id', 1);
			$this->ci->db->update('search_index_meta', array('last_sync_at' => time()));
		}
	}

	private function ack_apply_row(array $row, $result, $error = null)
	{
		$now = time();

		if ($result === self::STATUS_FAILED) {
			$this->ci->db->where('id', (int) $row['id']);
			$this->ci->db->update('search_index_queue', array(
				'status'     => self::STATUS_FAILED,
				'attempts'   => (int) $row['attempts'] + 1,
				'last_error' => $this->truncate_error($error),
				'changed'    => $now,
			));
			$this->upsert_state(
				$row['object_type'],
				(int) $row['object_id'],
				$row['object_key'],
				self::STATUS_FAILED,
				$now,
				$error
			);
			return array('applied' => true, 'result' => self::STATUS_FAILED);
		}

		$this->ci->db->where('id', (int) $row['id']);
		$this->ci->db->delete('search_index_queue');

		if ($row['change_class'] === self::CLASS_DELETE) {
			$this->delete_state($row['object_type'], (int) $row['object_id']);
			return array('applied' => true, 'result' => self::STATUS_DELETED);
		}

		$this->upsert_state(
			$row['object_type'],
			(int) $row['object_id'],
			$row['object_key'],
			self::STATUS_INDEXED,
			$now
		);
		return array('applied' => true, 'result' => self::STATUS_INDEXED);
	}

	private function process_inline(array $row)
	{
		$engine = $this->index_engine();
		$inline = $this->ci->config->item('search_index_inline_engines');
		if (!is_array($inline)) {
			$inline = array('solr', 'opensearch');
		}
		if (!in_array($engine, $inline, true)) {
			return;
		}

		try {
			$this->apply_to_engine($engine, $row);
			$fresh = $this->get_queue_by_id((int) $row['id']);
			if ($fresh && (int) $fresh['changed'] === (int) $row['changed']) {
				$this->ack_apply($fresh, self::STATUS_INDEXED);
			}
		} catch (Exception $e) {
			log_message('error', 'Search_index inline: ' . $e->getMessage());
			$fresh = $this->get_queue_by_id((int) $row['id']);
			if ($fresh && (int) $fresh['changed'] === (int) $row['changed']) {
				$this->ack_apply($fresh, self::STATUS_FAILED, $e->getMessage());
			}
		}
	}

	private function apply_to_engine($engine, array $row)
	{
		$table = ($row['object_type'] === self::OBJECT_SURVEY) ? 'surveys' : 'citations';
		$id    = (int) $row['object_id'];

		if ($engine === 'solr') {
			$this->ci->load->library('Solr_manager');
			if ($row['object_type'] === self::OBJECT_SURVEY && $row['change_class'] === self::CLASS_VARIABLES) {
				$this->ci->solr_manager->delete_document('var_survey_id:' . $id);
				$this->ci->solr_manager->import_survey_variables($id);
				return;
			}
			$this->ci->solr_manager->process_delta_update(
				$table,
				$this->engine_action($row['change_class']),
				$id
			);
			return;
		}

		if ($engine === 'opensearch') {
			$this->ci->load->library('OpenSearch/OpenSearch_manager');
			if ($row['object_type'] === self::OBJECT_SURVEY && $row['change_class'] === self::CLASS_VARIABLES) {
				$this->ci->opensearch_manager->index_survey_variables($id);
				return;
			}
			$this->ci->opensearch_manager->process_delta_update(
				$table,
				$this->engine_action($row['change_class']),
				$id
			);
		}
	}

	private function engine_action($change_class)
	{
		if ($change_class === self::CLASS_DELETE) {
			return 'delete';
		}
		if ($change_class === self::CLASS_PARTIAL) {
			return 'publish';
		}
		return 'refresh';
	}

	private function map_action($action)
	{
		$action = strtolower((string) $action);
		if ($action === 'publish' || $action === 'atomic') {
			return self::CLASS_PARTIAL;
		}
		// only filter fields changed (facets, repositories, countries, regions): nada-ai's partial update rewrites every
		// filter without embedding; Solr's and NADA's OpenSearch's partial update covers fewer fields, so a full one
		if ($action === 'filters' || $action === 'facet') {
			return $this->index_engine() === self::ENGINE_NADA_AI ? self::CLASS_PARTIAL : self::CLASS_FULL;
		}
		if ($action === 'variables') {
			return self::CLASS_VARIABLES;
		}
		if ($action === 'delete') {
			return self::CLASS_DELETE;
		}
		if (in_array($action, array('import', 'replace', 'update', 'create', 'refresh', 'facet'), true)) {
			return self::CLASS_FULL;
		}
		return self::CLASS_FULL;
	}

	private function coalesce_class($current, $incoming)
	{
		// a partial update (options, filters) and a variables update cover different things: together they need the
		// full index, which does both — keeping either one alone would drop the other change
		$pair = array($current, $incoming);
		if (in_array(self::CLASS_PARTIAL, $pair, true) && in_array(self::CLASS_VARIABLES, $pair, true)) {
			return self::CLASS_FULL;
		}
		$rank = array(
			self::CLASS_PARTIAL   => 1,
			self::CLASS_VARIABLES => 2,
			self::CLASS_FULL      => 3,
			self::CLASS_DELETE    => 4,
		);
		$cur = isset($rank[$current]) ? $rank[$current] : 0;
		$in  = isset($rank[$incoming]) ? $rank[$incoming] : 0;
		return ($in >= $cur) ? $incoming : $current;
	}

	private function normalize_object_type($type)
	{
		$type = strtolower((string) $type);
		if ($type === 'survey' || $type === 'surveys') {
			return self::OBJECT_SURVEY;
		}
		if ($type === 'citation' || $type === 'citations') {
			return self::OBJECT_CITATION;
		}
		return null;
	}

	private function resolve_object_key($object_type, $object_id)
	{
		$live = $this->lookup_live_key($object_type, $object_id);
		if ($live !== null && $live !== '') {
			return $live;
		}
		$state = $this->get_state($object_type, $object_id);
		if ($state && $state['object_key'] !== '') {
			return $state['object_key'];
		}
		return (string) $object_id;
	}

	private function lookup_live_key($object_type, $object_id)
	{
		if ($object_type === self::OBJECT_SURVEY) {
			$row = $this->ci->db->select('idno')
				->from('surveys')
				->where('id', (int) $object_id)
				->get()
				->row_array();
			return ($row && $row['idno'] !== '') ? $row['idno'] : null;
		}

		$row = $this->ci->db->select('uuid')
			->from('citations')
			->where('id', (int) $object_id)
			->get()
			->row_array();
		return ($row && $row['uuid'] !== '') ? $row['uuid'] : null;
	}

	private function upsert_state($object_type, $object_id, $object_key, $status, $changed, $last_error = null)
	{
		$existing  = $this->get_state($object_type, $object_id);
		$data_type = $this->resolve_data_type($object_type, $object_id);
		if ($data_type === null && $existing && isset($existing['data_type'])) {
			// catalog row is gone (e.g. this call is reporting the delete itself) —
			// keep whatever type we last saw rather than blanking it out.
			$data_type = $existing['data_type'];
		}
		$data = array(
			'object_key' => $object_key,
			'status'     => $status,
			'changed'    => $changed,
			'last_error' => $this->truncate_error($last_error),
			'data_type'  => $data_type,
		);
		if ($existing) {
			$this->ci->db->where('object_type', $object_type);
			$this->ci->db->where('object_id', $object_id);
			$this->ci->db->update('search_index_state', $data);
			return;
		}
		$data['object_type'] = $object_type;
		$data['object_id']   = $object_id;
		$this->ci->db->insert('search_index_state', $data);
	}

	private function delete_state($object_type, $object_id)
	{
		$this->ci->db->where('object_type', $object_type);
		$this->ci->db->where('object_id', (int) $object_id);
		$this->ci->db->delete('search_index_state');
	}

	private function get_queue_row($object_type, $object_id)
	{
		return $this->ci->db->from('search_index_queue')
			->where('object_type', $object_type)
			->where('object_id', (int) $object_id)
			->get()
			->row_array();
	}

	private function get_queue_by_id($id)
	{
		return $this->ci->db->from('search_index_queue')
			->where('id', (int) $id)
			->get()
			->row_array();
	}

	private function get_state($object_type, $object_id)
	{
		return $this->ci->db->from('search_index_state')
			->where('object_type', $object_type)
			->where('object_id', (int) $object_id)
			->get()
			->row_array();
	}

	private function count_queue($status)
	{
		return (int) $this->ci->db->from('search_index_queue')
			->where('status', $status)
			->count_all_results();
	}

	private function count_state($status, $object_type = null)
	{
		$this->ci->db->from('search_index_state');
		$this->ci->db->where('status', $status);
		if ($object_type !== null) {
			$this->ci->db->where('object_type', $object_type);
		}
		return (int) $this->ci->db->count_all_results();
	}

	/** When nada-ai last read the change queue (shown so a stopped scheduler is noticed). */
	public function record_poll()
	{
		if (!$this->tables_ready()) {
			return;
		}
		$this->ci->db->where('id', 1);
		$this->ci->db->update('search_index_meta', array('last_poll_at' => time()));
	}

	/**
	 * For nada-ai's calls: the rows must describe nada-ai's index. Rows of another engine are cleared when nada-ai is
	 * now the index engine; otherwise the call is refused (the rows belong to the engine set before, and nada-ai must
	 * not process or report into them).
	 *
	 * @throws Search_index_exception
	 */
	private function require_nada_ai_rows()
	{
		if (!$this->tables_ready()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}
		$meta = $this->get_meta();
		if (!$this->meta_describes($meta, self::ENGINE_NADA_AI)) {
			if ($this->index_engine() !== self::ENGINE_NADA_AI) {
				throw new Search_index_exception('INDEX_ENGINE_NOT_NADA_AI', 400);
			}
			$meta = $this->reset_tracking(self::ENGINE_NADA_AI, null);
		}
		$meta = $this->check_index_generation($meta);

		// the caller's index must be the one the rows describe: refuses another nada-ai pointed at this NADA. Only
		// when both are known; NADA learns the generation from its own nada-ai's /info, never from the caller.
		$caller = trim((string) $this->ci->input->get_request_header('X-Index-Generation', true));
		if ($caller !== '' && $meta['index_generation'] !== null && $caller !== $meta['index_generation']) {
			throw new Search_index_exception('INDEX_GENERATION_MISMATCH', 400);
		}
	}

	/** Clear the rows when they were written for another engine (or another nada-ai) than the index engine. */
	private function follow_index_engine()
	{
		$meta   = $this->get_meta();
		$engine = $this->index_engine();
		if (!$this->meta_describes($meta, $engine)) {
			$this->reset_tracking($engine, null);
		}
	}

	/** Whether the rows were written for $engine — for nada-ai, for the one at the configured URL. */
	private function meta_describes(array $meta, $engine)
	{
		if ($meta['engine'] !== $engine) {
			return false;
		}
		return $engine !== self::ENGINE_NADA_AI || (string) $meta['nada_ai_url'] === $this->nada_ai_url();
	}

	private function nada_ai_url()
	{
		return rtrim(trim((string) $this->ci->config->item('nada_ai_url')), '/');
	}

	/**
	 * Clear the rows when nada-ai's study index is not the one they were written against. Only compared when nada-ai
	 * answered /info just now; the first generation seen is stored without clearing anything.
	 *
	 * @return array the meta row, as it is now
	 */
	private function check_index_generation(array $meta)
	{
		$this->ci->load->library('nada_ai_link');
		$generation = $this->ci->nada_ai_link->index_generation();
		if ($generation === false || $generation === $meta['index_generation']) {
			return $meta;
		}
		if ($meta['index_generation'] === null) {
			$this->ci->db->where('id', 1);
			$this->ci->db->update('search_index_meta', array('index_generation' => $generation));
			return $this->get_meta();
		}
		return $this->reset_tracking(self::ENGINE_NADA_AI, $generation);
	}

	/** @return array the new meta row */
	private function reset_tracking($engine, $generation)
	{
		$this->ci->db->trans_start();
		$this->ci->db->empty_table('search_index_queue');
		$this->ci->db->empty_table('search_index_state');
		$this->ci->db->where('id', 1);
		$this->ci->db->update('search_index_meta', array(
			'engine'           => $engine,
			'index_generation' => $generation,
			'nada_ai_url'      => $engine === self::ENGINE_NADA_AI ? $this->nada_ai_url() : null,
			'tracked_since'    => time(),
			'last_sync_at'     => null,
		));
		$this->ci->db->trans_complete();
		log_message('info', 'Search_index: tracking reset for ' . $engine . ($generation !== null ? ' (index ' . $generation . ')' : ''));
		return $this->get_meta();
	}

	/** Whether the rows describe the index engine's index (else they are cleared on the next change). */
	private function rows_describe_index_engine()
	{
		return $this->meta_describes($this->get_meta(), $this->index_engine());
	}

	/**
	 * @return array the search_index_meta row
	 * @throws Search_index_exception when the row is missing (the migration adds it)
	 */
	private function get_meta()
	{
		$row = $this->ci->db->from('search_index_meta')->where('id', 1)->get()->row_array();
		if (!$row) {
			throw new Search_index_exception('SEARCH_INDEX_META_MISSING', 500);
		}
		return $row;
	}

	private function format_meta(array $meta)
	{
		return array(
			'engine'           => $meta['engine'],
			'index_generation' => $meta['index_generation'],
			'nada_ai_url'      => isset($meta['nada_ai_url']) ? $meta['nada_ai_url'] : null,
			'tracked_since'    => (int) $meta['tracked_since'],
			'last_sync_at'     => $meta['last_sync_at'] !== null ? (int) $meta['last_sync_at'] : null,
			'last_poll_at'     => $meta['last_poll_at'] !== null ? (int) $meta['last_poll_at'] : null,
		);
	}

	private function tables_ready()
	{
		return $this->ci->db->table_exists('search_index_queue')
			&& $this->ci->db->table_exists('search_index_state')
			&& $this->ci->db->table_exists('search_index_meta');
	}

	private function truncate_error($error)
	{
		$error = (string) $error;
		if (strlen($error) <= 500) {
			return $error !== '' ? $error : null;
		}
		return substr($error, 0, 497) . '...';
	}
}

class Search_index_exception extends Exception
{
	public $http_code;

	public function __construct($message, $http_code = 400)
	{
		parent::__construct($message);
		$this->http_code = $http_code;
	}
}
