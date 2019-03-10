<?php
/**
* Manage Survey ID aliases
*
**/
class Survey_alias_model extends CI_Model {
    
	public function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) {
		$result = $this->db->insert('survey_aliases', $data);
		return $result;
	}
	
	public function id_exists($alternate_id)
	{
		$this->db->select('sid');		
		$this->db->from('survey_aliases');		
		$this->db->where('alternate_id',$alternate_id);
        $found=$this->db->count_all_results();
		
		if ($found)
		{
			return $found;
		}
		
		//check surveys table if id is in use
		$this->db->select('id');		
		$this->db->from('surveys');		
		$this->db->where('idno',$alternate_id);				
        return $this->db->count_all_results();
	}
	
	public function delete($id) {
		$this->db->where('id', $id); 
		return $this->db->delete('survey_aliases');
	}
	
	public function single($id) {
		$this->db->select("*");
		$this->db->where('id', $id); 
		return $this->db->get('survey_aliases')->row_array();
	}
	
	public function get_aliases($sid) {
		$this->db->select("*");
		$this->db->where('sid', $sid);
		$this->db->order_by('id', 'DESC');
		return $this->db->get('survey_aliases')->result_array();
	}
}
	