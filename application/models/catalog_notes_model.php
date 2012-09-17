<?php
/**
* Catalog Notes
*
**/
class Catalog_Notes_model extends CI_Model {
    
	public function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) {
		$result = $this->db->insert('survey_notes', $data);
		return $result;
	}
	
	public function delete($id) {
		$this->db->where('id', $id); 
		return $this->db->delete('survey_notes');
	}
	
	public function single($id) {
		$this->db->select("*");
		$this->db->where('id', $id); 
		return $this->db->get('survey_notes')->row_array();
	}
	
	public function notes_from_catelog_id($sid, $type='admin') {
		$this->db->select("*");
		$this->db->where('type', $type);
		$this->db->where('sid', $sid);
		$this->db->order_by('date', 'DESC');
		return $this->db->get('survey_notes')->result_array();
	}
}
	