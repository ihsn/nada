<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Codelist_item_model
 *
 * Manages codelist_item and codelist_item_translation.
 * Caller is responsible for ensuring codelist exists when creating items.
 */
class Codelist_item_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get all items for a codelist, optionally with translations (lang => title).
	 *
	 * @param int $codelist_id
	 * @param bool $with_translations
	 * @return array
	 */
	public function get_items_by_codelist($codelist_id, $with_translations = true)
	{
		$codelist_id = (int) $codelist_id;
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('id', 'ASC');
		$_r = $this->db->get_where('codelist_item', ['codelist_id' => $codelist_id]);
		$rows = $_r ? $_r->result_array() : [];
		if ($with_translations && !empty($rows)) {
			$ids = array_column($rows, 'id');
			$trans = $this->get_item_translations_bulk($ids);
			foreach ($rows as &$row) {
				$row['translations'] = isset($trans[$row['id']]) ? $trans[$row['id']] : [];
			}
		}
		return $rows;
	}

	/**
	 * Count items for a codelist (no filter).
	 *
	 * @param int $codelist_id
	 * @return int
	 */
	public function count_items_by_codelist($codelist_id)
	{
		$codelist_id = (int) $codelist_id;
		if ($codelist_id <= 0) {
			return 0;
		}
		$this->db->where('codelist_id', $codelist_id);
		return (int) $this->db->count_all_results('codelist_item');
	}

	/**
	 * Optional text filter on item rows (code, title).
	 *
	 * @param string $search trimmed; empty skips
	 */
	private function _apply_codelist_item_list_search($search)
	{
		$search = trim((string) $search);
		if ($search === '') {
			return;
		}
		$this->db->group_start();
		$this->db->like('code', $search);
		$this->db->or_like('title', $search);
		$this->db->group_end();
	}

	/**
	 * Columns the paginated admin list may be sorted by.
	 *
	 * @return string[]
	 */
	public static function list_sort_fields()
	{
		return ['id', 'code', 'title', 'sort_order'];
	}

	/**
	 * Paginated items for one codelist (flat rows from codelist_item only).
	 *
	 * @param int   $codelist_id
	 * @param array $options page, per_page, search, with_translations (default false),
	 *                       sort_by (id|code|title|sort_order), sort_order (asc|desc)
	 * @return array{ rows: array, total: int, page: int, per_page: int }
	 */
	public function get_items_by_codelist_paged($codelist_id, array $options = [])
	{
		$codelist_id = (int) $codelist_id;
		if ($codelist_id <= 0) {
			return ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 50];
		}
		$page = isset($options['page']) ? max(1, (int) $options['page']) : 1;
		$perPage = isset($options['per_page']) ? (int) $options['per_page'] : 50;
		if ($perPage < 1) {
			$perPage = 50;
		}
		if ($perPage > 200) {
			$perPage = 200;
		}
		$search = isset($options['search']) ? trim((string) $options['search']) : '';
		$withTranslations = !empty($options['with_translations']);

		$this->db->from('codelist_item');
		$this->db->where('codelist_id', $codelist_id);
		$this->_apply_codelist_item_list_search($search);
		$total = (int) $this->db->count_all_results();

		$this->db->from('codelist_item');
		$this->db->where('codelist_id', $codelist_id);
		$this->_apply_codelist_item_list_search($search);
		$sortBy = isset($options['sort_by']) ? strtolower(trim((string) $options['sort_by'])) : '';
		$sortDir = (isset($options['sort_order']) && strtolower((string) $options['sort_order']) === 'desc') ? 'DESC' : 'ASC';
		if ($sortBy !== '' && in_array($sortBy, self::list_sort_fields(), true)) {
			$this->db->order_by($sortBy, $sortDir);
			if ($sortBy !== 'id') {
				$this->db->order_by('id', 'ASC');
			}
		} else {
			$this->db->order_by('sort_order', 'ASC');
			$this->db->order_by('id', 'ASC');
		}
		$offset = ($page - 1) * $perPage;
		$this->db->limit($perPage, $offset);
		$_r = $this->db->get();
		$rows = $_r ? $_r->result_array() : [];

		if ($withTranslations && !empty($rows)) {
			$ids = array_column($rows, 'id');
			$trans = $this->get_item_translations_bulk($ids);
			foreach ($rows as &$row) {
				$row['translations'] = isset($trans[$row['id']]) ? $trans[$row['id']] : [];
			}
			unset($row);
		}

		return [
			'rows'     => $rows,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $perPage,
		];
	}

	/**
	 * Return item metadata keyed by code for one codelist, restricted to a code subset.
	 *
	 * @param int   $codelist_id
	 * @param array $codes
	 * @return array<string,array{code:string,title:?string,sort_order:int,parent_id:?int}>
	 */
	public function get_items_map_by_codes($codelist_id, array $codes)
	{
		$codelist_id = (int) $codelist_id;
		if ($codelist_id <= 0 || empty($codes)) {
			return [];
		}
		$normalized = [];
		foreach ($codes as $code) {
			$s = trim((string) $code);
			if ($s !== '') {
				$normalized[$s] = true;
			}
		}
		$codes = array_keys($normalized);
		if (empty($codes)) {
			return [];
		}

		$out = [];
		foreach (array_chunk($codes, 500) as $chunk) {
			$this->db->select('code, title, sort_order, parent_id');
			$this->db->from('codelist_item');
			$this->db->where('codelist_id', $codelist_id);
			$this->db->where_in('code', $chunk);
			$_r = $this->db->get();
			$rows = $_r ? $_r->result_array() : [];
			foreach ($rows as $row) {
				$code = isset($row['code']) ? (string) $row['code'] : '';
				if ($code === '') {
					continue;
				}
				$out[$code] = [
					'code' => $code,
					'title' => isset($row['title']) ? $row['title'] : null,
					'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
					'parent_id' => isset($row['parent_id']) && $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
				];
			}
		}
		return $out;
	}

	/**
	 * Get one item by id, optionally with translations.
	 *
	 * @param int $item_id
	 * @param bool $with_translations
	 * @return array|null
	 */
	public function get_item_by_id($item_id, $with_translations = true)
	{
		$_r = $this->db->get_where('codelist_item', ['id' => (int) $item_id]);
		$row = $_r ? $_r->row_array() : null;
		if (!$row) {
			return null;
		}
		if ($with_translations) {
			$row['translations'] = $this->get_item_translations($item_id);
		}
		return $row;
	}

	/**
	 * Get translations for one item (lang => title).
	 *
	 * @param int $item_id
	 * @return array
	 */
	public function get_item_translations($item_id)
	{
		$_r = $this->db->get_where('codelist_item_translation', ['codelist_item_id' => (int) $item_id]);
		$rows = $_r ? $_r->result_array() : [];
		$out = [];
		foreach ($rows as $r) {
			$out[$r['lang']] = $r['title'];
		}
		return $out;
	}

	/**
	 * Get translations for multiple items. Returns [item_id => [lang => title]].
	 *
	 * @param array $item_ids
	 * @return array
	 */
	public function get_item_translations_bulk($item_ids)
	{
		if (empty($item_ids)) {
			return [];
		}
		$item_ids = array_map('intval', $item_ids);
		$this->db->where_in('codelist_item_id', $item_ids);
		$_r = $this->db->get('codelist_item_translation');
		$rows = $_r ? $_r->result_array() : [];
		$out = [];
		foreach ($rows as $r) {
			$id = (int) $r['codelist_item_id'];
			if (!isset($out[$id])) {
				$out[$id] = [];
			}
			$out[$id][$r['lang']] = $r['title'];
		}
		return $out;
	}

	/**
	 * Create codelist item. Caller must ensure codelist exists.
	 *
	 * @param int $codelist_id
	 * @param array $data ['code' => string, 'title' => string|null, 'parent_id' => int|null, 'sort_order' => int]
	 * @return int New item id
	 * @throws Exception
	 */
	public function create_item($codelist_id, $data)
	{
		$codelist_id = (int) $codelist_id;
		$code = isset($data['code']) ? trim($data['code']) : '';
		if ($code === '') {
			throw new Exception('Item code is required.');
		}
		$_r = $this->db->get_where('codelist_item', ['codelist_id' => $codelist_id, 'code' => $code]);
		$exists = $_r ? $_r->row_array() : null;
		if ($exists) {
			throw new Exception('Item code already exists in this codelist.');
		}
		$parent_id = isset($data['parent_id']) && $data['parent_id'] !== '' && $data['parent_id'] !== null
			? (int) $data['parent_id'] : null;
		$sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;

		$this->db->insert('codelist_item', [
			'codelist_id' => $codelist_id,
			'parent_id'   => $parent_id,
			'code'        => $code,
			'title'       => isset($data['title']) ? trim($data['title']) : null,
			'sort_order'  => $sort_order,
		]);
		return (int) $this->db->insert_id();
	}

	/**
	 * Update codelist item.
	 *
	 * @param int $item_id
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function update_item($item_id, $data)
	{
		$item_id = (int) $item_id;
		$existing = $this->get_item_by_id($item_id, false);
		if (!$existing) {
			throw new Exception('Item not found.');
		}
		$upd = [];
		if (array_key_exists('code', $data)) {
			$code = trim($data['code']);
			if ($code === '') {
				throw new Exception('Item code cannot be empty.');
			}
			$_r = $this->db->get_where('codelist_item', [
				'codelist_id' => $existing['codelist_id'],
				'code'        => $code,
			]);
			$other = $_r ? $_r->row_array() : null;
			if ($other && (int) $other['id'] !== $item_id) {
				throw new Exception('Item code already exists in this codelist.');
			}
			$upd['code'] = $code;
		}
		if (array_key_exists('title', $data)) {
			$upd['title'] = trim($data['title']) ?: null;
		}
		if (array_key_exists('parent_id', $data)) {
			$upd['parent_id'] = ($data['parent_id'] === '' || $data['parent_id'] === null) ? null : (int) $data['parent_id'];
		}
		if (array_key_exists('sort_order', $data)) {
			$upd['sort_order'] = (int) $data['sort_order'];
		}
		if (empty($upd)) {
			return true;
		}
		$this->db->where('id', $item_id);
		$this->db->update('codelist_item', $upd);
		return true;
	}

	/**
	 * Renumber sort_order for all items of a codelist by code or title (natural, case-insensitive).
	 *
	 * @param int    $codelist_id
	 * @param string $by    code|title (empty titles fall back to code)
	 * @param string $order asc|desc
	 * @param int    $step  increment between items (>= 1)
	 * @return int number of rows whose sort_order changed
	 */
	public function resequence_items($codelist_id, $by = 'code', $order = 'asc', $step = 10)
	{
		$codelist_id = (int) $codelist_id;
		$by = $by === 'title' ? 'title' : 'code';
		$desc = strtolower((string) $order) === 'desc';
		$step = max(1, (int) $step);

		$_r = $this->db->select('id, code, title, sort_order')
			->from('codelist_item')->where('codelist_id', $codelist_id)->get();
		$rows = $_r ? $_r->result_array() : [];

		usort($rows, function ($a, $b) use ($by, $desc) {
			$ka = $by === 'title' && trim((string) $a['title']) !== '' ? (string) $a['title'] : (string) $a['code'];
			$kb = $by === 'title' && trim((string) $b['title']) !== '' ? (string) $b['title'] : (string) $b['code'];
			$c = strnatcasecmp($ka, $kb);
			if ($c === 0) {
				$c = strnatcasecmp((string) $a['code'], (string) $b['code']);
			}
			return $desc ? -$c : $c;
		});

		$changed = 0;
		$this->db->trans_begin();
		try {
			foreach ($rows as $i => $row) {
				$new = ($i + 1) * $step;
				if ((int) $row['sort_order'] !== $new) {
					$this->db->where('id', (int) $row['id'])->update('codelist_item', ['sort_order' => $new]);
					$changed++;
				}
			}
			if ($this->db->trans_status() === false) {
				throw new Exception('Database transaction failed.');
			}
			$this->db->trans_commit();
		} catch (Exception $e) {
			$this->db->trans_rollback();
			throw $e;
		}
		return $changed;
	}

	/**
	 * Batch update items of one codelist, matched by code. Existing items keep their id,
	 * translations and group memberships; unknown codes are created.
	 *
	 * Each row: code (required), title, sort_order, new_code (rename). Keys that are absent
	 * are left unchanged on existing items.
	 *
	 * @param int   $codelist_id
	 * @param array $rows
	 * @param array $options delete_missing (bool) — delete items whose code is not in $rows;
	 *                       dry_run (bool) — compute the summary only
	 * @return array{created:int, updated:int, unchanged:int, deleted:int, errors:array}
	 * @throws Exception on validation errors (nothing written)
	 */
	public function batch_upsert_items($codelist_id, array $rows, array $options = [])
	{
		$codelist_id = (int) $codelist_id;
		$deleteMissing = !empty($options['delete_missing']);
		$dryRun = !empty($options['dry_run']);

		$_r = $this->db->select('id, code, title, sort_order')
			->from('codelist_item')->where('codelist_id', $codelist_id)->get();
		$existing = [];
		foreach (($_r ? $_r->result_array() : []) as $row) {
			$existing[(string) $row['code']] = $row;
		}

		// Validate and normalise
		$errors = [];
		$seen = [];
		$plan = [];
		foreach (array_values($rows) as $i => $row) {
			$line = $i + 1;
			if (!is_array($row)) {
				$errors[] = "Row {$line}: must be an object.";
				continue;
			}
			$code = isset($row['code']) ? trim((string) $row['code']) : '';
			if ($code === '') {
				$errors[] = "Row {$line}: code is required.";
				continue;
			}
			if (strlen($code) > 64) {
				$errors[] = "Row {$line}: code \"{$code}\" exceeds 64 characters.";
				continue;
			}
			if (isset($seen[$code])) {
				$errors[] = "Row {$line}: duplicate code \"{$code}\" (also row {$seen[$code]}).";
				continue;
			}
			$seen[$code] = $line;
			$p = ['code' => $code, 'line' => $line];
			if (array_key_exists('title', $row)) {
				$t = $row['title'] === null ? '' : trim((string) $row['title']);
				$p['title'] = $t === '' ? null : $t;
			}
			if (array_key_exists('sort_order', $row) && $row['sort_order'] !== '' && $row['sort_order'] !== null) {
				if (!is_numeric($row['sort_order'])) {
					$errors[] = "Row {$line}: sort_order must be a number.";
					continue;
				}
				$p['sort_order'] = (int) $row['sort_order'];
			}
			if (isset($row['new_code']) && trim((string) $row['new_code']) !== '' && trim((string) $row['new_code']) !== $code) {
				$p['new_code'] = trim((string) $row['new_code']);
				if (strlen($p['new_code']) > 64) {
					$errors[] = "Row {$line}: new_code exceeds 64 characters.";
					continue;
				}
			}
			$plan[] = $p;
		}

		// Renames must not collide with codes that remain in the final list
		$finalCodes = [];
		foreach ($plan as $p) {
			$final = isset($p['new_code']) ? $p['new_code'] : $p['code'];
			if (isset($finalCodes[$final])) {
				$errors[] = "Row {$p['line']}: resulting code \"{$final}\" is used more than once.";
			}
			$finalCodes[$final] = true;
		}
		if (!$deleteMissing) {
			foreach ($plan as $p) {
				if (isset($p['new_code']) && isset($existing[$p['new_code']]) && !isset($seen[$p['new_code']])) {
					$errors[] = "Row {$p['line']}: cannot rename to \"{$p['new_code']}\"; that code already exists.";
				}
			}
		}
		if (!empty($errors)) {
			return ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'errors' => $errors];
		}

		$summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'errors' => []];
		$toDelete = [];
		if ($deleteMissing) {
			foreach ($existing as $code => $row) {
				if (!isset($seen[$code])) {
					$toDelete[] = (int) $row['id'];
				}
			}
		}
		$summary['deleted'] = count($toDelete);

		$creates = [];
		$updates = [];
		foreach ($plan as $p) {
			if (!isset($existing[$p['code']])) {
				$creates[] = [
					'codelist_id' => $codelist_id,
					'parent_id'   => null,
					'code'        => isset($p['new_code']) ? $p['new_code'] : $p['code'],
					'title'       => array_key_exists('title', $p) ? $p['title'] : null,
					'sort_order'  => isset($p['sort_order']) ? $p['sort_order'] : 0,
				];
				continue;
			}
			$cur = $existing[$p['code']];
			$upd = [];
			if (array_key_exists('title', $p) && (string) $p['title'] !== (string) $cur['title']) {
				$upd['title'] = $p['title'];
			}
			if (isset($p['sort_order']) && (int) $p['sort_order'] !== (int) $cur['sort_order']) {
				$upd['sort_order'] = $p['sort_order'];
			}
			if (isset($p['new_code'])) {
				$upd['code'] = $p['new_code'];
			}
			if (empty($upd)) {
				$summary['unchanged']++;
			} else {
				$updates[(int) $cur['id']] = $upd;
			}
		}
		$summary['created'] = count($creates);
		$summary['updated'] = count($updates);

		if ($dryRun) {
			return $summary;
		}

		$this->db->trans_begin();
		try {
			if (!empty($toDelete)) {
				foreach (array_chunk($toDelete, 500) as $chunk) {
					$this->db->where_in('codelist_item_id', $chunk)->delete('codelist_item_translation');
					$this->db->where_in('codelist_item_id', $chunk)->delete('codelist_group_item');
					$this->db->where_in('id', $chunk)->delete('codelist_item');
				}
			}
			// Renames: move to a temporary code first so swaps (A->B, B->A) don't hit the unique key
			$renamed = [];
			foreach ($updates as $id => $upd) {
				if (isset($upd['code'])) {
					$this->db->where('id', $id)->update('codelist_item', ['code' => '__tmp_' . $id]);
					$renamed[$id] = true;
				}
			}
			foreach ($updates as $id => $upd) {
				$this->db->where('id', $id)->update('codelist_item', $upd);
			}
			foreach (array_chunk($creates, 500) as $chunk) {
				$this->db->insert_batch('codelist_item', $chunk);
			}
			if ($this->db->trans_status() === false) {
				throw new Exception('Database transaction failed.');
			}
			$this->db->trans_commit();
		} catch (Exception $e) {
			$this->db->trans_rollback();
			throw $e;
		}
		return $summary;
	}

	/**
	 * Remove all items (and their translations) for a codelist — e.g. before overwrite import.
	 *
	 * @param int $codelist_id
	 * @return void
	 */
	public function delete_all_items_for_codelist($codelist_id)
	{
		$codelist_id = (int) $codelist_id;
		if ($codelist_id <= 0) {
			return;
		}
		$q = $this->db->select('id')->from('codelist_item')->where('codelist_id', $codelist_id)->get();
		if (!$q) {
			return;
		}
		$rows = $q->result_array();
		if (empty($rows)) {
			return;
		}
		$ids = array_column($rows, 'id');
		$this->db->where_in('codelist_item_id', $ids)->delete('codelist_item_translation');
		$this->db->where('codelist_id', $codelist_id)->delete('codelist_item');
	}

	/**
	 * Delete codelist item.
	 *
	 * @param int $item_id
	 * @return bool
	 * @throws Exception
	 */
	public function delete_item($item_id)
	{
		$item_id = (int) $item_id;
		$existing = $this->get_item_by_id($item_id, false);
		if (!$existing) {
			throw new Exception('Item not found.');
		}
		$this->db->where('id', $item_id);
		$this->db->delete('codelist_item');
		return true;
	}

	/**
	 * Set (add or update) one translation for an item.
	 *
	 * @param int $item_id
	 * @param string $lang
	 * @param string $title
	 * @return void
	 * @throws Exception
	 */
	public function set_item_translation($item_id, $lang, $title)
	{
		$item_id = (int) $item_id;
		$existing = $this->get_item_by_id($item_id, false);
		if (!$existing) {
			throw new Exception('Item not found.');
		}
		$lang = trim($lang);
		$title = trim($title);
		if ($lang === '') {
			throw new Exception('Language code is required.');
		}
		$_r = $this->db->get_where('codelist_item_translation', ['codelist_item_id' => $item_id, 'lang' => $lang]);
		$row = $_r ? $_r->row_array() : null;
		if ($row) {
			$this->db->where('id', $row['id']);
			$this->db->update('codelist_item_translation', ['title' => $title]);
		} else {
			$this->db->insert('codelist_item_translation', [
				'codelist_item_id' => $item_id,
				'lang'             => $lang,
				'title'            => $title,
			]);
		}
	}

	/**
	 * Delete one item translation.
	 *
	 * @param int $item_id
	 * @param string $lang
	 * @return void
	 */
	public function delete_item_translation($item_id, $lang)
	{
		$this->db->where(['codelist_item_id' => (int) $item_id, 'lang' => $lang]);
		$this->db->delete('codelist_item_translation');
	}
}
