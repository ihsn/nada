<?php
class Permissions_model extends CI_Model {
    
	// cache the permission value
	private $_permission_value;
	
	public function __construct()
    {
        parent::__construct();
    }
	
	public function get_ordered_groups() {
		$q = $this->db->select('group_name')
			->from('permissions')
			->distinct()
			->order_by('group_name', 'asc')
			->order_by('weight', 'asc')
			->where('group_name IS NOT NULL', null);
			
		return $q->get()->result();
	}
	
	public function get_permissions_by_group_name($group_name) {
		$q = $this->db->select('*')
			->from('permissions')
			->distinct()
			->order_by('group_name', 'asc')
			->order_by('weight', 'asc')
			->where('group_name', $group_name);
			
		return $q->get()->result();
	}

	public function get_enabled_permissions() {
		$q = $this->db->select('permissions.id')
			->distinct()
			->from('permissions')
			->order_by('group_name', 'asc')
			->order_by('weight', 'asc')
			->where('group_name IS NOT NULL', null);
		$value  = (int) $this->_get_permission_value();
		$result = $q->get()->result();
		$array  = array();
		
		foreach($result as $permissions) {
			if ( !!( $value & (1 << (int) $permissions->id))) {
				$array[$permissions->id] = true;
			}
		}
		return $array;
	}
	
	private function _get_permission_value() {
		$q                       = $this->db->query('select * from admin_permissions');
		$result                  = $q->result();
		$this->_permission_value = (int) $result[0]->value;
		return $this->_permission_value;
	}
	
	public function update_permission_value($value) {
		$this->db->where('id', 1);
		$this->db->update('admin_permissions', array('value' => $value));
	}
	
	public function get_ordered_permissions() {
		$q = $this->db->select('id, label, description, url, group_name, weight')
			->from('permissions')
			->order_by('group_name', 'asc')
			->order_by('weight', 'asc')
			->where('group_name IS NOT NULL', null);
					
		return $q->get()->result();
	}
	
	public function group_has_permission($group_name, $permission_id) {
		$group_permissions = $this->get_permissions_by_group_name($group_name);
		foreach($group_permissions as $permissions) {
			if ((int)$permissions->id === (int)$permission_id) {
				
				return !!((int) $this->_get_permission_value() & (1 << (int) $permission_id));
			}
		}
	}
	
	public function group_add_permission($permission_id) {
		$permission   = (1 << (int) $permission_id);
		$value        = (int) $this->_get_permission_value() | $permission;
		
		$this->update_permission_value($value);
	}
	
	public function group_delete_permission($permission_id) {
		$permission  = (1 << (int) $permission_id);
		$value       =  (int) $this->_get_permission_value() & ~$permission;
		$this->update_permission_value($value);
	}
}