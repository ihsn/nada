<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Search for copy studies to collection
 *
 *
 */ 
class Copy_studies_search
{
	/**
	 * Constructor
	 */
	function __construct()
	{
		log_message('debug', "Copy_studies_search Class Initialized.");
		$this->ci =& get_instance();
	}

    public function search($search_options=array(),$limit = 15, $offset = 0,$sort_by=NULL,$sort_order=NULL)
    {
	
		$db_fields=array(
				'title'			=> 'surveys.title',
				'nation'		=> 'surveys.nation',
				'repositoryid'	=> 'survey_repos.repositoryid',
				'changed'		=> 'surveys.changed'
		);
		
		$where=$this->build_search_where($search_options);
		
		//set Limit clause
	  	$this->ci->db->select('surveys.id,surveys.title,surveys.nation,surveys.year_start,surveys.year_end,survey_repos.repositoryid,surveys.changed');
		$this->ci->db->join('forms', 'forms.formid= surveys.formid');
		$this->ci->db->join('survey_repos', 'survey_repos.sid= surveys.id');
		$this->ci->db->limit($limit, $offset);
		$this->ci->db->from('surveys');
		$this->ci->db->where('survey_repos.isadmin',1);
		
		if (count($where)>0)
		{
			$this->ci->db->where( implode(" AND ",$where), NULL, FALSE);
		}

		//set default sort order, if invalid fields are set
		if (!array_key_exists((string)$sort_by,$db_fields))
		{
			$sort_by='title';
			$sort_order='ASC';
		}		
		
		//must be set outside the start_cache...stop_cache to produce correct count_all_results query
		if ($sort_by!='' && $sort_order!='')
		{
			$this->ci->db->order_by($db_fields[$sort_by], $sort_order); 
		}
				
        $result= $this->ci->db->get()->result_array();
		
		//echo $this->ci->db->last_query();
		return $result;
    }
	
	private function build_search_where($search_options=array())
	{
		$where=array();
		
		foreach($search_options as $key=>$value)
		{
			if ($key=='keywords' && trim($value)!="" )
			{
				$where[]=sprintf(" (surveys.title like %s OR surveys.surveyid=%s OR surveys.nation like %s or survey_repos.repositoryid like %s)",
						$this->ci->db->escape('%'.$value.'%'),
						$this->ci->db->escape($value),
						$this->ci->db->escape('%'.$value.'%'),
						$this->ci->db->escape('%'.$value.'%')
						);
			}
			else if ($key=='selected_only' && is_array($value) && count($value)>0)//attached studies only
			{
				$where[]=sprintf(" (surveys.id in (%s) )",implode(",",$value) );
			}
		}
		
		if (isset($search_options['repositoryid']))
		{
			$where[]=' survey_repos.repositoryid!='.$this->ci->db->escape($search_options['repositoryid']);
		}
		return $where;
	}
	

	//returns the search result count  	
    public function search_count($search_options)
    {
		$where=$this->build_search_where($search_options);
		
		//set Limit clause
	  	$this->ci->db->select('count(*) as total');
		$this->ci->db->join('forms', 'forms.formid= surveys.formid');
		$this->ci->db->join('survey_repos', 'survey_repos.sid= surveys.id');
		$this->ci->db->from('surveys');
		$this->ci->db->where('survey_repos.isadmin',1);

		//$this->ci->db->where('forms.model','licensed');

		if (count($where)>0)
		{
			$this->ci->db->where( implode(" AND ",$where), NULL, FALSE);
		}
				
        $result= $this->ci->db->get()->row_array();
		return $result['total'];
    }
	

}//end-class

