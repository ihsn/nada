<?php
class Catalog_history_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array('titl', 'nation','proddate', 'authenty');
	
	//fields for the study description
	var $study_fields=array(
					'surveys.id',
					'repositoryid',
					'surveyid',
					'titl',
					'nation',
					'data_coll_start',
					'data_coll_end',
					'published',
					'created',
					'changed',
					'proddate'
					);
	
	//additional filters on search
	var $filter=array('isdeleted='=>0);
	var $active_repo=NULL;
	
    public function __construct()
    {
        parent::__construct();		
		//$this->output->enable_profiler(TRUE);
    }
	
	/**
	* searches the catalog
	* 
	* 	NOTE: search parameters such as keywords are accessed directly from 
	*	POST/GET variables
	**/
    function search($limit = NULL, $offset = NULL,$filter=NULL)
    {
	
		if ($filter!=NULL)
		{
			foreach($filter as $key=>$value)
			{
				$this->filter["$key"]=$value;
			}	
		}
		
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->input->get('sort_order');
		$sort_by=$this->input->get('sort_by');
		
		//select survey fields
		$this->db->select('surveys.id,surveys.repositoryid,surveyid,titl,nation,
							changed,created,published,data_coll_start');
		
		$this->db->where("published",1);
		$this->db->where("isdeleted",0);		

		//set order by
		if ($sort_by!='' && $sort_order!='')
		{		
			if (in_array($sort_by, $this->study_fields) )
			{
				$this->db->order_by('surveys.'.$sort_by, $sort_order); 
			}
		}
		else
		{
			$this->db->order_by('created', 'desc'); 
		}
				
	  	$this->db->limit($limit, $offset);
		$this->db->from('surveys');
		
        $result= $this->db->get()->result_array();		
		return $result;
    }
	
	//returns the search result count  	
    function search_count()
    {		
		$this->db->where("published",1);
		$this->db->where("isdeleted",0);
		return $this->db->count_all_results('surveys');
    }


}
?>