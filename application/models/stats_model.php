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
	
		if (!is_numeric($limit))
		{
			$limit=10;
		}
		
		$sql='SELECT 
					s.id as id,
					s.surveyid as surveyid, 
					s.titl as titl,
					s.nation as nation,
					count(*) as visits					 
			FROM sitelogs n
				  inner join surveys s on n. surveyid=s.id
			where logtype=\'survey\' ';
			
		if (is_numeric($start) && is_numeric($end) ) 
		{
			$sql.='	and (logtime between '.$start.' and '.$end.')';
		}

		$sql.='	group by s.surveyid, s.titl, s.id';
		$sql.= ' order by visits desc';			
		$sql.= " limit $limit";
		
		$query=$this->db->query($sql);

		if ($query)
		{
			return $query->result_array();
		}	
		return FALSE;
	}

	function get_latest_surveys($limit=10)
	{
		$this->db->select("id,titl,nation");
		$this->db->order_by("created", "desc"); 
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
		return $this->db->count_all('surveys');
	}

	/**
	*
	* Get total survey count
	*/
	function get_variable_count()
	{
		return $this->db->count_all('variables');
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