<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Codelist_model
 *
 * Catalogue-scoped codelists: one row per (agency, name, version).
 * Family pointer `pid` equals the latest row id for (agency, name); list heads satisfy id = pid.
 *
 * Items and groups are in Codelist_item_model and Codelist_group_model.
 */
class Codelist_model extends CI_Model {

	const DEFAULT_AGENCY  = 'NADA';
	const DEFAULT_VERSION = '1.0.0';

	/** Semantic version (same rule set as Data_structure_model). */
	const VERSION_REGEX = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|[0-9A-Za-z-]*[A-Za-z-][0-9A-Za-z-]*)(?:\.(?:0|[1-9]\d*|[0-9A-Za-z-]*[A-Za-z-][0-9A-Za-z-]*))*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';

	const STATUS_DRAFT      = 0;
	const STATUS_REVIEW     = 10;
	const STATUS_PUBLISHED  = 20;
	const STATUS_DEPRECATED = 30;
	const STATUS_ARCHIVED   = 40;

	public static $allowed_statuses = [
		self::STATUS_DRAFT,
		self::STATUS_REVIEW,
		self::STATUS_PUBLISHED,
		self::STATUS_DEPRECATED,
		self::STATUS_ARCHIVED,
	];

	public static $locked_statuses = [
		self::STATUS_PUBLISHED,
		self::STATUS_ARCHIVED,
	];

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * True when codelist should be treated as immutable for items/groups and non-status metadata.
	 *
	 * @param mixed $status
	 * @return bool
	 */
	public static function is_locked_status($status)
	{
		return in_array((int) $status, self::$locked_statuses, true);
	}

	/**
	 * Number of data structure (DSD) components bound to this codelist row.
	 *
	 * @param int $codelist_id
	 * @return int
	 */
	public function count_data_structure_components_by_codelist($codelist_id)
	{
		if (!$this->db->table_exists('data_structure_components')) {
			return 0;
		}
		$codelist_id = (int) $codelist_id;
		if ($codelist_id <= 0) {
			return 0;
		}
		$this->db->where('codelist_id', $codelist_id);
		return (int) $this->db->count_all_results('data_structure_components');
	}

	/**
	 * DSD components referencing any version row for (agency, name).
	 *
	 * @param string $agency
	 * @param string $name
	 * @return int
	 */
	public function count_data_structure_components_by_codelist_family($agency, $name)
	{
		if (!$this->db->table_exists('data_structure_components')) {
			return 0;
		}
		$agency = trim((string) $agency);
		$name = trim((string) $name);
		if ($agency === '' || $name === '') {
			return 0;
		}
		$sql = 'SELECT COUNT(*) AS n FROM data_structure_components WHERE codelist_id IN (SELECT id FROM codelists WHERE agency = ? AND name = ?)';
		$q = $this->db->query($sql, [$agency, $name]);
		if (!$q) {
			return 0;
		}
		$r = $q->row_array();
		return isset($r['n']) ? (int) $r['n'] : 0;
	}

	/**
	 * Deterministic idno builder. Format: '{agency}_{name}_{version}'.
	 */
	public static function make_idno($agency, $name, $version)
	{
		$agency  = trim((string) $agency)  !== '' ? trim((string) $agency)  : self::DEFAULT_AGENCY;
		$version = trim((string) $version) !== '' ? trim((string) $version) : self::DEFAULT_VERSION;
		$name    = trim((string) $name);
		return $agency . '_' . $name . '_' . $version;
	}

	/**
	 * Apply optional free-text filter (name, idno, agency, version, description).
	 *
	 * @param string $search trimmed; empty skips
	 * @param string $alias  table prefix with trailing dot, e.g. 'c.' or ''
	 */
	private function _apply_codelist_search($search, $alias = '')
	{
		$search = trim((string) $search);
		if ($search === '') {
			return;
		}
		$p = $alias === '' ? '' : rtrim((string) $alias, '.') . '.';
		$this->db->group_start();
		$this->db->like($p . 'name', $search);
		$this->db->or_like($p . 'idno', $search);
		$this->db->or_like($p . 'agency', $search);
		$this->db->or_like($p . 'version', $search);
		$this->db->or_like($p . 'description', $search);
		if (ctype_digit($search)) {
			$this->db->or_where($p . 'id', (int) $search);
		}
		$this->db->group_end();
	}

	/**
	 * @param string    $alias e.g. 'c.'
	 * @param int|null  $status exact code or null
	 */
	private function _apply_codelist_status_filter($alias, $status)
	{
		if ($status === null) {
			return;
		}
		$p = $alias === '' ? '' : rtrim((string) $alias, '.') . '.';
		$this->db->where($p . 'status', (int) $status);
	}

	/**
	 * All version rows (flat catalogue order).
	 *
	 * @param bool $with_item_count
	 * @return array
	 */
	public function get_all_codelists($with_item_count = false)
	{
		$this->db->order_by('agency', 'ASC');
		$this->db->order_by('name', 'ASC');
		$this->db->order_by('version_seq', 'ASC');
		$this->db->order_by('id', 'ASC');
		$_r = $this->db->get('codelists');
		$rows = $_r ? $_r->result_array() : [];
		if ($with_item_count && !empty($rows)) {
			$this->_attach_item_group_counts($rows, false);
			$this->_attach_dsd_component_counts($rows, false);
		}
		return $rows;
	}

	/**
	 * One row per codelist family (id = pid), with versions_count.
	 *
	 * @param bool $with_item_count
	 * @return array
	 */
	public function get_all_codelists_collapsed($with_item_count = false)
	{
		$sql = "
			SELECT c.*,
				(SELECT COUNT(*) FROM codelists v WHERE v.pid = c.id) AS versions_count
			FROM codelists c
			WHERE c.id = c.pid
			ORDER BY c.agency ASC, c.name ASC
		";
		$_r = $this->db->query($sql);
		$rows = $_r ? $_r->result_array() : [];
		if ($with_item_count && !empty($rows)) {
			$this->_attach_item_group_counts($rows, false);
			$this->_attach_dsd_component_counts($rows, true);
		}
		return $rows;
	}

	/**
	 * Paginated catalogue. Use flat=1 for all version rows; default is collapsed (family heads).
	 *
	 * @param array $options page, per_page, search, with_counts, flat, status (int|null)
	 * @return array{ rows: array, total: int, page: int, per_page: int }
	 */
	public function get_codelists_paged(array $options = [])
	{
		$page = isset($options['page']) ? max(1, (int) $options['page']) : 1;
		$perPage = isset($options['per_page']) ? (int) $options['per_page'] : 50;
		if ($perPage < 1) {
			$perPage = 50;
		}
		if ($perPage > 200) {
			$perPage = 200;
		}
		$offset = ($page - 1) * $perPage;
		$search = isset($options['search']) ? trim((string) $options['search']) : '';
		$withCounts = !empty($options['with_counts']);
		$flat = !empty($options['flat']);
		$status = null;
		if (array_key_exists('status', $options) && $options['status'] !== null && $options['status'] !== '' && $options['status'] !== false) {
			$status = (int) $options['status'];
		}

		if ($flat) {
			return $this->_get_codelists_flat_paged($page, $perPage, $offset, $search, $withCounts, $status);
		}
		return $this->_get_codelists_collapsed_paged($page, $perPage, $offset, $search, $withCounts, $status);
	}

	/**
	 * @return array{ rows: array, total: int, page: int, per_page: int }
	 */
	private function _get_codelists_flat_paged($page, $perPage, $offset, $search, $withCounts, $status)
	{
		$this->db->from('codelists');
		$this->_apply_codelist_search($search, '');
		$this->_apply_codelist_status_filter('', $status);
		$total = (int) $this->db->count_all_results();

		$this->db->from('codelists');
		$this->_apply_codelist_search($search, '');
		$this->_apply_codelist_status_filter('', $status);
		$this->db->order_by('agency', 'ASC');
		$this->db->order_by('name', 'ASC');
		$this->db->order_by('version_seq', 'ASC');
		$this->db->order_by('id', 'ASC');
		$this->db->limit($perPage, $offset);
		$_r = $this->db->get();
		$rows = $_r ? $_r->result_array() : [];

		if ($withCounts && !empty($rows)) {
			$this->_attach_item_group_counts($rows, false);
			$this->_attach_dsd_component_counts($rows, false);
		}

		return [
			'rows'     => $rows,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $perPage,
		];
	}

	/**
	 * @return array{ rows: array, total: int, page: int, per_page: int }
	 */
	private function _get_codelists_collapsed_paged($page, $perPage, $offset, $search, $withCounts, $status)
	{
		$this->db->from('codelists c');
		$this->db->where('c.id = c.pid', null, false);
		$this->_apply_codelist_search($search, 'c.');
		$this->_apply_codelist_status_filter('c.', $status);
		$total = (int) $this->db->count_all_results();

		$this->db->select(
			'c.*, (SELECT COUNT(*) FROM codelists v WHERE v.pid = c.id) AS versions_count',
			false
		);
		$this->db->from('codelists c');
		$this->db->where('c.id = c.pid', null, false);
		$this->_apply_codelist_search($search, 'c.');
		$this->_apply_codelist_status_filter('c.', $status);
		$this->db->order_by('c.agency', 'ASC');
		$this->db->order_by('c.name', 'ASC');
		$this->db->limit($perPage, $offset);
		$_r = $this->db->get();
		$rows = $_r ? $_r->result_array() : [];

		if ($withCounts && !empty($rows)) {
			$this->_attach_item_group_counts($rows, false);
			$this->_attach_dsd_component_counts($rows, true);
		}

		return [
			'rows'     => $rows,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $perPage,
		];
	}

	/**
	 * @param array $rows
	 * @param bool  $include_group_count when false, skips DB work and omits group_count (catalogue list).
	 */
	private function _attach_item_group_counts(array &$rows, $include_group_count = true)
	{
		if (empty($rows)) {
			return;
		}
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['id'];
		}
		$ids = array_values(array_unique(array_filter($ids)));
		$item_map = [];
		$group_map = [];
		if (!empty($ids)) {
			if ($this->db->table_exists('codelist_item')) {
				$this->db->select('codelist_id, COUNT(*) AS cnt', false);
				$this->db->from('codelist_item');
				$this->db->where_in('codelist_id', $ids);
				$this->db->group_by('codelist_id');
				$q = $this->db->get();
				if ($q) {
					foreach ($q->result_array() as $r) {
						$item_map[(int) $r['codelist_id']] = (int) $r['cnt'];
					}
				}
			}
			if ($include_group_count && $this->db->table_exists('codelist_group')) {
				$this->db->select('codelist_id, COUNT(*) AS cnt', false);
				$this->db->from('codelist_group');
				$this->db->where_in('codelist_id', $ids);
				$this->db->group_by('codelist_id');
				$q = $this->db->get();
				if ($q) {
					foreach ($q->result_array() as $r) {
						$group_map[(int) $r['codelist_id']] = (int) $r['cnt'];
					}
				}
			}
		}
		foreach ($rows as &$row) {
			$id = (int) $row['id'];
			$row['item_count'] = isset($item_map[$id]) ? $item_map[$id] : 0;
			if ($include_group_count) {
				$row['group_count'] = isset($group_map[$id]) ? $group_map[$id] : 0;
			} else {
				unset($row['group_count']);
			}
		}
		unset($row);
	}

	/**
	 * Adds dsd_component_count: DSD rows referencing this catalogue row, or (if $by_family) any version with same agency+name.
	 *
	 * @param array $rows
	 * @param bool  $by_family collapsed catalogue rows (aggregate across versions)
	 */
	private function _attach_dsd_component_counts(array &$rows, $by_family)
	{
		if (empty($rows)) {
			return;
		}
		if (!$this->db->table_exists('data_structure_components')) {
			foreach ($rows as &$row) {
				$row['dsd_component_count'] = 0;
			}
			unset($row);
			return;
		}
		if ($by_family) {
			$pairs = [];
			foreach ($rows as $row) {
				$a = trim((string) (isset($row['agency']) ? $row['agency'] : ''));
				$n = trim((string) (isset($row['name']) ? $row['name'] : ''));
				if ($a !== '' && $n !== '') {
					$pairs[$a . "\0" . $n] = ['agency' => $a, 'name' => $n];
				}
			}
			$family_map = [];
			if (!empty($pairs)) {
				$parts = [];
				$bind = [];
				foreach ($pairs as $p) {
					$parts[] = '(c.agency = ? AND c.name = ?)';
					$bind[] = $p['agency'];
					$bind[] = $p['name'];
				}
				$sql = 'SELECT c.agency, c.name, COUNT(*) AS cnt FROM data_structure_components dsc '
					. 'INNER JOIN codelists c ON c.id = dsc.codelist_id WHERE '
					. implode(' OR ', $parts)
					. ' GROUP BY c.agency, c.name';
				$q = $this->db->query($sql, $bind);
				if ($q) {
					foreach ($q->result_array() as $r) {
						$key = trim((string) (isset($r['agency']) ? $r['agency'] : '')) . "\0" . trim((string) (isset($r['name']) ? $r['name'] : ''));
						$family_map[$key] = (int) $r['cnt'];
					}
				}
			}
			foreach ($rows as &$row) {
				$a = trim((string) (isset($row['agency']) ? $row['agency'] : ''));
				$n = trim((string) (isset($row['name']) ? $row['name'] : ''));
				$key = $a . "\0" . $n;
				$row['dsd_component_count'] = isset($family_map[$key]) ? $family_map[$key] : 0;
			}
			unset($row);
			return;
		}
		$ids = [];
		foreach ($rows as $row) {
			$ids[] = (int) $row['id'];
		}
		$ids = array_values(array_unique(array_filter($ids)));
		if (empty($ids)) {
			foreach ($rows as &$row) {
				$row['dsd_component_count'] = 0;
			}
			unset($row);
			return;
		}
		$this->db->select('codelist_id, COUNT(*) AS cnt', false);
		$this->db->from('data_structure_components');
		$this->db->where_in('codelist_id', $ids);
		$this->db->group_by('codelist_id');
		$q = $this->db->get();
		$map = [];
		if ($q) {
			foreach ($q->result_array() as $r) {
				$map[(int) $r['codelist_id']] = (int) $r['cnt'];
			}
		}
		foreach ($rows as &$row) {
			$id = (int) $row['id'];
			$row['dsd_component_count'] = isset($map[$id]) ? $map[$id] : 0;
		}
		unset($row);
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function get_codelist_by_id($id)
	{
		$result = $this->db->get_where('codelists', ['id' => (int) $id]);
		if (!$result) {
			return null;
		}
		$row = $result->row_array();
		return $row ?: null;
	}

	/**
	 * One row by name. When $version is null or '', returns latest by version_seq for (agency, name).
	 *
	 * @param string      $name
	 * @param string|null $agency
	 * @param string|null $version
	 * @return array|null
	 */
	public function get_codelist_by_name($name, $agency = null, $version = null)
	{
		$agency = ($agency === null || $agency === '') ? self::DEFAULT_AGENCY : trim((string) $agency);
		$name   = trim((string) $name);
		if ($name === '') {
			return null;
		}
		$version = $version === null ? null : trim((string) $version);
		if ($version === null || $version === '') {
			$this->db->where(['name' => $name, 'agency' => $agency]);
			$this->db->order_by('version_seq', 'DESC');
			$this->db->order_by('id', 'DESC');
			$this->db->limit(1);
			$result = $this->db->get('codelists');
		} else {
			$result = $this->db->get_where('codelists', [
				'name'    => $name,
				'agency'  => $agency,
				'version' => $version,
			]);
		}
		if (!$result) {
			return null;
		}
		$row = $result->row_array();
		return $row ?: null;
	}

	/**
	 * @param string $idno
	 * @return array|null
	 */
	public function get_codelist_by_idno($idno)
	{
		$idno = trim((string) $idno);
		if ($idno === '') {
			return null;
		}
		$result = $this->db->get_where('codelists', ['idno' => $idno]);
		if (!$result) {
			return null;
		}
		$row = $result->row_array();
		return $row ?: null;
	}

	/**
	 * All version rows for (agency, name), ordered by version_seq.
	 *
	 * @param string      $name
	 * @param string|null $agency
	 * @return array
	 */
	public function get_codelist_versions($name, $agency = null)
	{
		$agency = ($agency === null || $agency === '') ? self::DEFAULT_AGENCY : trim((string) $agency);
		$this->db->where(['name' => trim((string) $name), 'agency' => $agency]);
		$this->db->order_by('version_seq', 'ASC');
		$this->db->order_by('id', 'ASC');
		$_r = $this->db->get('codelists');
		return $_r ? $_r->result_array() : [];
	}

	/**
	 * @param string $agency
	 * @param string $name
	 * @return int
	 */
	public function get_next_version_seq($agency, $name)
	{
		$agency = trim((string) $agency);
		$name   = trim((string) $name);
		$row = $this->db->select_max('version_seq')
			->from('codelists')
			->where('agency', $agency)
			->where('name', $name)
			->get()
			->row_array();
		$max = isset($row['version_seq']) ? (int) $row['version_seq'] : 0;
		return $max + 1;
	}

	/**
	 * @param array $data name (required), agency, version, idno, description, status, version_seq, created, changed
	 * @return int New id
	 * @throws Exception
	 */
	public function create_codelist($data)
	{
		$name = isset($data['name']) ? trim((string) $data['name']) : '';
		if ($name === '') {
			throw new Exception('Codelist name is required.');
		}

		$agency = isset($data['agency']) && trim((string) $data['agency']) !== '' ? trim((string) $data['agency']) : self::DEFAULT_AGENCY;
		$versionRaw = isset($data['version']) && trim((string) $data['version']) !== '' ? trim((string) $data['version']) : self::DEFAULT_VERSION;
		$version = $this->_coerce_version_string($versionRaw);

		if ($this->get_codelist_by_name($name, $agency, $version)) {
			throw new Exception("Codelist already exists for agency '{$agency}', name '{$name}', version '{$version}'.");
		}

		$idno = isset($data['idno']) ? trim((string) $data['idno']) : '';
		if ($idno === '') {
			$idno = self::make_idno($agency, $name, $version);
		}
		if ($this->get_codelist_by_idno($idno)) {
			throw new Exception("Codelist idno '{$idno}' already exists.");
		}

		$status = $this->_normalize_status(isset($data['status']) ? $data['status'] : null);
		$version_seq = array_key_exists('version_seq', $data)
			? (int) $data['version_seq']
			: $this->get_next_version_seq($agency, $name);
		if ($version_seq <= 0) {
			throw new Exception('version_seq must be a positive integer.');
		}
		$_existing_seq = $this->db->get_where('codelists', [
			'agency'      => $agency,
			'name'        => $name,
			'version_seq' => $version_seq,
		]);
		if ($_existing_seq && $_existing_seq->row_array()) {
			throw new Exception("Codelist already exists for agency '{$agency}', name '{$name}', version_seq '{$version_seq}'.");
		}

		$now = time();
		$insert = [
			'pid'         => null,
			'name'        => $name,
			'agency'      => $agency,
			'version'     => $version,
			'version_seq' => $version_seq,
			'idno'        => $idno,
			'description' => isset($data['description']) ? trim((string) $data['description']) : null,
			'status'      => $status,
			'created'     => isset($data['created']) ? (int) $data['created'] : $now,
			'changed'     => isset($data['changed']) ? (int) $data['changed'] : $now,
		];
		if ($insert['description'] === '') {
			$insert['description'] = null;
		}

		$this->db->trans_begin();
		$this->db->insert('codelists', $insert);
		$new_id = (int) $this->db->insert_id();
		$this->db->where(['agency' => $agency, 'name' => $name]);
		$this->db->update('codelists', ['pid' => $new_id]);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			throw new Exception('Failed to create codelist.');
		}
		$this->db->trans_commit();
		return $new_id;
	}

	/**
	 * Mutable fields only: description, idno, status. Bumps `changed`. Identity is fixed per row.
	 *
	 * @param int   $id
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function update_codelist($id, $data)
	{
		$id = (int) $id;
		$existing = $this->get_codelist_by_id($id);
		if (!$existing) {
			throw new Exception('Codelist not found.');
		}
		$is_locked = self::is_locked_status((int) $existing['status']);

		$upd = [];
		if (array_key_exists('status', $data) || array_key_exists('status_code', $data)) {
			$upd['status'] = $this->_normalize_status(array_key_exists('status_code', $data) ? $data['status_code'] : $data['status']);
		}
		if (array_key_exists('description', $data)) {
			if ($is_locked) {
				throw new Exception('Locked codelists only allow status changes.');
			}
			$upd['description'] = trim((string) $data['description']) ?: null;
		}
		if (array_key_exists('idno', $data)) {
			if ($is_locked) {
				throw new Exception('Locked codelists only allow status changes.');
			}
			$new_idno = trim((string) $data['idno']);
			if ($new_idno === '') {
				$new_idno = self::make_idno($existing['agency'], $existing['name'], $existing['version']);
			}
			if ($new_idno !== $existing['idno']) {
				$other_by_idno = $this->get_codelist_by_idno($new_idno);
				if ($other_by_idno && (int) $other_by_idno['id'] !== $id) {
					throw new Exception("Codelist idno '{$new_idno}' already exists.");
				}
				$upd['idno'] = $new_idno;
			}
		}

		if (empty($upd)) {
			return true;
		}
		$upd['changed'] = array_key_exists('changed', $data) ? (int) $data['changed'] : time();
		$this->db->where('id', $id);
		$this->db->update('codelists', $upd);
		return true;
	}

	/**
	 * @param int $id
	 * @return bool
	 * @throws Exception
	 */
	public function delete_codelist($id)
	{
		$id = (int) $id;
		$existing = $this->get_codelist_by_id($id);
		if (!$existing) {
			throw new Exception('Codelist not found.');
		}
		if ((int) $existing['status'] === self::STATUS_PUBLISHED) {
			throw new Exception('Published codelists cannot be deleted.');
		}

		$dsd_refs = $this->count_data_structure_components_by_codelist($id);
		if ($dsd_refs > 0) {
			throw new Exception(
				'This codelist cannot be deleted because it is referenced by '
				. $dsd_refs
				. ' data structure (DSD) component'
				. ($dsd_refs === 1 ? '' : 's')
				. '. Remove the codelist from those components or delete the structures first.'
			);
		}

		$agency = (string) $existing['agency'];
		$name   = (string) $existing['name'];
		$family = $this->db->select('id, version_seq')
			->from('codelists')
			->where('agency', $agency)
			->where('name', $name)
			->order_by('version_seq', 'DESC')
			->order_by('id', 'DESC')
			->get()
			->result_array();

		$this->db->trans_begin();
		if (count($family) > 1) {
			$new_pid = null;
			foreach ($family as $row) {
				if ((int) $row['id'] !== $id) {
					$new_pid = (int) $row['id'];
					break;
				}
			}
			if ($new_pid) {
				$this->db->where('agency', $agency)->where('name', $name)->where('id <>', $id);
				$this->db->update('codelists', ['pid' => $new_pid]);
			}
		}
		$this->db->where('id', $id);
		$this->db->update('codelists', ['pid' => null]);

		$this->db->where('id', $id);
		$this->db->delete('codelists');
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			throw new Exception('Failed to delete codelist.');
		}
		$this->db->trans_commit();
		return true;
	}

	/**
	 * Normalize semver (x.y -> x.y.0), validate semver, or accept a short legacy token (imports).
	 *
	 * @param string $version
	 * @return string
	 * @throws Exception
	 */
	private function _coerce_version_string($version)
	{
		$version = trim((string) $version);
		$normalized = $this->_normalize_semver_input($version);
		if (preg_match(self::VERSION_REGEX, $normalized)) {
			return $normalized;
		}
		if (preg_match('/^[0-9A-Za-z._\\-]{1,32}$/', $version)) {
			return $version;
		}
		throw new Exception("Invalid version '{$version}'. Use semantic version (e.g. 1.2.3) or a short safe token.");
	}

	private function _normalize_semver_input($version)
	{
		$version = trim((string) $version);
		if (preg_match('/^\d+\.\d+$/', $version)) {
			return $version . '.0';
		}
		return $version;
	}

	/**
	 * @param mixed $status
	 * @return int
	 * @throws Exception
	 */
	private function _normalize_status($status)
	{
		if ($status === null || $status === '') {
			return self::STATUS_DRAFT;
		}
		if (!is_numeric($status)) {
			throw new Exception('Invalid status. Use numeric status code.');
		}
		$code = (int) $status;
		if (!in_array($code, self::$allowed_statuses, true)) {
			throw new Exception('Invalid status code.');
		}
		return $code;
	}
}
