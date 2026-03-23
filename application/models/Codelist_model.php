<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Codelist_model
 *
 * Manages the codelists table only (id, name, description).
 * Items and groups are in Codelist_item_model and Codelist_group_model.
 */
class Codelist_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get all codelists (id, name, description). Optionally with item_count and group_count.
	 *
	 * @param bool $with_item_count
	 * @return array
	 */
	public function get_all_codelists($with_item_count = false)
	{
		$this->db->order_by('name', 'ASC');
		$_r = $this->db->get('codelists');
		$rows = $_r ? $_r->result_array() : [];
		if ($with_item_count && !empty($rows)) {
			$has_item_table  = $this->db->table_exists('codelist_item');
			$has_group_table = $this->db->table_exists('codelist_group');
			foreach ($rows as &$row) {
				$row['item_count']  = $has_item_table
					? (int) $this->db->where('codelist_id', $row['id'])->count_all_results('codelist_item')
					: 0;
				$row['group_count'] = $has_group_table
					? (int) $this->db->where('codelist_id', $row['id'])->count_all_results('codelist_group')
					: 0;
			}
		}
		return $rows;
	}

	/**
	 * Get one codelist by id.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function get_codelist_by_id($id)
	{
		$result = $this->db->get_where('codelists', ['id' => (int) $id]);
		if (!$result) { return null; }
		$row = $result->row_array();
		return $row ?: null;
	}

	/**
	 * Get one codelist by name.
	 *
	 * @param string $name
	 * @return array|null
	 */
	public function get_codelist_by_name($name)
	{
		$result = $this->db->get_where('codelists', ['name' => $name]);
		if (!$result) { return null; }
		$row = $result->row_array();
		return $row ?: null;
	}

	/**
	 * Create codelist.
	 *
	 * @param array $data ['name' => string, 'description' => string|null]
	 * @return int New id
	 * @throws Exception
	 */
	public function create_codelist($data)
	{
		$name = isset($data['name']) ? trim($data['name']) : '';
		if ($name === '') {
			throw new Exception('Codelist name is required.');
		}
		if ($this->get_codelist_by_name($name)) {
			throw new Exception('Codelist name already exists.');
		}
		$this->db->insert('codelists', [
			'name'        => $name,
			'description' => isset($data['description']) ? trim($data['description']) : null,
		]);
		return (int) $this->db->insert_id();
	}

	/**
	 * Update codelist.
	 *
	 * @param int $id
	 * @param array $data ['name' => string, 'description' => string|null]
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
		$upd = [];
		if (array_key_exists('name', $data)) {
			$name = trim($data['name']);
			if ($name === '') {
				throw new Exception('Codelist name cannot be empty.');
			}
			$other = $this->get_codelist_by_name($name);
			if ($other && (int) $other['id'] !== $id) {
				throw new Exception('Codelist name already exists.');
			}
			$upd['name'] = $name;
		}
		if (array_key_exists('description', $data)) {
			$upd['description'] = trim($data['description']) ?: null;
		}
		if (empty($upd)) {
			return true;
		}
		$this->db->where('id', $id);
		$this->db->update('codelists', $upd);
		return true;
	}

	/**
	 * Delete codelist (items, groups, translations removed by FK CASCADE).
	 *
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
		$this->db->where('id', $id);
		$this->db->delete('codelists');
		return true;
	}
}
