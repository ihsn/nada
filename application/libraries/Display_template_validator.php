<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validates display template trees against a core template field registry.
 */
class Display_template_validator
{
	private static $structural_types = array(
		'section',
		'section_container',
		'template',
		'template_root',
		'template_description',
	);

	private static $array_types = array(
		'array',
		'nested_array',
		'simple_array',
	);

	/**
	 * @param array|string|null $core_template_json
	 * @return array{fields: array<string, array>, props: array<string, array>, has_core: bool}
	 */
	public static function build_registry($core_template_json)
	{
		$fields = array();
		$props = array();

		$root = self::normalize_core_root($core_template_json);
		if ($root && isset($root['items']) && is_array($root['items'])) {
			self::walk_core_nodes($root['items'], $fields, $props, '__root__');
		}

		return array(
			'fields' => $fields,
			'props' => $props,
			'has_core' => !empty($fields) || !empty($props),
		);
	}

	/**
	 * @param array $template_json Decoded user template
	 * @param array|string|null $core_template_json
	 * @return array{valid: bool, errors: array<int, array{key: string, message: string}>, warnings: array<int, array{key: string, message: string}>}
	 */
	public static function validate_tree($template_json, $core_template_json = null)
	{
		$errors = array();
		$registry = self::build_registry($core_template_json);
		$require_core = !empty($registry['has_core']);

		if (!isset($template_json['items']) || !is_array($template_json['items'])) {
			return array(
				'valid' => false,
				'errors' => array(
					array('key' => 'template', 'message' => 'template_json.items must be an array'),
				),
				'warnings' => array(),
			);
		}

		$seen_field_keys = array();
		$seen_prop_keys = array();
		$warnings = array();
		self::walk_validate(
			$template_json['items'],
			$registry,
			$require_core,
			$seen_field_keys,
			$seen_prop_keys,
			$errors,
			$warnings,
			null,
			'items',
			'__root__'
		);

		$display = self::validate_display_layout(isset($template_json['items']) ? $template_json['items'] : array());
		$errors = array_merge($errors, $display['errors']);
		$warnings = array_merge($warnings, $display['warnings']);

		return array(
			'valid' => empty($errors),
			'errors' => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Structural payload checks plus core registry validation when a baseline exists.
	 *
	 * @param array $input Request payload with template_json
	 * @param array|string|null $core_template_json
	 * @return array<int, string>
	 */
	public static function validate_payload($input, $core_template_json = null)
	{
		$errors = array();

		if (!isset($input['template_json'])) {
			$errors[] = 'template_json is required';
			return $errors;
		}

		$decoded = self::decode_template_json($input['template_json']);
		if ($decoded === null) {
			$errors[] = 'Invalid template_json';
			return $errors;
		}

		if (!isset($decoded['items']) || !is_array($decoded['items'])) {
			$errors[] = 'template_json.items must be an array';
		}

		if (!isset($decoded['type'])) {
			$errors[] = 'template_json.type is required (use "template")';
		}

		if (!empty($errors)) {
			return $errors;
		}

		$result = self::validate_tree($decoded, $core_template_json);
		foreach ($result['errors'] as $issue) {
			$key = isset($issue['key']) ? $issue['key'] : 'template';
			$message = isset($issue['message']) ? $issue['message'] : 'Validation failed';
			$errors[] = $key . ': ' . $message;
		}

		return $errors;
	}

	/**
	 * Display layout checks for public study-page rendering.
	 *
	 * @param array $items
	 * @return array{errors: array<int, array{key: string, message: string}>, warnings: array<int, array{key: string, message: string}>}
	 */
	public static function validate_display_layout($items)
	{
		$errors = array();
		$warnings = array();
		$seen_section_keys = array();
		self::walk_display_layout((array) $items, 'root', $errors, $warnings, $seen_section_keys);
		return array('errors' => $errors, 'warnings' => $warnings);
	}

	/**
	 * @param array $items
	 * @param string $parent_kind root|section_container|section
	 * @param array<int, array{key: string, message: string}> $errors
	 * @param array<int, array{key: string, message: string}> $warnings
	 * @param array<string, bool> $seen_section_keys
	 */
	private static function walk_display_layout($items, $parent_kind, &$errors, &$warnings, &$seen_section_keys)
	{
		foreach ((array) $items as $item) {
			if (!is_array($item)) {
				continue;
			}

			$item_key = !empty($item['key']) ? (string) $item['key'] : 'template';
			$type = isset($item['type']) ? (string) $item['type'] : '';

			if (self::display_is_layout_section($item)) {
				self::walk_display_layout_section($item, $errors, $warnings, $seen_section_keys);
				continue;
			}

			if ($type === 'section_container') {
				$children = isset($item['items']) && is_array($item['items']) ? $item['items'] : array();
				if (!self::display_has_section_descendant($children)) {
					$label = !empty($item['title']) ? $item['title'] : $item_key;
					$warnings[] = array(
						'key' => $item_key,
						'message' => 'Section container "' . $label . '" has no sections.',
					);
				}
				self::walk_display_layout($children, 'section_container', $errors, $warnings, $seen_section_keys);
				continue;
			}

			if (self::display_is_layout_field_node($item)) {
				if ($parent_kind === 'section_container' || $parent_kind === 'root') {
					$field_key = !empty($item['key']) ? (string) $item['key'] : $item_key;
					$errors[] = array(
						'key' => $field_key,
						'message' => 'Field must be placed under a section, not directly under a section container or template root, for sidebar navigation.',
					);
				}
				self::validate_composite_renderer($item, $errors, $warnings);
				continue;
			}

			if (self::is_array_type($type)) {
				self::validate_composite_renderer($item, $errors, $warnings);
				$props = isset($item['props']) && is_array($item['props']) ? $item['props'] : array();
				if (empty($props)) {
					$label = !empty($item['title']) ? $item['title'] : $item_key;
					$warnings[] = array(
						'key' => !empty($item['key']) ? (string) $item['key'] : $item_key,
						'message' => 'Array field "' . $label . '" has no columns (props) defined.',
					);
				}
				continue;
			}

			if (isset($item['items']) && is_array($item['items']) && !empty($item['items'])) {
				self::walk_display_layout($item['items'], $parent_kind, $errors, $warnings, $seen_section_keys);
			}
		}
	}

	private static function walk_display_layout_section($item, &$errors, &$warnings, &$seen_section_keys)
	{
		$section_key = !empty($item['key']) ? (string) $item['key'] : 'template';
		if ($section_key !== '') {
			if (isset($seen_section_keys[$section_key])) {
				$errors[] = array(
					'key' => $section_key,
					'message' => 'Duplicate section key "' . $section_key . '". Sidebar anchors require unique section keys.',
				);
			} else {
				$seen_section_keys[$section_key] = true;
			}
		}

		$children = isset($item['items']) && is_array($item['items']) ? $item['items'] : array();
		if (!self::display_has_field_descendant($children)) {
			$label = !empty($item['title']) ? $item['title'] : $section_key;
			$warnings[] = array(
				'key' => $section_key,
				'message' => 'Section "' . $label . '" has no fields and will not appear on the study page.',
			);
		}
		self::walk_display_layout($children, 'section', $errors, $warnings, $seen_section_keys);
	}

	private static function display_is_layout_section($item)
	{
		if (!is_array($item)) {
			return false;
		}
		$type = isset($item['type']) ? $item['type'] : '';
		if ($type === 'section_container' || self::is_array_type($type)) {
			return false;
		}
		if ($type === 'section' && !self::is_prop_tree_section($item)) {
			return true;
		}
		return isset($item['items']) && is_array($item['items']) && !empty($item['items']) && !empty($item['key']);
	}

	private static function display_is_layout_field_node($item)
	{
		if (!is_array($item)) {
			return false;
		}
		if (self::display_is_layout_section($item) || (isset($item['type']) && $item['type'] === 'section_container')) {
			return false;
		}
		$type = isset($item['type']) ? $item['type'] : '';
		if (self::is_structural_type($type)) {
			return false;
		}
		if (isset($item['items']) && is_array($item['items']) && !empty($item['items']) && !self::is_array_type($type)) {
			return false;
		}
		return true;
	}

	private static function display_has_section_descendant($items)
	{
		foreach ((array) $items as $item) {
			if (!is_array($item)) {
				continue;
			}
			if (self::display_is_layout_section($item)) {
				return true;
			}
			if (isset($item['items']) && is_array($item['items']) && self::display_has_section_descendant($item['items'])) {
				return true;
			}
		}
		return false;
	}

	private static function display_has_field_descendant($items)
	{
		foreach ((array) $items as $item) {
			if (!is_array($item)) {
				continue;
			}
			if (self::display_is_layout_field_node($item)) {
				return true;
			}
			if (isset($item['items']) && is_array($item['items']) && self::display_has_field_descendant($item['items'])) {
				return true;
			}
		}
		return false;
	}

	private static function decode_template_json($template_json)
	{
		if (is_array($template_json)) {
			return $template_json;
		}

		if (!is_string($template_json)) {
			return null;
		}

		$decoded = json_decode($template_json, true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
			return null;
		}

		return $decoded;
	}

	private static function normalize_core_root($core_template_json)
	{
		if (is_string($core_template_json)) {
			$core_template_json = json_decode($core_template_json, true);
		}

		if (!is_array($core_template_json)) {
			return null;
		}

		if (isset($core_template_json['items']) && is_array($core_template_json['items'])) {
			return $core_template_json;
		}

		if (isset($core_template_json['template']['items']) && is_array($core_template_json['template']['items'])) {
			return $core_template_json['template'];
		}

		return null;
	}

	private static function walk_core_nodes($items, &$fields, &$props, $container_key)
	{
		foreach ((array) $items as $item) {
			if (!is_array($item)) {
				continue;
			}

			$active_container = $container_key;
			if (isset($item['type']) && $item['type'] === 'section_container' && !empty($item['key'])) {
				$active_container = $item['key'];
			}

			if (isset($item['key']) && $item['key'] !== '' && !self::is_structural_type(isset($item['type']) ? $item['type'] : '')) {
				$row = self::strip_runtime_ids($item);
				$row['_containerKey'] = $active_container;
				$fields[$item['key']] = $row;
			}

			if (self::is_array_type(isset($item['type']) ? $item['type'] : '') && isset($item['props']) && is_array($item['props'])) {
				self::walk_core_prop_nodes($item['props'], isset($item['key']) ? $item['key'] : '', $fields, $props);
			}

			if (isset($item['items']) && is_array($item['items']) && !empty($item['items'])) {
				self::walk_core_nodes($item['items'], $fields, $props, $active_container);
			}
		}
	}

	private static function walk_core_prop_nodes($prop_items, $parent_array_key, &$fields, &$props)
	{
		foreach ((array) $prop_items as $prop) {
			if (!is_array($prop)) {
				continue;
			}

			$prop_key = self::resolve_prop_key(array('key' => $parent_array_key), $prop);
			if ($prop_key !== '') {
				$props[$prop_key] = self::strip_runtime_ids($prop);
			}

			if (self::is_prop_tree_section($prop) && isset($prop['props']) && is_array($prop['props'])) {
				self::walk_core_prop_nodes($prop['props'], $parent_array_key, $fields, $props);
				continue;
			}

			if (self::is_array_type(isset($prop['type']) ? $prop['type'] : '') && isset($prop['props']) && is_array($prop['props'])) {
				$array_key = $prop_key !== '' ? $prop_key : $parent_array_key;
				self::walk_core_prop_nodes($prop['props'], $array_key, $fields, $props);
			}
		}
	}

	private static function walk_validate($items, $registry, $require_core, &$seen_field_keys, &$seen_prop_keys, &$errors, &$warnings, $parent_array_key, $child_kind, $container_key = '__root__')
	{
		if (!is_array($items)) {
			return;
		}

		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}

			if ($child_kind === 'props') {
				self::validate_prop_node($item, $parent_array_key, $registry, $require_core, $seen_prop_keys, $errors, $warnings);

				if (self::is_prop_tree_section($item) && isset($item['props']) && is_array($item['props']) && !empty($item['props'])) {
					self::walk_validate($item['props'], $registry, $require_core, $seen_field_keys, $seen_prop_keys, $errors, $warnings, $parent_array_key, 'props', $container_key);
				} elseif (self::is_array_type(isset($item['type']) ? $item['type'] : '') && isset($item['props']) && is_array($item['props']) && !empty($item['props'])) {
					$array_key = self::resolve_nested_array_key($item, $parent_array_key);
					self::walk_validate($item['props'], $registry, $require_core, $seen_field_keys, $seen_prop_keys, $errors, $warnings, $array_key, 'props', $container_key);
				}
				continue;
			}

			$active_container = $container_key;
			if (isset($item['type']) && $item['type'] === 'section_container' && !empty($item['key'])) {
				$active_container = $item['key'];
			}

			if (isset($item['key']) && $item['key'] !== '') {
				if (!self::is_layout_group_node($item)) {
					self::validate_field_node($item, $registry, $require_core, $seen_field_keys, $errors, $warnings, $active_container);
				}
			}

			if (isset($item['items']) && is_array($item['items']) && !empty($item['items'])) {
				self::walk_validate($item['items'], $registry, $require_core, $seen_field_keys, $seen_prop_keys, $errors, $warnings, null, 'items', $active_container);
			}

			if (self::is_array_type(isset($item['type']) ? $item['type'] : '') && isset($item['props']) && is_array($item['props']) && !empty($item['props'])) {
				$array_key = isset($item['key']) ? $item['key'] : $parent_array_key;
				self::walk_validate($item['props'], $registry, $require_core, $seen_field_keys, $seen_prop_keys, $errors, $warnings, $array_key, 'props', $active_container);
			}
		}
	}

	private static function validate_field_node($item, $registry, $require_core, &$seen_field_keys, &$errors, &$warnings, $container_key = '__root__')
	{
		$key = $item['key'];

		foreach (self::validate_key_format($key) as $message) {
			$errors[] = array('key' => $key, 'message' => $message);
		}

		if (isset($seen_field_keys[$key])) {
			$errors[] = array('key' => $key, 'message' => 'Duplicate field key "' . $key . '".');
		}
		$seen_field_keys[$key] = true;

		if ($require_core && !isset($registry['fields'][$key]) && (isset($item['type']) ? $item['type'] : '') !== 'widget') {
			$is_custom = isset($item['display_options']['custom']) && $item['display_options']['custom'];
			if (!$is_custom) {
				$warnings[] = array('key' => $key, 'message' => 'Unknown field key "' . $key . '" (not in core template).');
			}
		}

		if ($require_core && isset($registry['fields'][$key]['_containerKey'])) {
			$expected = $registry['fields'][$key]['_containerKey'];
			if ($expected !== '' && $container_key !== '' && $expected !== $container_key) {
				$errors[] = array(
					'key' => $key,
					'message' => 'Field "' . $key . '" must stay within section container "' . $expected . '".',
				);
			}
		}

		if (
			isset($registry['fields'][$key]['type'], $item['type'])
			&& $registry['fields'][$key]['type'] !== ''
			&& $item['type'] !== ''
			&& $registry['fields'][$key]['type'] !== $item['type']
		) {
			$errors[] = array(
				'key' => $key,
				'message' => 'Field type mismatch for "' . $key . '": expected "' . $registry['fields'][$key]['type'] . '", got "' . $item['type'] . '".',
			);
		}
	}

	private static function validate_prop_node($prop, $parent_array_key, $registry, $require_core, &$seen_prop_keys, &$errors, &$warnings)
	{
		$prop_key = self::resolve_prop_key(array('key' => $parent_array_key), $prop);
		$display_key = $prop_key !== '' ? $prop_key : (isset($prop['key']) ? $prop['key'] : 'column');

		if (self::is_prop_tree_section($prop)) {
			if ($prop_key !== '') {
				if (isset($seen_prop_keys[$prop_key])) {
					$errors[] = array('key' => $prop_key, 'message' => 'Duplicate array section "' . $prop_key . '".');
				}
				$seen_prop_keys[$prop_key] = true;
			}
			if ($require_core && $prop_key !== '' && !isset($registry['props'][$prop_key]) && !self::skip_unknown_prop_warning($prop, $parent_array_key, $registry)) {
				$warnings[] = array('key' => $prop_key, 'message' => 'Unknown array section "' . $prop_key . '" (not in core template).');
			}
			return;
		}

		if (
			!isset($prop['key'])
			|| strpos((string) $prop['key'], '.') !== false
			|| !preg_match('/^[a-zA-Z0-9:_-]+$/', (string) $prop['key'])
		) {
			$errors[] = array('key' => $display_key, 'message' => 'Array column key must be a simple identifier.');
		}

		if ($prop_key !== '') {
			if (isset($seen_prop_keys[$prop_key])) {
				$errors[] = array('key' => $prop_key, 'message' => 'Duplicate array column "' . $prop_key . '".');
			}
			$seen_prop_keys[$prop_key] = true;
		}

		if ($parent_array_key && $prop_key !== '' && strpos($prop_key, $parent_array_key . '.') !== 0) {
			$errors[] = array(
				'key' => $prop_key,
				'message' => 'Column "' . $prop_key . '" must belong to array "' . $parent_array_key . '".',
			);
		}

		if ($require_core && $prop_key !== '' && !isset($registry['props'][$prop_key]) && !self::skip_unknown_prop_warning($prop, $parent_array_key, $registry)) {
			$warnings[] = array('key' => $prop_key, 'message' => 'Unknown array column "' . $prop_key . '" (not in core template).');
		}
	}

	private static function skip_unknown_prop_warning($prop, $parent_array_key, $registry)
	{
		if (isset($prop['display_options']['custom']) && $prop['display_options']['custom']) {
			return true;
		}
		if ($parent_array_key === null || $parent_array_key === '') {
			return false;
		}
		return !isset($registry['fields'][$parent_array_key]) && !isset($registry['props'][$parent_array_key]);
	}

	private static function validate_key_format($key)
	{
		$messages = array();
		$parts = explode('.', (string) $key);

		foreach ($parts as $part) {
			if ($part === '') {
				$messages[] = 'Key must not contain empty path segments.';
				break;
			}
		}

		foreach ($parts as $part) {
			if (!preg_match('/^[a-zA-Z0-9:_-]+$/', $part)) {
				$messages[] = 'Key segments may only contain letters, numbers, colons, underscores, and hyphens.';
				break;
			}
		}

		return $messages;
	}

	private static function resolve_prop_key($array_item, $prop)
	{
		if (isset($prop['prop_key']) && $prop['prop_key'] !== '') {
			return (string) $prop['prop_key'];
		}

		if (isset($array_item['key'], $prop['key']) && $array_item['key'] !== '' && $prop['key'] !== '') {
			return $array_item['key'] . '.' . $prop['key'];
		}

		return '';
	}

	private static function resolve_nested_array_key($item, $parent_array_key)
	{
		if (isset($item['prop_key']) && $item['prop_key'] !== '') {
			return (string) $item['prop_key'];
		}

		if ($parent_array_key && isset($item['key']) && $item['key'] !== '') {
			return $parent_array_key . '.' . $item['key'];
		}

		return isset($item['key']) ? (string) $item['key'] : $parent_array_key;
	}

	private static function is_prop_tree_section($prop)
	{
		return is_array($prop)
			&& isset($prop['type'])
			&& $prop['type'] === 'section'
			&& isset($prop['prop_key'])
			&& $prop['prop_key'] !== '';
	}

	private static function is_structural_type($type)
	{
		return in_array($type, self::$structural_types, true);
	}

	private static function is_layout_group_node($item)
	{
		if (!is_array($item)) {
			return false;
		}
		$type = isset($item['type']) ? $item['type'] : '';
		if (self::is_structural_type($type)) {
			return true;
		}
		if (!isset($item['items']) || !is_array($item['items']) || empty($item['items'])) {
			return false;
		}
		if (self::is_array_type($type)) {
			return false;
		}
		return !empty($item['key']);
	}

	private static function is_array_type($type)
	{
		return in_array($type, self::$array_types, true);
	}

	/**
	 * @param array<string, mixed> $item
	 * @param array<int, array{key: string, message: string}> $errors
	 * @param array<int, array{key: string, message: string}> $warnings
	 */
	private static function validate_composite_renderer($item, &$errors, &$warnings)
	{
		$CI =& get_instance();
		$CI->load->helper('display_renderer');

		$renderer_key = display_template_field_composite_renderer_key($item);
		if ($renderer_key === null || $renderer_key === '') {
			return;
		}

		$field_key = !empty($item['key']) ? (string) $item['key'] : 'template';
		$record = display_renderer_lookup($renderer_key);

		if (!$record) {
			$errors[] = array(
				'key' => $field_key,
				'message' => 'Unknown display renderer "' . $renderer_key . '".',
			);
			return;
		}

		if (!display_renderer_is_active($record)) {
			$warnings[] = array(
				'key' => $field_key,
				'message' => 'Display renderer "' . $renderer_key . '" is inactive.',
			);
		}

		$layout_type = isset($item['type']) ? (string) $item['type'] : '';
		if ($layout_type !== '' && !display_renderer_supported_for_layout_type($record, $layout_type)) {
			$errors[] = array(
				'key' => $field_key,
				'message' => 'Renderer "' . $renderer_key . '" is not supported for field type "' . $layout_type . '".',
			);
		}
	}

	private static function strip_runtime_ids($value)
	{
		if (!is_array($value)) {
			return $value;
		}

		unset($value['_tid']);
		foreach ($value as $k => $v) {
			if (is_array($v)) {
				$value[$k] = self::strip_runtime_ids($v);
			}
		}

		return $value;
	}
}
