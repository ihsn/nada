<?php
class Data_classification_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
  		
	function get_single($id)
	{
		$this->db->where('id', $id); 
		return $this->db->get('data_classifications')->row_array();
	}
	
	
	/**
	*
	* Returns array of id/name values
	*
	*/
	function get_list()
	{
		$this->db->select('*');
		$this->db->order_by("id", "asc");
		$query=$this->db->get('data_classifications');
		
		if(!$query){
			return FALSE;
		}
		
		$query=$query->result_array();
		
		if($query){
			foreach($query as $row){
				$result[$row['id']]=$row['title'];
			}
			return $result;
		}
		
		return FALSE;
	}


	/**
	*
	* Returns a list of all classification by code
	*
	*/
	function get_all()
	{
		$this->db->select('*');
		$this->db->order_by("id", "asc");
		$query=$this->db->get('data_classifications');
		
		if(!$query){
			return FALSE;
		}
		
		$query=$query->result_array();
		
		if($query){
			foreach($query as $row){
				$result[$row['code']]=$row;
			}
			return $result;
		}
		
		return FALSE;
	}

	
	
	/*
	* Returns the license by survey id
	*
	*/
	function get_survey_data_classification($survey_id)
	{
		$this->db->select('data_classifications.*');
		$this->db->from('data_classifications');
		$this->db->join('surveys', 'classification_id.id = surveys.license_id');
		$this->db->where('id', $survey_id); 
		$query = $this->db->get()->row_array();		
		return $query;		
	}

	
	
	/** 
	 * 
	 * 
	 * Get classification info by name
	 * 
	 * 
	*/
	function get_license_by_code($code)
	{
		$this->db->select('*');
		$this->db->from('data_classifications');
		$this->db->where('code', $code); 
		$query = $this->db->get()->row_array();		
		
		if($query){
			return $query;
		}

		return false;
	}


	function get_classification_id($code)
	{
		$this->db->select('id');
		$this->db->from('data_classifications');
		$this->db->where('code', $code); 
		$query = $this->db->get()->row_array();		
		
		if($query){
			return $query['id'];
		}

		return false;
	}

}