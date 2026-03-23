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
