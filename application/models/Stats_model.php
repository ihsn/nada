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
	
	

	/**
	 * Public catalog headline stats (published content only).
	 */
	public function get_public_catalog_stats()
	{
		$row = $this->db->query("
			SELECT COUNT(id) AS studies
			FROM surveys
			WHERE published = 1
		")->row_array();
		$studies = (int)$row['studies'];

		$row = $this->db->query("
			SELECT COUNT(v.uid) AS variables
			FROM variables v
			INNER JOIN surveys s ON s.id = v.sid
			WHERE s.published = 1
		")->row_array();
		$variables = (int)$row['variables'];

		$row = $this->db->query("
			SELECT COUNT(id) AS citations
			FROM citations
			WHERE published = 1
		")->row_array();
		$citations = (int)$row['citations'];

		$row = $this->db->query("
			SELECT COUNT(DISTINCT sc.cid) AS countries_with_data
			FROM survey_countries sc
			INNER JOIN surveys s ON s.id = sc.sid
			WHERE s.published = 1
			AND sc.cid > 0
		")->row_array();
		$countries_with_data = (int)$row['countries_with_data'];

		$row = $this->db->query("
			SELECT MIN(year_start) AS min_year, MAX(year_end) AS max_year
			FROM surveys
			WHERE published = 1
			AND year_start > 0
		")->row_array();

		return array(
			'studies'             => $studies,
			'variables'           => $variables,
			'citations'           => $citations,
			'countries_with_data' => $countries_with_data,
			'min_year'            => isset($row['min_year']) ? (int)$row['min_year'] : 0,
			'max_year'            => isset($row['max_year']) ? (int)$row['max_year'] : 0,
		);
	}

	/**
	 * Cached public catalog stats (filesystem, 10-minute TTL).
	 */
	public function get_public_catalog_stats_cached($ttl = 600)
	{
		$cache_dir  = FCPATH . 'cache';
		$cache_file = $cache_dir . '/catalog_public_stats.json';

		if (file_exists($cache_file)) {
			$age = time() - filemtime($cache_file);
			if ($age < $ttl) {
				$cached = json_decode(file_get_contents($cache_file), true);
				if (is_array($cached)) {
					return $cached;
				}
			}
		}

		$stats = $this->get_public_catalog_stats();

		if (!is_dir($cache_dir)) {
			@mkdir($cache_dir, 0755, true);
		}
		@file_put_contents($cache_file, json_encode($stats));

		return $stats;
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