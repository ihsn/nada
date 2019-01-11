<?php
class Survey_type_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
		


	//get type
	public function get_stype_id_by_name($type_name)
	{
		$this->db->select("id");
		$this->db->where("name",$type_name);
		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['id'];
		}
	}
	
    //get type
	public function get_type_by_id($stype_id)
	{
		$this->db->select("*");
		$this->db->where("id",$stype_id);
		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row;
		}
	}
	
	public function get_type_name($stype_id)
	{
		$survey_type=$this->get_type_by_id($stype_id);
		if ($survey_type){
			return $survey_type['name'];
		}
	}
	

	
	//get survey type
	public function get_survey_stypeid($sid)
	{
		$this->db->select("stype_id");
		$this->db->where("id",$sid);
		
		$q=$this->db->get("surveys");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['stype_id'];
		}
	}
	
	
	
	//return an array of all survey types array
	public function get_list()
	{
		$this->db->select("id, name");		
		$q=$this->db->get("survey_types");
		
		if ($q)
		{
			return $q->result_array();
		}
	}
	

	//return an array of all survey types names only
	public function get_names_array()
	{
		$list=$this->get_list();

		$output=array();
		foreach($list as $row){
			$output[]=$row['name'];
		}

		return $output;
	}


	
}
	
