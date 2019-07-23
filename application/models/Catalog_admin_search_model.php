<?php
/**
* Catalog
*
**/
class Catalog_admin_search_model extends CI_Model {
	
	//database allowed column names
	var $allowed_fields=array('title', 'nation','year', 'authoring_entity');
	
	//fields for the study description
	var $study_fields=array(
					'surveys.id',
					'surveys.repositoryid',
					'idno',
					'title',
					'authoring_entity',
					'nation',
					'dirpath',
					'metafile',
					'link_technical', 
					'link_study',
					'link_report',
					'link_indicator',
					'link_questionnaire',
					'year_start',
					'year_end',
					'link_da',
					'published',
					'surveys.created',
					'changed'
					);
	
	//additional filters on search
	var $filter=array('isdeleted='=>0);
	var $active_repo=NULL;
	var $active_repo_negate=FALSE;//show repo surveys or negate repo surveys
	
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

	public function set_active_repo($repo)
	{
		$this->active_repo=$repo;
	}
	
	
	/**
	* searches the catalog
	* 
	* supported fields	idno, id, nation, title, repositoryid, country_name, tags, 
	**/
    function search($options=array(), $limit = 15, $offset = 0,$filter=NULL)
    {	
		$this->params=$options;
		
		if ($filter!=NULL)
		{
			$this->filter=$filter;
		}
			
		$this->search_count=$this->search_count();
		
		if ($this->search_count==0)
		{
			//no point in searching
			return NULL;
		}

		//sort
		$sort_order=$this->get_param('sort_order');
		$sort_by=$this->get_param('sort_by');
		
		//$this->db->start_cache();		
		
		//survey fields
		$this->db->select(implode(",", $this->study_fields));
		
		//form fields
		$this->db->select('forms.model as form_model, forms.path as form_path');
		$this->db->join('forms', 'forms.formid= surveys.formid','left');
		
		if ($this->active_repo!=NULL && $this->active_repo!='central') 
		{
			//$this->db->select("sr.repositoryid as repo_link, sr.isadmin as repo_isadmin");
			$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		}	

		//build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		if ($where!='')
		{
			$this->db->where($where,NULL,FALSE);
		}	

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
			$this->db->order_by('changed', 'desc'); 
		}

	  	$this->db->limit($limit, $offset);
		$this->db->from('surveys');
        $result= $this->db->get()->result_array();
		return $result;
    }
	
	//builds where clause 
	function _build_search_query()
	{		
		if ($this->active_repo!=NULL && $this->active_repo!='central')
		{
			$allowed_fields['repositoryid']='sr.repositoryid';
		}	
		
		$filter='';
		$where_clause='';
		
		//build where clause for FILTERS
		foreach($this->filter as $f)
		{
			if($f==''){break;}//skip blanks
			
			if ($filter!='')
			{
				$filter.=' AND (' . $f. ')';
			}
			else
			{
				$filter=' (' . $f. ')';
			}		
		}

		if ( trim($where_clause)!='')
		{
			$where_clause='('.$where_clause.') ';
			
			if ($filter!='')
			{
				$where_clause.=' AND '.$filter;
			}
		}
		else
		{
			$where_clause=$filter;
		}


		//additional search options
		$additional_filters=array();
		if ($this->active_repo!=NULL && $this->active_repo!='central')
		{
			$additional_filters=array('repositoryid');
		}	

		
		foreach($additional_filters as $afilter)
		{
			$value=$this->input->get($afilter,TRUE);
			if ($value)
			{
				if ( trim($where_clause)!='')
				{	
					$where_clause.= ' AND '.$afilter.' = '.$this->db->escape($value); 
				}
				else
				{
					$where_clause.= ' '.$afilter.'= '.$this->db->escape($value); 
				}
			}			
		}
		
		//search tags
		$tags=$this->get_param('tag');
		
		$tags_sql=NULL;
		$tags_sub_query=NULL;

		if (is_array($tags)){
			foreach($tags as $key=>$value)
			{
				if (trim($value)!=''){
					$tags_sql[$key]=sprintf('tag=%s',$this->db->escape($value));
				}	
			}
			
			if ( is_array($tags_sql) && count($tags_sql)>0){
				$tags_sub_query='select sid from survey_tags where '.implode(' OR ',$tags_sql);
			}	
		
			if ( !empty($tags_sub_query)){
				if ( trim($where_clause)!=''){	
					$where_clause.= sprintf(' AND surveys.id in (%s)',$tags_sub_query);
				}
				else{
					$where_clause.= sprintf('  surveys.id in (%s)',$tags_sub_query);
				}
			}	
		}		
		
		$active_repo_filter='';
		
		//active repo
		if ($this->active_repo!=NULL && $this->active_repo!='central')
		{
			if (!$this->active_repo_negate)
			{
				//$where_clause.=' and (sr.repositoryid='.$this->db->escape($this->active_repo).' AND surveys.repositoryid='.$this->db->escape($this->active_repo).')';
				$active_repo_filter=' (sr.repositoryid='.$this->db->escape($this->active_repo).')';
			}
			else
			{	//show studies not part of the active repository
				$active_repo_filter=' surveys.repositoryid!='.$this->db->escape($this->active_repo).' and surveys.id not in (select sid from survey_repos where repositoryid='.$this->db->escape($this->active_repo).')';
			}	
		}
		
		if ( trim($where_clause)!='' && $active_repo_filter!='')
		{	
			$where_clause.= ' AND ' .$active_repo_filter;
		}
		else
		{
			$where_clause.= $active_repo_filter;
		}
		
		
		//apply DA filters
		$da_filters=$this->get_param('dtype');
		
		if($da_filters)
		{
			$da_arr=array();
			foreach($da_filters as $dtype){
				if(is_numeric($dtype))
				{
				$da_arr[]=$dtype;
				}
			}
			
			if( count($da_arr)>0)
			{
				if ( trim($where_clause)!='')
				{	
					$where_clause.= ' AND ' . '(surveys.formid in ('.implode(",",$da_arr).') )';
				}
				else
				{
					$where_clause.= '(surveys.formid in ('.implode(",",$da_arr).') )';
				}			
			}
		}
		
		//studies with no questions
		$no_question=$this->input->get('no_question');
		
		if($no_question)
		{
			//get an array of surveys with no questions
			if ( trim($where_clause)!='')
			{	
				$where_clause.= ' AND ' . 'surveys.id not in (select survey_id from resources where dctype like \'%doc/qst]%\')';
			}
			else
			{
				$where_clause.= 'surveys.id not in (select survey_id from resources where dctype like \'%doc/qst]%\')';
			}		
		}

		//studies with no datafile
		$no_datafile=$this->input->get('no_datafile');
		
		if($no_datafile)
		{
			//get an array of surveys with no questions
			if ( trim($where_clause)!='')
			{	
				$where_clause.= ' AND ' . 'surveys.id not in (select survey_id from resources where dctype like \'%dat/micro]%\' OR dctype like \'%dat]%\' )';
			}
			else
			{
				$where_clause.= 'surveys.id not in (select survey_id from resources where dctype like \'%dat/micro]%\' OR dctype like \'%dat]%\' )';
			}		
		}
		
		
		//search on FIELDS [country, idno, title, producer]
		$search_fields=array('nation','idno','title','published');
		$search_options=NULL;
		
		foreach($search_fields as $name)
		{
			$value=$this->get_param($name);
			
			//for repeatable fields eg. nation[]=xyz&nation[]=abc
			if (is_array($value))
			{
				$tmp=NULL;
				foreach($value as $val)
				{
					if(trim($val)!=='') 
					{
						$tmp[]=sprintf("%s like %s",$name,$this->db->escape('%'.$val.'%'));
					}	
				}
				
				if (is_array($tmp)&& count($tmp)>0)
				{
					$search_options[]='('.implode(' OR ', $tmp).')';
				}
			}
			else
			{
				//single value fields
				if(trim($value)!=='') 
				{
					$search_options[]=sprintf("%s like %s",$name,$this->db->escape('%'.$value.'%'));
				}	
			}			
		}//end-foreach
				
		$search_options_str=NULL;
		if (is_array($search_options) && count($search_options)>0)
		{
			$search_options_str='('.implode(' AND ', $search_options).')';
			
			if ( trim($where_clause)!='')
			{	
				$where_clause.= ' AND ' . $search_options_str;
			}
			else
			{
				$where_clause=$search_options_str;
			}
		}
		 
		
		
		/*
		echo '<pre>';
		var_dump($search_options);
		var_dump($where_clause);
		echo '</pre>';
		*/
		
		return $where_clause;
	}

	//returns the search result count  	
    function search_count()
    {
        //build search using the parameters passed to the GET/POST variables
		$where=$this->_build_search_query();

		if ($where!='')
		{
			$this->db->where($where,NULL,FALSE);
		}
		if ($this->active_repo!=NULL && $this->active_repo!='central')
		{
			$this->db->join('survey_repos sr', 'sr.sid= surveys.id','left');
		}	
		$result=$this->db->count_all_results('surveys');
		//echo $this->db->last_query();
		return $result;
    }
	
	
	private function get_param($key)
	{
		if (isset($this->params[$key]))
		{
			return $this->params[$key];
		}
		
		return FALSE;
	}


}//end-class