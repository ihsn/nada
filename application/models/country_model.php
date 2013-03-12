<?php
class Country_model extends CI_Model { 

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* Returns a list of all vocabularies
	*
	*/
	function select_all()
	{		
		$this->db->select('*');
		$this->db->from('countries');
		$this->db->order_by('name');
		$query = $this->db->get()->result_array();		
		return $query;
	}

	/**
	*
	* Return a single vocabulary
	*/
	function select_single($id)
	{		
		$this->db->select('*');
		$this->db->from('countries');
		$this->db->where('countryid', $id);
		$query = $this->db->get()->row_array();		
		return $query;
	}
	
	
}