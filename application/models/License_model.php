<?php
class License_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
  	
	
	function get_single($id)
	{
		$this->db->where('id', $id); 
		return $this->db->get('licenses')->row_array();
	}
	
	
	function get_all()
	{
		return $this->db->get('licenses')->result_array();
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
		$query=$this->db->get('licenses');
		
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


	
	/*
	* Returns the license by survey id
	*
	*/
	function get_license_by_survey($survey_id)
	{
		$this->db->select('license.*');
		$this->db->from('license');
		$this->db->join('surveys', 'license.id = surveys.license_id');
		$this->db->where('id', $survey_id); 
		$query = $this->db->get()->row_array();
		
		return $query;		
	}

	
	
	/** 
	 * 
	 * 
	 * Get license info by name
	 * 
	 * 
	*/
	function get_license_by_code($code)
	{
		$this->db->select('*');
		$this->db->from('license');
		$this->db->where('code', $code); 
		$query = $this->db->get()->row_array();		
		
		if($query){
			return $query;
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Return licenses by data classification
	 * 
	 */
	function get_licenses_by_classfications($class_id)
	{
		$this->db->select('*');
		$this->db->from('license');
		$this->db->join('data_class_license', 'licenses.id = data_class_license.license_id');
		$this->db->where('classification_id', $class_id); 
		$query = $this->db->get()->result_array();		
		
		if($query){
			return $query;
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Validation license against classification
	 * 
	 * 
	 */
	function validate_license($class_id,$license_id)
	{
		$this->db->select('*');
		$this->db->from('license');
		$this->db->join('data_class_license', 'licenses.id = data_class_license.license_id');
		$this->db->where('classification_id', $class_id); 
		$this->db->where('license_id', $license_id);
		$query = $this->db->get()->result_array();		
		
		if($query){
			return $query;
		}

		return false;


		$licenses=$this->get_licenses_by_classfications($class_id);

		if(!$licenses){
			return false;
		}

		$license_id_arr=array_column($licenses,$license_id);

		if(in_array($license_id, $license_id_arr)){
			return true;
		}

		return false;
	}


}