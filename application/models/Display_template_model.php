<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Display_template_model extends CI_Model {

	private $table = 'display_templates';
	private $table_defaults = 'display_templates_default';
	private $core_templates = array();
	private $display_template_defaults = array();

	public function __construct()
	{
		parent::__construct();
		$this->init_core_templates();
	}

	public function get_all_templates($data_type = null, $status = null)
	{
		$this->db->from($this->table);
		$this->db->where('is_deleted', 0);
		if ($data_type) {
			$this->db->where('data_type', $data_type);
		}
		if ($status) {
			$this->db->where('status', $status);
		}
		$this->db->order_by('name', 'ASC');
		$this->db->order_by('updated_at', 'DESC');
		$rows = $this->db->get()->result_array();
		foreach ($rows as $idx => $row) {
			$rows[$idx] = $this->decode_row($row);
		}

		$defaults = $this->get_defaults_map();
		foreach ($rows as $idx => $row) {
			$rows[$idx]['default'] = isset($defaults[$row['data_type']]) && $defaults[$row['data_type']] === $row['uid'];
		}

		$core_rows = $this->get_core_template_rows($data_type, $status, $defaults);
		return array_merge($core_rows, $rows);
	}

	public function get_template_by_uid($uid)
	{
		$core = $this->get_core_template_by_uid($uid);
		if ($core) {
			$core['template_json'] = $this->get_core_template_json($uid);
			return $core;
		}

		$this->db->from($this->table);
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		return $this->decode_row($this->db->get()->row_array());
	}

	public function is_core_template_uid($uid)
	{
		return (bool) $this->get_core_template_by_uid($uid);
	}

	public function uid_exists($uid)
	{
		$uid = trim((string) $uid);
		if ($uid === '') {
			return false;
		}
		$this->db->select('uid');
		$this->db->from($this->table);
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		$row = $this->db->get()->row_array();
		return !empty($row['uid']);
	}

	public function create_template($options)
	{
		$data = $this->normalize_options($options, true);
		$this->db->insert($this->table, $data);
		return $this->inserted_template($data['uid']);
	}

	public function update_template($uid, $options)
	{
		$data = $this->normalize_options($options, false);
		if (empty($data)) {
			return $this->get_template_by_uid($uid);
		}
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		$this->db->update($this->table, $data);
		return $this->get_template_by_uid($uid);
	}

	public function delete_template($uid, $user_id = null)
	{
		$template = $this->get_template_by_uid($uid);
		if (!$template) {
			return false;
		}
		$this->db->where('template_uid', $uid)->delete($this->table_defaults);
		$this->db->where('uid', $uid);
		return $this->db->delete($this->table);
	}

	public function duplicate_template($uid, $user_id = null)
	{
		$template = $this->get_template_by_uid($uid);
		if (!$template) {
			throw new Exception('Template not found');
		}
		$template['uid'] = md5($template['data_type'] . '-' . mt_rand() . '-' . microtime(true));
		$template['name'] = $template['name'] . ' - copy';
		$template['template_type'] = 'custom';
		$template['status'] = 'draft';
		$template['created_by'] = $user_id ? (int) $user_id : null;
		$template['changed_by'] = $user_id ? (int) $user_id : null;
		unset($template['id'], $template['default'], $template['created_at'], $template['updated_at'], $template['is_deleted']);
		return $this->create_template($template);
	}

	public function set_default_template($data_type, $template_uid, $user_id = null)
	{
		$this->db->where('data_type', $data_type)->delete($this->table_defaults);
		$data = array(
			'data_type' => $data_type,
			'template_uid' => $template_uid,
			'created_by' => $user_id ? (int) $user_id : null,
			'updated_by' => $user_id ? (int) $user_id : null,
		);
		$this->db->insert($this->table_defaults, $data);
		return $this->get_default_template($data_type);
	}

	public function get_default_template($data_type)
	{
		return $this->db->from($this->table_defaults)->where('data_type', $data_type)->get()->row_array();
	}

	public function get_defaults_map()
	{
		$rows = $this->db->get($this->table_defaults)->result_array();
		$out = array();
		foreach ($rows as $row) {
			$out[$row['data_type']] = $row['template_uid'];
		}
		return $out;
	}

	private function inserted_template($uid)
	{
		return $this->get_template_by_uid($uid);
	}

	private function decode_row($row)
	{
		if (!$row) {
			return null;
		}
		if (isset($row['template_json']) && is_string($row['template_json'])) {
			$decoded = json_decode($row['template_json'], true);
			$row['template_json'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : array();
		}
		return $row;
	}

	private function init_core_templates()
	{
		require_once APPPATH . 'config/display_templates.php';

		if (!isset($config)) {
			throw new Exception('config/display_templates not loaded');
		}

		$meta_keys = isset($config['display_template_meta_keys'])
			? $config['display_template_meta_keys']
			: array(
				'display_template_path',
				'display_template_custom_path',
				'display_template_defaults',
				'display_template_meta_keys',
				'legacy_study_templates',
			);

		if (isset($config['display_template_defaults']) && is_array($config['display_template_defaults'])) {
			$this->display_template_defaults = $config['display_template_defaults'];
		}

		foreach ($config as $key => $templates) {
			if (in_array($key, $meta_keys, true) || !is_array($templates)) {
				continue;
			}

			foreach ($templates as $template) {
				if (!is_array($template) || empty($template['uid']) || empty($template['file'])) {
					continue;
				}

				$relative_file = ltrim(str_replace('\\', '/', $template['file']), '/');
				$this->core_templates[] = array(
					'uid' => $template['uid'],
					'template_type' => 'system',
					'name' => isset($template['name']) ? $template['name'] : $template['uid'],
					'data_type' => $key,
					'lang' => isset($template['lang']) ? $template['lang'] : 'en',
					'version' => isset($template['version']) ? $template['version'] : null,
					'organization' => isset($template['organization']) ? $template['organization'] : null,
					'author' => isset($template['author']) ? $template['author'] : null,
					'description' => isset($template['description']) ? $template['description'] : null,
					'file' => $relative_file,
					'status' => 'published',
					'is_core' => true,
				);
			}
		}
	}

	public function resolve_core_template_path($relative_file)
	{
		if ($relative_file === '' || $relative_file === null) {
			return null;
		}

		$relative_file = ltrim(str_replace('\\', '/', $relative_file), '/');
		$basename = basename($relative_file);
		$dir = dirname($relative_file);
		$custom_dir = ($dir === '.' || $dir === '') ? 'custom' : $dir . '/custom';
		$custom_path = APPPATH . $custom_dir . '/' . $basename;
		$core_path = APPPATH . $relative_file;

		if (is_file($custom_path)) {
			return $custom_path;
		}

		if (is_file($core_path)) {
			return $core_path;
		}

		return null;
	}

	public function get_core_template_by_uid($uid)
	{
		foreach ($this->core_templates as $template) {
			if ($template['uid'] === $uid) {
				return $template;
			}
		}
		return null;
	}

	public function get_core_templates_by_type($data_type = null)
	{
		if ($data_type === null || $data_type === '') {
			return $this->core_templates;
		}

		$out = array();
		foreach ($this->core_templates as $template) {
			if ($this->data_type_matches($data_type, $template['data_type'])) {
				$out[] = $template;
			}
		}
		return $out;
	}

	public function get_default_core_template_uid($data_type)
	{
		$lookup_types = $this->expand_data_type_aliases($data_type);
		foreach ($lookup_types as $lookup) {
			if (isset($this->display_template_defaults[$lookup])) {
				return $this->display_template_defaults[$lookup];
			}
		}

		$cores = $this->get_core_templates_by_type($data_type);
		if (!empty($cores[0]['uid'])) {
			return $cores[0]['uid'];
		}

		return null;
	}

	public function get_default_core_template($data_type)
	{
		$uid = $this->get_default_core_template_uid($data_type);
		if (!$uid) {
			return null;
		}
		return $this->get_template_by_uid($uid);
	}

	public function get_core_template_json($uid)
	{
		foreach ($this->core_templates as $template) {
			if ($template['uid'] !== $uid) {
				continue;
			}

			$template_path = $this->resolve_core_template_path($template['file']);
			if (!$template_path) {
				throw new Exception('Core display template not found: ' . $template['file']);
			}

			$json = json_decode(file_get_contents($template_path), true);
			if (!is_array($json)) {
				throw new Exception('Invalid core display template JSON: ' . $template['file']);
			}

			return $this->normalize_core_template_json($json);
		}

		return null;
	}

	public function get_template_parts($template_json)
	{
		$items = array();
		if (is_array($template_json)) {
			if (isset($template_json['items']) && is_array($template_json['items'])) {
				$items = $template_json['items'];
			} elseif (isset($template_json['template']['items']) && is_array($template_json['template']['items'])) {
				$items = $template_json['template']['items'];
			}
		}

		$output = array();
		$this->walk_template_parts($items, null, $output);
		return $output;
	}

	private function walk_template_parts($items, $parent, &$output)
	{
		foreach ((array) $items as $item) {
			if (!is_array($item)) {
				continue;
			}

			if (isset($item['items']) && is_array($item['items'])) {
				$parent_key = isset($item['key']) ? $item['key'] : $parent;
				$this->walk_template_parts($item['items'], $parent_key, $output);
			}

			$array_key = isset($item['key']) ? $item['key'] : $parent;
			if (isset($item['props']) && is_array($item['props'])) {
				$this->walk_template_prop_parts($item['props'], $array_key, $output);
			}

			if (isset($item['key'])) {
				$item['parent'] = $parent;
				$output[$item['key']] = $item;
			}
		}
	}

	private function walk_template_prop_parts($prop_items, $parent_array_key, &$output)
	{
		foreach ((array) $prop_items as $prop) {
			if (!is_array($prop)) {
				continue;
			}

			$prop_row = $prop;
			$prop_row['parent'] = $parent_array_key;
			$prop_row['is_prop'] = true;

			if (isset($prop['prop_key']) && $prop['prop_key'] !== '') {
				$output[$prop['prop_key']] = $prop_row;
				$prop_key = $prop['prop_key'];
			} elseif (isset($prop['key']) && $parent_array_key) {
				$prop_key = $parent_array_key . '.' . $prop['key'];
				$output[$prop_key] = $prop_row;
			} else {
				$prop_key = '';
			}

			$is_prop_section = isset($prop['type'])
				&& $prop['type'] === 'section'
				&& isset($prop['prop_key'])
				&& $prop['prop_key'] !== '';

			if ($is_prop_section && isset($prop['props']) && is_array($prop['props'])) {
				$this->walk_template_prop_parts($prop['props'], $parent_array_key, $output);
				continue;
			}

			$nested_array_key = $prop_key !== '' ? $prop_key : $parent_array_key;
			if (isset($prop['props']) && is_array($prop['props'])) {
				$this->walk_template_prop_parts($prop['props'], $nested_array_key, $output);
			}
		}
	}

	private function get_core_template_rows($data_type, $status, $defaults)
	{
		$rows = array();
		$seen_uids = array();
		foreach ($this->core_templates as $core) {
			if ($data_type && !$this->data_type_matches($data_type, $core['data_type'])) {
				continue;
			}
			if ($status && $status !== $core['status']) {
				continue;
			}
			if (isset($seen_uids[$core['uid']])) {
				continue;
			}
			$seen_uids[$core['uid']] = true;

			$row = $core;
			$row['default'] = false;
			if (isset($defaults[$core['data_type']]) && $defaults[$core['data_type']] === $core['uid']) {
				$row['default'] = true;
			} elseif (
				!isset($defaults[$core['data_type']])
				&& isset($this->display_template_defaults[$core['data_type']])
				&& $this->display_template_defaults[$core['data_type']] === $core['uid']
			) {
				$row['default'] = true;
			}
			$rows[] = $row;
		}
		return $rows;
	}

	private function normalize_core_template_json($json)
	{
		if (isset($json['template']) && is_array($json['template'])) {
			$json = $json['template'];
		}
		if (!isset($json['type'])) {
			$json['type'] = 'template';
		}
		if (!isset($json['items']) || !is_array($json['items'])) {
			$json['items'] = array();
		}
		$this->load->helper('display_template');
		$json['items'] = display_template_omit_custom_section_containers($json['items']);
		return $json;
	}

	private function expand_data_type_aliases($data_type)
	{
		$types = array((string) $data_type);
		if ($data_type === 'timeseries-db') {
			$types[] = 'timeseriesdb';
		} elseif ($data_type === 'timeseriesdb') {
			$types[] = 'timeseries-db';
		}
		return array_values(array_unique($types));
	}

	private function data_type_matches($requested, $candidate)
	{
		if ($requested === $candidate) {
			return true;
		}
		return in_array($candidate, $this->expand_data_type_aliases($requested), true)
			|| in_array($requested, $this->expand_data_type_aliases($candidate), true);
	}

	private function normalize_options($options, $is_create)
	{
		$valid_fields = array(
			'uid', 'template_type', 'data_type', 'name', 'version',
			'organization', 'author', 'description', 'status',
			'template_json', 'created_by', 'changed_by', 'is_deleted'
		);
		$data = array();
		foreach ($options as $key => $value) {
			if (in_array($key, $valid_fields, true)) {
				$data[$key] = $value;
			}
		}

		if ($is_create) {
			if (!isset($data['uid']) || !$data['uid']) {
				$seed = isset($data['data_type']) ? $data['data_type'] : 'template';
				$data['uid'] = md5($seed . '-' . mt_rand() . '-' . microtime(true));
			}
			if (!isset($data['template_type'])) {
				$data['template_type'] = 'custom';
			}
			if (!isset($data['status'])) {
				$data['status'] = 'draft';
			}
			if (!isset($data['is_deleted'])) {
				$data['is_deleted'] = 0;
			}
		}

		if (array_key_exists('template_json', $data)) {
			if (is_string($data['template_json'])) {
				$decoded = json_decode($data['template_json'], true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					throw new Exception('template_json must be valid JSON');
				}
				$data['template_json'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
			} else {
				$data['template_json'] = json_encode($data['template_json'], JSON_UNESCAPED_UNICODE);
			}
		}

		return $data;
	}
}

