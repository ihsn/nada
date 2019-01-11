<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Data Catalog Search Class for POSTGRES Database [beta]
 * 
 * 
 *
 * @subpackage	Libraries
 * @category	Data Catalog Search
 *
 */ 
class Catalog_search{    	
	
	var $ci;
	
	var $errors=array();
	
	//search fields
	var $study_keywords='';
	var $variable_keywords='';
	var $variable_fields=array();
	var $topics=array();
	var $countries=array();
	var $from=0;
	var $to=0;

	//allowed variable search fields
	var $variable_allowed_fields=array('labl','name','qstn','catgry');
	
	//allowed sort options
	var $sort_allowed_fields=array('titl','nation','proddate');
	var	$sort_allowed_order=array('asc','desc');
	
	//default sort
	var $sort_by='titl';
	var $sort_order='ASC';
	
		
    /**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	function __construct($params = array())
	{
		$this->ci=& get_instance();
		
		//change default sort if regional search is ON
		if ($this->ci->config->item("regional_search")=='yes')
		{
			$this->sort_by='nation';
		}
		
		if (count($params) > 0)
		{
			$this->initialize($params);
		}
		
		//log_message('debug', "Catalog_search Class Initialized");
		//$this->ci->output->enable_profiler(TRUE);
	}
	
	function initialize($params=array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}
		}
	}


	//build the search query
	function _search_count($limit=15, $offset=0)
	{
		$study=$this->_build_study_query();
		$variable=$this->_build_variable_query();
		$topics=$this->_build_topics_query();
		$countries=$this->_build_countries_query();
		$years=$this->_build_years_query();
		
		$sort_by=in_array($this->sort_by,$this->sort_allowed_fields) ? $this->sort_by : 'titl';
		$sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';		
		$sort_options[0]=$sort_options[0]=array('sort_by'=>$sort_by, 'sort_order'=>$sort_order);
						
		//array of all options
		$where_list=array($study,$variable,$topics,$countries,$years);
		
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
				if ($stmt!==FALSE) {
					$where.="\r\n".' AND '. $stmt;
				}
			}
		}
		
		//study fields returned by the select statement
		$study_fields='id,surveyid,titl,nation,authenty, f.model as form_model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid';

		//build final search sql query
		$sql='';
		$sql_array=array();
		
		if ($variable!==FALSE)
		{
			//variable search			
			$this->ci->db->select('count(*) as rowsfound',FALSE);
			$this->ci->db->from('surveys');
			$this->ci->db->join('forms f','surveys.formid=f.formid','left');
			$this->ci->db->join('variables v','surveys.id=v."sid"','inner');
			$this->ci->db->group_by('id,refno,surveyid,titl,nation,authenty, f.model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid,varcount');
			
			if ($where!='') 
			{
				$this->ci->db->where($where);
			}

			//group by
			$sql.=" GROUP BY id,refno,surveyid,titl,nation,authenty, f.model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid,varcount \r\n";

			$query=$this->ci->db->get();
		}		
		else 
		{
			//study search
			$this->ci->db->select(" count(*) as rowsfound ",FALSE);
			$this->ci->db->from('surveys');
			$this->ci->db->join('forms f','surveys.formid=f.formid','left');
			
			if ($where!='') 
			{
				$this->ci->db->where($where);
			}
		
			$query=$this->ci->db->get();
		}
		
		if ($query)
		{
			//result to array
			$rows_found=$query->row_array();
			return $rows_found['rowsfound'];
		}
		else
		{
			//some error occured
			return FALSE;
		}		
	}


	//perform the search
	function search($limit=15, $offset=0)
	{
		$study=$this->_build_study_query();
		$variable=$this->_build_variable_query();
		$topics=$this->_build_topics_query();
		$countries=$this->_build_countries_query();
		$years=$this->_build_years_query();
		
		$sort_by=in_array($this->sort_by,$this->sort_allowed_fields) ? $this->sort_by : 'titl';
		$sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';
		
		$sort_options[0]=$sort_options[0]=array('sort_by'=>$sort_by, 'sort_order'=>$sort_order);
		
		//multi-column sort
		if ($sort_by=='nation')
		{
			$sort_options[1]=array('sort_by'=>'proddate', 'sort_order'=>'desc');
			$sort_options[2]=array('sort_by'=>'titl', 'sort_order'=>'asc');
		}
		elseif ($sort_by=='titl')
		{
			$sort_options[1]=array('sort_by'=>'proddate', 'sort_order'=>'desc');
			$sort_options[2]=array('sort_by'=>'nation', 'sort_order'=>'asc');
		}
		if ($sort_by=='proddate')
		{
			$sort_options[2]=array('sort_by'=>'nation', 'sort_order'=>'asc');
			$sort_options[2]=array('sort_by'=>'titl', 'sort_order'=>'asc');
		}
				
		//array of all options
		$where_list=array($study,$variable,$topics,$countries,$years);
		
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
				if ($stmt!==FALSE) {
					$where.="\r\n".' AND '. $stmt;
				}
			}
		}
		
		//study fields returned by the select statement
		$study_fields='id,refno,surveyid,titl,nation,authenty, f.model as form_model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid';

		//build final search sql query
		$sql='';
		$sql_array=array();
		
		if ($variable!==FALSE)
		{
			//variable search
			
			$this->ci->db->select($study_fields.',varcount, count(*) as var_found',FALSE);
			$this->ci->db->from('surveys');
			$this->ci->db->join('forms f','surveys.formid=f.formid','left');
			$this->ci->db->join('variables v','surveys.id=v."sid"','inner');
			$this->ci->db->group_by('id,refno,surveyid,titl,nation,authenty, f.model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid,varcount');
			
			//$sql=sprintf('SELECT %s from surveys s',$study_fields.', varcount, count(*) as var_found');
			//$sql.=sprintf(" LEFT JOIN forms f on f.formid=s.formid \r\n");
			//$sql.=" INNER JOIN variables v on s.id=v.sid \r\n";
			
			if ($where!='') 
			{
				//$sql.=' WHERE '.$where;
				$this->ci->db->where($where);
			}

			//group by
			$sql.=" GROUP BY id,refno,surveyid,titl,nation,authenty, f.model,link_report,link_indicator, link_questionnaire, link_technical, link_study,proddate, isshared, repositoryid,varcount \r\n";

			//multi-sort
			$sql_sorts=array();
			foreach($sort_options as $sort)
			{
				//$sql_sorts[]=sprintf(' %s %s', $sort['sort_by'],$sort['sort_order']);
				$this->ci->db->order_by($sort['sort_by'],$sort['sort_order']);
			}
			
			/*if (count($sql_sorts)>0)
			{			
				$sql.=sprintf(' ORDER BY %s', implode(", ", $sql_sorts));
			}*/
			
			$this->ci->db->limit($limit,$offset);			
			$query=$this->ci->db->get();
		}		
		else 
		{
			//study search
			$this->ci->db->select(" $study_fields ",FALSE);
			$this->ci->db->from('surveys');
			$this->ci->db->join('forms f','surveys.formid=f.formid','left');

			//multi-sort
			foreach($sort_options as $sort)
			{
				$this->ci->db->order_by($sort['sort_by'],$sort['sort_order']);
			}

			$this->ci->db->limit($limit,$offset);
			
			if ($where!='') 
			{
				$this->ci->db->where($where);
			}
		
			$query=$this->ci->db->get();
		}
		
		if ($query)
		{
			//result to array
			$this->search_result=$query->result_array();
		}
		else
		{
			//some error occured
			return FALSE;
		}
		
		//get total search result count		
		//$query_found_rows=$this->ci->db->query('SELECT @@rowcount as rowsfound',FALSE)->row_array();
		//$this->search_found_rows=$query_found_rows['rowsfound'];
		$this->search_found_rows=$this->_search_count();
		
		//get total surveys in db
		$query_total_surveys=$this->ci->db->query(sprintf('SELECT count(*) as rowsfound from %ssurveys', $this->ci->db->dbprefix))->row_array();
		$this->total_surveys=$query_total_surveys['rowsfound'];

		//combine into one array
		$result['rows']=$this->search_result;
		$result['found']=$this->search_found_rows;
		$result['total']=$this->total_surveys;
		$result['limit']=$limit;
		$result['offset']=$offset;
		$result['citations']=$this->get_survey_citation();
		return $result;
	}
	
	/**
	* Build study search
	*/
	function _build_study_query()
	{
		//study search keywords
		$study_keywords=$this->study_keywords;
		
		//fulltext index name
		//$study_fulltext_index='surveys.titl,surveys.authenty,surveys.geogcover,surveys.nation,surveys.topic,surveys.scope,surveys.sername,surveys.producer,surveys.sponsor,surveys.refno';

		$tmp_where=NULL;
		$keyword_list=explode(" ", $study_keywords);
		foreach($keyword_list as $keyword)
		{
			if (strlen($keyword) >=3)
			{
				//get fulltext index name
				//$fulltext_index=$this->get_variable_search_field(TRUE);
				
				//wild card search
				$tmp_where[]=sprintf('titlstmt ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('authenty ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('nation ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('producer ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('sponsor ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('geogcover ilike %s',$this->ci->db->escape('%'.$keyword.'%'));
			}	
		}

		if ($tmp_where!=NULL)
		{
			return '('.implode(' OR ',$tmp_where).')';
		}
		
		return FALSE;

	}
			
	function _build_variable_query()
	{
		$variable_keywords=trim($this->variable_keywords);
		$variable_fields=$this->variable_fields();		//cleaned list of variable fields array
	
		if ($variable_keywords=='')
		{
			return FALSE;
		}

		$tmp_where=NULL;
		$keyword_list=explode(" ", $variable_keywords);
		
		foreach($keyword_list as $keyword)
		{
			if (strlen($keyword) >=3)
			{
				//get fulltext index name
				//$fulltext_index=$this->get_variable_search_field(TRUE);
				
				//wild card search
				$tmp_where[]=sprintf('v.labl like %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('v.name like %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('v.qstn like %s',$this->ci->db->escape('%'.$keyword.'%'));
				$tmp_where[]=sprintf('v.catgry like %s',$this->ci->db->escape('%'.$keyword.'%'));
			}	
		}	
				
		if ($tmp_where!=NULL)
		{
			return '('.implode(' OR ',$tmp_where).')';
		}
		
		return FALSE;
	}
	
	/**
	*
	* build where for topics
	*/
	function _build_topics_query()
	{
		$topics=$this->topics;//must always be an array

		if (!is_array($topics))
		{
			return FALSE;
		}
		
		//remove topics that are not numeric
		$topics_clean=array();
		foreach($topics as $topic)
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
		else
		{
			return FALSE;
		}
		
		//topics
		if ($topics!='')
		{
			return sprintf('surveys.id in (select sid from survey_topics where tid in (%s) )',$topics);
		}
		
		return FALSE;
	}
	

	/**
	*
	* build where for nations
	*/
	function _build_countries_query()
	{
		$countries=$this->countries;//must always be an array

		if (!is_array($countries))
		{
			return FALSE;
		}
		
		$countries_list=array();

		foreach($countries  as $country)
		{
			//escape country names for db
			$countries_list[]=$this->ci->db->escape($country);
		}

		if ( count($countries_list)>0)
		{
			$countries= implode(',',$countries_list);
		}
		else
		{
			return FALSE;
		}

		//topics
		if ($countries!='')
		{
			return sprintf('surveys.nation in (%s)',$countries);
		}
		
		return FALSE;
	}
	
	
	function _build_years_query()
	{
		$from=(integer)$this->from;
		$to=(integer)$this->to;
		
		if ($from>0 && $to>0)
		{
			return sprintf('surveys.id in (select sid from survey_years where (data_coll_year between %s and %s) or (data_coll_year=0) )',$from, $to);
		}
		
		return FALSE;
	}
	
		
	/**
	*
	* Get the fulltext index or concatenated fields for searching for variables
	*/	
	function get_variable_search_field($is_fulltext=TRUE)
	{
		$index=NULL;
		
		$variable_fields=$this->variable_fields();
		
		//select which index to use
		if( in_array('name',$variable_fields) )
		{
			$index[]='name';
		}
		if( in_array('labl',$variable_fields) )
		{
			$index[]='labl';
		}
		if( in_array('qstn',$variable_fields) )
		{
			$index[]='qstn';
		}
		if( in_array('catgry',$variable_fields) )
		{
			$index[]='catgry';
		}
		
		if ($index==NULL)
		{
			$index[]='name,labl,qstn';
		}

		if ($is_fulltext==TRUE)	
		{
			//fulltext
			return implode(',',$index);
		}
		else
		{	
			//concatenated fields
			return 'concat('.implode(',',$index).')';
		}
	}

	/*
	* setup variable fields 
	*
	* Note: sets a variable field to search on. If no field selected by the user, selects the default field.
	* 		the function will always return an array of variable field(s)
	*/
	function variable_fields()
	{
		$vf=$this->variable_fields;
		
		if (!is_array($vf))
		{
			//default search field if nothing is selected
			return array('label');
		}
		
		$tmp=NULL;
		foreach($vf as $field)
		{
			if (in_array($field,$this->variable_allowed_fields))
			{
				$tmp[]=$field;
			}
		}
	
		//no allowed fields found	
		if ($tmp==NULL)
		{
			return array('label');
		}
		else
		{
			return $tmp;
		}		
	}

	/**
	*
	* returns an array of surveys with citations
	*
	*/
	function get_survey_citation()
	{
		if (!is_array($this->search_result))
		{
			return FALSE;
		}
		else if (count($this->search_result)==0)
		{
			return FALSE;
		}
		
		//build a list of survey IDs
		foreach($this->search_result as $row)
		{
			if (is_numeric($row['id']))
			{
				$survey_id_list[]=$row['id'];
			}	
		}
		
		$surveys=implode(',',$survey_id_list);
		$this->ci->db->select('sid');	
		$this->ci->db->where("sid in ($surveys)");
		$this->ci->db->group_by('sid');	
		$query=$this->ci->db->get('survey_citations');
		
		if ($query)
		{
			$citation_rows=$query->result_array();
						
			$result=array();
			
			foreach($citation_rows as $row)
			{
				$result[]=$row['sid'];
			}
			return $result;
		}
		
		return FALSE;
	}

	//search on variables
	function vsearch($limit = 15, $offset = 0)
	{
		//sort allowed fields for the variable view
		$sortable_fields=array('name','labl','titl','nation');

		$sort_by=in_array($this->sort_by,$sortable_fields) ? $this->sort_by : 'titl';
		$sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';

		$variable_keywords=$this->variable_keywords;
		$variable_fields=$this->variable_fields;

		$study=$this->_build_study_query();
		$variable=$this->_build_variable_query();
		$topics=$this->_build_topics_query();
		$countries=$this->_build_countries_query();
		$years=$this->_build_years_query();
				
		//array of all options
		$where_list=array($study,$variable,$topics,$countries,$years);
		
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
				if ($stmt!==FALSE) {
					$where.="\r\n".' AND '. $stmt;
				}
			}
		}

		if ($where=='') {
			return FALSE;
		}

		//echo $where;exit;
		
		//search
		$this->ci->db->limit($limit, $offset);		
		$this->ci->db->select("v.uid,v.name,v.labl,v.vid,  surveys.titl as titl,surveys.nation as nation, v.sid");
		$this->ci->db->join('surveys', 'v."sid" = surveys.id','inner');
		$this->ci->db->order_by($sort_by, $sort_order); 
		$this->ci->db->where($where);
		
		//get resultset
		$result=$this->ci->db->get("variables as v")->result_array();
		
		//get total search result count
		$this->ci->db->select("count(*) as rowsfound",FALSE);
		$this->ci->db->join('surveys', 'v."sid" = surveys.id','inner');	
		$this->ci->db->where($where);		
		$query_found_rows=$this->ci->db->get("variables as v")->row_array();		
		$found_rows=$query_found_rows['rowsfound'];

		//return $result;
		$tmp['total']=$this->ci->db->count_all('variables');
		$tmp['found']=$found_rows;
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$result;
		return $tmp;		
	}

	//search for variables for a single survey
	function v_quick_search($surveyid=NULL,$limit=50,$offset=0)
	{
		//sort allowed fields for the variable view
		$sortable_fields=array('name','labl','nation');

		$sort_by=in_array($this->sort_by,$sortable_fields) ? $this->sort_by : 'name';
		$sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';

		$variable_keywords=$this->variable_keywords;
		$variable_fields=$this->variable_fields;

		$variable=$this->_build_variable_query();
				
		//array of all options
		$where_list=array($variable);
		
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
				if ($stmt!==FALSE) {
					$where.="\r\n".' AND '. $stmt;
				}
			}
		}
		
		if ($where=='') {
			return FALSE;
		}

		//echo $where;exit;
		
		//search
		$this->ci->db->limit($limit, $offset);		
		$this->ci->db->select("v.uid,v.name,v.labl,v.vid,v.qstn");
		$this->ci->db->order_by($sort_by, $sort_order); 
		$this->ci->db->where($where);
		$this->ci->db->where('v.sid',$surveyid);
		
		//get resultset
		$query=$this->ci->db->get("variables as v");

		if ($query)
		{
			return $query->result_array();
		}
		else
		{
			return FALSE;
		}
	}


}// END Search class

/* End of file Catalog_search.php */
/* Location: ./application/libraries/Catalog_search.php */