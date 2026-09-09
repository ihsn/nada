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
			'deleted' => 0,
		);

		if ($enabled) {
			$queue['pending'] = $this->count_queue(self::STATUS_PENDING);
			$queue['failed']  = $this->count_queue(self::STATUS_FAILED);
			$state['indexed'] = $this->count_state(self::STATUS_INDEXED);
			$state['pending'] = $this->count_state(self::STATUS_PENDING);
			$state['failed']  = $this->count_state(self::STATUS_FAILED);
			$state['deleted'] = $this->count_state(self::STATUS_DELETED);
		}

		return array(
			'search_provider'   => $provider,
			'tracking_enabled'  => $enabled,
			'queue'             => $queue,
			'state'             => $state,
		);
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
				$now
			);
			return array('applied' => true, 'result' => self::STATUS_FAILED);
		}

		$state_status = ($row['change_class'] === self::CLASS_DELETE)
			? self::STATUS_DELETED
			: self::STATUS_INDEXED;

		$this->ci->db->where('id', (int) $row['id']);
		$this->ci->db->delete('search_index_queue');
		$this->upsert_state(
			$row['object_type'],
			(int) $row['object_id'],
			$row['object_key'],
			$state_status,
			$now
		);
		return array('applied' => true, 'result' => $state_status);
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

	private function upsert_state($object_type, $object_id, $object_key, $status, $changed)
	{
		$existing = $this->get_state($object_type, $object_id);
		$data     = array(
			'object_key' => $object_key,
			'status'     => $status,
			'changed'    => $changed,
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

	private function count_state($status)
	{
		return (int) $this->ci->db->from('search_index_state')
			->where('status', $status)
			->count_all_results();
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
