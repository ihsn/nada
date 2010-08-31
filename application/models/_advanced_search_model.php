<?php
//NOTE: This is a draft search and needs lots of code cleaning
class Advanced_search_model extends Model {
 
 	var $use_cache=FALSE; //enable/disable caching of search
	var $is_variable_search=FALSE;
		
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		//$this->load->library('cache');
    }

	/**
	* Return min. data collection year
	*
	*/
	function get_min_year()
	{
		$this->db->select_min('data_coll_start','min_year');
		$this->db->where('data_coll_start > 0'); 
		$result=$this->db->get('surveys')->row_array();
		
		if ($result)
		{
			return $result['min_year'];
		}
		return FALSE;
	}
	
	
	/**
	* Return max. data collection year
	*
	*/
	function get_max_year()
	{
		$this->db->select_max('data_coll_end','max_year');
		$this->db->where('data_coll_end > 0'); 
		$result=$this->db->get('surveys')->row_array();
		if ($result)
		{
			return $result['max_year'];
		}
		return FALSE;
	}

		
	/**
	* Get start and End data collection years
	*
	*/
	function get_collection_years()
	{
		//get start years
		$sql='select data_coll_start from surveys
				where data_coll_start>0
				group by data_coll_start;';
		$result=$this->db->query($sql)->result_array();
		
		$years['from']=array('--'=>'--');
		
		if ($result)
		{
			foreach($result as $row)
			{
				$years['from'][$row['data_coll_start']]=$row['data_coll_start'];
			}
		}

		//get end years
		$sql='select data_coll_end from surveys
				where data_coll_end>0
				group by data_coll_end;';
				
		$result=$this->db->query($sql)->result_array();
		
		$years['to']=array('--'=>'--');
		if ($result)
		{
			foreach($result as $row)
			{
				$years['to'][$row['data_coll_end']]=$row['data_coll_end'];
			}
		}
		
		return $years;		
	}

	/**
	* Topics with survey counts 
	*
	*/	
	function get_active_topics()
	{
		$sql='select t.tid as tid,t.pid as pid,t.title as title,count(st.sid) as surveys_found
				from topics t
				inner join survey_topics st on t.tid=st.tid
					   where pid<>0 group by t.tid
				union all
				select t.tid as tid,t.pid as pid,t.title as title,count(st.sid) as surveys_found
				from topics t
					left join survey_topics st on t.tid=st.tid
					   where pid=0 and t.tid
						in(
							select t.pid from topics t
						inner join survey_topics st on t.tid=st.tid
					  )
					group by t.tid
					order by pid,tid;';		
		
		return $this->db->query($sql)->result_array();
	}
	
	
	/**
	* List of countries from the survey table [nation field]
	*
	*/
	function get_active_countries()
	{
		$sql='select nation,count(nation) as surveys_found from surveys
			  group by nation';		
		
		return $this->db->query($sql)->result_array();		
	}


	/**
	* search surveys
	*
	* all values are read from the querystring directly
	*/
	function search($limit = 15, $offset = 0)
	{	
		//sort allowed fields and sort order
		$sortable_fields=array('titl','nation,titl','nation','proddate');
		$sortable_order=array('asc','desc');

		$sort_by=in_array($this->input->get('sort_by'),$sortable_fields) ? $this->input->get('sort_by') : 'titl';
		$sort_order=in_array($this->input->get('sort_order'),$sortable_order) ? $this->input->get('sort_order') : 'asc';			
		
		$where=$this->_build_where();
		
		if ($where!='')
		{
			$where='WHERE '. $where;
		}

		$fields='id,refno,surveyid,titl,nation,authenty, forms.model as form_model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared';
		
		//build search sql
		$sql='';
		if ($this->is_variable_search)
		{
			//variable search
			$sql='select '.$fields.',varcount, count(*) as var_found
					from surveys
					left join forms on surveys.formid=forms.formid
			    	right join variables v on surveys.id=v.surveyid_fk '.$where;
			$sql.=' group by id,surveyid,titl,nation';
			$sql.=" ORDER BY $sort_by $sort_order";
		}		
		else 
		{
			//study search
			$sql='select '.$fields.' from surveys ';
			$sql.=' left join forms on surveys.formid=forms.formid ';
			$sql.=$where;
			$sql.=" ORDER BY $sort_by $sort_order";
		}
		
		//echo $sql;
		//$sql.= " LIMIT $offset, $limit";		
		
		if ($this->use_cache)
		{
			$cache_key=md5($sql);
			
			//check if data in cache
			$result = $this->cache->get($cache_key);
			
			if ($result===FALSE)
			{			
				//get search result
				$result= $this->db->query($sql)->result_array();
				
				//sort result				
				//$result = $this->array_msort($result, array('titl'=>SORT_ASC));
				
				//cache search result
				$this->cache->write($result, $cache_key,60);
			}
		}
		else
		{
				//get search result
				$result= $this->db->query($sql)->result_array();		
				
				//sort result				
				//$result = $this->array_msort($result, array('titl'=>SORT_ASC));
		}
		

		//echo $sql;
		//$this->db->cache_on();
		//$result= $this->db->query($sql)->result_array();		
		//$this->db->cache_off();

		if ( count($result)==0 )
		{
			return NULL;
		}

		$output=NULL;
		for($i=$offset;$i<count($result);$i++)
		{	
			if (($i-$offset)>=$limit)
			{
				break;
			}
			$output[]=$result[$i];			
		}
		
		//get citations for the surveys shown
		$survey_id_list=array();
		foreach($output as $row)
		{
			$survey_id_list[]=$row['id'];
		}
		
		$citations=$this->get_survey_has_citations($survey_id_list);
		
		$tmp['total']=count($result);
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$output;
		$tmp['citations']=$citations;
		return $tmp;		
	}

	/**
	*
	* returns an array of surveys with citations
	*
	*/
	function get_survey_has_citations($survey_id_list)
	{
		if (!is_array($survey_id_list))
		{
			return FALSE;
		}		
		$surveys=implode(',',$survey_id_list);
		$this->db->select('sid');	
		$this->db->where("sid in ($surveys)");
		$this->db->group_by('sid');	
		$query=$this->db->get('survey_citations')->result_array();
		
		if ($query)
		{
			$result=array();
			foreach($query as $row)
			{
				$result[]=$row['sid'];
			}
			return $result;
		}
		
		return FALSE;
	}
	
	/**
	* Variable search
	*
	*/
	function v_quick_search($survey_id=NULL,$limit=50,$offset=0)
	{
		if (!is_numeric($survey_id))
		{
			return false;
		}
		
		$variable_keywords=$this->input->get('vk');
		$variable_fields=$this->input->get('vf');

		//variables
		if ($variable_keywords!='')
		{
			if (is_array($variable_fields))
			{
				$tmp_where=NULL;
				
				$variable_allowed_fields=array('labl','name','qstn','catgry');
				
				foreach($variable_fields as $field)
				{
					if (in_array($field,$variable_allowed_fields) )
					{
						if (strlen($variable_keywords) ==3)
						{
								//LIKE query
								//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
								
								//REGEXP query 
								$tmp_where[]=sprintf('%s REGEXP (\'[[:<:]]%s[[:>:]]\' )','v.'.$field,$variable_keywords);	
						}
						else
						{
							$tmp_where[]=sprintf('MATCH(%s) AGAINST (\'%s\')','v.'.$field,$variable_keywords);
						}
					}	
				}
				
				if ($tmp_where!=NULL)
				{
					$where_list[]='('.implode(' OR ',$tmp_where).')';
					$this->is_variable_search=TRUE;
				}
			}
			else //no variable search field is selected, use the default field
			{
				if (strlen($variable_keywords) ==3)
				{
					//LIKE query
					//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
				
					//REGEXP query 
					$where_list[]=sprintf('%s REGEXP (\'[[:<:]]%s[[:>:]]\' )','v.labl',$variable_keywords);	
					$this->is_variable_search=TRUE;
				}
				else if (strlen($variable_keywords)>3)
				{
					$where_list[]=sprintf('MATCH(%s) AGAINST (\'%s\')','v.labl',$variable_keywords);
					$this->is_variable_search=TRUE;
				}
			}			
		}
		else if ($variable_keywords=='' || strlen($variable_keywords)<3)
		{
			return FALSE;
		}
				
		//create combined where clause
		$where='';	
		foreach($where_list as $stmt)
		{
			if ($where=='')
			{
				$where=$stmt;
			}
			else
			{
				$where.=' AND '. $stmt;
			}
		}
		
		//search database
		$this->db->limit($limit, $offset);
		
		$this->db->select("v.name,v.labl,v.varID, v.qstn, v.surveyid_FK");

		$this->db->where($where);

		$this->db->where('surveyid_fk', $survey_id);
		$result=$this->db->get("variables as v")->result_array();
		
		
		return $result;	
	}
		
	
	function _build_where()
	{		
		$topic_array=$this->input->get('topic');
		$country_array=$this->input->get('country');

		$study_keywords=$this->input->xss_clean($this->input->get('sk'));
		$variable_keywords=$this->input->xss_clean($this->input->get('vk'));
		$variable_fields=$this->input->xss_clean($this->input->get('vf'));
		
		$year_from=$this->input->get('from');
		$year_to=$this->input->get('to');
		
		$topics='';		
		//remove topics that are not numeric
		if ($topic_array!='' && is_array($topic_array) )
		{
			//remove unwanted values
			$topics_clean=array();
			foreach($topic_array as $topic)
			{
				if (is_numeric($topic) )
				{
					$topics_clean[]=$topic;
				}	
			}
			
			if ( count($topics_clean)>0)
			{
				$topics=implode(',',$topics_clean);
			}
		}
		
		//escape country names
		$countries='';		
		if ($country_array!='' && is_array($country_array) )
		{
			$countries_list=array();
			foreach($country_array as $country)
			{
					//escape country names for db
					$countries_list[]=$this->db->escape($country);
			}
			$countries=implode(',',$countries_list);
		}

		
		$this->is_variable_search=FALSE;
		$where_list=array();
		
		//years [from/to]
		if (is_numeric($year_from) && is_numeric($year_to))
		{
			//$where_list[]=sprintf('surveys.data_coll_start >=%s AND surveys.data_coll_end <=%s',$year_from, $year_to);
			$where_list[]=sprintf('surveys.id in (select sid from survey_years where (data_coll_year between %s and %s) or (data_coll_year=0) )',$year_from, $year_to);			
		}

		//topics
		if ($topics!='')
		{
			$where_list[]=sprintf('surveys.id in (select sid from survey_topics where tid in (%s) )',$topics);
		}

		//countries
		if ($countries!='')
		{
			$where_list[]=sprintf('surveys.nation in (%s)',$countries);
		}
				
		//study
		if ($study_keywords!='')
		{
			$study_fulltext_index='surveys.titl,surveys.authenty,surveys.geogcover,surveys.nation,surveys.topic,surveys.scope,surveys.sername,surveys.producer,surveys.sponsor,surveys.refno';
			$where_list[]=sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$study_fulltext_index,$this->db->escape($study_keywords));
			//$where_list[]=sprintf('titl like (%s)',$this->db->escape('%'.$study_keywords.'%'));
			/*
			$tmp=$this->_build_single_field($field=$study_fulltext_index,$study_keywords,$fulltext=TRUE);
			if ($tmp!==FALSE)
			{
				$tmp_where[]=$tmp;
			}*/			
		}

		//variables
		if ($variable_keywords!='')
		{
			if (is_array($variable_fields))
			{
				$tmp_where=NULL;
				
				$variable_allowed_fields=array('labl','name','qstn','catgry');
				
				foreach($variable_fields as $field)
				{
					if (in_array($field,$variable_allowed_fields) )
					{
						/*
						if (strlen($variable_keywords) <=3)
						{
							if (strlen($variable_keywords) ==3)
							{
								//LIKE query
								//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
								
								//REGEXP query 
								$tmp_where[]=sprintf('%s REGEXP (\'[[:<:]]%s[[:>:]]\' )','v.'.$field,$variable_keywords);	
							}
						}
						else
						{
						$tmp_where[]=sprintf('MATCH(%s) AGAINST (\'%s\')','v.'.$field,$variable_keywords);
						}*/
						$tmp=$this->_build_single_field($field,$variable_keywords);
						if ($tmp!==FALSE)
						{
							$tmp_where[]=$tmp;
						}
					}	
				}
				
				if ($tmp_where!=NULL)
				{
					$where_list[]='('.implode(' OR ',$tmp_where).')';
					$this->is_variable_search=TRUE;
				}
			}
			else //no variable search field is selected, use the default field
			{
				if (strlen($variable_keywords) ==3)
				{
					//LIKE query
					//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
				
					//REGEXP query 
					$where_list[]=sprintf('%s REGEXP (\'[[:<:]]%s[[:>:]]\' )','v.labl',$this->db->escape($variable_keywords));	
					$this->is_variable_search=TRUE;
				}
				else if (strlen($variable_keywords)>3)
				{
					$where_list[]=sprintf('MATCH(%s) AGAINST (%s)','v.labl',$this->db->escape($variable_keywords));
					$this->is_variable_search=TRUE;
				}
			}			
		}		
				
		//create combined where clause
		$where='';
		foreach($where_list as $stmt)
		{
			if ($where=='')
			{
				$where=$stmt;
			}
			else
			{
				$where.=' AND '. $stmt;
			}
		}
		
		return $where;
	}
	
	/**
	* build a single line of filter
	*
	*/
	function _build_single_field($field,$keywords,$fulltext=FALSE)
	{
		//minimum search keyword must be 3 characters long
		if (strlen($keywords)<3)
		{
			return FALSE;
		}

		switch(strtolower($this->db->dbdriver))
		{
			case 'mysql':
				if ($fulltext===TRUE)
				{
					return sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$field,$this->db->escape($keywords));			
				}
				else if (strlen($keywords) <=3)
				{						
					//REGEXP query 
					return sprintf('%s REGEXP (%s)','v.'.$field,$this->db->escape('[[:<:]]'.$keywords.'[[:>:]]'));	
				}
				else if (strlen($keywords)>3)
				{
					return sprintf('MATCH(%s) AGAINST (%s IN BOOLEAN MODE)','v.'.$field,$this->db->escape($keywords));
				}
				break;
				
			case 'sqlite':
			case 'mssql':
			case 'postgre':
				if (strlen($keywords) <=3)
				{
					//LIKE query
					return sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$keywords.'%');							
				}
				else if (strlen($keywords)>3)
				{
					return sprintf('xxxxxxMATCH(%s) AGAINST (\'%s\')','v.'.$field,$keywords);
				}			
			break;
		}	
		return FALSE;
	}
	
	//return search by variables
	function vsearch($limit = 15, $offset = 0)
	{
		//sort allowed fields and sort order
		$sortable_fields=array('name','labl','titl','nation');
		$sortable_order=array('asc','desc');

		$sort_by=in_array($this->input->get('sort_by'),$sortable_fields) ? $this->input->get('sort_by') : 'titl';
		$sort_order=in_array($this->input->get('sort_order'),$sortable_order) ? $this->input->get('sort_order') : 'asc';	
	
		$variable_keywords=$this->input->get('vk');
		$variable_fields=$this->input->get('vf');

		$where=$this->_build_where();
		//echo $where;exit;
		//search database
		$this->db->limit($limit, $offset);
		
		$this->db->select("v.uid,v.name,v.labl,v.varID,  surveys.titl as titl,surveys.nation as nation, v.surveyid_FK");
		$this->db->join('surveys', 'v.surveyid_fk = surveys.id','right');	
		$this->db->order_by($sort_by, $sort_order); 
		$this->db->where($where);

		$result=$this->db->get("variables as v")->result_array();
	
		//return $result;
		$tmp['total']=$this->db->count_all('variables');
		$tmp['found']=$this->_get_variable_count($where,TRUE);
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$result;
		return $tmp;		
	}
	
	function _get_variable_count($where,$join_study=FALSE)
	{
		$this->db->where($where);
		if ($join_study===TRUE)
		{
			$this->db->join('surveys', 'v.surveyid_fk = surveys.id','right');	
		}
		return $this->db->count_all_results('variables as v');
	}
	
}
?>