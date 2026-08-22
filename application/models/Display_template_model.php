<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Display_template_model extends CI_Model {

	private $table = 'display_templates';
	private $table_defaults = 'display_templates_default';
	private $table_translations = 'display_template_translations';

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('display_template');
	}

	public function get_all_templates($data_type = null, $status = null)
	{
		$this->db->from($this->table);
		$this->db->where('is_deleted', 0);
		if ($data_type) {
			$this->db->group_start();
			foreach ($this->expand_data_type_aliases($data_type) as $alias) {
				$this->db->or_where('data_type', $alias);
			}
			$this->db->group_end();
		}
		if ($status) {
			$this->db->where('status', $status);
		}
		$this->db->order_by('name', 'ASC');
		$this->db->order_by('updated_at', 'DESC');
		$rows = $this->db->get()->result_array();
		$defaults = $this->get_defaults_map();
		foreach ($rows as $idx => $row) {
			$rows[$idx] = $this->hydrate_row($row, false);
			$rows[$idx]['default'] = isset($defaults[$row['data_type']]) && $defaults[$row['data_type']] === $row['uid'];
		}
		return $this->attach_languages_to_rows($rows);
	}

	public function get_template_by_uid($uid)
	{
		$this->db->from($this->table);
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		$row = $this->hydrate_row($this->db->get()->row_array(), true);
		return $row ? $this->attach_languages_to_row($row) : null;
	}

	public function is_core_template_uid($uid)
	{
		$row = $this->get_stored_row($uid);
		return $this->is_file_backed($row);
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
		$data['source'] = 'inline';
		$data['file_path'] = null;
		if (isset($data['template_type']) && $data['template_type'] === 'system') {
			$data['template_type'] = 'custom';
		}
		$this->db->insert($this->table, $data);
		return $this->inserted_template($data['uid']);
	}

	public function update_template($uid, $options)
	{
		$existing = $this->get_stored_row($uid);
		if (!$existing) {
			return null;
		}
		if ($this->is_file_backed($existing)) {
			throw new Exception('System core templates cannot be edited');
		}
		$previous_lang = display_template_normalize_lang(isset($existing['lang']) ? $existing['lang'] : 'en', false);
		$data = $this->normalize_options($options, false);
		if (empty($data)) {
			return $this->get_template_by_uid($uid);
		}
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		$this->db->update($this->table, $data);
		if (isset($data['lang'])) {
			$new_lang = display_template_normalize_lang($data['lang'], false);
			if ($new_lang !== '' && $new_lang !== $previous_lang && !empty($existing['id'])) {
				$this->delete_translation_lang((int) $existing['id'], $new_lang);
			}
		}
		return $this->get_template_by_uid($uid);
	}

	public function delete_template($uid, $user_id = null)
	{
		$template = $this->get_stored_row($uid);
		if (!$template) {
			return false;
		}
		if ($this->is_file_backed($template)) {
			throw new Exception('System core templates cannot be deleted');
		}
		if (!empty($template['id'])) {
			$this->delete_translations_for_template((int) $template['id']);
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
		$source_id = isset($template['id']) ? (int) $template['id'] : 0;
		$template['uid'] = md5($template['data_type'] . '-' . mt_rand() . '-' . microtime(true));
		$template['name'] = $template['name'] . ' - copy';
		$template['template_type'] = 'custom';
		$template['source'] = 'inline';
		$template['file_path'] = null;
		$template['status'] = 'draft';
		$template['created_by'] = $user_id ? (int) $user_id : null;
		$template['changed_by'] = $user_id ? (int) $user_id : null;
		unset(
			$template['id'],
			$template['default'],
			$template['created_at'],
			$template['updated_at'],
			$template['is_deleted'],
			$template['is_core'],
			$template['file'],
			$template['languages'],
			$template['translations']
		);
		$copy = $this->create_template($template);
		if ($source_id > 0 && !empty($copy['id'])) {
			$this->copy_translations($source_id, (int) $copy['id']);
			$copy = $this->get_template_by_uid($copy['uid']);
		}
		return $copy;
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

	public function is_file_backed($row)
	{
		if (!$row || !is_array($row)) {
			return false;
		}
		if (isset($row['source']) && $row['source'] === 'file') {
			return true;
		}
		return isset($row['template_type']) && $row['template_type'] === 'system';
	}

	/**
	 * Register shipped cores as file-backed rows. Safe to run more than once.
	 */
	public function sync_shipped_cores()
	{
		$cores = display_template_shipped_core_registry();
		foreach ($cores as $core) {
			$existing = $this->get_stored_row($core['uid']);
			if ($existing) {
				if ($this->is_file_backed($existing) || $existing['template_type'] === 'system') {
					$this->db->where('uid', $core['uid'])->update($this->table, array(
						'name' => $core['name'],
						'data_type' => $core['data_type'],
						'lang' => $core['lang'],
						'version' => $core['version'],
						'organization' => $core['organization'],
						'author' => $core['author'],
						'description' => $core['description'],
						'template_type' => 'system',
						'source' => 'file',
						'file_path' => $core['file_path'],
						'status' => 'published',
						'is_deleted' => 0,
						'template_json' => null,
					));
				}
				continue;
			}

			$this->db->insert($this->table, array(
				'uid' => $core['uid'],
				'template_type' => 'system',
				'source' => 'file',
				'data_type' => $core['data_type'],
				'lang' => $core['lang'],
				'name' => $core['name'],
				'version' => $core['version'],
				'organization' => $core['organization'],
				'author' => $core['author'],
				'description' => $core['description'],
				'status' => 'published',
				'template_json' => null,
				'file_path' => $core['file_path'],
				'is_deleted' => 0,
			));
		}

		$defaults = display_template_shipped_core_defaults();
		foreach ($defaults as $data_type => $uid) {
			if ($this->get_default_template($data_type)) {
				continue;
			}
			if (!$this->get_stored_row($uid)) {
				continue;
			}
			$this->db->insert($this->table_defaults, array(
				'data_type' => $data_type,
				'template_uid' => $uid,
			));
		}
	}

	private function get_stored_row($uid)
	{
		$uid = trim((string) $uid);
		if ($uid === '') {
			return null;
		}
		$this->db->from($this->table);
		$this->db->where('uid', $uid);
		$this->db->where('is_deleted', 0);
		$row = $this->db->get()->row_array();
		return $row ? $row : null;
	}

	private function hydrate_row($row, $load_tree)
	{
		$row = $this->decode_row($row);
		if (!$row) {
			return null;
		}
		$file_backed = $this->is_file_backed($row);
		$row['is_core'] = $file_backed;
		if (!empty($row['file_path'])) {
			$row['file'] = $row['file_path'];
		}
		if ($load_tree && $file_backed) {
			$row['template_json'] = $this->load_file_template_json($row['file_path']);
		}
		return $row;
	}

	private function decode_row($row)
	{
		if (!$row) {
			return null;
		}
		if (isset($row['template_json']) && is_string($row['template_json']) && $row['template_json'] !== '') {
			$decoded = json_decode($row['template_json'], true);
			$row['template_json'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : array();
		}
		return $row;
	}

	public function resolve_core_template_path($relative_file)
	{
		if ($relative_file === '' || $relative_file === null) {
			return null;
		}

		$relative_file = ltrim(str_replace('\\', '/', $relative_file), '/');
		if (!display_template_is_allowed_file_path($relative_file)) {
			return null;
		}
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
		$row = $this->get_stored_row($uid);
		if (!$this->is_file_backed($row)) {
			return null;
		}
		return $this->hydrate_row($row, true);
	}

	public function get_core_templates_by_type($data_type = null)
	{
		$this->db->from($this->table);
		$this->db->where('is_deleted', 0);
		$this->db->group_start();
		$this->db->where('source', 'file');
		$this->db->or_where('template_type', 'system');
		$this->db->group_end();
		if ($data_type) {
			$this->db->group_start();
			foreach ($this->expand_data_type_aliases($data_type) as $alias) {
				$this->db->or_where('data_type', $alias);
			}
			$this->db->group_end();
		}
		$this->db->order_by('name', 'ASC');
		$rows = $this->db->get()->result_array();
		$out = array();
		foreach ($rows as $row) {
			$out[] = $this->hydrate_row($row, false);
		}
		return $out;
	}

	public function get_default_core_template_uid($data_type)
	{
		$cores = $this->get_core_templates_by_type($data_type);
		foreach ($cores as $core) {
			$path = isset($core['file_path']) ? (string) $core['file_path'] : '';
			if (strpos($path, '_display_template.json') !== false) {
				return $core['uid'];
			}
		}
		foreach ($cores as $core) {
			$uid = isset($core['uid']) ? (string) $core['uid'] : '';
			if (substr($uid, -10) === '-system-en') {
				return $uid;
			}
		}
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
		$row = $this->get_stored_row($uid);
		if (!$this->is_file_backed($row) || empty($row['file_path'])) {
			return null;
		}
		return $this->load_file_template_json($row['file_path']);
	}

	private function load_file_template_json($relative_file)
	{
		if (!display_template_is_allowed_file_path($relative_file)) {
			throw new Exception('Invalid core display template path: ' . $relative_file);
		}
		$template_path = $this->resolve_core_template_path($relative_file);
		if (!$template_path) {
			throw new Exception('Core display template not found: ' . $relative_file);
		}
		$json = json_decode(file_get_contents($template_path), true);
		if (!is_array($json)) {
			throw new Exception('Invalid core display template JSON: ' . $relative_file);
		}
		return $this->normalize_core_template_json($json);
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

	private function translations_table_ready()
	{
		return $this->db->table_exists($this->table_translations);
	}

	private function attach_languages_to_rows($rows)
	{
		if (!is_array($rows) || $rows === array()) {
			return $rows;
		}
		$ids = array();
		foreach ($rows as $row) {
			if (is_array($row) && !empty($row['id'])) {
				$ids[] = (int) $row['id'];
			}
		}
		$by_id = $this->translation_langs_by_ids($ids);
		foreach ($rows as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$primary = display_template_normalize_lang(isset($row['lang']) ? $row['lang'] : 'en', false);
			if ($primary === '') {
				$primary = 'en';
			}
			$extra = (!empty($row['id']) && isset($by_id[(int) $row['id']])) ? $by_id[(int) $row['id']] : array();
			$rows[$idx]['lang'] = $primary;
			$rows[$idx]['languages'] = display_template_merge_languages($primary, $extra);
		}
		return $rows;
	}

	private function attach_languages_to_row($row)
	{
		$rows = $this->attach_languages_to_rows(array($row));
		return isset($rows[0]) ? $rows[0] : $row;
	}

	private function translation_langs_by_ids($ids)
	{
		$out = array();
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
		if ($ids === array() || !$this->translations_table_ready()) {
			return $out;
		}
		$this->db->select('template_id, lang');
		$this->db->from($this->table_translations);
		$this->db->where_in('template_id', $ids);
		$this->db->order_by('lang', 'ASC');
		foreach ($this->db->get()->result_array() as $row) {
			$tid = (int) $row['template_id'];
			$code = display_template_normalize_lang(isset($row['lang']) ? $row['lang'] : '', false);
			if ($code === '') {
				continue;
			}
			$out[$tid][] = $code;
		}
		return $out;
	}

	public function get_translation_bundle($template)
	{
		$id = isset($template['id']) ? (int) $template['id'] : 0;
		$primary = display_template_normalize_lang(isset($template['lang']) ? $template['lang'] : 'en', false);
		if ($primary === '') {
			$primary = 'en';
		}
		$overlays = $this->get_translations_by_template_id($id);
		unset($overlays[$primary]);
		$languages = display_template_merge_languages($primary, array_keys($overlays));
		$json = isset($template['template_json']) ? $template['template_json'] : array();
		return array(
			'primary_lang' => $primary,
			'languages' => $languages,
			'overlays' => $overlays,
			'rows' => display_template_collect_translation_rows($json),
			'iso_languages' => display_template_iso_languages(),
		);
	}

	public function get_translations_by_template_id($template_id)
	{
		$out = array();
		$template_id = (int) $template_id;
		if ($template_id < 1 || !$this->translations_table_ready()) {
			return $out;
		}
		$this->db->from($this->table_translations);
		$this->db->where('template_id', $template_id);
		foreach ($this->db->get()->result_array() as $row) {
			$lang = display_template_normalize_lang(isset($row['lang']) ? $row['lang'] : '', false);
			if ($lang === '') {
				continue;
			}
			$out[$lang] = display_template_sanitize_translations_map(isset($row['translations']) ? $row['translations'] : array());
		}
		return $out;
	}

	public function get_translation_map($template_id, $lang)
	{
		$lang = display_template_normalize_lang($lang, false);
		if ($lang === '') {
			return array();
		}
		$all = $this->get_translations_by_template_id($template_id);
		return isset($all[$lang]) ? $all[$lang] : array();
	}

	public function add_translation_lang($template, $lang)
	{
		$id = isset($template['id']) ? (int) $template['id'] : 0;
		if ($id < 1) {
			throw new Exception('Template is missing an id');
		}
		$lang = display_template_normalize_lang($lang, true);
		$primary = display_template_normalize_lang(isset($template['lang']) ? $template['lang'] : 'en', false);
		if ($lang === $primary) {
			throw new Exception('Primary language titles live on the layout; add a different locale');
		}
		$this->assert_translations_table();
		$existing = $this->translation_row($id, $lang);
		if ($existing) {
			return $this->get_translation_bundle($this->get_template_by_uid($template['uid']));
		}
		$this->db->insert($this->table_translations, array(
			'template_id' => $id,
			'lang' => $lang,
			'translations' => json_encode(new stdClass(), JSON_UNESCAPED_UNICODE),
		));
		return $this->get_translation_bundle($this->get_template_by_uid($template['uid']));
	}

	public function save_translation_lang($template, $lang, $map)
	{
		$id = isset($template['id']) ? (int) $template['id'] : 0;
		if ($id < 1) {
			throw new Exception('Template is missing an id');
		}
		$lang = display_template_normalize_lang($lang, true);
		$primary = display_template_normalize_lang(isset($template['lang']) ? $template['lang'] : 'en', false);
		if ($lang === $primary) {
			throw new Exception('Primary language titles live on the layout');
		}
		$this->assert_translations_table();
		$clean = display_template_sanitize_translations_map($map);
		$encoded = json_encode($clean, JSON_UNESCAPED_UNICODE);
		$existing = $this->translation_row($id, $lang);
		if ($existing) {
			$this->db->where('id', (int) $existing['id']);
			$this->db->update($this->table_translations, array('translations' => $encoded));
		} else {
			$this->db->insert($this->table_translations, array(
				'template_id' => $id,
				'lang' => $lang,
				'translations' => $encoded,
			));
		}
		return $this->get_translation_bundle($this->get_template_by_uid($template['uid']));
	}

	public function delete_translation_lang($template_id, $lang)
	{
		$template_id = (int) $template_id;
		if ($template_id < 1 || !$this->translations_table_ready()) {
			return false;
		}
		$lang = display_template_normalize_lang($lang, false);
		if ($lang === '') {
			return false;
		}
		$this->db->where('template_id', $template_id);
		$this->db->where('lang', $lang);
		return $this->db->delete($this->table_translations);
	}

	public function replace_translations($template, $overlays)
	{
		$id = isset($template['id']) ? (int) $template['id'] : 0;
		if ($id < 1 || !is_array($overlays)) {
			return;
		}
		$primary = display_template_normalize_lang(isset($template['lang']) ? $template['lang'] : 'en', false);
		foreach ($overlays as $lang => $map) {
			$code = display_template_normalize_lang($lang, false);
			if ($code === '' || $code === $primary) {
				continue;
			}
			$this->save_translation_lang($template, $code, $map);
		}
	}

	private function copy_translations($from_id, $to_id)
	{
		$from_id = (int) $from_id;
		$to_id = (int) $to_id;
		if ($from_id < 1 || $to_id < 1 || !$this->translations_table_ready()) {
			return;
		}
		foreach ($this->get_translations_by_template_id($from_id) as $lang => $map) {
			$this->db->insert($this->table_translations, array(
				'template_id' => $to_id,
				'lang' => $lang,
				'translations' => json_encode($map, JSON_UNESCAPED_UNICODE),
			));
		}
	}

	private function delete_translations_for_template($template_id)
	{
		$template_id = (int) $template_id;
		if ($template_id < 1 || !$this->translations_table_ready()) {
			return;
		}
		$this->db->where('template_id', $template_id);
		$this->db->delete($this->table_translations);
	}

	private function translation_row($template_id, $lang)
	{
		if (!$this->translations_table_ready()) {
			return null;
		}
		$this->db->from($this->table_translations);
		$this->db->where('template_id', (int) $template_id);
		$this->db->where('lang', $lang);
		$row = $this->db->get()->row_array();
		return $row ? $row : null;
	}

	private function assert_translations_table()
	{
		if (!$this->translations_table_ready()) {
			throw new Exception('display_template_translations is missing; run database migrations');
		}
	}

	private function normalize_options($options, $is_create)
	{
		$valid_fields = array(
			'uid', 'template_type', 'data_type', 'lang', 'name', 'version',
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
			if (!isset($data['lang']) || $data['lang'] === '') {
				$data['lang'] = 'en';
			}
		}

		if (isset($data['lang'])) {
			$data['lang'] = display_template_normalize_lang($data['lang'], true);
		}

		if (!$is_create && isset($data['template_type']) && $data['template_type'] === 'system') {
			unset($data['template_type']);
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

