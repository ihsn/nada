<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin display templates API
 *
 * Base URL: /api/admin/templates
 */
class Templates extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_admin_or_die();
		$this->load->model('Display_template_model');
	}

	/**
	 * GET /api/admin/templates
	 */
	public function index_get()
	{
		try {
			$data_type = $this->get('data_type');
			$status = $this->get('status');
			$templates = $this->Display_template_model->get_all_templates($data_type ?: null, $status ?: null);
			$this->set_response([
				'status' => 'success',
				'result' => ['templates' => $templates],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/templates/{uid}
	 */
	public function item_get($uid = null)
	{
		try {
			$template = $this->Display_template_model->get_template_by_uid($uid);
			if (!$template) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['template' => $template],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates
	 */
	public function index_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$this->assert_create_payload($input);
			$input = $this->merge_audit_ids($input, true);
			$template = $this->Display_template_model->create_template($input);
			$this->set_response([
				'status' => 'success',
				'result' => ['template' => $template],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/{uid}
	 */
	public function item_post($uid = null)
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			if (isset($input['template_json'])) {
				$errors = $this->validate_template_payload($input);
				if (!empty($errors)) {
					throw new Exception($errors[0]);
				}
			}
			$input = $this->merge_audit_ids($input, false);
			$template = $this->Display_template_model->update_template($uid, $input);
			if (!$template) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['template' => $template],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/{uid}/delete
	 */
	public function delete_post($uid = null)
	{
		try {
			$user_id = $this->session->userdata('user_id');
			$ok = $this->Display_template_model->delete_template($uid, $user_id ? (int) $user_id : null);
			if (!$ok) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['uid' => $uid],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/{uid}/duplicate
	 */
	public function duplicate_post($uid = null)
	{
		try {
			$user_id = $this->session->userdata('user_id');
			$template = $this->Display_template_model->duplicate_template($uid, $user_id ? (int) $user_id : null);
			$this->set_response([
				'status' => 'success',
				'result' => ['template' => $template],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/default/{data_type}/{uid}
	 */
	public function default_post($data_type = null, $uid = null)
	{
		try {
			$template = $this->Display_template_model->get_template_by_uid($uid);
			if (!$template) {
				$this->set_response(['status' => 'error', 'message' => 'Template not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if ($template['data_type'] !== $data_type) {
				$this->set_response(['status' => 'error', 'message' => 'data_type does not match template'], REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			$user_id = $this->session->userdata('user_id');
			$row = $this->Display_template_model->set_default_template($data_type, $uid, $user_id ? (int) $user_id : null);
			$this->set_response([
				'status' => 'success',
				'result' => ['default_template' => $row],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/import
	 */
	public function import_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$renderers = $this->load_renderers_registry();
			$converted = $this->convert_import_input_to_display_template($input, $renderers);
			$create_options = array(
				'uid' => isset($input['uid']) ? $input['uid'] : null,
				'template_type' => isset($input['template_type']) ? $input['template_type'] : 'imported',
				'data_type' => $converted['data_type'],
				'name' => $converted['name'],
				'version' => isset($input['version']) ? $input['version'] : null,
				'organization' => isset($input['organization']) ? $input['organization'] : null,
				'author' => isset($input['author']) ? $input['author'] : null,
				'description' => isset($input['description']) ? $input['description'] : null,
				'status' => isset($input['status']) ? $input['status'] : 'draft',
				'template_json' => $converted['template_json'],
			);
			$create_options = $this->merge_audit_ids($create_options, true);
			$template = $this->Display_template_model->create_template($create_options);
			$this->set_response([
				'status' => 'success',
				'result' => [
					'template' => $template,
					'import_summary' => $converted['summary'],
				],
			], REST_Controller::HTTP_CREATED);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/templates/{uid}/export
	 */
	public function export_get($uid = null)
	{
		try {
			$template = $this->Display_template_model->get_template_by_uid($uid);
			if (!$template) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['template' => $template],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/templates/validate
	 */
	public function validate_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$errors = $this->validate_template_payload($input);
			if (!empty($errors)) {
				throw new Exception($errors[0]);
			}
			$this->set_response([
				'status' => 'success',
				'result' => [
					'valid' => true,
					'errors' => [],
				],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'result' => [
					'valid' => false,
					'errors' => [$e->getMessage()],
				],
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/templates/renderers
	 */
	public function renderers_get()
	{
		try {
			$renderers = $this->load_renderers_registry();
			$this->set_response([
				'status' => 'success',
				'result' => ['renderers' => array_values($renderers)],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/templates/renderers/{source_type}
	 */
	public function renderers_by_type_get($source_type = null)
	{
		try {
			$all = $this->load_renderers_registry();
			$filtered = array();
			foreach ($all as $row) {
				$supported = isset($row['supported_source_types']) ? (array) $row['supported_source_types'] : array();
				if (in_array($source_type, $supported, true)) {
					$filtered[] = $row;
				}
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['renderers' => $filtered],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	private function load_renderers_registry()
	{
		$core = array();
		$custom = array();

		$core_path = APPPATH . 'config/display_renderers.php';
		$custom_path = APPPATH . 'config/display_renderers_custom.php';

		if (file_exists($core_path)) {
			$config = array();
			include $core_path;
			$core = isset($config['display_renderers']) && is_array($config['display_renderers'])
				? $config['display_renderers']
				: array();
		}

		if (file_exists($custom_path)) {
			$config = array();
			include $custom_path;
			$custom = isset($config['display_renderers_custom']) && is_array($config['display_renderers_custom'])
				? $config['display_renderers_custom']
				: array();
		}

		$merged = $core;
		foreach ($custom as $key => $row) {
			$merged[$key] = $row;
		}
		return $merged;
	}

	private function merge_audit_ids($input, $is_create)
	{
		$user_id = $this->session->userdata('user_id');
		if ($user_id) {
			$input['changed_by'] = (int) $user_id;
			if ($is_create) {
				$input['created_by'] = (int) $user_id;
			}
		}
		return $input;
	}

	private function validate_template_payload($input)
	{
		$errors = array();
		if (!isset($input['template_json'])) {
			$errors[] = 'template_json is required';
			return $errors;
		}
		$json_string = is_string($input['template_json'])
			? $input['template_json']
			: json_encode($input['template_json']);
		$decoded = json_decode($json_string, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$errors[] = 'Invalid template_json: ' . json_last_error_msg();
			return $errors;
		}
		if (!is_array($decoded)) {
			$errors[] = 'template_json must be a JSON object';
			return $errors;
		}
		if (!isset($decoded['sections']) || !is_array($decoded['sections'])) {
			$errors[] = 'template_json.sections must be an array';
		}
		return $errors;
	}

	private function assert_create_payload($input)
	{
		$required = array('data_type', 'name', 'template_json');
		foreach ($required as $field) {
			if (!array_key_exists($field, $input)) {
				throw new Exception($field . ' is required');
			}
		}
		$errors = $this->validate_template_payload($input);
		if (!empty($errors)) {
			throw new Exception($errors[0]);
		}
	}

	/**
	 * Accept either the form-definition root { type?, title?, items[] } or a catalog export envelope
	 * { uid?, name?, data_type?, lang?, template: { type, title, items[] }, ... }.
	 *
	 * @return array{0: array, 1: array|null} Form tree with items[], then optional envelope for defaults.
	 */
	private function resolve_metadata_editor_form_tree($raw)
	{
		if (!is_array($raw)) {
			return array(null, null);
		}
		$has_root_items = isset($raw['items']) && is_array($raw['items']);
		$nested_ok = isset($raw['template']) && is_array($raw['template'])
			&& isset($raw['template']['items']) && is_array($raw['template']['items']);
		if ($nested_ok) {
			return array($raw['template'], $raw);
		}
		if ($has_root_items) {
			return array($raw, null);
		}
		return array(null, null);
	}

	private function convert_import_input_to_display_template($input, $renderers)
	{
		$raw = null;
		if (isset($input['template']) && is_array($input['template'])) {
			$raw = $input['template'];
		} elseif (isset($input['template_json']) && is_array($input['template_json'])) {
			$raw = $input['template_json'];
		}
		if (!$raw) {
			throw new Exception('Import requires "template" or "template_json" object');
		}
		list($editor_template, $envelope) = $this->resolve_metadata_editor_form_tree($raw);
		if (!$editor_template || !isset($editor_template['items']) || !is_array($editor_template['items'])) {
			throw new Exception(
				'Imported metadata-editor template must contain items[] at the root or under "template"'
			);
		}

		$summary = array(
			'total_nodes' => 0,
			'total_sections' => 0,
			'total_fields' => 0,
			'missing_renderer' => 0,
			'warnings' => array(),
		);

		$sections = $this->convert_editor_nodes($editor_template['items'], $renderers, $summary, 0);
		$name = isset($input['name']) ? trim((string) $input['name']) : '';
		if ($name === '' && is_array($envelope) && isset($envelope['name'])) {
			$name = trim((string) $envelope['name']);
		}
		if ($name === '') {
			$name = isset($editor_template['title']) ? (string) $editor_template['title'] : 'Imported display template';
		}
		$data_type = isset($input['data_type']) ? trim((string) $input['data_type']) : '';
		if ($data_type === '') {
			$data_type = isset($input['type']) ? trim((string) $input['type']) : '';
		}
		if ($data_type === '' && is_array($envelope) && isset($envelope['data_type'])) {
			$data_type = trim((string) $envelope['data_type']);
		}
		if ($data_type === '') {
			throw new Exception('data_type is required for import (or include it on the exported template object)');
		}

		return array(
			'data_type' => $data_type,
			'name' => $name,
			'template_json' => array(
				'type' => 'display_template',
				'title' => $name,
				'data_type' => $data_type,
				'sections' => $sections,
			),
			'summary' => $summary,
		);
	}

	private function convert_editor_nodes($nodes, $renderers, &$summary, $depth)
	{
		$output = array();
		foreach ((array) $nodes as $node) {
			if (!is_array($node)) {
				continue;
			}
			$summary['total_nodes']++;
			$type = isset($node['type']) ? (string) $node['type'] : '';
			if ($type === 'section_container') {
				$summary['total_sections']++;
				$output[] = array(
					'node_type' => 'section_group',
					'key' => isset($node['key']) ? $node['key'] : 'section_group_' . $summary['total_nodes'],
					'title' => isset($node['title']) ? $node['title'] : 'Section group',
					'sections' => $this->convert_editor_nodes(isset($node['items']) ? $node['items'] : array(), $renderers, $summary, $depth + 1),
				);
				continue;
			}
			if ($type === 'section') {
				$summary['total_sections']++;
				$output[] = array(
					'node_type' => 'section',
					'key' => isset($node['key']) ? $node['key'] : 'section_' . $summary['total_nodes'],
					'title' => isset($node['title']) ? $node['title'] : 'Section',
					'fields' => $this->convert_editor_nodes(isset($node['items']) ? $node['items'] : array(), $renderers, $summary, $depth + 1),
				);
				continue;
			}

			if ($type === 'array' || $type === 'nested_array' || $type === 'simple_array') {
				$summary['total_fields']++;
				$source_type = $this->normalize_source_type($type);
				$renderer_key = $this->find_default_renderer_key($source_type, $renderers);
				if (!$renderer_key) {
					$summary['missing_renderer']++;
					$summary['warnings'][] = 'No renderer for source_type: ' . $source_type . ' (key: ' . (isset($node['key']) ? $node['key'] : 'n/a') . ')';
				}
				$parent_key = isset($node['key']) ? $node['key'] : 'field_' . $summary['total_nodes'];
				$nested = $this->convert_editor_props_to_fields(
					isset($node['props']) ? $node['props'] : array(),
					$parent_key,
					$renderers,
					$summary,
					$depth + 1
				);
				$field = array(
					'node_type' => 'field',
					'key' => $parent_key,
					'path' => $parent_key,
					'title' => isset($node['title']) ? $node['title'] : $parent_key,
					'source_type' => $source_type,
					'renderer' => $renderer_key,
					'renderer_options' => $this->default_renderer_params($renderer_key, $renderers),
					'label_override' => isset($node['title']) ? $node['title'] : null,
					'visibility_rules' => array(),
				);
				if (!empty($nested)) {
					$field['fields'] = $nested;
				}
				if (isset($node['items']) && is_array($node['items']) && count($node['items']) > 0) {
					$inner = $this->convert_editor_nodes($node['items'], $renderers, $summary, $depth + 1);
					if (!isset($field['fields'])) {
						$field['fields'] = array();
					}
					$field['fields'] = array_merge($field['fields'], $inner);
				}
				$output[] = $field;
				continue;
			}

			if (isset($node['items']) && is_array($node['items'])) {
				$children = $this->convert_editor_nodes($node['items'], $renderers, $summary, $depth + 1);
				$output = array_merge($output, $children);
				continue;
			}

			$summary['total_fields']++;
			$source_type = $this->normalize_source_type($type);
			$renderer_key = $this->find_default_renderer_key($source_type, $renderers);
			if (!$renderer_key) {
				$summary['missing_renderer']++;
				$summary['warnings'][] = 'No renderer for source_type: ' . $source_type . ' (key: ' . (isset($node['key']) ? $node['key'] : 'n/a') . ')';
			}
			$output[] = array(
				'node_type' => 'field',
				'key' => isset($node['key']) ? $node['key'] : 'field_' . $summary['total_nodes'],
				'path' => isset($node['key']) ? $node['key'] : null,
				'title' => isset($node['title']) ? $node['title'] : (isset($node['key']) ? $node['key'] : 'Field'),
				'source_type' => $source_type,
				'renderer' => $renderer_key,
				'renderer_options' => $this->default_renderer_params($renderer_key, $renderers),
				'label_override' => isset($node['title']) ? $node['title'] : null,
				'visibility_rules' => array(),
			);
		}
		return $output;
	}

	/**
	 * Map metadata-editor array/nested_array `props[]` into nested display-template field nodes.
	 *
	 * @param array $props
	 * @param string $parent_key
	 * @param array $renderers
	 * @param array $summary
	 * @param int $depth
	 * @return array
	 */
	private function convert_editor_props_to_fields($props, $parent_key, $renderers, &$summary, $depth)
	{
		$out = array();
		foreach ((array) $props as $idx => $prop) {
			if (!is_array($prop)) {
				continue;
			}
			$summary['total_nodes']++;
			$pkey = isset($prop['key']) ? (string) $prop['key'] : 'prop_' . $idx;
			$prop_key_full = isset($prop['prop_key']) ? (string) $prop['prop_key'] : $parent_key . '.' . $pkey;
			$ptype = isset($prop['type']) ? (string) $prop['type'] : 'string';

			if ($ptype === 'array' || $ptype === 'nested_array' || $ptype === 'simple_array') {
				$summary['total_fields']++;
				$source_type = $this->normalize_source_type($ptype);
				$renderer_key = $this->find_default_renderer_key($source_type, $renderers);
				if (!$renderer_key) {
					$summary['missing_renderer']++;
					$summary['warnings'][] = 'No renderer for source_type: ' . $source_type . ' (prop: ' . $prop_key_full . ')';
				}
				$nested = $this->convert_editor_props_to_fields(
					isset($prop['props']) ? $prop['props'] : array(),
					$prop_key_full,
					$renderers,
					$summary,
					$depth + 1
				);
				$row = array(
					'node_type' => 'field',
					'key' => $prop_key_full,
					'path' => $prop_key_full,
					'title' => isset($prop['title']) ? $prop['title'] : $pkey,
					'source_type' => $source_type,
					'renderer' => $renderer_key,
					'renderer_options' => $this->default_renderer_params($renderer_key, $renderers),
					'is_prop' => true,
					'parent_array_key' => $parent_key,
					'visibility_rules' => array(),
				);
				if (!empty($nested)) {
					$row['fields'] = $nested;
				}
				$out[] = $row;
				continue;
			}

			$summary['total_fields']++;
			$source_type = $this->normalize_source_type($ptype);
			$renderer_key = $this->find_default_renderer_key($source_type, $renderers);
			if (!$renderer_key) {
				$summary['missing_renderer']++;
				$summary['warnings'][] = 'No renderer for source_type: ' . $source_type . ' (prop: ' . $prop_key_full . ')';
			}
			$out[] = array(
				'node_type' => 'field',
				'key' => $prop_key_full,
				'path' => $prop_key_full,
				'title' => isset($prop['title']) ? $prop['title'] : $pkey,
				'source_type' => $source_type,
				'renderer' => $renderer_key,
				'renderer_options' => $this->default_renderer_params($renderer_key, $renderers),
				'is_prop' => true,
				'parent_array_key' => $parent_key,
				'visibility_rules' => array(),
			);
		}
		return $out;
	}

	private function normalize_source_type($type)
	{
		$map = array(
			'text' => 'string',
			'textarea' => 'string',
			'dropdown' => 'string',
			'dropdown-custom' => 'string',
			'simple_array' => 'array',
			'nested_array' => 'array',
			'nested_array_' => 'array',
		);
		return isset($map[$type]) ? $map[$type] : $type;
	}

	private function find_default_renderer_key($source_type, $renderers)
	{
		foreach ((array) $renderers as $key => $renderer) {
			if (isset($renderer['status']) && $renderer['status'] !== 'active') {
				continue;
			}
			$supported = isset($renderer['supported_source_types']) ? (array) $renderer['supported_source_types'] : array();
			if (in_array($source_type, $supported, true)) {
				return isset($renderer['key']) ? $renderer['key'] : $key;
			}
		}
		return null;
	}

	private function default_renderer_params($renderer_key, $renderers)
	{
		if (!$renderer_key) {
			return array();
		}
		foreach ((array) $renderers as $key => $renderer) {
			$r_key = isset($renderer['key']) ? $renderer['key'] : $key;
			if ($r_key === $renderer_key) {
				return isset($renderer['default_params']) && is_array($renderer['default_params'])
					? $renderer['default_params']
					: array();
			}
		}
		return array();
	}
}

