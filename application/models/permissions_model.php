<?php
/**
 * User Permissions System
 * Schema:                
 *		Backend:       [ USER_PERMISSIONS ]
 *       		                |
 *								|
 *								|
 *					   [ PERMISSIONS_URLS ]
 *			        		    |
 *							    /
 *		Frontend:	     ___ __/
 *						/
 *				[ PERMISSIONS ]	
 * 	Group `permissions' will be tied via `permissions_urls'
 *
 *  `permissions' is a frontend template for coupling and decoupling these relationships, respectively.
 **/
  
class Permissions_model extends CI_Model {
    
	public function __construct()
    {
        parent::__construct();
    }
	
	public function get_group_permissions($id) {
		$permissions = $this->get_ordered_permissions();
		$array       = array();
		$value       = $this->_get_permission_value($id);
		foreach($permissions as $permission) {
			if ( !! ($value	 & (1 << (int) $permission->id))) {
				$array[$permission->id] = true;
			}
		}
		return $array;
	}
	
	public function get_ordered_permissions() {
		$q = $this->db->select('id, label, description, section, weight')
			->from('permissions')
			->order_by('section', 'asc')
			->order_by('weight', 'asc')
			->where('section IS NOT NULL', null);
					
		return $q->get()->result();
	}
	
	public function get_permission($id) {
		$q = $this->db->select('label, description, section, weight')
			->from('permissions')
			->order_by('section', 'asc')
			->order_by('weight', 'asc')
			->where('id', $id);
					
		return $q->get()->result();
	}
	
	public function update_permission($id, $data) {
		$this->db->where('id', $id);
		$this->db->update('permissions', $data);
	}
	
	public function create_permission($data) {
		$this->db->insert('permissions', $data);
	}
	
	public function update_urls($url, $data) {
		$this->db->where('url', $url);
		$this->db->update('permission_urls', $data);
	}
	
	public function delete_permission($permission_id) {
		$this->db->where('permission_id', $permission_id);
		$this->db->delete('permission_urls');

		$this->db->where('id', $permission_id);
		$this->db->delete('permissions');
	}
	
	
	public function add_url($data) {
		$this->db->insert('permission_urls', $data);
	}
	
	private function _get_permission_value($id) {
		$q                       = $this->db->select('permissions')
									->from('user_permissions')
									->where('usergroup_id', $id);
		$result                  = $q->get()->result();
		$this->_permission_value = (int) ($result) ? $result[0]->permissions : 0;
		return $this->_permission_value;
	}
	
	public function update_permission_value($group_id, $value) {
		$q = $this->db->select('id')
			->from('user_permissions')
			->where('usergroup_id', $group_id);
		$result = $q->get()->result();
		var_dump(isset($result[0]));
		if (!isset($result[0]->id)) {
			$data = array(
				'usergroup_id' => $group_id,
				'permissions'  => $value
			);
			$this->db->insert('user_permissions', $data);
		} else {
			$this->db->where('usergroup_id', $group_id);
			$this->db->update('user_permissions', array('permissions' => $value));
		}
	}
	
	public function get_permissions() {
		$q = $this->db->select('permission_id, url')
			->from('permission_urls');
					
		return $q->get()->result();
	}
	
	public function get_permissions_by_section($section) {
		$q = $this->db->select('id')
			->from('permissions')
			->where('section', $section);
					
		return $q->get()->result();
	}
	
	public function group_has_url_access($group_id, $url) {
		$q = $this->db->select('permission_id, url')
			->from('permission_urls');
		$result = $q->get()->result();
		
		foreach ($result as $permissions) {
			// Convert wild-cards to RegEx
			$key = str_replace('*', '.+', $permissions->url);
			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $url))
			{
				return $this->group_has_permission($group_id, $permissions->permission_id);
			}
		}
		return false;
	}

	public function get_urls_by_permission_id($permission_id) {
		$q = $this->db->select('permission_id, url, id')
			->from('permission_urls')
			->where('permission_id', $permission_id);
		return $q->get()->result();
	}

	public function group_has_permission($group_id, $permission_id) {

		return !!((int) $this->_get_permission_value($group_id) & (1 << (int) $permission_id));
		
	}
	
	public function group_add_permission($group_id, $permission_id) {
		$permission   = (1 << (int) $permission_id);
		$value        = (int) $this->_get_permission_value($group_id) | $permission;
		
		$this->update_permission_value($group_id, $value);
	}
	
	public function group_delete_permission($group_id, $permission_id) {
		$permission  = (1 << (int) $permission_id);
		$value       =  (int) $this->_get_permission_value($group_id) & ~$permission;
		$this->update_permission_value($group_id, $value);
	}
}