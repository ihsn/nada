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
				s.year_start,
				s.year_end,
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
	

	
	function get_latest_surveys($limit=10,$repositoryid=null)
	{
		$this->db->select("surveys.id,surveys.type,surveys.title,surveys.subtitle,surveys.year_start, surveys.year_end, surveys.nation,surveys.authoring_entity,forms.model as form_model,surveys.created, surveys.changed");
		$this->db->join("forms", "surveys.formid=forms.formid","left");
		$this->db->where("surveys.published", 1); 
		$this->db->order_by("surveys.created", "desc"); 

		if($repositoryid){
			$this->db->join('survey_repos', 'surveys.id= survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}

		$this->db->limit($limit);
		$query=$this->db->get("surveys");

		if ($query){
			return $query->result_array();
		}	
		return FALSE;
	}

	/**
	*
	* Get total survey count
	*/
	function get_survey_count($repositoryid=null)
	{
		$this->db->where('surveys.published',1);
		$this->db->select('count(surveys.id) as total');

		if($repositoryid){
			$this->db->join('survey_repos', 'surveys.id= survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}

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
		
		if ($query){
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
	
	

	function get_counts_by_type($repositoryid=null)
	{
		//$result=$this->db->query('select count(id) as total,type from surveys where published=1 group by type')->result_array();

		$this->db->select("count(surveys.id) as total,type");
		$this->db->where("surveys.published",1);
		$this->db->group_by("surveys.type");

		if($repositoryid){
			$this->db->join('survey_repos', 'surveys.id= survey_repos.sid','inner');
			$this->db->where('survey_repos.repositoryid',$repositoryid);
		}

		$result=$this->db->get("surveys")->result_array();

		$output=array();
		foreach($result as $row){
			$output[$row['type']]=$row['total'];
		}

		return $output;
	}
	
}