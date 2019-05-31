<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Data Catalog Search Class for SOLR
 *
 *
 *
 *
 */
class Catalog_search_solr{

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
	var $repo='';
	var $collections=array();
	var $dtype=array();//data access type
	var $sid=''; //comma separated list of survey IDs
	var $debug=false;

	//allowed variable search fields
	var $variable_allowed_fields=array('labl','name','qstn','catgry');

	//allowed sort options
	var $sort_allowed_fields=array(
		'title'=>'title',
		'nation'=>'nation',
		'year'=>'year_start',
		'popularity'=>'total_views',
		'rank'=>'score'
	);

	var	$sort_allowed_order=array('asc','desc');

	//default sort
	var $sort_by='title';
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
		$this->ci->config->load('solr');

		//change default sort if regional search is ON
		if ($this->ci->config->item("regional_search")=='yes'){
			$this->sort_by='nation';
		}

		if (count($params) > 0){
			$this->initialize($params);
		}
		//$this->ci->output->enable_profiler(TRUE);
	}

	function initialize($params=array())
	{
		if (count($params) > 0){
			foreach ($params as $key => $val){
				if (isset($this->$key)){
					$this->$key = $val;
				}
			}
		}

		//intialize solr client
		$this->initialize_solr();
	}

	private function initialize_solr()
	{
		require('vendor/autoload.php');
		
		$config = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $this->ci->config->item('solr_host'),
                    'port' => $this->ci->config->item('solr_port'),
                    'path' => $this->ci->config->item('solr_collection'),
                )
            )
        );

		// create a client instance
		$this->solr_client = new Solarium\Client($config);

	}

	function search($limit=15,$offset=0)
	{
		$search_query=array();
		$result=array();
		$query = $this->solr_client->createSelect();

		//set edismax options
		$edismax = $query->getEDisMax();		

		$query->createFilterQuery('published')->setQuery('published:1');
		$helper = $query->getHelper();

		//SK
		if($this->study_keywords){
			$search_query[]='_text_:'.$helper->escapeTerm($this->study_keywords);
		}

		//VK
		if($this->variable_keywords){
			$search_query[]='{!join from=sid to=survey_uid}'.$helper->escapeTerm($this->variable_keywords);
		}

		$study=$this->_build_study_query();
		$variable=$this->_build_variable_query();
		$topics=$this->_build_topics_query();
		$countries=$this->_build_countries_query();
		$collections=$this->_build_collections_query();
		$years=$this->_build_years_query();
		$repository=$this->_build_repository_query();
		$dtype=$this->_build_dtype_query();


		if ($topics){
			$search_query[]=$topics;
		}

		//$sid=$this->_build_sid_query();

		$sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';

		$sort_by='title';
		if (array_key_exists($this->sort_by,$this->sort_allowed_fields)){
			$sort_by=$this->sort_by;
		}

		//years filter
		if ($years)	{
			foreach($years as $key=>$year){
				$query->createFilterQuery('years'.$key)->setQuery($year);
			}
		}

		//dtype filter
		if ($dtype){
			$query->createFilterQuery('dtype')->setQuery($dtype);

		}

		//countries filter
		if($countries){
			$query->createFilterQuery('countries')->setQuery($countries);
		}

		$sort_options[0]=array('sort_by'=>$sort_by, 'sort_order'=> (strtolower($sort_order)=='asc') ? $query::SORT_ASC : $query::SORT_DESC);


		//multi-column sort
		if ($sort_by=='nation'){
			$sort_options[1]=array('sort_by'=>'year', 'sort_order'=>$query::SORT_DESC);
			$sort_options[2]=array('sort_by'=>'title', 'sort_order'=>$query::SORT_ASC);
		}
		elseif ($sort_by=='title'){
			//$sort_options[1]=array('sort_by'=>'year', 'sort_order'=>$query::SORT_DESC);
			//$sort_options[2]=array('sort_by'=>'nation', 'sort_order'=>$query::SORT_ASC);
		}
		if ($sort_by=='year'){
			$sort_options[2]=array('sort_by'=>'nation', 'sort_order'=>$query::SORT_ASC);
			$sort_options[2]=array('sort_by'=>'title', 'sort_order'=>$query::SORT_ASC);
		}

		//multi-sort
		foreach($sort_options as $sort){
			$query->addSort($this->sort_allowed_fields[$sort['sort_by']], $sort['sort_order']);
		}

		/////////////////// variable search ///////////////////////////////////////////////////////
		if ($variable!==FALSE){
			/*
			//set keyword search query
			if (count($search_query)>0){
				$query->setQuery(implode(" AND ",$search_query));
			}

			//set filter - varcount > 0
			$query->createFilterQuery('varcount')->setQuery('!varcount:0');
			
			$query->setStart($offset)->setRows($limit);
			$query->setFields(array(
				'id:survey_uid',
				'type:dataset_type',
				'survey_uid',
				'title',
				'nation',
				'formid',
				'form_model',
				'repositoryid',
				'repo_title',
				'total_views',
				'total_downloads',
				'link_da',
				'authoring_entity',
				'created',
				'changed',
				'year_start',
				'year_end',
				'varcount'
			));

			//enable debugging
			if ($this->debug){
				$debug = $query->getDebug();
			}

			//execute search
			$resultset = $this->solr_client->select($query);//->getData();

			//get raw query
			if($this->debug){
				$request = $this->solr_client->createRequest($query);
				$result['request_uri']=$request->getUri();
				$result['debug']=$resultset->getDebug();

				echo '<pre>';
				echo ($result['request_uri']);
				echo '</pre>';
			}

			//get the total number of documents found by solr
			$this->search_found_rows=$resultset->getNumFound();

			//get total survey count from index
			$this->total_surveys=$this->solr_total_count($doctype=1);

			//get search result as array
			$this->search_result=$resultset->getData();

			$this->search_result=$this->search_result['response']['docs'];

			$vars_found_per_survey=array();

			//get variables counts per survey
			if ($this->search_found_rows>0)
			{
				$survey_arr=array();
				foreach($resultset as $doc){
					$survey_arr[]=$doc->survey_uid;
				}

				$vars_found_per_survey=$this->get_var_count_by_surveys($survey_arr,$this->variable_keywords);
			}

			//add variable counts to the main search result
			foreach($this->search_result as $key=>$row){
				if (array_key_exists($row['survey_uid'],$vars_found_per_survey)){
					$this->search_result[$key]['var_found']=$vars_found_per_survey[$row['survey_uid']];
				}
			}

			$result['rows']=$this->search_result;
			$result['found']=$this->search_found_rows;
			$result['total']=$this->total_surveys;
			$result['limit']=$limit;
			$result['offset']=$offset;
			$result['citations']=$this->get_survey_citation();

			return $result;*/
			throw new exception ("VARIABLE_SEARCH_DISABLED");
		}
		else
		{
			//study search //////////////////////////////////////////////////////////////////////////////////////
			$edismax->setQueryFields("title^2.0 nation^20.0 years^30.0");

			//keywords <N all required, else match percent
			$edismax->setMinimumMatch("3<90%");

			if (count($search_query)>0){
				$query->setQuery(implode(" AND ",$search_query));
			}

			//$debug = $query->getDebug();
			$query->createFilterQuery('study_search')->setQuery('doctype:1');
			$query->setStart($offset)->setRows($limit);
			$query->setFields(array(
				'id:survey_uid',
				'type:dataset_type',
				'idno',
				'title',
				'nation',
				'formid',
				'form_model',
				'repositoryid',
				'repo_title',
				'total_views',
				'total_downloads',
				'link_da',
				'created',
				'changed',
                'year_start',
                'year_end',
				'authoring_entity',
				'score',
				'varcount'
			));

			//enable debugging
			if ($this->debug){
				$debug = $query->getDebug();
			}

			$resultset = $this->solr_client->select($query);//->getData();

			//get raw query
			if($this->debug){
				$request = $this->solr_client->createRequest($query);
				$result['request_uri']=$request->getUri();
				$result['debug']=$resultset->getDebug();
			}
		}

		//get the total number of documents found by solr
		$this->search_found_rows=$resultset->getNumFound();

		//get total survey count from index
		$this->total_surveys=$this->solr_total_count($doctype=1);

		//get search result as array
		$this->search_result=$resultset->getData();

		$this->search_result=$this->search_result['response']['docs'];


		/*
		///////// DEBUG RESULTS //////////////////////////////////////////////
		
			$debugResult = $resultset->getDebug();
			
			echo '<pre>';
			echo $result['request_uri'];
			echo '</pre>';

			echo '<h1>Debug data</h1>';
			echo 'Querystring: ' . $debugResult->getQueryString() . '<br/>';
			echo 'Parsed query: ' . $debugResult->getParsedQuery() . '<br/>';
			echo 'Query parser: ' . $debugResult->getQueryParser() . '<br/>';
			echo 'Other query: ' . $debugResult->getOtherQuery() . '<br/>';
			
		////////////// END DEBUG ////////////////////////////////////////////
		*/

		//combine into one array
		$result['rows']=$this->search_result;
		$result['found']=$this->search_found_rows;
		$result['total']=$this->total_surveys;
		$result['limit']=$limit;
		$result['offset']=$offset;
		$result['citations']=$this->get_survey_citation();


		if ($result['found']>0){
			//search for variables for SURVEY types
			$id_list=array_column($this->search_result, "id");

			if(count($id_list)>0){
				
				//search variables and get the counts
				$variables_by_study=$this->get_var_count_by_surveys($id_list,$this->study_keywords);

				if(!empty($variables_by_study)){
					foreach($this->search_result as $idx=>$row)
					{
						if(array_key_exists($row['id'],$variables_by_study)){
							$this->search_result[$idx]['var_found']=$variables_by_study[$row['id']];
						}
					}
				}
			}

			$result['rows']=$this->search_result;
		}

		return $result;
	}


	//find variables by survey list
	function get_var_count_by_surveys($survey_arr,$variable_keywords)
	{
		/*
		Query to be executed:

		q= doctype:2 AND text:education AND sid:(590 OR ...)
		 other params: group=true&group.field=sid&group.ngroups=true&group.limit=0

		 ?q=doctype%3A2+AND+text%3Aeducation+AND+sid%3A(590)&wt=json&indent=true&group=true&group.field=sid&group.ngroups=true&group.limit=0
		*/

		if(empty($variable_keywords)){
			return;
		}


		//get a select query instance
		$query = $this->solr_client->createSelect();

		//set a query (all prices starting from 12)
		$query->setQuery(sprintf('doctype:2 AND _text_:(%s) AND sid:(%s)',$variable_keywords, implode(" OR ",$survey_arr)) );

		//set start and rows param (comparable to SQL limit) using fluent interface
		$query->setStart(0)->setRows(100);


		if ($this->debug){
			$debug = $query->getDebug();
		}

	
		//get grouping component and set a field to group by
		$groupComponent = $query->getGrouping();
		$groupComponent->addField('sid'); //group by field
		$groupComponent->setLimit(0); // maximum number of items per group
		$groupComponent->setNumberOfGroups(true); // get a group count

		//execute search
		$resultset = $this->solr_client->select($query);

	
		//get raw query
		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			echo "<HR>";
			echo $request->getUri();
			echo "<HR>";
			//var_dump($resultset->getDebug());
		}
	

		//get groups resultset
		$groups = $resultset->getGrouping();

		$output=array();

		foreach ($groups as $groupKey => $fieldGroup)
		{
			foreach ($fieldGroup as $valueGroup)
			{
				//format=$output[sid]=num of vars found
				$output[(int)$valueGroup->getValue()]=(int)$valueGroup->getNumFound();
			}
		}

		return $output;
	}



	//return total documents county by doctype
	//1=survey, 2=variable
	function solr_total_count($doctype=1)
	{
		$query = $this->solr_client->createSelect();
		$query->setQuery('doctype:'.$doctype);
		$query->createFilterQuery('published')->setQuery('published:1');
		$query->setStart(0)->setRows(0);
		$resultset = $this->solr_client->select($query);
		return $resultset->getNumFound();
	}


	/**
	* Build study search
	*/
	function _build_study_query()
	{
		if (!$this->study_keywords){
			return false;
		}

		return array('title'=>$this->study_keywords);
	}


	function _build_variable_query()
	{
		$variable_keywords=trim($this->variable_keywords);
		$variable_fields=$this->variable_fields();		//cleaned list of variable fields array

		if ($variable_keywords==''){
			return FALSE;
		}

		$tmp_where=array();

		if (strlen($variable_keywords) >3){
			//get fulltext index name
			$fulltext_index=$this->get_variable_search_field(TRUE);

			//FULLTEXT
			$tmp_where[]=sprintf('MATCH(%s) AGAINST (%s IN BOOLEAN MODE)','v.'.$fulltext_index,$this->ci->db->escape($variable_keywords));
		}
		else if (strlen($variable_keywords) ==3){
			//get concatenated fields for wild card/regex search
			$regex_fields=$this->get_variable_search_field(FALSE);

			//REGEXP query
			$variable_keywords=sprintf("[[:<:]]%s[[:>:]]",$variable_keywords);
			$tmp_where[]=sprintf('%s REGEXP (%s)',$regex_fields,$this->ci->db->escape($variable_keywords));
		}

		if (count($tmp_where)>0)
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
			$topics=implode(' OR ',$topics_clean);

			if ($topics)
			{
				return sprintf(' topics_id:(%s)',$topics);
			}
		}

		return FALSE;
	}

	

	//returns country IDs by country names
	function get_country_id_by_name($country_names=array())
	{
		$this->ci->db->select("countryid");
		$this->ci->db->where_in('name',$country_names);
		$query= $this->ci->db->get('countries')->result_array();

		if (!$query)
		{
			return array();
		}

		$output=NULL;

		foreach($query as $country)
		{
			$output[]=$country['countryid'];
		}

		return $output;
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

		//check if country[] param contains the country name instead of country id
		if (isset($countries[0]) && !is_numeric($countries[0]))
		{
			//get country id by name
			$countries=$this->get_country_id_by_name($countries);
		}

		foreach($countries  as $country)
		{
			if (is_numeric($country))
			{
				$countries_list[]=(int)$country;
			}
		}

		if ( count($countries_list)>0)
		{
			$countries_str=implode(' OR ',$countries_list);

			if ($countries_str!='')
			{
				return sprintf(' countries:(%s)',$countries_str);
			}

		}

		return FALSE;
	}


	function _build_years_query()
	{
		$from=(integer)$this->from;
		$to=(integer)$this->to;

		if ($from>0 && $to>0)
		{
			$years=array();
			$years[]=sprintf('years:[%s TO %s]',$from, $to);
			return $years;
		}

		return FALSE;
	}

	function _build_sid_query()
	{
		$sid=explode(",",$this->sid);

		$sid_list=array();
		foreach($sid as $item)
		{
			if (is_numeric($item))
			{
				$sid_list[]=$item;
			}
		}

		if (count($sid_list)>0)
		{
			return sprintf('surveys.id in (%s)',implode(",",$sid_list));
		}

		return FALSE;
	}


	function _build_centers_query()
	{
		$centers=$this->center;//must always be an array

		if (!is_array($centers))
		{
			return FALSE;
		}

		$centers_list=array();

		foreach($centers  as $center)
		{
			//escape country names for db
			$centers_list[]=$this->ci->db->escape($center);
		}

		if ( count($centers_list)>0)
		{
			$centers= implode(',',$centers_list);
		}
		else
		{
			return FALSE;
		}

		if ($centers!='')
		{
			return sprintf('surveys.id in (select sid from survey_centers where id in (%s) )',$centers);
		}

		return FALSE;
	}


	function _build_collections_query()
	{
		$params=$this->collections;//must always be an array

		if (!is_array($params))
		{
			return FALSE;
		}

		$param_list=array();

		foreach($params  as $param)
		{
			//escape country names for db
			$param_list[]=$this->ci->db->escape($param);
		}

		if ( count($param_list)>0)
		{
			$params= implode(',',$param_list);
		}
		else
		{
			return FALSE;
		}

		if ($param!='')
		{
			return sprintf('surveys.id in (select sid from survey_repos where survey_repos.repositoryid in (%s) )',$params);
		}

		return FALSE;
	}

	/**
	*
	* Get the fulltext index or concatenated fields for searching for variables
	*/
	function get_variable_search_field($is_fulltext=TRUE)
	{
		$index=array();

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

		if (count($index)==0)
		{
			$index[]='name,labl,qstn,catgry';
		}

		if ($is_fulltext==TRUE)
		{
			//fulltext
			return implode(',',$index);
		}
		else
		{
			//concatenated fields
			return 'concat(' . implode(",' ',",$index) .')';
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
			return array('labl,qstn,catgry');
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
			return array('labl');
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
			$survey_id_list[]=$row['id'];
		}

		$surveys=implode(',',$survey_id_list);
		$this->ci->db->select('sid,count(sid) as total');
		$this->ci->db->where("sid in ($surveys)");
		$this->ci->db->group_by('sid');
		$query=$this->ci->db->get('survey_citations');

		if ($query)
		{
			$citation_rows=$query->result_array();

			$result=array();

			foreach($citation_rows as $row)
			{
				$result[$row['sid']]=$row['total'];
			}
			return $result;
		}

		return FALSE;
	}

	//search on variables
	function vsearch($limit = 15, $offset = 0)
	{
		$countries=$this->_build_countries_query();
		$years=$this->_build_years_query();
		$dtype=$this->_build_dtype_query();

		//get a select query instance
		$query = $this->solr_client->createSelect();

		$query->setFields(array(
				'vid',
				'labl',
				'name',
				'qstn',
				'sid'
			));

		//vk
		if($this->variable_keywords)
		{
			//$search_query[]='{!join from=sid to=survey_uid}'.$this->variable_keywords;
			//{!join from=survey_uid to=sid}survey AND countries:1

			//join FQ must have all the other filters for countries, years, topics, data access types in the same FQ to work

			$join_fq=array();

			$sk=$this->study_keywords;

			if (trim($sk)=="")
			{
				$sk="*";
			}

			//survey keyword search
			$var_survey_join=sprintf("{!join from=survey_uid to=sid} %s",$sk);

			//published content only
			$join_fq[]='published:1';

			//countries filter
			if($countries){
				$join_fq[]=$countries;
			}

			//years filter
			if ($years)	{
				foreach($years as $key=>$year){
					$join_fq[]=$year;
				}
			}

			//dtype filter
			if($dtype){
				$join_fq[]=$dtype;
			}

			$join_query=$var_survey_join . ' AND '.implode(" AND ",$join_fq);
			$query->createFilterQuery('variable_join')->setQuery($join_query);
		}

		// create a filterquery
		$query->createFilterQuery('doctype_vsearch')->setQuery('doctype:2');
		// {!join from=survey_uid to=sid}survey AND countries:1

		//set a query (all prices starting from 12)
		$query->setQuery(sprintf('_text_:%s',$this->variable_keywords) );
		$query->setStart($offset)->setRows($limit); //get 0-100 rows

		//execute search
		$resultset = $this->solr_client->select($query);

		//get the total number of documents found by solr
		$found_rows=$resultset->getNumFound();

		//get search result as array
		$this->search_result=$resultset->getData();

		if ($found_rows>0)
		{
			//get the survey title, country info for all found variables
			$survey_list=array();
			foreach($this->search_result['response']['docs'] as $row)
			{
				$survey_list[]=$row['sid'];
			}

			//get survey info from db
			$surveys=$this->_get_survey_by_id($survey_list);

			//update the resultset with survey info
			foreach($this->search_result['response']['docs'] as $key=>$row)
			{
				$this->search_result['response']['docs'][$key]['title']=$surveys[$row['sid']]['title'];
				$this->search_result['response']['docs'][$key]['nation']=$surveys[$row['sid']]['nation'];
			}
		}

		$tmp['total']=$this->ci->db->count_all('variables');
		$tmp['found']=$found_rows;
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$this->search_result['response']['docs'];
		return $tmp;		
	}

	//search for variables for a single survey
	function v_quick_search($surveyid=NULL,$limit=50,$offset=0)
	{
		//get a select query instance
		$query = $this->solr_client->createSelect();

		//set Edismax
		$edismax = $query->getEDisMax();


		$query->setFields(array(
				'vid',
				'labl',
				'name'
			));

		//set a query (all prices starting from 12)
		$query->setQuery(sprintf('doctype:2 AND _text_:(%s) AND sid:(%s)',$this->study_keywords, $surveyid ) );
		$query->setStart(0)->setRows(100); //get 0-100 rows

		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			echo 'Request URI: ' . $request->getUri() . '<br/>';
		}

		//execute search
		$resultset = $this->solr_client->select($query);

		//get the total number of documents found by solr
		$this->search_found_rows=$resultset->getNumFound();

		//get search result as array
		$this->search_result=$resultset->getData();
		return $this->search_result['response']['docs'];
	}


 	/*
	*	find surveys by survey ID
	*
	*	@id_arr - array of survey ids
 	*/
  	private function _get_survey_by_id($id_arr)
	{
		//create filter query for survey IDs
		$survey_fq=implode(" OR ", $id_arr);
		$survey_fq= sprintf(' survey_uid:(%s)',$survey_fq);

		//get a select query instance
		$query = $this->solr_client->createSelect();

		$query->setFields(array(
				'id:survey_uid',
				'title:title',
				'nation'
			));

		//filter on survey id
		$query->createFilterQuery('survey_list')->setQuery($survey_fq);

		//set to return surveys only
		$query->createFilterQuery('survey_doctype_1')->setQuery('doctype:1');

		$query->setRows(count($id_arr)); //get 0-100 rows

		//execute search
		$resultset = $this->solr_client->select($query);

		//get the total number of documents found by solr
		$found_rows=$resultset->getNumFound();

		$output=array();

		$search_result=$resultset->getData();

		//get search result as array
		foreach($search_result['response']['docs'] as $row){
				$output[$row['id']]=$row;
		}

		return $output;
	}

	
	function _build_repository_query()
	{
		$repo=(string)$this->repo;

		if ($repo!='')
		{
			return sprintf('survey_repos.repositoryid = %s',$this->ci->db->escape($repo));
		}
		return FALSE;
	}

	function _build_dtype_query()
	{
		$dtypes=$this->dtype;

		if (!is_array($dtypes) || count($dtypes)<1)
		{
			return FALSE;
		}

		foreach($dtypes as $key=>$value)
		{
			if (!is_numeric($value))
			{
				unset($dtypes[$key]);
			}
		}

		$types_str=implode(" OR ",$dtypes);

		if ($types_str!='')
		{
			return sprintf(' formid:(%s)',$types_str);
		}

		return FALSE;
	}

}// END Search class

/* End of file Catalog_search_solr.php */
/* Location: ./application/libraries/Catalog_search_solr.php */