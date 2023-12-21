<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Data Catalog Search Class for SOLR
 *
 *
 *
 *
 */

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;


 class Catalog_search_solr{

	var $ci;

	var $errors=array();

	//search fields
	var $study_keywords='';
	var $variable_keywords='';
	var $variable_fields=array();
	var $topics=array();
	var $countries=array();
	var $regions=array();
	var $from=0;
	var $to=0;
	var $repo='';
    var $collections=array();
    var $type=array();
	var $dtype=array();//data access type
	var $sid=''; //comma separated list of survey IDs
	var $created='';
	var $debug=false;
	var $params=null;
	var $solr_options=array();
	var $varcount='';

	//allowed variable search fields
	var $variable_allowed_fields=array('labl','name','qstn','catgry');

	//allowed sort options
	var $sort_allowed_fields=array(
		'title'=>'title',
        'nation'=>'nation',
        'country'=>'nation',
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
		$this->ci->load->model("Facet_model");
		$this->user_facets=$this->ci->Facet_model->select_all('user');

		//change default sort if regional search is ON
		if ($this->ci->config->item("regional_search")=='yes'){
			$this->sort_by='nation';
		}

		$this->solr_options=$this->ci->config->item("solr_edismax_options");
		$this->solr_variable_options=$this->ci->config->item("solr_edismax_variable_options");

		if($this->ci->config->item('solr_debug')==true){
			$this->debug=true;
		}

		if (count($params) > 0){
			$this->initialize($params);
		}

		$this->params=$params;
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
		$this->solr_config = array(
			'endpoint' => array(
				'localhost' => array(
					'host' => $this->ci->config->item('solr_host'),
					'port' => $this->ci->config->item('solr_port'),
					'path' => '/',
					'core' => $this->ci->config->item('solr_collection'),
				)
			)
		);
		$adapter = new Curl();
		$eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
		$this->solr_client =  new Solarium\Client($adapter,$eventDispatcher, $this->solr_config);
	}


	function search($limit=15,$offset=0)
	{
        $study=$this->_build_study_query();
        $dataset_types=$this->_build_dataset_type_query();
		$topics=$this->_build_topics_query();
		$countries=$this->_build_countries_query();
		$regions=$this->_build_regions_query();
		$collections=$this->_build_collections_query();
		$years=$this->_build_years_query();
		$repository=!empty($this->repo) ? (string)$this->repo : false;
        $dtype=$this->_build_dtype_query();
		$varcount=$this->_build_varcount_query();

		$search_query=array();
		$result=array();
        $query = $this->solr_client->createSelect();
        
        // get the facetset component
        $facetSet = $query->getFacetSet();

        //set facet.field
        $facetSet->createFacetField('dataset_types')->setField('dataset_type')->getLocalParameters()->addExcludes(['tag_dataset_type']);

		//set edismax options
		$edismax = $query->getEDisMax();		

		$query->createFilterQuery('published')->setQuery('published:1');
		$helper = $query->getHelper();

        //dataset type filter
		if($dataset_types){
            $query->createFilterQuery('dataset_type')->addTag('tag_dataset_type')->setQuery($dataset_types);
		}

		//SK
		if($this->study_keywords){
			$query->setQuery(($this->study_keywords));
		}

		//repo filter
		if($repository){
			$query->createFilterQuery('repo')->setQuery('repositories:'.$helper->escapeTerm($repository));
		}

		//region filter
		if($regions){
			$query->createFilterQuery('region')->setQuery($regions);
		}

		if ($topics){
			$search_query[]=$topics;
		}

		//custom user defined filters
		foreach($this->user_facets as $fc){
			if (array_key_exists($fc['name'],$this->params)){
				$filter_=$this->_build_facet_query('fq_'.$fc['name'],$this->params[$fc['name']]);
				if($filter_){
					$query->createFilterQuery('fq_'.$fc['name'])->setQuery($filter_);
				}
			}
		}

		//sort
        $sort_order=in_array($this->sort_order,$this->sort_allowed_order) ? $this->sort_order : 'ASC';
		$sort_by=array_key_exists($this->sort_by,$this->sort_allowed_fields) ? $this->sort_by : 'title';
		
		//order desc by RANK for keyword search
		if(!empty($study) && empty($this->sort_by)){
			$sort_by='rank';
			$sort_order='desc';
		}

		if(empty($study) && $this->sort_by=='rank'){
			$sort_by='title';
			$sort_order='asc';
		}

        $sort_options[0]=array('sort_by'=>$sort_by, 'sort_order'=> (strtolower($sort_order)=='asc') ? $query::SORT_ASC : $query::SORT_DESC);
        $sort_options[1]=array('sort_by'=>'year', 'sort_order'=>$query::SORT_DESC);
		$sort_options[2]=array('sort_by'=>'title', 'sort_order'=>$query::SORT_ASC);
		
		//multi-column sort
		switch($sort_by){

			case 'country':
			case 'nation':
				$sort_options[1]=array('sort_by'=>'year', 'sort_order'=>$query::SORT_DESC);
				$sort_options[2]=array('sort_by'=>'title', 'sort_order'=>$query::SORT_ASC);
				$sort_options[3]=array('sort_by'=>'popularity', 'sort_order'=>$query::SORT_DESC);
				break;
			
			case 'title':
				$sort_options[1]=array('sort_by'=>'year', 'sort_order'=>$query::SORT_DESC);
				$sort_options[2]=array('sort_by'=>'country', 'sort_order'=>$query::SORT_ASC);
				$sort_options[3]=array('sort_by'=>'popularity', 'sort_order'=>$query::SORT_DESC);
				break;
				break;

			case 'year':			
				$sort_options[2]=array('sort_by'=>'country', 'sort_order'=>$query::SORT_ASC);
				$sort_options[2]=array('sort_by'=>'title', 'sort_order'=>$query::SORT_ASC);
				$sort_options[3]=array('sort_by'=>'popularity', 'sort_order'=>$query::SORT_DESC);
				break;

			case 'rank':
				if(!empty($study)){
					$sort_options[0]=$sort_options[0]=array('sort_by'=>'rank', 'sort_order'=>$query::SORT_DESC);
				}
				break;
        }
        
        //multi-sort
		foreach($sort_options as $sort){            
			$query->addSort($this->sort_allowed_fields[$sort['sort_by']], $sort['sort_order']);
		}
        //end-sort
		

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

		//created
		$created_range=$this->_build_created_query();
		if($created_range!==false){
			$query->createFilterQuery('created')->setQuery($created_range);
		}

		//countries filter
		if($countries){
			$query->createFilterQuery('countries')->setQuery($countries);
		}

		if($collections){
			$query->createFilterQuery('collections')->setQuery($collections);
		}

		//varcount filter
		if ($varcount)	{
			$query->createFilterQuery('varcount')->setQuery($varcount);
		}
				
        //study search 
        $edismax->setQueryFields($this->solr_options['qf']);
		
		//$edismax->setQueryFields("title nation years");

        //keywords <N all required, else match percent
        $edismax->setMinimumMatch($this->solr_options['mm']);

        $query->createFilterQuery('study_search')->setQuery('doctype:1');
        $query->setStart($offset)->setRows($limit);
        $query->setFields(array(
            'id:survey_uid',
			'idno',
            'type:dataset_type',
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
            'rank:score',
            'thumbnail',
            'varcount'
        ));

        //enable debugging
        if ($this->debug){
            $debug = $query->getDebug();
        }

        $resultset = $this->solr_client->select($query);
        $facet = $resultset->getFacetSet()->getFacet('dataset_types');

        $dataset_types_facet_counts=array();
        
        foreach ($facet as $value => $count) {
            $dataset_types_facet_counts[$value]=$count;
        }

        //get raw query
        if($this->debug){
            $request = $this->solr_client->createRequest($query);
            $result['request_uri']=$request->getUri();
            $result['debug']=$resultset->getDebug();
            var_dump(urldecode($result['request_uri']));
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
        $result['search_counts_by_type']=$dataset_types_facet_counts;
        
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

		$query = $this->solr_client->createSelect();
		$query->setQuery(sprintf('doctype:2 AND labl:(%s) AND sid:(%s)',($variable_keywords), implode(" OR ",$survey_arr)) );
		$query->setStart(0)->setRows(100);

		if ($this->debug){
			$debug = $query->getDebug();
		}
	
		//Group by SID
		$groupComponent = $query->getGrouping();
		$groupComponent->addField('sid'); //group by field
		$groupComponent->setLimit(0); // maximum number of items per group
		$groupComponent->setNumberOfGroups(true); // get a group count

		try{
			//execute search
			$resultset = $this->solr_client->select($query);
		}
		catch(Exception $e){

			if ($this->debug){
				throw new Exception("Variable search failed: ".$e->getMessage());
			}

			log_message('error', 'Variable search failed: ' . $e->getMessage());
			return false;
		}
	
		//get raw query
		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			echo "<HR>";
			echo $request->getUri();
			echo "<HR>";
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
		$repository=!empty($this->repo) ? (string)$this->repo : false;
		$query = $this->solr_client->createSelect();
		$query->setQuery('doctype:'.$doctype);

		//repo filter
		if($repository){
			$helper = $query->getHelper();
			$query->createFilterQuery('repo')->setQuery('repositories:'.$helper->escapeTerm($repository));
		}

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
	

	protected function _build_facet_query($facet_name,$values)
	{
		if (empty($values)){
			return false;
		}
		
		$values=(array)$values;
		foreach($values  as $idx=>$value){
			if(!empty($value) && is_numeric($value)){
				$values[$idx]=$value;
			}
		}

		$values= implode(' OR ',$values);

		if ($values){
			return sprintf(' %s:(%s)',$facet_name,$values);
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

	function _build_regions_query()
	{
		$regions=$this->regions;

		if (!is_array($regions)){
			return FALSE;
		}

		if ( !count($regions)>0){
			return false;
		}

		$regions_list=array();
		foreach($regions as $region)
		{
			if (is_numeric($region))
			{
				$regions_list[]=(int)$region;
			}
		}

		if ( count($regions_list)>0)
		{
			$regions_str=implode(' OR ',$regions_list);

			if ($regions_str!='')
			{
				return sprintf(' regions:(%s)',$regions_str);
			}

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
    

    function _build_dataset_type_query()
	{
		$types=(array)$this->type;//must always be an array

		if (!is_array($types)){
			return FALSE;
		}
		
		$types_list=array();
				
		foreach($types  as $type){
			if(!empty($type)){
				$types_list[]=$this->ci->db->escape($type);
			}
        }

		$types= implode(',',$types_list);

		if ($types!=''){
            return sprintf(' dataset_type:(%s)',$types);
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


	


	function _build_collections_query()
	{
		$params=$this->collections;//must always be an array

		if (!is_array($params))
		{
			return FALSE;
		}

		$param_list=array();

		foreach($params  as $param){
			if (trim($param)!==''){
				$param_list[]=$this->ci->db->escape($param);
			}
		}

		if ( count($param_list)>0){
			$params= implode(' OR ',$param_list);
			return sprintf(' repositories:(%s)',$params);
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
        $dataset_types=$this->_build_dataset_type_query();
		$countries=$this->_build_countries_query();
		$collections=$this->_build_collections_query();
		$years=$this->_build_years_query();
		$repository=!empty($this->repo) ? (string)$this->repo : false;
        $dtype=$this->_build_dtype_query();

		$query = $this->solr_client->createSelect();

		$query->setFields(array(
				'vid',
				'labl',
				'name',
				'qstn',
				'sid'
			));
		
		$edismax = $query->getEDisMax();
		$helper = $query->getHelper();

		//vk
		//$search_query[]='{!join from=sid to=survey_uid}'.$this->variable_keywords;
		//{!join from=survey_uid to=sid}survey AND countries:1

		//join FQ must have all the other filters for countries, years, topics, data access types in the same FQ to work

		$join_fq=array();
		$sk="*";

		//survey keyword search
		$var_survey_join=sprintf("{!join from=survey_uid to=sid} %s",$sk);

		//published content only
		$join_fq[]='published:1';

		if($countries){
			$join_fq[]=$countries;
		}

		if ($years)	{
			foreach($years as $key=>$year){
				$join_fq[]=$year;
			}
		}

		if($dtype){
			$join_fq[]=$dtype;
		}

		if($repository){
			$join_fq[]=$repository;
		}

		if($collections){
			$join_fq[]=$collections;
		}

		if($dataset_types){
			$join_fq[]=$dataset_types;
		}

		//custom user defined filters
		foreach($this->user_facets as $fc){
			if (array_key_exists($fc['name'],$this->params)){
				$filter_=$this->_build_facet_query('fq_'.$fc['name'],$this->params[$fc['name']]);
				if($filter_){
					$join_fq[]=$filter_;
					//$query->createFilterQuery('fq_'.$fc['name'])->setQuery($filter_);
				}
			}
		}

		$join_query=$var_survey_join . ' AND '.implode(" AND ",$join_fq);
		$query->createFilterQuery('variable_join')->setQuery($join_query);

		$query->createFilterQuery('doctype_vsearch')->setQuery('doctype:2');
		// {!join from=survey_uid to=sid}survey AND countries:1
		
		$edismax->setQueryFields($this->solr_variable_options['qf']);
        $edismax->setMinimumMatch($this->solr_variable_options['mm']);
		
		if($this->study_keywords){
			$query->setQuery($this->study_keywords);
		}

		$query->setStart($offset)->setRows($limit); //get 0-100 rows

		$resultset = $this->solr_client->select($query);
		$found_rows=$resultset->getNumFound();
		$this->search_result=$resultset->getData();

		//get raw query
		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			echo 'Request URI: ' . $request->getUri() . '<br/>';
		}

		if ($found_rows>0)
		{
			//get the survey title, country info for all found variables
			$survey_list=array();
			foreach($this->search_result['response']['docs'] as $row){
				$survey_list[]=$row['sid'];
			}

			$surveys=$this->_get_survey_by_id($survey_list);
			foreach($this->search_result['response']['docs'] as $key=>$row){
				$this->search_result['response']['docs'][$key]['title']=$surveys[$row['sid']]['title'];
				$this->search_result['response']['docs'][$key]['nation']=$surveys[$row['sid']]['nation'];
				$this->search_result['response']['docs'][$key]['idno']=$surveys[$row['sid']]['idno'];
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
				'name',
				'fid'
			));

		//set a query (all prices starting from 12)
		$query->setQuery(sprintf('doctype:2 AND labl:(%s) AND sid:(%s)',$this->study_keywords, $surveyid ) );
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
				'nation',
				'idno'
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



	protected function _build_created_query()
	{
		$created_range=explode("-",$this->created);
		
		if(empty($created_range)){
			return false;
		}

		$created_start=strtotime($created_range[0]);

		if (empty($created_start)){
			return false;
		}

		if (isset($created_range[1]) && strtotime($created_range[1])){
			$created_end= + strtotime($created_range[1]) + 86399;
		}
		else{
			$created_end= $created_start + 86399;
		}		

		if (!empty($created_end)){
			return sprintf('created:[%s TO %s]',$created_start,$created_end);			
		}
		return false;
	}

	function _build_varcount_query()
	{
		//handles only these cases

		//varcount= 
		// >0
		// 0 

		$varcount=$this->varcount;

		if ($varcount=='>0'){
			return sprintf('varcount:[%s TO %s]',1, '*');
		}
		else if ($varcount=='0'){
			return sprintf('varcount:%s',0);
		}

		return FALSE;
	}

}// END Search class

/* End of file Catalog_search_solr.php */
/* Location: ./application/libraries/Catalog_search_solr.php */