<?php
class Stats_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

	/**
	*
	* Return top N popular surveys
	*/	
	function get_popular_surveys($limit=10)
	{
		if (!is_numeric($limit)){
			$limit=10;
		}
		
		$fields='s.id as id,
				s.idno as idno, 
				s.title as title,
				s.authoring_entity,
				s.nation,
				s.total_views as visits';
								
		$this->db->select($fields);
		$this->db->limit($limit);
		$this->db->where('s.published',1);		
		$query=$this->db->get('surveys s');
				
		if ($query){
			return $query->result_array();
		}

		return FALSE;
	}
	

	function get_latest_surveys($limit=10)
	{
		$this->db->select("surveys.id,surveys.title,surveys.nation,surveys.authoring_entity,forms.model as form_model,surveys.created");
		$this->db->join("forms", "surveys.formid=forms.formid","left");
		$this->db->where("surveys.published", 1); 
		$this->db->order_by("surveys.created", "desc"); 
		$this->db->limit($limit);
		$query=$this->db->get("surveys");

		if ($query)
		{
			return $query->result_array();
		}	
		return FALSE;
	}

	/**
	*
	* Get total survey count
	*/
	function get_survey_count()
	{
		$this->db->where('published',1);
		$this->db->select('count(id) as total');
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			return $query['total'];
		}
		
		return FALSE;
	}

	/**
	*
	* Get total survey count
	*/
	function get_variable_count()
	{		
		$this->db->select('count(surveys.id) as total');
		$this->db->join('variables v', 'surveys.id= v.sid','inner');
		$this->db->where('surveys.published',1);
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			return $query['total'];
		}
		
		return FALSE;
	}	

	/**
	*
	* Get total survey count
	*/
	function get_citation_count()
	{
		return $this->db->count_all('citations');
	}	
	
}