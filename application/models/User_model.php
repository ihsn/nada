<?php
class User_model extends CI_Model {

	public $tables;

	public function __construct()
	{
		parent::__construct();

		$this->load->config('ion_auth');
		$this->tables = $this->config->item('tables');
	}

	/**
	 * @return array<string,string>
	 */
	private function _user_list_db_fields()
	{
		return array(
			'username'    => 'username',
			'first_name'  => 'first_name',
			'last_name'   => 'last_name',
			'email'       => 'email',
			'country'     => 'country',
			'company'     => 'company',
			'active'      => 'active',
			'created_on'  => 'created_on',
			'last_login'  => 'last_login',
		);
	}

	/**
	 * @param array|null $filter
	 */
	private function _apply_user_list_filters($filter)
	{
		if (empty($filter) || ! is_array($filter)) {
			return;
		}

		$db_fields = $this->_user_list_db_fields();
		$has_keyword_search = false;
		$keyword_field      = '';
		$keyword_value      = '';

		foreach ($filter as $f) {
			if (isset($f['keywords'])) {
				$has_keyword_search = true;
				$keyword_field      = isset($f['field']) ? $f['field'] : '';
				$keyword_value      = $f['keywords'];
				break;
			}
		}

		if ($has_keyword_search) {
			if ($keyword_field === 'all') {
				$this->db->group_start();
				foreach ($db_fields as $field) {
					$this->db->or_like($field, $keyword_value);
				}
				$this->db->group_end();
			}
			elseif (array_key_exists($keyword_field, $db_fields)) {
				$this->db->like($keyword_field, $keyword_value);
			}
		}

		foreach ($filter as $f) {
			if (isset($f['keywords'])) {
				continue;
			}

			if (isset($f['field']) && $f['field'] === 'status' && isset($f['value'])) {
				$this->_apply_status_filter((string) $f['value']);
				continue;
			}

			if (isset($f['field']) && $f['field'] === 'collection_access' && isset($f['value'])) {
				$managed_keys = (isset($f['managed_keys']) && is_array($f['managed_keys'])) ? $f['managed_keys'] : array();
				$this->_apply_collection_access_filter((string) $f['value'], $managed_keys);
				continue;
			}

			if (isset($f['field']) && $f['field'] === 'api_keys' && isset($f['value'])) {
				$this->_apply_api_keys_filter((string) $f['value']);
				continue;
			}

			if (isset($f['operator'])) {
				if ( ! array_key_exists($f['field'], $db_fields)) {
					continue;
				}
				if ($f['operator'] === 'NEVER') {
					$this->db->group_start();
					$this->db->where($f['field'], null);
					$this->db->or_where($f['field'] . ' = created_on', null, false);
					$this->db->group_end();
				}
				else {
					$this->db->where($f['field'] . ' ' . $f['operator'], $f['value']);
				}
				continue;
			}

			if (isset($f['value']) && $f['value'] !== null && $f['value'] !== '') {
				if (array_key_exists($f['field'], $db_fields)) {
					$this->db->where($f['field'], $f['value']);
				}
			}
		}
	}

	/**
	 * @param string $status active|disabled|pending|1|0
	 */
	private function _apply_status_filter($status)
	{
		if ($status === '1' || $status === 'active') {
			$this->db->where('active', 1);
			return;
		}

		if ($status === 'pending') {
			$this->db->where('active', 0);
			$this->db->where('activation_code IS NOT NULL', null, false);
			$this->db->where('activation_code !=', '');
			return;
		}

		if ($status === '0' || $status === 'disabled') {
			$this->db->where('active', 0);
			$this->db->group_start();
			$this->db->where('activation_code IS NULL', null, false);
			$this->db->or_where('activation_code', '');
			$this->db->group_end();
		}
	}

	/**
	 * @param string $value has|none
	 * @param string[] $managed_keys repositories_acl.permission whitelist (empty = any grant)
	 */
	private function _apply_collection_access_filter($value, array $managed_keys)
	{
		if ($value !== 'has' && $value !== 'none') {
			return;
		}

		if ( ! $this->db->table_exists('repositories_acl')) {
			if ($value === 'has') {
				$this->db->where('1 = 0', null, false);
			}
			return;
		}

		$subquery = 'SELECT DISTINCT user_id FROM repositories_acl';
		if ( ! empty($managed_keys)) {
			$escaped = array();
			foreach ($managed_keys as $key) {
				$key = trim((string) $key);
				if ($key !== '') {
					$escaped[] = $this->db->escape($key);
				}
			}
			if ( ! empty($escaped)) {
				$subquery .= ' WHERE permission IN (' . implode(',', $escaped) . ')';
			}
		}

		$users_id = $this->tables['users'] . '.id';
		if ($value === 'has') {
			$this->db->where($users_id . ' IN (' . $subquery . ')', null, false);
		}
		else {
			$this->db->where($users_id . ' NOT IN (' . $subquery . ')', null, false);
		}
	}

	/**
	 * @param string $value has|none
	 */
	private function _apply_api_keys_filter($value)
	{
		if ($value !== 'has' && $value !== 'none') {
			return;
		}

		if ( ! $this->db->table_exists('api_keys')) {
			if ($value === 'has') {
				$this->db->where('1 = 0', null, false);
			}
			return;
		}

		$subquery = 'SELECT DISTINCT user_id FROM api_keys WHERE revoked_at IS NULL';
		$users_id = $this->tables['users'] . '.id';

		if ($value === 'has') {
			$this->db->where($users_id . ' IN (' . $subquery . ')', null, false);
		}
		else {
			$this->db->where($users_id . ' NOT IN (' . $subquery . ')', null, false);
		}
	}

	/**
	 * @param array|null $filter
	 */
	private function _apply_user_list_order($sort_by, $sort_order, $filter = null)
	{
		$db_fields = $this->_user_list_db_fields();

		if ($sort_by !== '' && $sort_order !== '' && array_key_exists($sort_by, $db_fields)) {
			$this->db->order_by($db_fields[$sort_by], $sort_order);
		}
	}

	function search($limit = null, $offset = null, $filter = null, $sort_by = null, $sort_order = null)
	{
		$this->db->flush_cache();

		$columns = sprintf(
			'%s.id,username,email,active,activation_code,created_on,last_login,country,company',
			$this->tables['users']
		);

		$this->db->select($columns);
		$this->_apply_user_list_filters($filter);
		$this->db->join($this->tables['meta'], sprintf('%s.user_id = %s.id', $this->tables['meta'], $this->tables['users']));
		$this->_apply_user_list_order($sort_by, $sort_order, $filter);
		$this->db->limit($limit, $offset);
		$this->db->from($this->tables['users']);

		return $this->db->get()->result_array();
	}

	function search_count($filter = null)
	{
		$this->db->flush_cache();
		$this->_apply_user_list_filters($filter);
		$this->db->join($this->tables['meta'], sprintf('%s.user_id = %s.id', $this->tables['meta'], $this->tables['users']));
		$this->db->from($this->tables['users']);

		return $this->db->count_all_results();
	}

	function get_users_by_role($role_id, $limit = null, $offset = null, $filter = null, $sort_by = null, $sort_order = null)
	{
		$this->db->flush_cache();

		$columns = sprintf(
			'%s.id,username,email,active,activation_code,created_on,last_login,country,company',
			$this->tables['users']
		);

		$this->db->select($columns);
		$this->_apply_user_list_filters($filter);
		$this->db->join('user_roles', sprintf('%s.id = user_roles.user_id', $this->tables['users']));
		$this->db->where('user_roles.role_id', (int) $role_id);
		$this->db->join($this->tables['meta'], sprintf('%s.user_id = %s.id', $this->tables['meta'], $this->tables['users']));
		$this->_apply_user_list_order($sort_by, $sort_order, $filter);
		$this->db->limit($limit, $offset);
		$this->db->from($this->tables['users']);

		return $this->db->get()->result_array();
	}

	function get_users_by_role_count($role_id, $filter = null)
	{
		$this->db->flush_cache();
		$this->_apply_user_list_filters($filter);
		$this->db->join('user_roles', sprintf('%s.id = user_roles.user_id', $this->tables['users']));
		$this->db->where('user_roles.role_id', (int) $role_id);
		$this->db->join($this->tables['meta'], sprintf('%s.user_id = %s.id', $this->tables['meta'], $this->tables['users']));
		$this->db->from($this->tables['users']);

		return $this->db->count_all_results();
	}

	function getSingle($userid)
	{
		$this->db->where('id', $userid);
		return $this->db->get($this->tables['users']);
	}

	function delete($id)
	{
		$this->db->where('id', $id);
		$deleted = $this->db->delete($this->tables['users']);

		if ($deleted) {
			$this->db->where('user_id', $id);
			$this->db->delete($this->tables['meta']);
		}

		return $deleted;
	}

	function get_all_countries()
	{
		$this->db->select('countryid,name');
		$query = $this->db->get('countries');

		$output = array('-' => '-');

		if ($query) {
			$rows = $query->result_array();

			foreach ($rows as $row) {
				$output[$row['countryid']] = $row['name'];
			}
		}

		return $output;
	}

	function get_users_by_group($group_id, $limit = null, $offset = null, $filter = null, $sort_by = null, $sort_order = null)
	{
		$this->db->flush_cache();

		$columns = sprintf(
			'%s.id,group_id,username,email,active,activation_code,created_on,last_login,country,company',
			$this->tables['users']
		);
		$columns .= ',' . $this->tables['groups'] . '.name as group_name';

		$this->db->select($columns);
		$this->_apply_user_list_filters($filter);
		$this->db->join($this->tables['meta'], sprintf('%s.user_id = %s.id', $this->tables['meta'], $this->tables['users']));
		$this->db->join($this->tables['groups'], sprintf('%s.id = %s.group_id', $this->tables['groups'], $this->tables['users']), 'left');
		$this->_apply_user_list_order($sort_by, $sort_order, $filter);
		$this->db->limit($limit, $offset);
		$this->db->from($this->tables['users']);
		$this->db->where('group_id', $group_id);

		return $this->db->get()->result_array();
	}

	/**
	 * Return user groups by user
	 */
	public function get_user_roles($id_arr = array())
	{
		if (is_array($id_arr) && count($id_arr) == 0) {
			return false;
		}

		$this->db->flush_cache();
		$this->db->select('role_id,user_id,name');
		$this->db->where_in('user_id', $id_arr);
		$this->db->join('roles', sprintf('%s.id= %s.role_id', 'roles', 'user_roles'));
		$query = $this->db->get('user_roles');

		$rows   = $query->result_array();
		$output = array();
		foreach ($rows as $row) {
			$output[$row['user_id']][] = $row;
		}

		return $output;
	}
}
