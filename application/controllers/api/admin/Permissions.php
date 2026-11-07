<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Admin permissions / roles API.
 *
 * Base URL: /api/admin/permissions
 * Auth: session or API key; site admin only (same gate as admin/Permissions).
 *
 *   GET  resources
 *   GET  roles
 *   POST roles
 *   GET  roles/{id}
 *   POST roles/{id}
 *   POST roles/{id}/delete
 *   GET  roles/{id}/permissions
 *   POST roles/{id}/permissions   body: { "resources": { "menu": ["view","edit"], ... } }
 */
class Permissions extends MY_REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('acl_manager');
		$this->is_authenticated_or_die();
		$this->_require_site_admin();
	}

	/**
	 * GET /api/admin/permissions/resources
	 *
	 * ACL resources and allowed privileges from acl_permissions config.
	 */
	public function resources_get()
	{
		try {
			$this->set_response(
				array(
					'status'    => 'success',
					'resources' => $this->_format_resources($this->acl_manager->get_all_permissions()),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * GET /api/admin/permissions/roles
	 */
	public function roles_get()
	{
		try {
			$this->set_response(
				array(
					'status' => 'success',
					'roles'  => $this->_format_roles($this->acl_manager->get_roles()),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * POST /api/admin/permissions/roles
	 *
	 * JSON: role (required), description, weight
	 */
	public function roles_post()
	{
		try {
			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$role = $this->_normalize_role_name(isset($input['role']) ? $input['role'] : (isset($input['name']) ? $input['name'] : ''));
			$description = $this->_normalize_description(isset($input['description']) ? $input['description'] : '');
			$weight = $this->_normalize_weight(isset($input['weight']) ? $input['weight'] : 0);

			if ($role === '') {
				throw new Exception('ROLE_REQUIRED');
			}

			$this->acl_manager->create_role($role, $description, $weight);
			$created = $this->acl_manager->get_role_by_name($role);
			if (! $created) {
				throw new Exception('CREATE_FAILED');
			}

			$this->set_response(
				array(
					'status' => 'success',
					'role'   => $this->_format_role($created),
				),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array('status' => 'error', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * GET /api/admin/permissions/roles/{id}
	 */
	public function role_get($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->set_response(
				array(
					'status' => 'success',
					'role'   => $this->_format_role($role),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = ($e->getMessage() === 'NOT_FOUND') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * POST /api/admin/permissions/roles/{id}
	 *
	 * JSON: role, description, weight
	 */
	public function role_post($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->_reject_locked($role);

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$name = array_key_exists('role', $input)
				? $this->_normalize_role_name($input['role'])
				: (array_key_exists('name', $input)
					? $this->_normalize_role_name($input['name'])
					: (string) $role['name']);
			$description = array_key_exists('description', $input)
				? $this->_normalize_description($input['description'])
				: (isset($role['description']) ? (string) $role['description'] : '');
			$weight = array_key_exists('weight', $input)
				? $this->_normalize_weight($input['weight'])
				: (isset($role['weight']) ? (int) $role['weight'] : 0);

			if ($name === '') {
				throw new Exception('ROLE_REQUIRED');
			}

			$ok = $this->acl_manager->update_role((int) $role['id'], $name, $description, $weight);
			if ($ok !== TRUE) {
				throw new Exception('UPDATE_FAILED');
			}

			$updated = $this->acl_manager->get_role_by_id((int) $role['id']);
			$this->set_response(
				array(
					'status' => 'success',
					'role'   => $this->_format_role($updated),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = REST_Controller::HTTP_BAD_REQUEST;
			if ($e->getMessage() === 'NOT_FOUND') {
				$code = REST_Controller::HTTP_NOT_FOUND;
			}
			elseif ($e->getMessage() === 'ROLE_LOCKED') {
				$code = REST_Controller::HTTP_FORBIDDEN;
			}
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * POST /api/admin/permissions/roles/{id}/delete
	 * DELETE /api/admin/permissions/roles/{id}
	 */
	public function role_delete_post($id = null)
	{
		return $this->role_delete($id);
	}

	public function role_delete($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->_reject_locked($role);

			$role_id = (int) $role['id'];
			// delete_role also clears user_roles + role_permissions
			$this->acl_manager->delete_role($role_id);

			$this->set_response(
				array('status' => 'success', 'message' => 'DELETED'),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = REST_Controller::HTTP_BAD_REQUEST;
			if ($e->getMessage() === 'NOT_FOUND') {
				$code = REST_Controller::HTTP_NOT_FOUND;
			}
			elseif ($e->getMessage() === 'ROLE_LOCKED') {
				$code = REST_Controller::HTTP_FORBIDDEN;
			}
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * GET /api/admin/permissions/roles/{id}/permissions
	 */
	public function role_permissions_get($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->set_response(
				array(
					'status'    => 'success',
					'role'      => $this->_format_role($role),
					'resources' => $this->_role_resources_map((int) $role['id']),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = ($e->getMessage() === 'NOT_FOUND') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST;
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	/**
	 * POST /api/admin/permissions/roles/{id}/permissions
	 *
	 * Replaces all role permissions. JSON:
	 *   { "resources": { "menu": ["view","edit"], "catalog": ["view"] } }
	 * Empty resources clears all grants (same as unchecking every box in the PHP UI).
	 */
	public function role_permissions_post($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->_reject_locked($role);

			$input = $this->raw_json_input();
			if (! is_array($input)) {
				throw new Exception('INVALID_JSON_INPUT');
			}

			$resources = array();
			if (isset($input['resources']) && is_array($input['resources'])) {
				$resources = $input['resources'];
			}
			elseif (isset($input['resource']) && is_array($input['resource'])) {
				// PHP form field name compatibility
				$resources = $input['resource'];
			}

			$acl_permissions = $this->acl_manager->get_all_permissions();
			$allowed_resources = is_array($acl_permissions) ? array_keys($acl_permissions) : array();

			$role_id = (int) $role['id'];
			$this->acl_manager->remove_role_permissions($role_id);

			foreach ($resources as $resource => $permissions) {
				$resource = trim((string) $resource);
				if ($resource === '' || ! in_array($resource, $allowed_resources, true)) {
					continue;
				}
				$perms = $this->_normalize_permission_list($permissions, $resource, $acl_permissions);
				if (empty($perms)) {
					continue;
				}
				$this->acl_manager->set_role_permissions($role_id, $resource, $perms);
			}

			$this->set_response(
				array(
					'status'    => 'success',
					'role'      => $this->_format_role($this->acl_manager->get_role_by_id($role_id)),
					'resources' => $this->_role_resources_map($role_id),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$code = REST_Controller::HTTP_BAD_REQUEST;
			if ($e->getMessage() === 'NOT_FOUND') {
				$code = REST_Controller::HTTP_NOT_FOUND;
			}
			elseif ($e->getMessage() === 'ROLE_LOCKED') {
				$code = REST_Controller::HTTP_FORBIDDEN;
			}
			$this->set_response(array('status' => 'error', 'message' => $e->getMessage()), $code);
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function _require_site_admin()
	{
		$user = $this->api_user();
		if (! $user || ! $this->acl_manager->user_is_admin($user)) {
			$this->set_response(
				array('status' => 'error', 'message' => 'ACCESS_DENIED'),
				REST_Controller::HTTP_FORBIDDEN
			);
			exit;
		}
	}

	/**
	 * @param mixed $id
	 * @return array
	 * @throws Exception
	 */
	private function _require_role($id)
	{
		if (! is_numeric($id)) {
			throw new Exception('INVALID_ID');
		}
		$role = $this->acl_manager->get_role_by_id((int) $id);
		if (empty($role)) {
			throw new Exception('NOT_FOUND');
		}
		return $role;
	}

	/**
	 * @param array $role
	 * @throws Exception
	 */
	private function _reject_locked($role)
	{
		if (! empty($role['is_locked']) && (int) $role['is_locked'] === 1) {
			throw new Exception('ROLE_LOCKED');
		}
	}

	/**
	 * @param array $roles
	 * @return array
	 */
	private function _format_roles($roles)
	{
		$out = array();
		if (! is_array($roles)) {
			return $out;
		}
		foreach ($roles as $role) {
			$out[] = $this->_format_role($role);
		}
		return $out;
	}

	/**
	 * @param array|null $role
	 * @return array|null
	 */
	private function _format_role($role)
	{
		if (! is_array($role) || empty($role)) {
			return null;
		}
		return array(
			'id'          => isset($role['id']) ? (int) $role['id'] : 0,
			'name'        => isset($role['name']) ? (string) $role['name'] : '',
			'description' => isset($role['description']) ? (string) $role['description'] : '',
			'weight'      => isset($role['weight']) ? (int) $role['weight'] : 0,
			'is_locked'   => ! empty($role['is_locked']) && (int) $role['is_locked'] === 1,
			'is_admin'    => ! empty($role['is_admin']) && (int) $role['is_admin'] === 1,
		);
	}

	/**
	 * Normalize acl_permissions config into a stable resource list.
	 *
	 * @param mixed $acl_permissions
	 * @return array
	 */
	private function _format_resources($acl_permissions)
	{
		$out = array();
		if (! is_array($acl_permissions)) {
			return $out;
		}
		foreach ($acl_permissions as $resource => $meta) {
			$perms = array();
			if (isset($meta['permissions']) && is_array($meta['permissions'])) {
				foreach ($meta['permissions'] as $p) {
					if (! is_array($p) || empty($p['permission'])) {
						continue;
					}
					$perms[] = array(
						'permission'  => (string) $p['permission'],
						'description' => isset($p['description']) ? (string) $p['description'] : '',
					);
				}
			}
			$out[] = array(
				'resource'    => (string) $resource,
				'title'       => isset($meta['title']) ? (string) $meta['title'] : (string) $resource,
				'permissions' => $perms,
			);
		}
		return $out;
	}

	/**
	 * @param int $role_id
	 * @return array resource => string[]
	 */
	private function _role_resources_map($role_id)
	{
		$map = array();
		$rows = $this->acl_manager->get_role_permissions($role_id);
		if (! is_array($rows)) {
			return $map;
		}
		foreach ($rows as $row) {
			$resource = isset($row['resource']) ? (string) $row['resource'] : '';
			if ($resource === '') {
				continue;
			}
			$perms = isset($row['permissions']) && is_array($row['permissions']) ? $row['permissions'] : array();
			$clean = array();
			foreach ($perms as $p) {
				$p = trim((string) $p);
				if ($p !== '') {
					$clean[] = $p;
				}
			}
			$map[$resource] = array_values(array_unique($clean));
		}
		return $map;
	}

	/**
	 * @param mixed $permissions
	 * @param string $resource
	 * @param array $acl_permissions
	 * @return string[]
	 */
	private function _normalize_permission_list($permissions, $resource, $acl_permissions)
	{
		if (! is_array($permissions)) {
			$permissions = array($permissions);
		}

		$allowed = array();
		if (isset($acl_permissions[$resource]['permissions']) && is_array($acl_permissions[$resource]['permissions'])) {
			foreach ($acl_permissions[$resource]['permissions'] as $p) {
				if (is_array($p) && ! empty($p['permission'])) {
					$allowed[] = (string) $p['permission'];
				}
			}
		}

		$out = array();
		foreach ($permissions as $p) {
			$p = trim((string) $p);
			if ($p === '') {
				continue;
			}
			if (! empty($allowed) && ! in_array($p, $allowed, true)) {
				continue;
			}
			$out[] = $p;
		}
		return array_values(array_unique($out));
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function _normalize_role_name($value)
	{
		$name = trim((string) $value);
		if ($name === '') {
			return '';
		}
		if (strlen($name) > 100 || ! preg_match('/^[A-Za-z0-9 ]+$/', $name)) {
			throw new Exception('INVALID_ROLE_NAME');
		}
		return $name;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function _normalize_description($value)
	{
		$description = trim((string) $value);
		if (strlen($description) > 255) {
			$description = substr($description, 0, 255);
		}
		return $description;
	}

	/**
	 * @param mixed $value
	 * @return int
	 */
	private function _normalize_weight($value)
	{
		if ($value === null || $value === '') {
			return 0;
		}
		if (! is_numeric($value) || (int) $value < 0) {
			throw new Exception('INVALID_WEIGHT');
		}
		$weight = (int) $value;
		if ($weight > 999) {
			$weight = 999;
		}
		return $weight;
	}
}
