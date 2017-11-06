<?php
class Advanced_search_model extends CI_Model {
 
 	var $use_cache=TRUE; //enable/disable caching of search
	
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		$this->load->library('cache');
    }
	
	/**
	* Projects with survey counts 
	*
	*/	
	function get_active_projects()
	{
		$sql='select
				p.projectid,
				p.projectname,
				p.country,
				count(sp.sid) as surveys_found
			from projects p
			  	inner join survey_projects sp on p.projectid=sp.projectid
		  	group by p.projectid';		
		
		return $this->db->query($sql)->result_array();
	}
	
	
	
	/**
	* Topics with survey counts 
	*
	*/	
	function get_active_topics()
	{
		$sql='select t.*,count(st.sid) as surveys_found from topics t
			  left join survey_topics st on t.tid=st.tid
			  group by t.tid
			  order by pid';		
		
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

		$topic_array=$this->input->get('topic');
		$country_array=$this->input->get('country');
		$project_array=$this->input->get('pid');
		$study_keywords=$this->input->get('sk');
		$variable_keywords=$this->input->get('vk');
		$variable_fields=$this->input->get('vf');		
		$sort_by=in_array($this->input->get('sort_by'),$sortable_fields) ? $this->input->get('sort_by') : 'titl';
		$sort_order=in_array($this->input->get('sort_order'),$sortable_order) ? $this->input->get('sort_order') : 'asc';			

		
		$topics='';		
		//remove topics that are not numeric
		if ($topic_array!='' || is_array($topic_array) )
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
		if ($country_array!='' || is_array($country_array) )
		{
			$countries_list=array();
			foreach($country_array as $country)
			{
					//escape country names for db
					$countries_list[]=$this->db->escape($country);
			}
			$countries=implode(',',$countries_list);
		}

		//escape project ids
		$projects='';		
		if ($project_array!='' || is_array($project_array) )
		{
			$projects_list=array();
			foreach($project_array as $projectid)
			{
					//escape country names for db
					$projects_list[]=$this->db->escape($projectid);
			}
			$projects=implode(',',$projects_list);
		}
				
//		var_dump($countries);
		
		$is_variable_search=FALSE;
		$where_list=array();
		
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
		
		//projects
		if ($projects!='')
		{
			$where_list[]=sprintf('surveys.id in (select sid from survey_projects where projectid in (%s) )',$projects);
		}
		
		//study
		if ($study_keywords!='')
		{
			$study_fulltext_index='surveys.titl,surveys.authenty,surveys.geogcover,surveys.nation,surveys.topic,surveys.scope,surveys.sername,surveys.producer,surveys.sponsor,surveys.refno';
			$where_list[]=sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)',$study_fulltext_index,$this->db->escape($study_keywords));
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
						}
					}	
				}
				
				if ($tmp_where!=NULL)
				{
					$where_list[]='('.implode(' OR ',$tmp_where).')';
					$is_variable_search=TRUE;
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
					$is_variable_search=TRUE;
				}
				else if (strlen($variable_keywords)>3)
				{
					$where_list[]=sprintf('MATCH(%s) AGAINST (\'%s\')','v.labl',$variable_keywords);
					$is_variable_search=TRUE;
				}
			}			
		}		
				
		//create combined where clause
		$where='';
		foreach($where_list as $stmt)
		{
			if ($where=='')
			{
				$where=' WHERE '. $stmt;
			}
			else
			{
				$where.=' AND '. $stmt;
			}
		}

		$fields='id,refno,surveyid,titl,nation,authenty, forms.model as form_model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate';
		
		//build final sql
		$sql='';
		if ($is_variable_search)
		{
			//variable search
			$sql='select '.$fields.',varcount, count(*) as var_found
					from surveys
					left join forms on surveys.formid=forms.formid
			    	right join variables v on surveys.id=v.sid '.$where;
			$sql.=' group by id,surveyid,titl,nation';
		}		
		else //study search
		{
			
			$sql='select '.$fields.' from surveys ';
			$sql.=' left join forms on surveys.formid=forms.formid ';
			$sql.=$where;
			$sql.=" ORDER BY $sort_by $sort_order";
		}
		
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

		$tmp['total']=count($result);
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$output;
		return $tmp;		
	}

	/**
	* return survey topics
	* TODO:///
	* @survey_list	array of survey ids
	*/
	function get_survey_topics($survey_list)
	{
		
	}
	
	
	/**
	* Variable search
	*
	*/
	function vsearch($survey_id=NULL,$limit=50,$offset=0,$variable_view=FALSE)
	{
		$variable_keywords=$this->input->get('vk');
		$variable_fields=$this->input->get('vf');

		$where_list=NULL;		
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
						if (strlen($variable_keywords) <=3)
						{
							if (strlen($variable_keywords) ==3)
							{
								//LIKE query
								//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
								
								//REGEXP query 
								$tmp_where[]=sprintf('%s REGEXP ( %s)',''.$field,$this->db->escape('[[:<:]]'.$variable_keywords.'[[:>:]]'));	
							}
						}
						else
						{
						$tmp_where[]=sprintf('MATCH(%s) AGAINST (%s)',''.$field,$this->db->escape($variable_keywords));
						}
					}	
				}
				
				if ($tmp_where!=NULL)
				{
					$where_list[]='('.implode(' OR ',$tmp_where).')';
					$is_variable_search=TRUE;
				}
			}
			else //no variable search field is selected, use the default field
			{
				if (strlen($variable_keywords) ==3)
				{
					//LIKE query
					//$tmp_where[]=sprintf('%s LIKE (\'%s\')','v.'.$field,'%'.$variable_keywords.'%');	
				
					//REGEXP query 
					$where_list[]=sprintf('%s REGEXP ( %s)','labl',$this->db->escape('[[:<:]]'.$variable_keywords.'[[:>:]]'));
				}
				else if (strlen($variable_keywords)>3)
				{
					$where_list[]=sprintf('MATCH(%s) AGAINST (%s)','labl',$this->db->escape($variable_keywords));
				}
			}			
		}		
			
		if ($where_list==NULL)
		{
			return false;
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
		
		if ($variable_view==TRUE)
		{
			$this->db->select("variables.uid,variables.name,variables.labl,variables.vid,  surveys.titl, variables.sid");
			$this->db->join('surveys', 'variables.sid = surveys.id','right');	
		}
		else
		{
			$this->db->select("variables.uid,variables.name,variables.labl,variables.vid, variables.sid");
		}	

		$this->db->where($where);

		if ($survey_id!=NULL)
		{
			$this->db->where('sid', $survey_id);
			$result=$this->db->get("variables")->result_array();
			return $result;
		}

		$result=$this->db->get("variables")->result_array();
	
		//return $result;
		$tmp['total']=$this->db->count_all('variables');
		$tmp['found']=$this->_get_variable_count($where);
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$result;
		return $tmp;		
	}
	
	function _get_variable_count($where)
	{
		$this->db->where($where);
		return $this->db->count_all_results('variables');
	}
	
	
	function array_msort($array, $cols)
	{
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$eval = 'array_multisort(';
		foreach ($cols as $col => $order) {
			$eval .= '$colarr[\''.$col.'\'],'.$order.',';
		}
		$eval = substr($eval,0,-1).');';
		eval($eval);
		$ret = array();
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				$k = substr($k,1);
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
		}
		return $ret;
	}
	
}
?>