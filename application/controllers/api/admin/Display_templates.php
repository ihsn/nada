<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin display template manager API
 *
 * Base URL: /api/admin/display_templates
 */
class Display_templates extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->is_authenticated_or_die();
		$this->load->model('Display_template_model');
		$this->load->library('Display_template_validator');
		$this->load->helper('display_template');
		$this->_apply_display_template_acl();
	}

	private function _apply_display_template_acl()
	{
		$method = strtolower((string) $this->router->fetch_method());
		if ($method === '' || $method === 'index') {
			return;
		}
		if (preg_match('/_get$/', $method)) {
			$this->require_access('display_template', 'view');
			return;
		}
		if (preg_match('/delete/', $method)) {
			$this->require_access('display_template', 'delete');
			return;
		}
		if ($method === 'index_post') {
			$this->require_access('display_template', 'create');
			return;
		}
		if (preg_match('/_(post|put)$/', $method)) {
			$this->require_access('display_template', 'edit');
		}
	}

	/**
	 * GET /api/admin/display_templates
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
	 * GET /api/admin/display_templates/{uid}
	 */
	public function item_get($uid = null)
	{
		try {
			$template = $this->Display_template_model->get_template_by_uid($uid);
			if (!$template) {
				$this->set_response(['status' => 'error', 'message' => 'Not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			$result = array('template' => $template);
			$defaults = $this->Display_template_model->get_defaults_map();
			if (!empty($template['data_type']) && !empty($template['uid'])) {
				$template['default'] = isset($defaults[$template['data_type']])
					&& $defaults[$template['data_type']] === $template['uid'];
				$result['template'] = $template;
			}
			$core = $this->resolve_core_baseline_for_template($template);
			if ($core) {
				$result['core_template'] = $core['template_json'];
				$result['core_template_parts'] = $this->Display_template_model->get_template_parts($core['template_json']);
				$result['core_template_uid'] = $core['uid'];
			}
			$this->set_response([
				'status' => 'success',
				'result' => $result,
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/display_templates/cores
	 */
	public function cores_get()
	{
		try {
			$data_type = $this->get('data_type');
			$cores = $this->Display_template_model->get_core_templates_by_type($data_type ?: null);
			$defaults = $this->Display_template_model->get_defaults_map();
			foreach ($cores as $idx => $core) {
				$cores[$idx]['default'] = isset($defaults[$core['data_type']])
					&& $defaults[$core['data_type']] === $core['uid'];
			}
			$this->set_response([
				'status' => 'success',
				'result' => ['cores' => array_values($cores)],
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/admin/display_templates/core/{data_type_or_uid}
	 */
	public function core_get($identifier = null)
	{
		try {
			$core = $this->Display_template_model->get_core_template_by_uid($identifier);
			if (!$core) {
				$core = $this->Display_template_model->get_default_core_template($identifier);
			}
			if (!$core) {
				$this->set_response(['status' => 'error', 'message' => 'Core template not found'], REST_Controller::HTTP_NOT_FOUND);
				return;
			}
			if (empty($core['template_json'])) {
				$core['template_json'] = $this->Display_template_model->get_core_template_json($core['uid']);
			}
			$this->set_response([
				'status' => 'success',
				'result' => array(
					'core' => $core,
					'core_template' => $core['template_json'],
					'core_template_parts' => $this->Display_template_model->get_template_parts($core['template_json']),
				),
			], REST_Controller::HTTP_OK);
		} catch (Exception $e) {
			$this->set_response([
				'status' => 'error',
				'message' => $e->getMessage(),
			], REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * POST /api/admin/display_templates
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
	 * POST /api/admin/display_templates/{uid}
	 */
	public function item_post($uid = null)
	{
		try {
			if ($this->Display_template_model->is_core_template_uid($uid)) {
				throw new Exception('System core templates cannot be edited');
			}
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			if (isset($input['template_json'])) {
				$existing = $this->Display_template_model->get_template_by_uid($uid);
				$errors = $this->validate_template_payload($input, $existing);
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
	 * POST /api/admin/display_templates/{uid}/delete
	 */
	public function delete_post($uid = null)
	{
		try {
			if ($this->Display_template_model->is_core_template_uid($uid)) {
				throw new Exception('System core templates cannot be deleted');
			}
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
	 * POST /api/admin/display_templates/{uid}/duplicate
	 */
	public function duplicate_post($uid = null)
	{
		try {
			if ($this->Display_template_model->is_core_template_uid($uid)) {
				$core = $this->Display_template_model->get_template_by_uid($uid);
				if (!$core || empty($core['template_json'])) {
					throw new Exception('Core template not found');
				}
				$user_id = $this->session->userdata('user_id');
				$create_options = array(
					'template_type' => 'custom',
					'data_type' => $core['data_type'],
					'name' => $core['name'] . ' - copy',
					'version' => isset($core['version']) ? $core['version'] : null,
					'organization' => isset($core['organization']) ? $core['organization'] : null,
					'author' => isset($core['author']) ? $core['author'] : null,
					'description' => isset($core['description']) ? $core['description'] : null,
					'status' => 'draft',
					'template_json' => $core['template_json'],
				);
				$create_options = $this->merge_audit_ids($create_options, true);
				$template = $this->Display_template_model->create_template($create_options);
				$this->set_response([
					'status' => 'success',
					'result' => ['template' => $template],
				], REST_Controller::HTTP_CREATED);
				return;
			}
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
	 * POST /api/admin/display_templates/default/{data_type}/{uid}
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
	 * POST /api/admin/display_templates/import
	 */
	public function import_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$converted = $this->resolve_import_template($input);
			$copy_if_uid_exists = !empty($input['copy_if_uid_exists']);
			$uid = $converted['uid'];
			$name = $converted['name'];

			if ($uid !== '' && $this->Display_template_model->uid_exists($uid)) {
				if ($copy_if_uid_exists) {
					$uid = null;
					if (substr($name, -7) !== ' - copy') {
						$name .= ' - copy';
					}
				} else {
					throw new Exception(
						'A display template with UID "' . $uid . '" already exists. '
						. 'Enable "Create as new copy if UID already exists" to import with a new UID.'
					);
				}
			}

			$create_options = array(
				'uid' => $uid,
				'template_type' => isset($input['template_type']) ? $input['template_type'] : 'imported',
				'data_type' => $converted['data_type'],
				'name' => $name,
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
	 * GET /api/admin/display_templates/{uid}/export
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
	 * POST /api/admin/display_templates/validate
	 */
	public function validate_post()
	{
		try {
			$input = $this->raw_json_input();
			if (!$input || !is_array($input)) {
				throw new Exception('JSON body required');
			}
			$template = null;
			if (!empty($input['uid'])) {
				$template = $this->Display_template_model->get_template_by_uid($input['uid']);
			}
			$core_template_json = $this->resolve_core_template_json_for_validation($input, $template);
			$decoded = isset($input['template_json']) ? $input['template_json'] : null;
			if (is_string($decoded)) {
				$decoded = json_decode($decoded, true);
			}
			if (!is_array($decoded)) {
				throw new Exception('Invalid template_json');
			}
			$result = Display_template_validator::validate_tree($decoded, $core_template_json);
			$error_strings = array();
			foreach ($result['errors'] as $issue) {
				$key = isset($issue['key']) ? $issue['key'] : 'template';
				$message = isset($issue['message']) ? $issue['message'] : 'Validation failed';
				$error_strings[] = $key . ': ' . $message;
			}
			$warning_strings = array();
			foreach ($result['warnings'] as $issue) {
				$key = isset($issue['key']) ? $issue['key'] : 'template';
				$message = isset($issue['message']) ? $issue['message'] : 'Validation warning';
				$warning_strings[] = $key . ': ' . $message;
			}
			if (!empty($error_strings)) {
				$this->set_response([
					'status' => 'success',
					'result' => [
						'valid' => false,
						'errors' => $error_strings,
						'warnings' => $warning_strings,
					],
				], REST_Controller::HTTP_OK);
				return;
			}
			$this->set_response([
				'status' => 'success',
				'result' => [
					'valid' => true,
					'errors' => [],
					'warnings' => $warning_strings,
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
	 * GET /api/admin/display_templates/renderers
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
	 * GET /api/admin/display_templates/renderers/{source_type}
	 */
	public function renderers_by_type_get($source_type = null)
	{
		try {
			$this->load->helper('display_renderer');
			$all = $this->load_renderers_registry();
			$data_type = $this->get('data_type');
			$filtered = array();
			foreach ($all as $row) {
				$supported = isset($row['supported_source_types']) ? (array) $row['supported_source_types'] : array();
				if (!in_array($source_type, $supported, true)) {
					continue;
				}
				if (!display_renderer_supported_for_data_type($row, $data_type)) {
					continue;
				}
				$filtered[] = $row;
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

	private function resolve_core_baseline_for_template($template)
	{
		if (!is_array($template) || empty($template['data_type'])) {
			return null;
		}
		if (($template['template_type'] ?? '') === 'system' || !empty($template['is_core'])) {
			return $template;
		}
		return $this->Display_template_model->get_default_core_template($template['data_type']);
	}

	private function validate_template_payload($input, $template = null)
	{
		$core_template_json = $this->resolve_core_template_json_for_validation($input, $template);
		return Display_template_validator::validate_payload($input, $core_template_json);
	}

	private function resolve_core_template_json_for_validation($input, $template = null)
	{
		if (is_array($template)) {
			$core = $this->resolve_core_baseline_for_template($template);
			if ($core && !empty($core['template_json'])) {
				return $core['template_json'];
			}
		}

		$data_type = null;
		if (is_array($template) && !empty($template['data_type'])) {
			$data_type = $template['data_type'];
		} elseif (isset($input['data_type']) && $input['data_type'] !== '') {
			$data_type = $input['data_type'];
		}

		if ($data_type) {
			$core = $this->Display_template_model->get_default_core_template($data_type);
			if ($core && !empty($core['template_json'])) {
				return $core['template_json'];
			}
		}

		return null;
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

	private function resolve_import_template($input)
	{
		$raw = $this->resolve_import_raw($input);
		if (!$raw) {
			throw new Exception('Import requires JSON with items[] at the root, under "template", or a display export envelope');
		}

		list($editor_template, $envelope) = $this->resolve_import_editor_tree($raw);
		if (!$editor_template || !isset($editor_template['items']) || !is_array($editor_template['items'])) {
			throw new Exception(
				'Imported template must contain items[] at the root or under "template" (metadata-editor / display format)'
			);
		}

		if (!isset($editor_template['type'])) {
			$editor_template['type'] = 'template';
		}
		if (!isset($editor_template['title'])) {
			$editor_template['title'] = '';
		}

		$name = $this->resolve_import_name($input, $raw, $envelope, $editor_template);
		$data_type = $this->resolve_import_data_type($input, $raw, $envelope);
		if ($data_type === '') {
			throw new Exception('Imported JSON must include data_type (display export envelope or legacy display template file)');
		}

		$uid = $this->resolve_import_uid($input, $raw, $envelope);

		$skipped_custom = array();
		$editor_template['items'] = display_template_omit_custom_section_containers(
			$editor_template['items'],
			$skipped_custom
		);
		$summary = $this->summarize_template_tree($editor_template['items']);
		$summary['skipped_custom_section_containers'] = $skipped_custom;

		return array(
			'uid' => $uid,
			'data_type' => $data_type,
			'name' => $name,
			'template_json' => $editor_template,
			'summary' => $summary,
		);
	}

	/**
	 * Resolve editor tree from legacy envelope, bare tree, or DB export record shapes.
	 *
	 * @return array{0: array|null, 1: array|null}
	 */
	private function resolve_import_editor_tree($raw)
	{
		if (!is_array($raw)) {
			return array(null, null);
		}

		list($tree, $envelope) = $this->resolve_metadata_editor_form_tree($raw);
		if ($tree) {
			return array($tree, $envelope ?: $raw);
		}

		if (isset($raw['template_json']) && is_array($raw['template_json'])
			&& isset($raw['template_json']['items']) && is_array($raw['template_json']['items'])) {
			return array($raw['template_json'], $raw);
		}

		if (isset($raw['template']) && is_array($raw['template'])) {
			$inner = $raw['template'];
			if (isset($inner['template_json']) && is_array($inner['template_json'])
				&& isset($inner['template_json']['items']) && is_array($inner['template_json']['items'])) {
				return array($inner['template_json'], $inner);
			}
			list($tree, $envelope) = $this->resolve_metadata_editor_form_tree($inner);
			if ($tree) {
				return array($tree, $envelope ?: $inner);
			}
		}

		return array(null, null);
	}

	private function resolve_import_raw($input)
	{
		if (!is_array($input)) {
			return null;
		}
		if (isset($input['data_type']) && isset($input['template']) && is_array($input['template'])) {
			return $input;
		}
		if (isset($input['template']) && is_array($input['template'])) {
			$nested = $input['template'];
			if (isset($nested['data_type']) && isset($nested['template']) && is_array($nested['template'])) {
				return $nested;
			}
			return $nested;
		}
		if (isset($input['template_json']) && is_array($input['template_json'])) {
			return $input['template_json'];
		}
		if (isset($input['items']) && is_array($input['items'])) {
			return $input;
		}
		return null;
	}

	private function resolve_import_name($input, $raw, $envelope, $editor_template)
	{
		$name = isset($input['name']) ? trim((string) $input['name']) : '';
		if ($name !== '') {
			return $name;
		}
		if (is_array($envelope) && isset($envelope['name'])) {
			$name = trim((string) $envelope['name']);
			if ($name !== '') {
				return $name;
			}
		}
		if (isset($raw['name'])) {
			$name = trim((string) $raw['name']);
			if ($name !== '') {
				return $name;
			}
		}
		if (isset($editor_template['title'])) {
			$title = trim((string) $editor_template['title']);
			if ($title !== '') {
				return $title;
			}
		}
		return 'Imported display template';
	}

	private function resolve_import_data_type($input, $raw, $envelope)
	{
		$data_type = isset($input['data_type']) ? trim((string) $input['data_type']) : '';
		if ($data_type !== '') {
			return $data_type;
		}
		if (is_array($envelope) && isset($envelope['data_type'])) {
			$data_type = trim((string) $envelope['data_type']);
			if ($data_type !== '') {
				return $data_type;
			}
		}
		if (isset($raw['data_type'])) {
			$data_type = trim((string) $raw['data_type']);
			if ($data_type !== '') {
				return $data_type;
			}
		}
		return '';
	}

	private function resolve_import_uid($input, $raw, $envelope)
	{
		$candidates = array();
		if (isset($input['uid'])) {
			$candidates[] = trim((string) $input['uid']);
		}
		if (is_array($envelope) && isset($envelope['uid'])) {
			$candidates[] = trim((string) $envelope['uid']);
		}
		if (isset($raw['uid'])) {
			$candidates[] = trim((string) $raw['uid']);
		}
		foreach ($candidates as $uid) {
			if ($uid !== '') {
				return $uid;
			}
		}
		return '';
	}

	private function summarize_template_tree($items)
	{
		$summary = array(
			'total_nodes' => 0,
			'total_sections' => 0,
			'total_fields' => 0,
			'warnings' => array(),
		);
		$this->walk_template_nodes($items, $summary);
		return $summary;
	}

	private function walk_template_nodes($nodes, &$summary)
	{
		foreach ((array) $nodes as $node) {
			if (!is_array($node)) {
				continue;
			}
			$summary['total_nodes']++;
			$type = isset($node['type']) ? (string) $node['type'] : '';
			if ($type === 'section_container' || $type === 'section') {
				$summary['total_sections']++;
				if (isset($node['items']) && is_array($node['items'])) {
					$this->walk_template_nodes($node['items'], $summary);
				}
				continue;
			}
			$summary['total_fields']++;
			if (isset($node['props']) && is_array($node['props'])) {
				$this->walk_template_nodes($node['props'], $summary);
			}
			if (isset($node['items']) && is_array($node['items'])) {
				$this->walk_template_nodes($node['items'], $summary);
			}
		}
	}
}

