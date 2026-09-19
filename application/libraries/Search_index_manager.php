<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search index change tracking.
 *
 * One configured provider. Database search does not write rows.
 * Solr/OpenSearch process queue rows inline so live sync is unchanged.
 * Semantic leaves rows pending for the pull API.
 */
class Search_index_manager
{
	const OBJECT_SURVEY   = 'survey';
	const OBJECT_CITATION = 'citation';

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

	/** @var CI_Controller */
	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('search_index');
	}

	public function tracking_enabled()
	{
		$provider = $this->current_provider();
		$allowed  = $this->ci->config->item('search_index_tracking_providers');
		if (!is_array($allowed)) {
			$allowed = array('solr', 'opensearch', 'semantic');
		}
		return in_array($provider, $allowed, true);
	}

	public function current_provider()
	{
		$provider = (string) $this->ci->config->item('search_provider');
		if ($provider === 'mysql' || $provider === 'mysqli' || $provider === 'sqlsrv') {
			return 'db';
		}
		return $provider !== '' ? $provider : 'db';
	}

	/**
	 * @param string     $table     surveys | citations
	 * @param int|string $object_id
	 * @param string     $action    Events action
	 * @param bool       $is_delete
	 */
	public function handle_event($table, $object_id, $action = 'atomic', $is_delete = false)
	{
		if (!$this->tracking_enabled() || !$this->tables_ready()) {
			return;
		}

		$object_type = $this->normalize_object_type($table);
		if ($object_type === null) {
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
	 * @return array{items: array, total: int, limit: int, tracking_enabled: bool}
	 */
	public function list_queue($status = self::STATUS_PENDING, $limit = 50, $object_type = null)
	{
		$defaults = (int) $this->ci->config->item('search_index_queue_default_limit') ?: 50;
		$max      = (int) $this->ci->config->item('search_index_queue_max_limit') ?: 100;
		$limit    = (int) $limit;
		if ($limit <= 0) {
			$limit = $defaults;
		}
		$limit = min($limit, $max);

		if (!$this->tracking_enabled() || !$this->tables_ready()) {
			return array(
				'items'            => array(),
				'total'            => 0,
				'limit'            => $limit,
				'tracking_enabled' => false,
			);
		}

		if (!in_array($status, array(self::STATUS_PENDING, self::STATUS_FAILED), true)) {
			$status = self::STATUS_PENDING;
		}

		$this->ci->db->from('search_index_queue');
		$this->ci->db->where('status', $status);
		if ($object_type === self::OBJECT_SURVEY || $object_type === self::OBJECT_CITATION) {
			$this->ci->db->where('object_type', $object_type);
		}
		$total = (int) $this->ci->db->count_all_results();

		$this->ci->db->from('search_index_queue');
		$this->ci->db->where('status', $status);
		if ($object_type === self::OBJECT_SURVEY || $object_type === self::OBJECT_CITATION) {
			$this->ci->db->where('object_type', $object_type);
		}
		$this->ci->db->order_by('changed', 'ASC');
		$this->ci->db->limit($limit);
		$rows = $this->ci->db->get()->result_array();

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

	/**
	 * @return array applied result
	 * @throws Search_index_exception
	 */
	public function ack($id, $result, $changed, $error = null)
	{
		if (!$this->tracking_enabled()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}
		if (!$this->tables_ready()) {
			throw new Search_index_exception('QUEUE_NOT_FOUND', 404);
		}

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
		$provider = $this->current_provider();
		$enabled  = $this->tracking_enabled() && $this->tables_ready();

		$queue = array('pending' => 0, 'failed' => 0);
		$state = array(
			'indexed' => 0,
			'pending' => 0,
			'failed'  => 0,
		);

		if ($enabled) {
			$queue['pending'] = $this->count_queue(self::STATUS_PENDING);
			$queue['failed']  = $this->count_queue(self::STATUS_FAILED);
			$state['indexed'] = $this->count_state(self::STATUS_INDEXED);
			$state['pending'] = $this->count_state(self::STATUS_PENDING);
			$state['failed']  = $this->count_state(self::STATUS_FAILED);
		}

		return array(
			'search_provider'   => $provider,
			'tracking_enabled'  => $enabled,
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
		if ($this->tracking_enabled() && $this->tables_ready()) {
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
			LEFT JOIN surveys live ON live.id = st.object_id
			WHERE st.object_type = 'survey' AND st.status = 'indexed' AND live.id IS NULL
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
	 * the write path for that case: given just (object_type, object_key,
	 * status), resolve the internal id and upsert state directly ('deleted'
	 * removes the state row instead — see STATUS_DELETED). Never
	 * touches search_index_queue; a genuine queue-driven change for the same
	 * object is unaffected and still processed normally later.
	 *
	 * @param array $items each: {object_type, object_key, status}
	 * @return array {applied: int, results: array} one result entry per item, in order
	 */
	public function bulk_set_state(array $items)
	{
		if (!$this->tracking_enabled() || !$this->tables_ready()) {
			throw new Search_index_exception('TRACKING_DISABLED', 400);
		}

		$allowed_status = array(self::STATUS_INDEXED, self::STATUS_FAILED, self::STATUS_DELETED);
		$now = time();
		$results = array();

		foreach ($items as $item) {
			$object_type = isset($item['object_type']) ? $this->normalize_object_type($item['object_type']) : null;
			$object_key  = isset($item['object_key']) ? trim((string) $item['object_key']) : '';
			$status      = isset($item['status']) ? (string) $item['status'] : '';

			if ($object_type === null) {
				$results[] = array('object_key' => $object_key, 'applied' => false, 'error' => 'INVALID_OBJECT_TYPE');
				continue;
			}
			if ($object_key === '') {
				$results[] = array('object_key' => $object_key, 'applied' => false, 'error' => 'OBJECT_KEY_REQUIRED');
				continue;
			}
			if (!in_array($status, $allowed_status, true)) {
				$results[] = array('object_key' => $object_key, 'applied' => false, 'error' => 'INVALID_STATUS');
				continue;
			}

			$object_id = $this->resolve_object_id($object_type, $object_key);
			if ($object_id === null) {
				$results[] = array('object_key' => $object_key, 'applied' => false, 'error' => 'OBJECT_KEY_NOT_FOUND');
				continue;
			}

			if ($status === self::STATUS_DELETED) {
				$this->delete_state($object_type, $object_id);
				$results[] = array('object_key' => $object_key, 'applied' => true, 'status' => $status);
				continue;
			}

			$last_error = ($status === self::STATUS_FAILED && isset($item['error'])) ? (string) $item['error'] : null;
			$this->upsert_state($object_type, $object_id, $object_key, $status, $now, $last_error);
			$results[] = array('object_key' => $object_key, 'applied' => true, 'status' => $status);
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
	 * @return array{items: array{idno: string, type: string}[], total: int}
	 */
	public function diff_missing($object_type, $limit = 100, $offset = 0, $data_type = null, $has_error = false)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}
		$limit  = max(1, min((int) $limit, 1000));
		$offset = max(0, (int) $offset);
		$data_type = ($data_type !== null && $data_type !== '') ? (string) $data_type : null;
		$error_filter = $has_error ? ' AND st.last_error IS NOT NULL' : '';

		if ($object_type === self::OBJECT_SURVEY) {
			$type_filter = ($data_type !== null) ? ' AND s.type = ?' : '';
			$params = ($data_type !== null) ? array($data_type) : array();
			$sql = "
				SELECT s.idno AS object_key, s.type AS type, st.last_error AS last_error
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
				SELECT c.uuid AS object_key, 'citation' AS type, st.last_error AS last_error
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
				return array('idno' => $r['object_key'], 'type' => $r['type'], 'last_error' => isset($r['last_error']) ? $r['last_error'] : null);
			}, $rows),
			'total' => $total,
		);
	}

	/**
	 * search_index_state rows of $object_type marked 'indexed' whose catalog
	 * row is gone entirely — these should be removed from the index.
	 *
	 * Published status is not considered — see diff_missing()'s docblock for
	 * why. An unpublished row is not "stale"; only a genuinely deleted one is.
	 *
	 * ``$data_type``, when given, narrows to one search_index_state.data_type
	 * value — ignored for citations (see diff_missing()'s docblock).
	 *
	 * @return array{items: array{idno: string}[], total: int}
	 */
	public function diff_stale($object_type, $limit = 100, $offset = 0, $data_type = null)
	{
		$object_type = $this->normalize_object_type($object_type);
		if ($object_type === null) {
			throw new Search_index_exception('OBJECT_TYPE_REQUIRED', 400);
		}
		$limit  = max(1, min((int) $limit, 1000));
		$offset = max(0, (int) $offset);
		$data_type = ($data_type !== null && $data_type !== '' && $object_type === self::OBJECT_SURVEY) ? (string) $data_type : null;

		$table = ($object_type === self::OBJECT_SURVEY) ? 'surveys' : 'citations';
		$type_filter = ($data_type !== null) ? ' AND st.data_type = ?' : '';
		$params = ($data_type !== null) ? array($object_type, $data_type) : array($object_type);

		$sql = "
			SELECT st.object_key AS object_key
			FROM search_index_state st
			LEFT JOIN {$table} live ON live.id = st.object_id
			WHERE st.object_type = ?
				AND st.status = 'indexed'
				AND live.id IS NULL{$type_filter}
			ORDER BY st.object_id ASC
		";
		$count_sql = "
			SELECT COUNT(*) AS n
			FROM search_index_state st
			LEFT JOIN {$table} live ON live.id = st.object_id
			WHERE st.object_type = ?
				AND st.status = 'indexed'
				AND live.id IS NULL{$type_filter}
		";

		$total = (int) $this->ci->db->query($count_sql, $params)->row_array()['n'];
		$rows  = $this->ci->db->query(
			$sql . $this->paginate_clause($limit, $offset),
			$params
		)->result_array();

		return array(
			'items' => array_map(function ($r) {
				return array('idno' => $r['object_key']);
			}, $rows),
			'total' => $total,
		);
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

	private function resolve_object_id($object_type, $object_key)
	{
		if ($object_type === self::OBJECT_SURVEY) {
			$this->ci->load->library('Dataset_manager');
			$sid = $this->ci->dataset_manager->find_by_idno($object_key);
			return $sid ? (int) $sid : null;
		}

		$row = $this->ci->db->select('id')
			->from('citations')
			->where('uuid', $object_key)
			->get()
			->row_array();
		return $row ? (int) $row['id'] : null;
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
			'fetch_document' => $row['change_class'] !== self::CLASS_DELETE,
		);
	}

	private function ack_apply(array $row, $result, $error = null)
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
		$provider = $this->current_provider();
		$inline   = $this->ci->config->item('search_index_inline_providers');
		if (!is_array($inline)) {
			$inline = array('solr', 'opensearch');
		}
		if (!in_array($provider, $inline, true)) {
			return;
		}

		try {
			$this->apply_to_engine($provider, $row);
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

	private function apply_to_engine($provider, array $row)
	{
		$table = ($row['object_type'] === self::OBJECT_SURVEY) ? 'surveys' : 'citations';
		$id    = (int) $row['object_id'];

		if ($provider === 'solr') {
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

		if ($provider === 'opensearch') {
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

	private function tables_ready()
	{
		return $this->ci->db->table_exists('search_index_queue')
			&& $this->ci->db->table_exists('search_index_state');
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
