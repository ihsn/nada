<?php
/*
 projects
 --------------------------
  `id`            int unsigned  AUTO_INCREMENT NOT NULL,
  `uid`           int unsigned                 NOT NULL,
  `created_by`    tinytext                     NOT NULL,
  `title`         tinytext                     NOT NULL,
  `shortname`     VARCHAR(50)                  NOT NULL,
  `created_on`    datetime                     NOT NULL,
  `data_type`     tinytext                     NOT NULL,
  `last_modified` datetime                     NOT NULL,
  `status`        VARCHAR(20)                  NOT NULL default 'draft',
  `description`   tinytext                     NOT NULL,
  PRIMARY KEY (`id`) ENGINE=InnoDB 
----------------------------
*/
class Projects_model extends CI_Model {
	public function __construct() {
		parent::__construct();
	}

	public function project_id ($id) {
		$q  = $this->db->select('*')
			->from('dd_projects')
			->where('id', $id);

		return $q->get()->result();
	}

	public function all_projects() {
		 return $this->db->get('dd_projects')->result();
	}
	
	public function projects ($uid, $order='created_on', $order_by = 'asc', $limit = 0, $offset = 0) {
		$uid = (int) $uid;
		$q = $this->db->select('id, group_id')
			->from('users')
			->where('id', $uid);
		
		$user = $q->get()->result();
		$q  = $this->db->select('*')
			->from('dd_projects')
			->where('uid', $uid)	
			->order_by($order, $order_by);
		if ($limit > 0) {
			$q->limit($limit, $offset);
		}
		return $q->get()->result();
	}

	public function insert ($data) {
		$this->db->insert('dd_projects', $data);
		return $this->db->insert_id();
	}
	
	public function update ($id, $data) {
		$this->db->where('id', $id)
			->update('dd_projects', $data);
	}

	public function log_history($data) {
		$this->db->insert('dd_datadeposit_history', $data);
		return $this->db->insert_id();
	}
	
	public function history_id($id) {
		$q = $this->db->select('*')
			->from('dd_datadeposit_history')
			->where('project_id', $id)
			->order_by('created_on', 'desc');
		return $q->get()->result();
	}

	public function delete ($id) {
		$this->load->model('Study_model');
		$this->db->delete('dd_projects', array('id' => $id));
		// Children tables
   	    $this->db->delete('dd_study', array('id' => $id));
 		$this->db->delete('dd_datadeposit_history', array('project_id' => $id));
	    $this->db->delete('dd_project_resources', array('project_id' => $id));
	}
}