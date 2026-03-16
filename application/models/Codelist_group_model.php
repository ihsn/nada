<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Codelist_group_model
 *
 * Manages codelist_group, codelist_group_item, and codelist_group_translation.
 * Caller is responsible for ensuring codelist exists when creating groups.
 */
class Codelist_group_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get all groups for a codelist, with item ids per group and optional group translations.
	 *
	 * @param int $codelist_id
	 * @param bool $with_translations
	 * @return array
	 */
	public function get_groups_by_codelist($codelist_id, $with_translations = true)
	{
		$codelist_id = (int) $codelist_id;
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('id', 'ASC');
		$rows = $this->db->get_where('codelist_group', ['codelist_id' => $codelist_id])->result_array();
		if (empty($rows)) {
			return [];
		}
		$group_ids = array_column($rows, 'id');
		$items_per_group = [];
		$group_items = $this->db->where_in('codelist_group_id', $group_ids)
			->order_by('sort_order')
			->get('codelist_group_item')->result_array();
		foreach ($group_items as $gi) {
			$gid = (int) $gi['codelist_group_id'];
			if (!isset($items_per_group[$gid])) {
				$items_per_group[$gid] = [];
			}
			$items_per_group[$gid][] = (int) $gi['codelist_item_id'];
		}
		foreach ($rows as &$row) {
			$row['item_ids'] = isset($items_per_group[$row['id']]) ? $items_per_group[$row['id']] : [];
			if ($with_translations) {
				$row['translations'] = $this->get_group_translations($row['id']);
			}
		}
		return $rows;
	}

	/**
	 * Get one group by id.
	 *
	 * @param int $group_id
	 * @param bool $with_item_ids
	 * @param bool $with_translations
	 * @return array|null
	 */
	public function get_group_by_id($group_id, $with_item_ids = true, $with_translations = true)
	{
		$row = $this->db->get_where('codelist_group', ['id' => (int) $group_id])->row_array();
		if (!$row) {
			return null;
		}
		if ($with_item_ids) {
			$items = $this->db->order_by('sort_order')
				->get_where('codelist_group_item', ['codelist_group_id' => $row['id']])
				->result_array();
			$row['item_ids'] = array_map(function ($r) { return (int) $r['codelist_item_id']; }, $items);
		}
		if ($with_translations) {
			$row['translations'] = $this->get_group_translations($row['id']);
		}
		return $row;
	}

	/**
	 * Get translations for a group (lang => title).
	 *
	 * @param int $group_id
	 * @return array
	 */
	public function get_group_translations($group_id)
	{
		$rows = $this->db->get_where('codelist_group_translation', ['codelist_group_id' => (int) $group_id])->result_array();
		$out = [];
		foreach ($rows as $r) {
			$out[$r['lang']] = $r['title'];
		}
		return $out;
	}

	/**
	 * Create group. Caller must ensure codelist exists.
	 *
	 * @param int $codelist_id
	 * @param array $data ['name' => string, 'sort_order' => int]
	 * @return int New group id
	 * @throws Exception
	 */
	public function create_group($codelist_id, $data)
	{
		$codelist_id = (int) $codelist_id;
		$name = isset($data['name']) ? trim($data['name']) : '';
		if ($name === '') {
			throw new Exception('Group name is required.');
		}
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
			throw new Exception('Group name may only contain letters, numbers, underscores and dashes.');
		}
		$exists = $this->db->get_where('codelist_group', ['codelist_id' => $codelist_id, 'name' => $name])->row_array();
		if ($exists) {
			throw new Exception('Group name already exists in this codelist.');
		}
		$sort_order = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
		$this->db->insert('codelist_group', [
			'codelist_id' => $codelist_id,
			'name'        => $name,
			'sort_order'  => $sort_order,
		]);
		return (int) $this->db->insert_id();
	}

	/**
	 * Update group.
	 *
	 * @param int $group_id
	 * @param array $data ['name' => string, 'sort_order' => int]
	 * @return bool
	 * @throws Exception
	 */
	public function update_group($group_id, $data)
	{
		$group_id = (int) $group_id;
		$existing = $this->get_group_by_id($group_id, false, false);
		if (!$existing) {
			throw new Exception('Group not found.');
		}
		$upd = [];
		if (array_key_exists('name', $data)) {
			$name = trim($data['name']);
			if ($name === '') {
				throw new Exception('Group name cannot be empty.');
			}
			if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
				throw new Exception('Group name may only contain letters, numbers, underscores and dashes.');
			}
			$other = $this->db->get_where('codelist_group', [
				'codelist_id' => $existing['codelist_id'],
				'name'        => $name,
			])->row_array();
			if ($other && (int) $other['id'] !== $group_id) {
				throw new Exception('Group name already exists in this codelist.');
			}
			$upd['name'] = $name;
		}
		if (array_key_exists('sort_order', $data)) {
			$upd['sort_order'] = (int) $data['sort_order'];
		}
		if (empty($upd)) {
			return true;
		}
		$this->db->where('id', $group_id);
		$this->db->update('codelist_group', $upd);
		return true;
	}

	/**
	 * Delete group.
	 *
	 * @param int $group_id
	 * @return bool
	 * @throws Exception
	 */
	public function delete_group($group_id)
	{
		$group_id = (int) $group_id;
		$existing = $this->get_group_by_id($group_id, false, false);
		if (!$existing) {
			throw new Exception('Group not found.');
		}
		$this->db->where('id', $group_id);
		$this->db->delete('codelist_group');
		return true;
	}

	/**
	 * Add item to group. Validates that item belongs to the same codelist as the group.
	 *
	 * @param int $group_id
	 * @param int $codelist_item_id
	 * @param int $sort_order
	 * @return void
	 * @throws Exception
	 */
	public function add_group_item($group_id, $codelist_item_id, $sort_order = 0)
	{
		$group_id = (int) $group_id;
		$codelist_item_id = (int) $codelist_item_id;
		$existing = $this->get_group_by_id($group_id, true, false);
		if (!$existing) {
			throw new Exception('Group not found.');
		}
		$this->load->model('Codelist_item_model');
		$item = $this->Codelist_item_model->get_item_by_id($codelist_item_id, false);
		if (!$item || (int) $item['codelist_id'] !== (int) $existing['codelist_id']) {
			throw new Exception('Item not found or does not belong to this codelist.');
		}
		$exists = $this->db->get_where('codelist_group_item', [
			'codelist_group_id' => $group_id,
			'codelist_item_id'  => $codelist_item_id,
		])->row_array();
		if ($exists) {
			return;
		}
		$this->db->insert('codelist_group_item', [
			'codelist_group_id' => $group_id,
			'codelist_item_id'  => $codelist_item_id,
			'sort_order'        => $sort_order,
		]);
	}

	/**
	 * Remove item from group.
	 *
	 * @param int $group_id
	 * @param int $codelist_item_id
	 * @return void
	 */
	public function remove_group_item($group_id, $codelist_item_id)
	{
		$this->db->where([
			'codelist_group_id' => (int) $group_id,
			'codelist_item_id'  => (int) $codelist_item_id,
		]);
		$this->db->delete('codelist_group_item');
	}

	/**
	 * Set (add or update) one translation for a group.
	 *
	 * @param int $group_id
	 * @param string $lang
	 * @param string $title
	 * @return void
	 * @throws Exception
	 */
	public function set_group_translation($group_id, $lang, $title)
	{
		$group_id = (int) $group_id;
		$existing = $this->get_group_by_id($group_id, false, false);
		if (!$existing) {
			throw new Exception('Group not found.');
		}
		$lang = trim($lang);
		$title = trim($title);
		if ($lang === '') {
			throw new Exception('Language code is required.');
		}
		$row = $this->db->get_where('codelist_group_translation', ['codelist_group_id' => $group_id, 'lang' => $lang])->row_array();
		if ($row) {
			$this->db->where('id', $row['id']);
			$this->db->update('codelist_group_translation', ['title' => $title]);
		} else {
			$this->db->insert('codelist_group_translation', [
				'codelist_group_id' => $group_id,
				'lang'              => $lang,
				'title'             => $title,
			]);
		}
	}

	/**
	 * Delete one group translation.
	 *
	 * @param int $group_id
	 * @param string $lang
	 * @return void
	 */
	public function delete_group_translation($group_id, $lang)
	{
		$this->db->where(['codelist_group_id' => (int) $group_id, 'lang' => $lang]);
		$this->db->delete('codelist_group_translation');
	}
}
