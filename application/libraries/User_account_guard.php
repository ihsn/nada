<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Rules for which user accounts and roles the signed-in staff member may change.
 *
 * Shared by the admin Users pages and the admin users API so both enforce the same limits:
 * - only site admins may change an admin-role account or assign admin roles;
 * - bulk actions never touch the caller's own account;
 * - an admin cannot remove their own admin role;
 * - a non-admin cannot change their own roles.
 *
 * The permission to use the Users admin at all (`user` view/create/edit/delete) is checked separately.
 */
class User_account_guard
{
	private $acl_manager;
	private $ion_auth;

	private $roles = null;
	private $caller_is_admin = null;

	/**
	 * @param array|null $params optional `acl_manager` and `ion_auth` overrides
	 */
	public function __construct($params = null)
	{
		$params = is_array($params) ? $params : array();

		if (isset($params['acl_manager'], $params['ion_auth'])) {
			$this->acl_manager = $params['acl_manager'];
			$this->ion_auth    = $params['ion_auth'];
			return;
		}

		$ci =& get_instance();
		$ci->load->library('ion_auth');
		$this->acl_manager = $ci->acl_manager;
		$this->ion_auth    = $ci->ion_auth;
	}

	/**
	 * @return int 0 when nobody is signed in
	 */
	public function current_user_id()
	{
		$user = $this->acl_manager->current_user();
		if ( ! $user || empty($user->id)) {
			return 0;
		}

		return (int) $user->id;
	}

	/**
	 * @return bool
	 */
	public function caller_is_admin()
	{
		if ($this->caller_is_admin === null) {
			try {
				$this->caller_is_admin = $this->current_user_id() > 0 && (bool) $this->acl_manager->user_is_admin();
			}
			catch (Exception $e) {
				$this->caller_is_admin = false;
			}
		}

		return $this->caller_is_admin;
	}

	/**
	 * @return array
	 */
	public function roles()
	{
		if ($this->roles === null) {
			$roles = $this->acl_manager->get_roles();
			$this->roles = is_array($roles) ? $roles : array();
		}

		return $this->roles;
	}

	/**
	 * Roles the caller may assign. Site admins may assign every role.
	 *
	 * @return array
	 */
	public function assignable_roles()
	{
		if ($this->caller_is_admin()) {
			return $this->roles();
		}

		return array_values(array_filter($this->roles(), function ($role) {
			return empty($role['is_admin']);
		}));
	}

	/**
	 * Keep only known role ids the caller may assign.
	 *
	 * @param mixed $role_ids
	 * @return int[]
	 */
	public function filter_assignable_role_ids($role_ids)
	{
		if ( ! is_array($role_ids)) {
			return array();
		}

		$allowed = array();
		foreach ($this->assignable_roles() as $role) {
			$allowed[(int) $role['id']] = true;
		}

		$out = array();
		foreach ($role_ids as $role_id) {
			if ( ! is_numeric($role_id)) {
				continue;
			}
			$role_id = (int) $role_id;
			if ($role_id > 0 && isset($allowed[$role_id]) && ! in_array($role_id, $out, true)) {
				$out[] = $role_id;
			}
		}

		return $out;
	}

	/**
	 * @param mixed $role_ids
	 * @return bool
	 */
	public function role_ids_include_admin($role_ids)
	{
		if ( ! is_array($role_ids) || $role_ids === array()) {
			return false;
		}

		$admin_ids = array();
		foreach ($this->roles() as $role) {
			if ( ! empty($role['is_admin'])) {
				$admin_ids[(int) $role['id']] = true;
			}
		}

		foreach ($role_ids as $role_id) {
			if (is_numeric($role_id) && isset($admin_ids[(int) $role_id])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * True when the caller is not a site admin and the target account holds an admin role.
	 *
	 * @param int         $user_id
	 * @param object|null $user preloaded account, to avoid a second lookup
	 * @return bool
	 */
	public function target_is_protected_admin($user_id, $user = null)
	{
		if ($this->caller_is_admin()) {
			return false;
		}

		if ($user === null) {
			$user = $this->ion_auth->get_user((int) $user_id);
		}

		if ( ! $user || empty($user->id)) {
			return false;
		}

		try {
			return (bool) $this->acl_manager->user_is_admin($user);
		}
		catch (Exception $e) {
			return true;
		}
	}

	/**
	 * True when an admin is about to save their own account without any admin role.
	 *
	 * @param int   $user_id
	 * @param array $role_ids roles after the change
	 * @return bool
	 */
	public function would_remove_own_admin_role($user_id, $role_ids)
	{
		if ((int) $user_id !== $this->current_user_id()) {
			return false;
		}

		if ( ! $this->caller_is_admin()) {
			return false;
		}

		return ! $this->role_ids_include_admin($role_ids);
	}

	/**
	 * Whether a bulk action (delete, activate, deactivate, role changes) may touch this account.
	 * The caller's own account and, for non-admins, admin-role accounts are skipped.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function may_change_user_account($user_id)
	{
		if ( ! is_numeric($user_id)) {
			return false;
		}

		$user_id = (int) $user_id;
		if ($user_id < 1 || $user_id === $this->current_user_id()) {
			return false;
		}

		return ! $this->target_is_protected_admin($user_id);
	}

	/**
	 * Whether the caller may change this account's roles from the edit form.
	 * Non-admins may not change admin-role accounts or their own roles.
	 *
	 * @param int         $user_id
	 * @param object|null $user
	 * @return bool
	 */
	public function may_change_roles($user_id, $user = null)
	{
		$user_id = (int) $user_id;
		if ($user_id < 1) {
			return false;
		}

		if ($this->caller_is_admin()) {
			return true;
		}

		if ($user_id === $this->current_user_id()) {
			return false;
		}

		return ! $this->target_is_protected_admin($user_id, $user);
	}
}
