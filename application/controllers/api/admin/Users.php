<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . '/libraries/MY_REST_Controller.php');

/**
 * Error with an HTTP status and optional per-field messages.
 */
class Users_api_error extends Exception {

	public $status;
	public $errors;

	public function __construct($message, $status = 400, array $errors = array())
	{
		parent::__construct($message);
		$this->status = $status;
		$this->errors = $errors;
	}
}

/**
 * Admin users API.
 *
 * Base URL: /api/admin/users
 * Auth: browser session only (API keys are refused); ACL user/view for every call, plus create / edit /
 * delete for writes. Writes need a CSRF token (require_session_csrf) and go through User_account_guard.
 *
 *   GET  /                          paginated user list
 *   POST /                          create a user                        (user/create)
 *   GET  options                    roles, countries and what the caller may do
 *   POST batch                      activate | deactivate | delete | assign_roles | remove_roles
 *   GET  {id}                       one user
 *   POST {id}                       update a user (fields present in the body only)   (user/edit)
 *   POST {id}/delete                delete a user                        (user/delete)
 *   GET  {id}/api_keys              that user's active API keys (masked)
 *   POST {id}/api_keys              create a key; the full key is returned once        (user/edit)
 *   GET  api_keys                   all API keys (masked), paginated
 *   POST api_keys/{key}/revoke      revoke a key                         (user/edit)
 *   POST api_keys/{key}/extend      extend a key's expiry                (user/edit)
 *   POST api_keys/{key}/delete      delete a key                         (user/edit)
 *
 * Responses are built from explicit field lists: password hashes, salts, activation / reset / OTP codes
 * and full API keys are never returned (except a new key, once, in the create response).
 */
class Users extends MY_REST_Controller {

	const MAX_API_KEYS = 5;
	const MAX_BATCH_USERS = 500;
	const API_KEY_MAX_DAYS = 730;

	private static $list_sort_fields = array(
		'username', 'email', 'first_name', 'last_name', 'company', 'country', 'active', 'created_on', 'last_login',
	);

	private static $keyword_fields = array(
		'all', 'username', 'email', 'first_name', 'last_name', 'company', 'country',
	);

	private static $api_key_sort_fields = array(
		'date_created' => 'api_keys.date_created',
		'expires_at'   => 'api_keys.expires_at',
		'last_used_at' => 'api_keys.last_used_at',
		'username'     => 'users.username',
		'key_prefix'   => 'api_keys.key_prefix',
	);

	private $admin_role_ids = null;

	public function __construct()
	{
		parent::__construct();
		$this->_require_session_user();
		$this->require_access('user', 'view');
		$this->require_session_csrf();

		$this->load->model('User_model');
		$this->load->library('User_account_guard');
		$this->load->config('ion_auth');
		$this->lang->load('general');
		$this->lang->load('users');
	}

	/**
	 * GET /api/admin/users
	 *
	 * Query: page, ps (1-100), keywords, field (all|username|email|first_name|last_name|company|country),
	 * status (active|disabled|pending), last_login (today|week|month|never), role (role id),
	 * collection_access (has|none), api_keys (has|none), sort_by, sort_order (asc|desc)
	 */
	public function index_get()
	{
		try {
			$page_size = $this->_get_page_size(25, 1, 100);
			$page      = max(1, (int) $this->input->get('page'));
			$offset    = ($page - 1) * $page_size;

			$filter     = $this->_list_filter_from_request();
			$sort_by    = $this->_enum_param('sort_by', self::$list_sort_fields, 'username');
			$sort_order = $this->_enum_param('sort_order', array('asc', 'desc'), 'asc');
			$role_id    = (int) $this->input->get('role');

			if ($role_id > 0) {
				$total = $this->User_model->get_users_by_role_count($role_id, $filter);
				$rows  = $this->User_model->get_users_by_role($role_id, $page_size, $offset, $filter, $sort_by, $sort_order);
			}
			else {
				$total = $this->User_model->search_count($filter);
				$rows  = $this->User_model->search($page_size, $offset, $filter, $sort_by, $sort_order);
			}

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'rows'      => $this->_format_list_rows($rows),
						'total'     => (int) $total,
						'page'      => $page,
						'pages'     => (int) max(1, ceil($total / $page_size)),
						'page_size' => $page_size,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/users/options
	 */
	public function options_get()
	{
		try {
			$guard = $this->user_account_guard;

			$roles = array();
			foreach ($guard->roles() as $role) {
				$roles[] = $this->_format_role($role);
			}

			$assignable = array();
			foreach ($guard->assignable_roles() as $role) {
				$assignable[] = (int) $role['id'];
			}

			$countries = array();
			$query = $this->db->select('name')->order_by('name', 'asc')->get('countries');
			if ($query) {
				foreach ($query->result_array() as $row) {
					$countries[] = (string) $row['name'];
				}
			}

			$this->set_response(
				array(
					'status'  => 'success',
					'options' => array(
						'current_user_id'     => $guard->current_user_id(),
						'permissions'         => $this->_caller_permissions(),
						'roles'               => $roles,
						'assignable_role_ids' => $assignable,
						'countries'           => $countries,
						'max_api_keys'        => self::MAX_API_KEYS,
						'api_key_max_days'    => self::API_KEY_MAX_DAYS,
						'password_min_length' => (int) $this->config->item('min_password_length'),
						'password_max_length' => (int) $this->config->item('max_password_length'),
						'require_complex_password' => (bool) $this->config->item('require_complex_password'),
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/users/{id}
	 */
	public function item_get($id = null)
	{
		try {
			$user = $this->_require_user($id);

			$this->set_response(array('status' => 'success', 'user' => $this->_user_item((int) $user->id)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/users/{id}/api_keys
	 *
	 * Active (not revoked) keys for one user.
	 */
	public function user_api_keys_get($id = null)
	{
		try {
			$user = $this->_require_user($id);

			$this->_select_api_key_columns();
			$this->db->where('api_keys.user_id', (int) $user->id);
			$this->db->where('api_keys.revoked_at IS NULL', null, false);
			$this->db->order_by('api_keys.date_created', 'desc');
			$rows = $this->db->get()->result_array();

			$this->set_response(
				array(
					'status'  => 'success',
					'user_id' => (int) $user->id,
					'keys'    => $this->_format_api_key_rows($rows),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * GET /api/admin/users/api_keys
	 *
	 * Query: page, ps (1-100), user (username or email, partial), prefix (key prefix, partial),
	 * status (active|expired|revoked|all, default active), sort_by, sort_order
	 */
	public function api_keys_get()
	{
		try {
			$page_size  = $this->_get_page_size(25, 1, 100);
			$page       = max(1, (int) $this->input->get('page'));
			$offset     = ($page - 1) * $page_size;
			$status     = $this->_enum_param('status', array('active', 'expired', 'revoked', 'all'), 'active');
			$sort_by    = $this->_enum_param('sort_by', array_keys(self::$api_key_sort_fields), 'date_created');
			$sort_order = $this->_enum_param('sort_order', array('asc', 'desc'), 'desc');
			$user_term  = $this->_text_param('user', 100);
			$prefix     = $this->_text_param('prefix', 12);

			if ($prefix !== '' && ! preg_match('/^[A-Za-z0-9_\-]+$/', $prefix)) {
				throw new Exception('INVALID_PREFIX');
			}

			$this->db->flush_cache();
			$this->db->from('api_keys');
			$this->db->join('users', 'api_keys.user_id = users.id', 'left');
			$this->_apply_api_key_filters($status, $user_term, $prefix);
			$total = (int) $this->db->count_all_results();

			$this->_select_api_key_columns();
			$this->_apply_api_key_filters($status, $user_term, $prefix);
			$this->db->order_by(self::$api_key_sort_fields[$sort_by], $sort_order);
			$this->db->order_by('api_keys.id', 'desc');
			$this->db->limit($page_size, $offset);
			$rows = $this->db->get()->result_array();

			$this->set_response(
				array(
					'status' => 'success',
					'result' => array(
						'rows'      => $this->_format_api_key_rows($rows),
						'total'     => $total,
						'page'      => $page,
						'pages'     => (int) max(1, ceil($total / $page_size)),
						'page_size' => $page_size,
					),
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// -------------------------------------------------------------------------
	// Writes: users
	// -------------------------------------------------------------------------

	/**
	 * POST /api/admin/users
	 *
	 * JSON: username, email, password, password_confirm, first_name, last_name, company, phone, country,
	 * active (bool, default true), role_ids (int[])
	 */
	public function index_post()
	{
		try {
			$this->require_access('user', 'create');

			$input  = $this->_json_body();
			$values = $this->_validate_profile($input, true);
			$values['username'] = strtolower($values['username']);
			$this->_assert_unique_identity($values, 0);

			$role_ids = $this->user_account_guard->filter_assignable_role_ids($this->_int_list($input, 'role_ids'));
			$active   = array_key_exists('active', $input) ? $this->_bool($input['active']) : true;

			$user_id = $this->ion_auth_model->register(
				$values['username'],
				$values['password'],
				$values['email'],
				$this->_meta_fields($values)
			);
			if ( ! $user_id) {
				throw new Users_api_error('CREATE_FAILED', 400);
			}

			$this->ion_auth->update_user($user_id, array('role_id' => $role_ids));
			$this->User_model->set_user_status($user_id, $active);

			$this->_audit('user-create', 'user_id=' . (int) $user_id . ' roles=' . implode(',', $role_ids) . ' active=' . (int) $active);

			$this->set_response(
				array('status' => 'success', 'user' => $this->_user_item((int) $user_id)),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/{id}
	 *
	 * JSON (all optional; only fields present are changed): username, email, first_name, last_name,
	 * company, phone, country, password + password_confirm, active (bool), role_ids (int[])
	 */
	public function item_post($id = null)
	{
		try {
			$this->require_access('user', 'edit');

			$user    = $this->_require_user($id);
			$user_id = (int) $user->id;
			$guard   = $this->user_account_guard;
			if ($guard->target_is_protected_admin($user_id, $user)) {
				throw new Users_api_error('PROTECTED_ACCOUNT', 403);
			}

			$input   = $this->_json_body();
			$values  = $this->_validate_profile($input, false);
			$this->_assert_unique_identity($values, $user_id);
			$changed = array_keys($values);

			$data = array();
			foreach (array('username', 'email', 'password') as $field) {
				if (isset($values[$field])) {
					$data[$field] = $values[$field];
				}
			}
			$data = array_merge($data, $this->_meta_fields($values));

			if (array_key_exists('role_ids', $input)) {
				$role_ids = $guard->filter_assignable_role_ids($this->_int_list($input, 'role_ids'));
				if ($guard->would_remove_own_admin_role($user_id, $role_ids)) {
					throw new Users_api_error('CANNOT_REMOVE_OWN_ADMIN_ROLE', 400);
				}

				if ($guard->may_change_roles($user_id, $user)) {
					$data['role_id'] = $role_ids;
					$changed[] = 'roles';
				}
				elseif ($this->_sorted_ints($role_ids) !== $this->_sorted_ints(array_keys($this->acl_manager->get_user_roles($user_id)))) {
					throw new Users_api_error('CANNOT_CHANGE_ROLES', 403);
				}
			}

			$active = null;
			if (array_key_exists('active', $input)) {
				$active = $this->_bool($input['active']);
				if ( ! $active && $user_id === $guard->current_user_id()) {
					throw new Users_api_error('CANNOT_DISABLE_OWN_ACCOUNT', 400);
				}
				$changed[] = 'active';
			}

			$accepted = array('username', 'email', 'first_name', 'last_name', 'company', 'phone', 'country', 'password', 'active', 'role_ids');
			if ( ! array_intersect($accepted, array_keys($input))) {
				throw new Users_api_error('NOTHING_TO_UPDATE', 400);
			}

			if ( ! empty($data)) {
				$this->ion_auth->update_user($user_id, $data);
			}
			if ($active !== null) {
				$this->User_model->set_user_status($user_id, $active);
			}

			$this->_audit('user-update', 'user_id=' . $user_id . ' fields=' . implode(',', array_unique($changed)));

			$this->set_response(array('status' => 'success', 'user' => $this->_user_item($user_id)), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/{id}/delete
	 */
	public function item_delete_post($id = null)
	{
		try {
			$this->require_access('user', 'delete');

			$user    = $this->_require_user($id);
			$user_id = (int) $user->id;
			if ($user_id === $this->user_account_guard->current_user_id()) {
				throw new Users_api_error('CANNOT_DELETE_OWN_ACCOUNT', 400);
			}
			if ($this->user_account_guard->target_is_protected_admin($user_id, $user)) {
				throw new Users_api_error('PROTECTED_ACCOUNT', 403);
			}

			if ( ! $this->User_model->delete($user_id)) {
				throw new Users_api_error('DELETE_FAILED', 500);
			}

			$this->_audit('user-delete', 'user_id=' . $user_id . ' email=' . $user->email);

			$this->set_response(array('status' => 'success', 'deleted' => $user_id), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/batch
	 *
	 * JSON: action (activate|deactivate|delete|assign_roles|remove_roles), user_ids (int[], max 500),
	 * role_ids (int[], for role actions)
	 *
	 * The caller's own account and, for non-admins, admin-role accounts are skipped and reported.
	 */
	public function batch_post()
	{
		try {
			$input  = $this->_json_body();
			$action = isset($input['action']) && is_string($input['action']) ? $input['action'] : '';
			$allowed_actions = array('activate', 'deactivate', 'delete', 'assign_roles', 'remove_roles');
			if ( ! in_array($action, $allowed_actions, true)) {
				throw new Users_api_error('INVALID_ACTION', 400);
			}
			$this->require_access('user', $action === 'delete' ? 'delete' : 'edit');

			$user_ids = array_values(array_unique(array_filter($this->_int_list($input, 'user_ids'), function ($v) {
				return $v > 0;
			})));
			if (empty($user_ids)) {
				throw new Users_api_error('NO_USERS_SELECTED', 400);
			}
			if (count($user_ids) > self::MAX_BATCH_USERS) {
				throw new Users_api_error('TOO_MANY_USERS', 400);
			}

			$role_ids = array();
			if ($action === 'assign_roles' || $action === 'remove_roles') {
				$role_ids = $this->user_account_guard->filter_assignable_role_ids($this->_int_list($input, 'role_ids'));
				if (empty($role_ids)) {
					throw new Users_api_error('NO_ASSIGNABLE_ROLES', 400);
				}
			}

			$existing = $this->_existing_user_ids($user_ids);
			$guard    = $this->user_account_guard;
			$changed  = array();
			$skipped  = array();

			foreach ($user_ids as $user_id) {
				if ( ! isset($existing[$user_id])) {
					$skipped[] = array('id' => $user_id, 'reason' => 'not_found');
					continue;
				}
				if ($user_id === $guard->current_user_id()) {
					$skipped[] = array('id' => $user_id, 'reason' => 'own_account');
					continue;
				}
				if ( ! $guard->may_change_user_account($user_id)) {
					$skipped[] = array('id' => $user_id, 'reason' => 'protected_admin');
					continue;
				}

				switch ($action) {
					case 'activate':
					case 'deactivate':
						$ok = $this->User_model->set_user_status($user_id, $action === 'activate');
						break;
					case 'delete':
						$ok = (bool) $this->User_model->delete($user_id);
						break;
					case 'assign_roles':
						foreach ($role_ids as $role_id) {
							$this->acl_manager->set_user_role($user_id, $role_id);
						}
						$ok = true;
						break;
					default:
						$this->db->where('user_id', $user_id);
						$this->db->where_in('role_id', $role_ids);
						$ok = (bool) $this->db->delete('user_roles');
				}

				if ($ok) {
					$changed[] = $user_id;
				}
				else {
					$skipped[] = array('id' => $user_id, 'reason' => 'failed');
				}
			}

			if ( ! empty($changed)) {
				$this->_audit(
					'user-batch-' . $action,
					'user_ids=' . implode(',', $changed) . ($role_ids ? ' roles=' . implode(',', $role_ids) : '')
				);
			}

			$this->set_response(
				array('status' => 'success', 'action' => $action, 'changed' => $changed, 'skipped' => $skipped),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// -------------------------------------------------------------------------
	// Writes: API keys
	// -------------------------------------------------------------------------

	/**
	 * POST /api/admin/users/{id}/api_keys
	 *
	 * JSON: name (optional, max 100), expires_in_days (optional, 1-730, default 365)
	 *
	 * The full key is in `secret` in this response only; it is not stored and cannot be shown again.
	 */
	public function user_api_keys_post($id = null)
	{
		try {
			$this->require_access('user', 'edit');

			$user    = $this->_require_user($id);
			$user_id = (int) $user->id;
			if ($this->user_account_guard->target_is_protected_admin($user_id, $user)) {
				throw new Users_api_error('PROTECTED_ACCOUNT', 403);
			}

			$input = $this->_json_body();

			$name = isset($input['name']) && is_string($input['name']) ? trim($input['name']) : '';
			if (mb_strlen($name) > 100 || strip_tags($name) !== $name) {
				throw new Users_api_error('VALIDATION_FAILED', 400, array('name' => 'Invalid key name.'));
			}

			$days = 365;
			if (isset($input['expires_in_days']) && $input['expires_in_days'] !== '') {
				$days = is_numeric($input['expires_in_days']) ? (int) $input['expires_in_days'] : 0;
				if ($days < 1 || $days > self::API_KEY_MAX_DAYS) {
					throw new Users_api_error('VALIDATION_FAILED', 400, array('expires_in_days' => 'Must be between 1 and ' . self::API_KEY_MAX_DAYS . ' days.'));
				}
			}

			$active_keys = $this->db->where('user_id', $user_id)->where('revoked_at IS NULL', null, false)->count_all_results('api_keys');
			if ($active_keys >= self::MAX_API_KEYS) {
				throw new Users_api_error('MAX_API_KEYS_REACHED', 400);
			}

			$created = $this->ion_auth_model->set_api_key($user_id, null, $name !== '' ? $name : null, $days);
			if (empty($created['id']) || empty($created['key'])) {
				throw new Users_api_error('CREATE_FAILED', 500);
			}

			$caller_id = $this->user_account_guard->current_user_id();
			$this->db->where('id', (int) $created['id'])->update('api_keys', array('created_by' => $caller_id));

			$this->_audit('user-api-key-create', 'user_id=' . $user_id . ' key_id=' . (int) $created['id']);

			header('Cache-Control: no-store');
			$this->set_response(
				array(
					'status' => 'success',
					'key'    => $this->_api_key_item((int) $created['id']),
					'secret' => (string) $created['key'],
				),
				REST_Controller::HTTP_CREATED
			);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/api_keys/{key_id}/revoke
	 */
	public function api_key_revoke_post($key_id = null)
	{
		try {
			$key = $this->_require_manageable_key($key_id);
			if ( ! empty($key['revoked_at'])) {
				throw new Users_api_error('ALREADY_REVOKED', 400);
			}

			$this->db->where('id', (int) $key['id'])->update('api_keys', array('revoked_at' => time()));
			$this->_audit('user-api-key-revoke', 'user_id=' . (int) $key['user_id'] . ' key_id=' . (int) $key['id']);

			$this->set_response(array('status' => 'success', 'key' => $this->_api_key_item((int) $key['id'])), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/api_keys/{key_id}/extend
	 *
	 * JSON: months (1-60). Expiry is counted from the later of now and the current expiry, and never set
	 * beyond 730 days from now.
	 */
	public function api_key_extend_post($key_id = null)
	{
		try {
			$key = $this->_require_manageable_key($key_id);
			if ( ! empty($key['revoked_at'])) {
				throw new Users_api_error('KEY_REVOKED', 400);
			}
			if (empty($key['key_prefix'])) {
				throw new Users_api_error('LEGACY_KEY_HAS_NO_EXPIRY', 400);
			}

			$input  = $this->_json_body();
			$months = isset($input['months']) && is_numeric($input['months']) ? (int) $input['months'] : 0;
			if ($months < 1 || $months > 60) {
				throw new Users_api_error('VALIDATION_FAILED', 400, array('months' => 'Must be between 1 and 60.'));
			}

			$now     = time();
			$from    = max($now, (int) $key['expires_at']);
			$expires = min($from + $months * 30 * 86400, $now + self::API_KEY_MAX_DAYS * 86400);

			$this->db->where('id', (int) $key['id'])->update('api_keys', array('expires_at' => $expires));
			$this->_audit('user-api-key-extend', 'user_id=' . (int) $key['user_id'] . ' key_id=' . (int) $key['id'] . ' expires_at=' . $expires);

			$this->set_response(array('status' => 'success', 'key' => $this->_api_key_item((int) $key['id'])), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	/**
	 * POST /api/admin/users/api_keys/{key_id}/delete
	 */
	public function api_key_delete_post($key_id = null)
	{
		try {
			$key = $this->_require_manageable_key($key_id);

			$this->db->where('id', (int) $key['id'])->delete('api_keys');
			$this->_audit('user-api-key-delete', 'user_id=' . (int) $key['user_id'] . ' key_id=' . (int) $key['id']);

			$this->set_response(array('status' => 'success', 'deleted' => (int) $key['id']), REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$this->_error_response($e);
		}
	}

	// -------------------------------------------------------------------------
	// Write helpers
	// -------------------------------------------------------------------------

	/**
	 * @return array
	 */
	private function _json_body()
	{
		$raw = (string) $this->input->raw_input_stream;
		if (trim($raw) === '') {
			return array();
		}

		$input = json_decode($raw, true);
		if ( ! is_array($input)) {
			throw new Users_api_error('INVALID_JSON_INPUT', 400);
		}

		return $input;
	}

	/**
	 * Validate profile fields with the same rules as the admin forms. On update only fields present in the
	 * body are checked and returned.
	 *
	 * @param array $input
	 * @param bool  $is_create
	 * @return array<string,string> trimmed values
	 */
	private function _validate_profile(array $input, $is_create)
	{
		$complex = $this->config->item('require_complex_password') ? '1' : '';
		$min     = (int) $this->config->item('min_password_length');
		$max     = (int) $this->config->item('max_password_length');

		$rules = array(
			'username'   => array(t('username'), 'required|disable_html_tags|max_length[' . ($is_create ? 20 : 100) . ']'),
			'email'      => array(t('email'), 'required|valid_email|max_length[100]'),
			'first_name' => array(t('first_name'), 'required|disable_html_tags|max_length[50]'),
			'last_name'  => array(t('last_name'), 'required|disable_html_tags|max_length[50]'),
			'company'    => array(t('company'), 'disable_html_tags|max_length[100]'),
			'phone'      => array(t('phone'), 'disable_html_tags|max_length[20]'),
			'country'    => array(t('country'), 'disable_html_tags|max_length[100]'),
			'password'   => array(t('password'), 'required|min_length[' . $min . ']|max_length[' . $max . ']|matches[password_confirm]|is_complex_password[' . $complex . ']'),
		);

		$present = array();
		foreach (array_keys($rules) as $field) {
			if ($is_create || array_key_exists($field, $input)) {
				$present[] = $field;
			}
		}
		if ( ! $is_create && in_array('password', $present, true)
			&& (! isset($input['password']) || $input['password'] === '' || $input['password'] === null)) {
			$present = array_values(array_diff($present, array('password')));
		}

		$data = array();
		foreach (array_merge($present, in_array('password', $present, true) ? array('password_confirm') : array()) as $field) {
			$value = isset($input[$field]) ? $input[$field] : '';
			if ( ! is_scalar($value)) {
				throw new Users_api_error('VALIDATION_FAILED', 400, array($field => 'Invalid value.'));
			}
			$data[$field] = ($field === 'password' || $field === 'password_confirm') ? (string) $value : trim((string) $value);
		}

		if (empty($present)) {
			return array();
		}

		$this->load->library('form_validation');
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($data);
		foreach ($present as $field) {
			$this->form_validation->set_rules($field, $rules[$field][0], $rules[$field][1]);
		}
		if (in_array('password', $present, true)) {
			$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');
		}

		if ( ! $this->form_validation->run()) {
			throw new Users_api_error('VALIDATION_FAILED', 400, $this->form_validation->error_array());
		}

		$values = array();
		foreach ($present as $field) {
			$values[$field] = $data[$field];
		}

		return $values;
	}

	/**
	 * @param array $values
	 * @param int   $user_id 0 when creating
	 */
	private function _assert_unique_identity(array $values, $user_id)
	{
		$errors = array();

		if (isset($values['username'])) {
			$existing = $this->ion_auth->get_user_by_username($values['username']);
			if ($existing && (int) $existing->id !== (int) $user_id) {
				$errors['username'] = sprintf(t('callback_username_exists'), t('username'));
			}
		}

		if (isset($values['email'])) {
			$existing = $this->ion_auth->get_user_by_email($values['email']);
			if ($existing && (int) $existing->id !== (int) $user_id) {
				$errors['email'] = sprintf(t('callback_email_exists'), t('email'));
			}
		}

		if ($errors) {
			throw new Users_api_error('VALIDATION_FAILED', 400, $errors);
		}
	}

	/**
	 * @param array $values
	 * @return array meta table fields present in $values
	 */
	private function _meta_fields(array $values)
	{
		$meta = array();
		foreach (array('first_name', 'last_name', 'company', 'phone', 'country') as $field) {
			if (array_key_exists($field, $values)) {
				$meta[$field] = $values[$field];
			}
		}

		return $meta;
	}

	/**
	 * @param array  $input
	 * @param string $key
	 * @return int[]
	 */
	private function _int_list(array $input, $key)
	{
		if ( ! isset($input[$key])) {
			return array();
		}
		if ( ! is_array($input[$key])) {
			throw new Users_api_error('VALIDATION_FAILED', 400, array($key => 'Must be a list of ids.'));
		}

		$out = array();
		foreach ($input[$key] as $value) {
			if ( ! is_numeric($value)) {
				throw new Users_api_error('VALIDATION_FAILED', 400, array($key => 'Must be a list of ids.'));
			}
			$out[] = (int) $value;
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	private function _bool($value)
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value) || (is_string($value) && in_array($value, array('0', '1'), true))) {
			return (int) $value === 1;
		}
		if (is_string($value) && in_array(strtolower($value), array('true', 'false'), true)) {
			return strtolower($value) === 'true';
		}

		throw new Users_api_error('VALIDATION_FAILED', 400, array('active' => 'Must be true or false.'));
	}

	/**
	 * @param array $values
	 * @return int[]
	 */
	private function _sorted_ints(array $values)
	{
		$out = array_map('intval', $values);
		sort($out);

		return array_values(array_unique($out));
	}

	/**
	 * @param int[] $user_ids
	 * @return array<int,true>
	 */
	private function _existing_user_ids(array $user_ids)
	{
		$tables = $this->config->item('tables');

		$this->db->flush_cache();
		$this->db->select('id');
		$this->db->where_in('id', $user_ids);

		$out = array();
		foreach ($this->db->get($tables['users'])->result_array() as $row) {
			$out[(int) $row['id']] = true;
		}

		return $out;
	}

	/**
	 * @param int $user_id
	 * @return array
	 */
	private function _user_item($user_id)
	{
		$row = $this->_load_user_row($user_id);
		if ( ! $row) {
			throw new Users_api_error('NOT_FOUND', 404);
		}

		$formatted = $this->_format_list_rows(array($row));
		$item = $formatted[0];
		$item['phone'] = isset($row['phone']) ? (string) $row['phone'] : '';
		$item['can_change_roles'] = $this->_caller_permissions()['can_edit']
			&& $this->user_account_guard->may_change_roles((int) $user_id);

		return $item;
	}

	/**
	 * @param mixed $key_id
	 * @return array api_keys row (id, user_id, key_prefix, expires_at, revoked_at)
	 */
	private function _require_manageable_key($key_id)
	{
		$this->require_access('user', 'edit');

		if ( ! is_numeric($key_id) || (int) $key_id < 1) {
			throw new Users_api_error('NOT_FOUND', 404);
		}

		$this->db->flush_cache();
		$row = $this->db->select('id, user_id, key_prefix, expires_at, revoked_at')
			->where('id', (int) $key_id)
			->get('api_keys')
			->row_array();
		if ( ! $row) {
			throw new Users_api_error('NOT_FOUND', 404);
		}

		if ( ! empty($row['user_id']) && $this->user_account_guard->target_is_protected_admin((int) $row['user_id'])) {
			throw new Users_api_error('PROTECTED_ACCOUNT', 403);
		}

		return $row;
	}

	/**
	 * @param int $key_id
	 * @return array|null
	 */
	private function _api_key_item($key_id)
	{
		$this->_select_api_key_columns();
		$this->db->where('api_keys.id', (int) $key_id);
		$rows = $this->_format_api_key_rows($this->db->get()->result_array());

		return $rows ? $rows[0] : null;
	}

	/**
	 * @param string $type
	 * @param string $message
	 */
	private function _audit($type, $message)
	{
		if ( ! isset($this->db_logger)) {
			$this->load->library('db_logger');
		}
		$this->db_logger->write_log($type, $message, 'admin-users');
	}

	// -------------------------------------------------------------------------
	// Request parsing
	// -------------------------------------------------------------------------

	/**
	 * The account-protection rules need the signed-in user, which only a browser session provides.
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

	/**
	 * @return array filter rows in the shape User_model expects
	 */
	private function _list_filter_from_request()
	{
		$filter = array();

		$keywords = $this->_text_param('keywords', 100);
		if ($keywords !== '') {
			$filter[] = array(
				'field'    => $this->_enum_param('field', self::$keyword_fields, 'all'),
				'keywords' => $keywords,
			);
		}

		$status = $this->_enum_param('status', array('active', 'disabled', 'pending'), '');
		if ($status !== '') {
			$filter[] = array('field' => 'status', 'value' => $status);
		}

		$last_login = $this->_enum_param('last_login', array('today', 'week', 'month', 'never'), '');
		if ($last_login === 'never') {
			$filter[] = array('field' => 'last_login', 'operator' => 'NEVER', 'value' => null);
		}
		elseif ($last_login !== '') {
			$since = array('today' => strtotime('today'), 'week' => strtotime('-1 week'), 'month' => strtotime('-1 month'));
			$filter[] = array('field' => 'last_login', 'operator' => '>=', 'value' => (int) $since[$last_login]);
		}

		$collection_access = $this->_enum_param('collection_access', array('has', 'none'), '');
		if ($collection_access !== '') {
			$filter[] = array(
				'field'        => 'collection_access',
				'value'        => $collection_access,
				'managed_keys' => array_keys($this->acl_manager->get_manageable_repositories_acl_permission_whitelist_map()),
			);
		}

		$api_keys = $this->_enum_param('api_keys', array('has', 'none'), '');
		if ($api_keys !== '') {
			$filter[] = array('field' => 'api_keys', 'value' => $api_keys);
		}

		return $filter;
	}

	/**
	 * @param string   $name
	 * @param string[] $allowed
	 * @param string   $default
	 * @return string
	 */
	private function _enum_param($name, array $allowed, $default)
	{
		$value = $this->input->get($name);
		if ( ! is_string($value)) {
			return $default;
		}

		$value = strtolower(trim($value));
		return in_array($value, $allowed, true) ? $value : $default;
	}

	/**
	 * @param string $name
	 * @param int    $max_length
	 * @return string
	 */
	private function _text_param($name, $max_length)
	{
		$value = $this->input->get($name);
		if ( ! is_string($value)) {
			return '';
		}

		$value = trim($value);
		if (mb_strlen($value) > $max_length) {
			throw new Exception('PARAM_TOO_LONG: ' . $name);
		}

		return $value;
	}

	private function _get_page_size($default, $min, $max)
	{
		$ps = (int) $this->input->get('ps');
		if ($ps >= $min && $ps <= $max) {
			return $ps;
		}
		return $default;
	}

	// -------------------------------------------------------------------------
	// Users
	// -------------------------------------------------------------------------

	/**
	 * @param mixed $id
	 * @return object
	 */
	private function _require_user($id)
	{
		if ( ! is_numeric($id) || (int) $id < 1) {
			throw new Exception('NOT_FOUND');
		}

		$user = $this->ion_auth->get_user((int) $id);
		if ( ! $user || empty($user->id)) {
			throw new Exception('NOT_FOUND');
		}

		return $user;
	}

	/**
	 * @param int $user_id
	 * @return array|null
	 */
	private function _load_user_row($user_id)
	{
		$tables = $this->config->item('tables');
		$users  = $tables['users'];
		$meta   = $tables['meta'];

		$this->db->flush_cache();
		$this->db->select(sprintf(
			'%1$s.id, %1$s.username, %1$s.email, %1$s.active, %1$s.activation_code, %1$s.created_on, %1$s.last_login, '
			. '%2$s.first_name, %2$s.last_name, %2$s.company, %2$s.country, %2$s.phone',
			$users,
			$meta
		));
		$this->db->from($users);
		$this->db->join($meta, sprintf('%s.user_id = %s.id', $meta, $users), 'left');
		$this->db->where($users . '.id', (int) $user_id);
		$row = $this->db->get()->row_array();

		return $row ? $row : null;
	}

	/**
	 * @param array $rows rows from User_model (include activation_code, used only to work out status)
	 * @return array
	 */
	private function _format_list_rows(array $rows)
	{
		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row['id'];
		}

		$roles_by_user = $this->_roles_by_user($ids);
		$collections   = $ids ? $this->acl_manager->repositories_acl_collections_for_users($ids) : array();
		$key_counts    = $this->_api_key_counts($ids);
		$permissions   = $this->_caller_permissions();
		$guard         = $this->user_account_guard;
		$caller_id     = $guard->current_user_id();
		$caller_admin  = $guard->caller_is_admin();

		$out = array();
		foreach ($rows as $row) {
			$id        = (int) $row['id'];
			$roles     = isset($roles_by_user[$id]) ? $roles_by_user[$id] : array();
			$is_admin  = false;
			foreach ($roles as $role) {
				if ($role['is_admin']) {
					$is_admin = true;
					break;
				}
			}
			$protected = $is_admin && ! $caller_admin;
			$is_self   = $id === $caller_id;

			$created_on = isset($row['created_on']) ? (int) $row['created_on'] : 0;
			$last_login = isset($row['last_login']) ? (int) $row['last_login'] : 0;

			$out[] = array(
				'id'                 => $id,
				'username'           => (string) $row['username'],
				'email'              => (string) $row['email'],
				'first_name'         => isset($row['first_name']) ? (string) $row['first_name'] : '',
				'last_name'          => isset($row['last_name']) ? (string) $row['last_name'] : '',
				'company'            => isset($row['company']) ? (string) $row['company'] : '',
				'country'            => isset($row['country']) ? (string) $row['country'] : '',
				'status'             => $this->_user_status($row),
				'created_on'         => $created_on ?: null,
				'last_login'         => ($last_login > 0 && $last_login !== $created_on) ? $last_login : null,
				'roles'              => $roles,
				'is_admin'           => $is_admin,
				'collections'        => isset($collections[$id]) ? $collections[$id] : array(),
				'api_key_count'      => isset($key_counts[$id]) ? $key_counts[$id] : 0,
				'is_self'            => $is_self,
				'is_protected_admin' => $protected,
				'can_edit'           => $permissions['can_edit'] && ! $protected,
				'can_delete'         => $permissions['can_delete'] && ! $protected && ! $is_self,
			);
		}

		return $out;
	}

	/**
	 * Matches User_model::_apply_status_filter.
	 *
	 * @param array $row
	 * @return string active|pending|disabled
	 */
	private function _user_status(array $row)
	{
		if ((int) $row['active'] === 1) {
			return 'active';
		}

		return (isset($row['activation_code']) && $row['activation_code'] !== '' && $row['activation_code'] !== null)
			? 'pending'
			: 'disabled';
	}

	/**
	 * @param int[] $user_ids
	 * @return array<int,array> user id => list of roles
	 */
	private function _roles_by_user(array $user_ids)
	{
		if (empty($user_ids)) {
			return array();
		}

		$this->db->flush_cache();
		$this->db->select('user_roles.user_id, roles.id, roles.name, roles.is_admin');
		$this->db->from('user_roles');
		$this->db->join('roles', 'roles.id = user_roles.role_id');
		$this->db->where_in('user_roles.user_id', $user_ids);
		$this->db->order_by('roles.weight', 'asc');
		$this->db->order_by('roles.name', 'asc');

		$out = array();
		foreach ($this->db->get()->result_array() as $row) {
			$out[(int) $row['user_id']][] = $this->_format_role($row);
		}

		return $out;
	}

	/**
	 * @param int[] $user_ids
	 * @return array<int,int>
	 */
	private function _api_key_counts(array $user_ids)
	{
		if (empty($user_ids)) {
			return array();
		}

		$this->db->flush_cache();
		$this->db->select('user_id, COUNT(*) AS key_count');
		$this->db->from('api_keys');
		$this->db->where_in('user_id', $user_ids);
		$this->db->where('revoked_at IS NULL', null, false);
		$this->db->group_by('user_id');

		$out = array();
		foreach ($this->db->get()->result_array() as $row) {
			$out[(int) $row['user_id']] = (int) $row['key_count'];
		}

		return $out;
	}

	/**
	 * @param array $role
	 * @return array
	 */
	private function _format_role(array $role)
	{
		return array(
			'id'       => (int) $role['id'],
			'name'     => (string) $role['name'],
			'is_admin' => ! empty($role['is_admin']) && (int) $role['is_admin'] === 1,
		);
	}

	/**
	 * @return array<string,bool>
	 */
	private function _caller_permissions()
	{
		static $permissions = null;
		if ($permissions === null) {
			$permissions = array(
				'can_view'      => true,
				'can_create'    => $this->acl_manager->user_has_access('user', 'create'),
				'can_edit'      => $this->acl_manager->user_has_access('user', 'edit'),
				'can_delete'    => $this->acl_manager->user_has_access('user', 'delete'),
				'is_site_admin' => $this->user_account_guard->caller_is_admin(),
			);
		}

		return $permissions;
	}

	// -------------------------------------------------------------------------
	// API keys
	// -------------------------------------------------------------------------

	/**
	 * Display columns only. The key hash is never selected; the stored legacy key is read only to mask it.
	 */
	private function _select_api_key_columns()
	{
		$tables = $this->config->item('tables');

		$this->db->flush_cache();
		$this->db->select(
			'api_keys.id, api_keys.user_id, api_keys.api_key, api_keys.key_prefix, api_keys.name, api_keys.date_created, '
			. 'api_keys.expires_at, api_keys.last_used_at, api_keys.revoked_at, '
			. 'users.username, users.email, meta.first_name, meta.last_name'
		);
		$this->db->from('api_keys');
		$this->db->join('users', 'api_keys.user_id = users.id', 'left');
		$this->db->join($tables['meta'] . ' meta', 'users.id = meta.user_id', 'left');
	}

	/**
	 * Searching by prefix only: matching against the stored legacy key would let a caller recover it.
	 *
	 * @param string $status
	 * @param string $user_term
	 * @param string $prefix
	 */
	private function _apply_api_key_filters($status, $user_term, $prefix)
	{
		$now = time();

		switch ($status) {
			case 'active':
				$this->db->where('api_keys.revoked_at IS NULL', null, false);
				$this->db->where('(api_keys.expires_at IS NULL OR api_keys.expires_at > ' . (int) $now . ')', null, false);
				break;
			case 'expired':
				$this->db->where('api_keys.revoked_at IS NULL', null, false);
				$this->db->where('api_keys.expires_at IS NOT NULL', null, false);
				$this->db->where('api_keys.expires_at <=', (int) $now);
				break;
			case 'revoked':
				$this->db->where('api_keys.revoked_at IS NOT NULL', null, false);
				break;
		}

		if ($user_term !== '') {
			$this->db->group_start();
			$this->db->like('users.username', $user_term);
			$this->db->or_like('users.email', $user_term);
			$this->db->group_end();
		}

		if ($prefix !== '') {
			$this->db->like('api_keys.key_prefix', $prefix, 'after');
		}
	}

	/**
	 * @param array $rows
	 * @return array
	 */
	private function _format_api_key_rows(array $rows)
	{
		$owner_ids = array();
		foreach ($rows as $row) {
			if ( ! empty($row['user_id'])) {
				$owner_ids[(int) $row['user_id']] = true;
			}
		}
		$admin_owners = $this->_admin_user_ids(array_keys($owner_ids));
		$can_edit     = $this->_caller_permissions()['can_edit'];
		$caller_admin = $this->user_account_guard->caller_is_admin();
		$now          = time();

		$out = array();
		foreach ($rows as $row) {
			$owner_id  = isset($row['user_id']) ? (int) $row['user_id'] : 0;
			$is_legacy = empty($row['key_prefix']);
			$expires   = isset($row['expires_at']) ? (int) $row['expires_at'] : 0;

			if ( ! empty($row['revoked_at'])) {
				$status = 'revoked';
			}
			elseif ( ! $is_legacy && $expires > 0 && $expires <= $now) {
				$status = 'expired';
			}
			else {
				$status = 'active';
			}

			$display_name = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
			$protected    = ! $caller_admin && isset($admin_owners[$owner_id]);

			$out[] = array(
				'id'                => (int) $row['id'],
				'user_id'           => $owner_id ?: null,
				'username'          => isset($row['username']) ? (string) $row['username'] : '',
				'email'             => isset($row['email']) ? (string) $row['email'] : '',
				'user_display_name' => $display_name !== '' ? $display_name : (string) $row['username'],
				'name'              => isset($row['name']) ? (string) $row['name'] : '',
				'prefix'            => $this->_mask_key($row),
				'is_legacy'         => $is_legacy,
				'status'            => $status,
				'date_created'      => ! empty($row['date_created']) ? (int) $row['date_created'] : null,
				'expires_at'        => ( ! $is_legacy && $expires > 0) ? $expires : null,
				'last_used_at'      => ( ! $is_legacy && ! empty($row['last_used_at'])) ? (int) $row['last_used_at'] : null,
				'revoked_at'        => ! empty($row['revoked_at']) ? (int) $row['revoked_at'] : null,
				'can_manage'        => $can_edit && ! $protected && $status !== 'revoked',
				'can_delete'        => $can_edit && ! $protected,
			);
		}

		return $out;
	}

	/**
	 * New keys show their stored prefix; legacy keys (stored in full) show only their first 6 characters.
	 *
	 * @param array $row
	 * @return string
	 */
	private function _mask_key(array $row)
	{
		if ( ! empty($row['key_prefix'])) {
			return (string) $row['key_prefix'] . '…';
		}

		$legacy = isset($row['api_key']) ? (string) $row['api_key'] : '';
		return strlen($legacy) >= 12 ? substr($legacy, 0, 6) . '…' : '…';
	}

	/**
	 * @param int[] $user_ids
	 * @return array<int,true> ids that hold an admin role
	 */
	private function _admin_user_ids(array $user_ids)
	{
		if (empty($user_ids)) {
			return array();
		}

		$this->db->flush_cache();
		$this->db->distinct();
		$this->db->select('user_roles.user_id');
		$this->db->from('user_roles');
		$this->db->join('roles', 'roles.id = user_roles.role_id');
		$this->db->where_in('user_roles.user_id', $user_ids);
		$this->db->where('roles.is_admin', 1);

		$out = array();
		foreach ($this->db->get()->result_array() as $row) {
			$out[(int) $row['user_id']] = true;
		}

		return $out;
	}

	// -------------------------------------------------------------------------
	// Errors
	// -------------------------------------------------------------------------

	private function _error_response(Exception $e)
	{
		$message = $e->getMessage();

		if ($e instanceof Users_api_error && $e->status < 500) {
			$body = array('status' => 'error', 'message' => $message);
			if ($e->errors) {
				$body['errors'] = $e->errors;
			}
			$this->set_response($body, $e->status);
			return;
		}

		if ($message === 'NOT_FOUND') {
			$code = REST_Controller::HTTP_NOT_FOUND;
		}
		elseif ($message === 'INVALID_PREFIX' || strpos($message, 'PARAM_TOO_LONG') === 0) {
			$code = REST_Controller::HTTP_BAD_REQUEST;
		}
		else {
			log_message('error', 'api/admin/users: ' . $message);
			$code    = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
			$message = 'SERVER_ERROR';
		}

		$this->set_response(array('status' => 'error', 'message' => $message), $code);
	}
}
