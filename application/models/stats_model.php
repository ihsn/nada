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
	function get_popular_surveys($limit=10,$start=NULL, $end=NULL)
	{
		if (!is_numeric($start) || !is_numeric($end))
		{
			$start=strtotime("-3 weeks");
			$end=date("U");
		}
	
		if (!is_numeric($limit))
		{
			$limit=10;
		}
		
		$fields='s.id as id,
				s.surveyid as surveyid, 
				s.titl as titl,
				s.authenty as authenty,
				s.nation,
				count(*) as visits';
				
		$fields_group_by='s.id,
				s.surveyid, 
				s.titl,
				s.authenty,
				s.nation';
				
		$this->db->select($fields);
		$this->db->join('surveys s', 's.id= n.surveyid','inner');
		$this->db->limit($limit);
		$this->db->group_by($fields_group_by); 
		$this->db->where('logtype','survey');
		$this->db->where('s.published',1);

		if (is_numeric($start) && is_numeric($end) ) 
		{
			$this->db->where('(logtime between '.$start.' and '.$end.')');
		}
		
		$query=$this->db->get('sitelogs n');
				
		if ($query)
		{
			return $query->result_array();
		}	
		return FALSE;
	}

	function get_latest_surveys($limit=10)
	{
		$this->db->select("surveys.id,surveys.titl,surveys.nation,surveys.authenty,forms.model as form_model,surveys.created");
		$this->db->join("forms", "surveys.formid=forms.formid","inner");
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
		$this->db->join('variables v', 'surveys.id= v.surveyid_fk','inner');
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