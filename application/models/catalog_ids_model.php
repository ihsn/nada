<?php
/**
* Catalog ids
*
**/
class Catalog_ids_model extends CI_Model {
    
	public function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) {
		$result = $this->db->insert('survey_references', $data);
		return $result;
	}
	
	public function id_exists($sid,$id)
	{
		$this->db->select('sid');		
		$this->db->from('survey_references');		
		$this->db->where('sid',$sid);
		$this->db->where('survey_id',$id);				
        return $this->db->count_all_results();
	}
	
	public function delete($id) {
		$this->db->where('id', $id); 
		return $this->db->delete('survey_references');
	}
	
	public function single($id) {
		$this->db->select("*");
		$this->db->where('id', $id); 
		return $this->db->get('survey_references')->row_array();
	}
	
	public function ids_from_catelog_id($sid) {
		$this->db->select("*");
		$this->db->where('sid', $sid);
		$this->db->order_by('id', 'DESC');
		return $this->db->get('survey_references')->result_array();
	}
}
	