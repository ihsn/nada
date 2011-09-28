<?php
class Lic_files_model extends CI_Model {

	//database allowed column names
	var $allowed_fields=array('titl','title','author', 'dcdate','country', 'language', 'contributor','publisher','toc', 'abstract', 'filename','format','description','changed');
	

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
    
  	
	/**
	* get all licensed files by survey id
	*
	*/
	function get_survey_licensed_files($surveyid)
	{		
		$this->db->select('*');
		$this->db->from('lic_files');
		$this->db->where('surveyid', $surveyid);
		$query = $this->db->get()->result_array();		
		return $query;
	}
	
	/**
	* Add files to a survey
	*
	* 	@surveyid	int
	*	@files		array of file path
	*/
	function add_files($surveyid,$files)
	{		
		foreach($files as $file)
		{	
			$data = array(
               'surveyid' => $surveyid,
               'file_path' => $file,
			   'changed' => date("U")
            );
		
			if ( trim($file)!='')
			{
				log_message('info',"Adding new file[$file] to the survey $surveyid");
				$result=$this->db->insert('lic_files', $data); 
				
				if ($result===false)
				{
					log_message('debug',"FAILED Adding file[$file] to the survey $surveyid");
					return FALSE;
				}			
			}
		}
				
		return TRUE;
	}
	
	
	/**
	* list of licensed surveys
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    /**
	* searche database
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL)
    {
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		$this->db->start_cache();		
		
		//select survey fields
		$this->db->select('*');
		
		//build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND forms.model='.$this->db->escape('licensed');
		}
		else
		{
			$where_clause='forms.model='.$this->db->escape('licensed');
		}
		
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause, NULL, FALSE);

		//set order by
		if ($sort_by!='' && $sort_order!=''){
			$this->db->order_by($sort_by, $sort_order); 
		}
		
	  	$this->db->limit($limit, $offset);
		$this->db->join('forms', 'forms.formid = surveys.formid');
		$this->db->from('surveys');
		$this->db->stop_cache();

        $result= $this->db->get()->result_array();		
		return $result;
    }
	
	//builds where clause using the variables from GET
	function _build_search_query()
	{		
		$fields=$this->input->get("field");
		$keywords=$this->input->get("keywords");
		
		$allowed_fields=$this->allowed_fields;
		
		if ($keywords=='')
		{
			return NULL;
		}
		
		if ($fields=='')
		{
			return NULL;
		}
		else if ($fields=='all')
		{			
			$where['field']=$allowed_fields;
			$where['keywords']=$keywords;
			
			return $where;
		}
		else if (in_array($fields, $allowed_fields) )
		{
			$where['field']=array($fields);
			$where['keywords']=$keywords;
			
			return $where;
		}
		
		return NULL;
	}

	//returns the search result count  	
    function search_count()
    {
        //build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		$where_clause='';
		
		if ($where!=NULL){
			foreach($where['field'] as $field)
			{
				if ( trim($where_clause)!='')
				{	//$this->db->or_like($field,$where['keywords']);
					$where_clause.= ' OR '.$field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}
				else
				{
					$where_clause= $field.' LIKE '.$this->db->escape('%'.$where['keywords'].'%'); 
				}	
			}	
		}
		
		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') AND forms.model='.$this->db->escape('licensed');
		}
		else
		{
			$where_clause='forms.model='.$this->db->escape('licensed');
		}
		//print $where_clause;
		//$this->db->like('surveyid',1);
		$this->db->where($where_clause,NULL,FALSE);
		$this->db->join('forms', 'forms.formid = surveys.formid');
		$result=$this->db->count_all_results('surveys');
		return $result;
    }
	
}
?>