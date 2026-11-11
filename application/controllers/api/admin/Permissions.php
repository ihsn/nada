<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

class Permissions_api_error extends Exception
{
	public $status;

	public function __construct($message, $status = 400)
	{
		parent::__construct($message);
		$this->status = $status;
	}
}

/**
 * Admin permissions / roles API.
 *
 * Base URL: /api/admin/permissions
 * Auth: browser session only; site admin only (same gate as admin/Permissions).
 * Writes need a CSRF token (require_session_csrf). System roles (locked or admin) are read-only.
 *
 *   GET  resources
 *   GET  roles
 *   POST roles                    body: { "role", "description", "weight" }
 *   GET  roles/{id}
 *   POST roles/{id}               body: any of { "role", "description", "weight", "resources" }
 *   POST roles/{id}/delete
 *   GET  roles/{id}/permissions
 *   POST roles/{id}/permissions   body: { "resources": { "menu": ["view","edit"], ... } }
 */
class Permissions extends MY_REST_Controller {

	const MAX_ROLE_NAME = 100;
	const MAX_DESCRIPTION = 255;
	const MAX_WEIGHT = 999;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('acl_manager');
		$this->_require_session_user();
		$this->_require_site_admin();
		$this->require_session_csrf();
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
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/permissions/roles
	 */
	public function roles_get()
	{
		try {
			$counts = $this->_role_user_counts();
			$roles = array();
			foreach ((array) $this->acl_manager->get_roles() as $role) {
				$roles[] = $this->_format_role($role, $counts);
			}
			$this->set_response(
				array('status' => 'success', 'roles' => $roles),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/permissions/roles
	 */
	public function roles_post()
	{
		try {
			$input = $this->_json_body();

			$role = $this->_normalize_role_name(isset($input['role']) ? $input['role'] : (isset($input['name']) ? $input['name'] : ''));
			$description = $this->_normalize_description(isset($input['description']) ? $input['description'] : '');
			$weight = $this->_normalize_weight(isset($input['weight']) ? $input['weight'] : 0);

			$this->_assert_unique_name($role, 0);
			$this->acl_manager->create_role($role, $description, $weight);
			$created = $this->acl_manager->get_role_by_name($role);
			if (! $created) {
				throw new Exception('CREATE_FAILED');
			}

			$this->_audit('role-create', sprintf('role created: id=%d name="%s"', (int) $created['id'], $role));

			$this->set_response(
				array('status' => 'success', 'role' => $this->_format_role($created)),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
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
				array('status' => 'success', 'role' => $this->_format_role($role)),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/permissions/roles/{id}
	 *
	 * Partial update. Only the keys present are changed; "resources" replaces all grants.
	 * Details and permissions are saved in one transaction.
	 */
	public function role_post($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->_reject_system_role($role);
			$input = $this->_json_body();

			$has_details = array_key_exists('role', $input) || array_key_exists('name', $input)
				|| array_key_exists('description', $input) || array_key_exists('weight', $input);
			$has_resources = array_key_exists('resources', $input);

			if (! $has_details && ! $has_resources) {
				throw new Permissions_api_error('NOTHING_TO_UPDATE');
			}

			$name = (string) $role['name'];
			if (array_key_exists('role', $input)) {
				$name = $this->_normalize_role_name($input['role']);
			}
			elseif (array_key_exists('name', $input)) {
				$name = $this->_normalize_role_name($input['name']);
			}
			$description = array_key_exists('description', $input)
				? $this->_normalize_description($input['description'])
				: (string) $role['description'];
			$weight = array_key_exists('weight', $input)
				? $this->_normalize_weight($input['weight'])
				: (int) $role['weight'];

			$resources = $has_resources ? $this->_normalize_resources($input['resources']) : null;

			if ($has_details) {
				$this->_assert_unique_name($name, (int) $role['id']);
			}

			$this->_save_role($role, $has_details ? compact('name', 'description', 'weight') : null, $resources);

			$role_id = (int) $role['id'];
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
			$this->_error_response($e);
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
			$this->_reject_system_role($role);

			$role_id = (int) $role['id'];
			$counts = $this->_role_user_counts();
			$user_count = isset($counts[$role_id]) ? $counts[$role_id] : 0;

			$this->db->trans_begin();
			try {
				// delete_role also clears user_roles + role_permissions
				$this->acl_manager->delete_role($role_id);
			}
			catch (Exception $e) {
				$this->db->trans_rollback();
				throw $e;
			}
			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				throw new Exception('DELETE_FAILED');
			}
			$this->db->trans_commit();

			$this->_audit('role-delete', sprintf(
				'role deleted: id=%d name="%s"; removed from %d user(s)',
				$role_id,
				$role['name'],
				$user_count
			));

			$this->set_response(
				array('status' => 'success', 'message' => 'DELETED'),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
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
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/permissions/roles/{id}/permissions
	 *
	 * Replaces all role permissions. JSON:
	 *   { "resources": { "menu": ["view","edit"], "catalog": ["view"] } }
	 * Empty resources clears all grants.
	 */
	public function role_permissions_post($id = null)
	{
		try {
			$role = $this->_require_role($id);
			$this->_reject_system_role($role);
			$input = $this->_json_body();

			$resources = array();
			if (isset($input['resources'])) {
				$resources = $input['resources'];
			}
			elseif (isset($input['resource'])) {
				// PHP form field name compatibility
				$resources = $input['resource'];
			}

			$this->_save_role($role, null, $this->_normalize_resources($resources));

			$role_id = (int) $role['id'];
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
			$this->_error_response($e);
		}
	}

	// -------------------------------------------------------------------------
	// Saving
	// -------------------------------------------------------------------------

	/**
	 * @param array      $role
	 * @param array|null $details   name, description, weight
	 * @param array|null $resources resource => string[]; replaces all grants
	 */
	private function _save_role($role, $details, $resources)
	{
		$role_id = (int) $role['id'];
		$before = $resources !== null ? $this->_role_resources_map($role_id) : array();

		$this->db->trans_begin();
		try {
			if ($details !== null) {
				$this->acl_manager->update_role($role_id, $details['name'], $details['description'], $details['weight']);
			}
			if ($resources !== null) {
				$this->acl_manager->remove_role_permissions($role_id);
				foreach ($resources as $resource => $perms) {
					$this->acl_manager->set_role_permissions($role_id, $resource, $perms);
				}
			}
		}
		catch (Exception $e) {
			$this->db->trans_rollback();
			throw $e;
		}
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			throw new Exception('UPDATE_FAILED');
		}
		$this->db->trans_commit();

		if ($details !== null) {
			$changes = array();
			if ($details['name'] !== (string) $role['name']) {
				$changes[] = sprintf('name "%s" -> "%s"', $role['name'], $details['name']);
			}
			if ($details['description'] !== (string) $role['description']) {
				$changes[] = 'description';
			}
			if ($details['weight'] !== (int) $role['weight']) {
				$changes[] = sprintf('weight %d -> %d', (int) $role['weight'], $details['weight']);
			}
			if ($changes) {
				$this->_audit('role-update', sprintf('role id=%d "%s" updated: %s', $role_id, $details['name'], implode('; ', $changes)));
			}
		}

		if ($resources !== null) {
			$old = $this->_flatten_grants($before);
			$new = $this->_flatten_grants($resources);
			$added = array_values(array_diff($new, $old));
			$removed = array_values(array_diff($old, $new));
			if ($added || $removed) {
				$this->_audit('role-permissions', sprintf(
					'role id=%d "%s" permissions changed; added: %s; removed: %s',
					$role_id,
					$details !== null ? $details['name'] : $role['name'],
					$added ? implode(', ', $added) : 'none',
					$removed ? implode(', ', $removed) : 'none'
				));
			}
		}
	}

	/**
	 * @param array $map resource => string[]
	 * @return string[] "resource.permission", sorted
	 */
	private function _flatten_grants($map)
	{
		$out = array();
		foreach ($map as $resource => $perms) {
			foreach ((array) $perms as $perm) {
				$out[] = $resource . '.' . $perm;
			}
		}
		sort($out);
		return $out;
	}

	private function _audit($type, $message)
	{
		if ( ! isset($this->db_logger)) {
			$this->load->library('db_logger');
		}
		$this->db_logger->write_log($type, $message, 'admin-roles');
	}

	// -------------------------------------------------------------------------
	// Access
	// -------------------------------------------------------------------------

	/**
	 * Role changes are audited against the signed-in user, which only a browser session provides.
	 */
	private function _require_session_user()
	{
		$user_id = (int) $this->session->userdata('user_id');
		if ($user_id < 1 || ! $this->ion_auth->get_user($user_id)) {
			$this->response(
				array('status' => 'failed', 'message' => 'SESSION-REQUIRED'),
				REST_Controller::HTTP_UNAUTHORIZED,
				false
			);
			exit;
		}
	}

	private function _require_site_admin()
	{
		$user = $this->ion_auth->get_user((int) $this->session->userdata('user_id'));
		if (! $user || ! $this->acl_manager->user_is_admin($user)) {
			$this->response(
				array('status' => 'error', 'message' => 'ACCESS_DENIED'),
				REST_Controller::HTTP_FORBIDDEN,
				false
			);
			exit;
		}
	}

	/**
	 * @param mixed $id
	 * @return array
	 */
	private function _require_role($id)
	{
		if (! is_numeric($id) || (int) $id < 1) {
			throw new Permissions_api_error('INVALID_ID');
		}
		$role = $this->acl_manager->get_role_by_id((int) $id);
		if (empty($role)) {
			throw new Permissions_api_error('NOT_FOUND', REST_Controller::HTTP_NOT_FOUND);
		}
		return $role;
	}

	/**
	 * Locked roles and admin roles are built in; changing them could lock everyone out.
	 *
	 * @param array $role
	 */
	private function _reject_system_role($role)
	{
		if ($this->_is_system_role($role)) {
			throw new Permissions_api_error('ROLE_LOCKED', REST_Controller::HTTP_FORBIDDEN);
		}
	}

	private function _is_system_role($role)
	{
		return (! empty($role['is_locked']) && (int) $role['is_locked'] === 1)
			|| (! empty($role['is_admin']) && (int) $role['is_admin'] === 1);
	}

	// -------------------------------------------------------------------------
	// Input
	// -------------------------------------------------------------------------

	/**
	 * @return array decoded JSON object ({} and an empty body are allowed)
	 */
	private function _json_body()
	{
		$raw = (string) $this->input->raw_input_stream;
		if (trim($raw) === '') {
			return array();
		}
		$data = json_decode($raw, true);
		if (! is_array($data)) {
			throw new Permissions_api_error('INVALID_JSON_INPUT');
		}
		return $data;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function _normalize_role_name($value)
	{
		if (! is_scalar($value)) {
			throw new Permissions_api_error('INVALID_ROLE_NAME');
		}
		$name = trim((string) $value);
		if ($name === '') {
			throw new Permissions_api_error('ROLE_REQUIRED');
		}
		if (strlen($name) > self::MAX_ROLE_NAME || ! preg_match('/^[A-Za-z0-9 ]+$/', $name)) {
			throw new Permissions_api_error('INVALID_ROLE_NAME');
		}
		return $name;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function _normalize_description($value)
	{
		if ($value === null) {
			return '';
		}
		if (! is_scalar($value)) {
			throw new Permissions_api_error('INVALID_DESCRIPTION');
		}
		$description = trim((string) $value);
		if (mb_strlen($description) > self::MAX_DESCRIPTION) {
			throw new Permissions_api_error('DESCRIPTION_TOO_LONG');
		}
		if ($description !== strip_tags($description)) {
			throw new Permissions_api_error('INVALID_DESCRIPTION');
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
		if (! is_numeric($value) || (int) $value != $value || (int) $value < 0 || (int) $value > self::MAX_WEIGHT) {
			throw new Permissions_api_error('INVALID_WEIGHT');
		}
		return (int) $value;
	}

	/**
	 * Every resource and permission must exist in the acl_permissions config.
	 *
	 * @param mixed $resources
	 * @return array resource => string[] (resources with no grants are dropped)
	 */
	private function _normalize_resources($resources)
	{
		if ($resources === null) {
			return array();
		}
		if (! is_array($resources)) {
			throw new Permissions_api_error('INVALID_RESOURCES');
		}

		$allowed = $this->_allowed_permissions();
		$out = array();
		foreach ($resources as $resource => $permissions) {
			$resource = (string) $resource;
			if (! isset($allowed[$resource])) {
				throw new Permissions_api_error('INVALID_RESOURCE');
			}
			if (! is_array($permissions)) {
				throw new Permissions_api_error('INVALID_PERMISSION');
			}
			$perms = array();
			foreach ($permissions as $perm) {
				if (! is_string($perm) || ! in_array($perm, $allowed[$resource], true)) {
					throw new Permissions_api_error('INVALID_PERMISSION');
				}
				$perms[$perm] = true;
			}
			if ($perms) {
				$out[$resource] = array_keys($perms);
			}
		}
		return $out;
	}

	/**
	 * @return array resource => string[]
	 */
	private function _allowed_permissions()
	{
		$allowed = array();
		foreach ($this->_format_resources($this->acl_manager->get_all_permissions()) as $res) {
			$allowed[$res['resource']] = array();
			foreach ($res['permissions'] as $p) {
				$allowed[$res['resource']][] = $p['permission'];
			}
		}
		return $allowed;
	}

	private function _assert_unique_name($name, $role_id)
	{
		$existing = $this->acl_manager->get_role_by_name($name);
		if (! empty($existing) && (int) $existing['id'] !== (int) $role_id) {
			throw new Permissions_api_error('ROLE_EXISTS', REST_Controller::HTTP_CONFLICT);
		}
	}

	// -------------------------------------------------------------------------
	// Output
	// -------------------------------------------------------------------------

	/**
	 * @return array role_id => number of users holding the role
	 */
	private function _role_user_counts()
	{
		$rows = $this->db->select('role_id, COUNT(DISTINCT user_id) AS total', false)
			->group_by('role_id')
			->get('user_roles')
			->result_array();
		$counts = array();
		foreach ($rows as $row) {
			$counts[(int) $row['role_id']] = (int) $row['total'];
		}
		return $counts;
	}

	/**
	 * @param array|null $role
	 * @param array|null $counts from _role_user_counts(); loaded when omitted
	 * @return array|null
	 */
	private function _format_role($role, $counts = null)
	{
		if (! is_array($role) || empty($role)) {
			return null;
		}
		if ($counts === null) {
			$counts = $this->_role_user_counts();
		}
		$id = isset($role['id']) ? (int) $role['id'] : 0;
		return array(
			'id'          => $id,
			'name'        => isset($role['name']) ? (string) $role['name'] : '',
			'description' => isset($role['description']) ? (string) $role['description'] : '',
			'weight'      => isset($role['weight']) ? (int) $role['weight'] : 0,
			'is_locked'   => ! empty($role['is_locked']) && (int) $role['is_locked'] === 1,
			'is_admin'    => ! empty($role['is_admin']) && (int) $role['is_admin'] === 1,
			'is_system'   => $this->_is_system_role($role),
			'user_count'  => isset($counts[$id]) ? $counts[$id] : 0,
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

	private function _error_response(Exception $e)
	{
		$message = $e->getMessage();

		if ($e instanceof Permissions_api_error && $e->status < 500) {
			$this->set_response(array('status' => 'error', 'message' => $message), $e->status);
			return;
		}
		if ($message === 'ROLE_LOCKED') {
			$this->set_response(array('status' => 'error', 'message' => $message), REST_Controller::HTTP_FORBIDDEN);
			return;
		}

		log_message('error', 'api/admin/permissions: ' . $message);
		$this->set_response(array('status' => 'error', 'message' => 'SERVER_ERROR'), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
	}
}
