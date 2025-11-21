<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Data Catalog Search Class for SOLR
 */

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;


 class Catalog_search_solr{

	var $ci;

	var $errors=array();

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
	var $dtype=array();
	var $sid='';
	var $created='';
	var $debug=false;
	var $params=null;
	var $solr_options=array();
	var $varcount='';

	var $variable_allowed_fields=array('var_label','var_name','var_question');
	
	var $field_mapping_new_to_old=array(
		'var_label' => 'labl',
		'var_name' => 'name', 
		'var_question' => 'qstn',
		'var_survey_id' => 'sid',
		'var_uid' => 'uid',
		'vid' => 'vid',
		'fid' => 'fid'
	);

	var $sort_allowed_fields=array(
		'title'=>'title_sort',
        'nation'=>'nation_sort',
        'country'=>'nation_sort',
		'year'=>'year_start',
		'popularity'=>'total_views',
		'rank'=>'score',
		'relevance'=>'score'
	);

	var	$sort_allowed_order=array('asc','desc');
	var $sort_by='title';
	var $sort_order='ASC';

	var $allowed_search_fields=array(
		'title'=>'title',
		'nation'=>'nation', 
		'country'=>'nation',
		'year'=>'year_start',
		'author'=>'authoring_entity',
		'abstract'=>'abstract',
		'keywords'=>'keywords',
		'methodology'=>'methodology',
		'idno'=>'idno',
		'type'=>'dataset_type'
	);

	var $allowed_variable_search_fields=array(
		'var_name'=>'var_name',
		'var_label'=>'var_label',
		'var_question'=>'var_question', 
		'survey_title'=>'title',
		'survey_nation'=>'nation',
		'survey_year'=>'year_start'
	);


	function __construct($params = array())
	{
		$this->ci=& get_instance();
		$this->ci->config->load('solr');
		$this->ci->load->model("Facet_model");
		$this->user_facets=$this->ci->Facet_model->select_all('user');

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
	}

	function initialize($params=array())
	{
		if (count($params) > 0){
			foreach ($params as $key => $val){
				if (isset($this->$key)){
					$this->$key = $this->validate_parameter($key, $val);
				}
			}
		}

		$this->initialize_solr();
	}

	
	/**
	 * Process query token for advanced queries
	 * 
	 * This function processes a query token for advanced queries.
	 * It handles operators and quoted phrases.
	 * 
	 * @example
	 * $token = '+health';
	 * $processed_token = $this->process_query_token($token);
	 * echo $processed_token; // Output: +health
	 * 
	 * 
	 * @param string $token Query token
	 * @return string Processed query token
	 * 
	 */
	private function process_query_token($token)
	{
		if (preg_match('/^([+\-])(.+)$/', $token, $matches)) {
			$operator = $matches[1];
			$term = $matches[2];
			$term = trim($term, '"\'');
			$escaped_term = preg_replace('/([\\+&\\-\\|\\(\\)\\{\\}\\[\\]\\^~\\*\\?\\:\\/\\\\])/', '\\\\$1', $term);
			return $operator . $escaped_term;
		}
		
		if (preg_match('/^["\'].*["\']$/', $token)) {
			$escaped = preg_replace('/([\\+&\\-\\|\\(\\)\\{\\}\\[\\]\\^~\\*\\?\\:\\/\\\\])/', '\\\\$1', $token);
			return $escaped;
		}
		
		$escaped = preg_replace('/([\\+\\-&\\|\\(\\)\\{\\}\\[\\]\\^"~\\*\\?\\:\\/\\\\])/', '\\\\$1', $token);
		return $escaped;
	}
	

	/**
	 * Validate parameter
	 * 
	 * This function validates a parameter and returns the value.
	 * 
	 * for keywords, it sanitizes the query, validates the length and escapes special characters.
	 * for other parameters, it strips tags and returns the value.
	 * 
	 * @param string $key Parameter key
	 * @param string $value Parameter value
	 * @return string Validated parameter value
	 */
	private function validate_parameter($key, $value)
	{
		switch($key) {
			case 'from':
			case 'to':
				return max(0, (int)$value);
			case 'limit':
				return max(1, min(1000, (int)$value));
			case 'offset':
				return max(0, (int)$value);
			case 'study_keywords':
			case 'variable_keywords':
				$keywords = trim(strip_tags($value));
				$keywords = $this->sanitize_query($keywords);
				if (strlen($keywords) > 500) {
					$keywords = substr($keywords, 0, 500);
				}
				
				$trimmed = trim($keywords);
				if (preg_match('/^".*"$/', $trimmed)) {
					$keywords = preg_replace('/([+\-&|!(){}[\]^~*?:\\/\\\\])/', '\\\\$1', $trimmed);
				} else if (preg_match('/[+\-]/', $trimmed)) {
					$keywords = preg_replace('/([&|!(){}[\]^"~*?:\\/\\\\])/', '\\\\$1', $trimmed);
				} else if (preg_match('/[a-zA-Z_]+:/', $trimmed)) {
					$keywords = preg_replace('/([&|!(){}[\]^"~*?\\/\\\\])/', '\\\\$1', $trimmed);
				} else {
					$keywords = preg_replace('/[\\+\\-&\\|\\(\\)\\{\\}\\[\\]\\^"~\\*\\?\\:\\/\\\\]/', ' ', $keywords);
					$keywords = preg_replace('/\s+/', ' ', $keywords);
					$keywords = trim($keywords);
				}
				
				if (empty($keywords)) {
					return '';
				}
				return $keywords;
			case 'repo':
				return trim(strip_tags($value));
			case 'created':
				return trim(strip_tags($value));
			case 'varcount':
				return in_array($value, array('0', '>0')) ? $value : '';
			case 'countries':
			case 'regions':
			case 'topics':
			case 'collections':
			case 'type':
			case 'dtype':
				if (!is_array($value)) {
					return array();
				}
				return array_filter(array_map(function($item) {
					return is_numeric($item) ? (int)$item : trim(strip_tags($item));
				}, $value));
			case 'sort_by':
				return array_key_exists($value, $this->sort_allowed_fields) ? $value : '';
			case 'sort_order':
				return in_array(strtolower($value), $this->sort_allowed_order) ? strtolower($value) : 'asc';
			case 'debug':
				return (bool)$value;
			default:
				return is_string($value) ? trim(strip_tags($value)) : $value;
		}
	}
	
	private function apply_sorting($query)
	{
		if (empty($this->sort_by)) {
			$sort_by = $this->study_keywords ? 'rank' : 'title';
			$sort_order = $this->study_keywords ? 'desc' : 'asc';
		} else {
			$sort_by = $this->sort_by;
			$sort_order = $this->sort_order;
		}

		$sort_by = array_key_exists($sort_by, $this->sort_allowed_fields) ? $sort_by : 'title';
		$sort_order = in_array(strtolower($sort_order), $this->sort_allowed_order) ? strtolower($sort_order) : 'asc';

		$sort_options = array();
		$sort_options[0] = array(
			'sort_by' => $sort_by, 
			'sort_order' => (strtolower($sort_order) == 'asc') ? $query::SORT_ASC : $query::SORT_DESC
		);

		switch($sort_by) {
			case 'country':
			case 'nation':
				$sort_options[1] = array('sort_by' => 'year', 'sort_order' => $query::SORT_DESC);
				$sort_options[2] = array('sort_by' => 'title', 'sort_order' => $query::SORT_ASC);
				break;
			
			case 'title':
				$sort_options[1] = array('sort_by' => 'year', 'sort_order' => $query::SORT_DESC);
				$sort_options[2] = array('sort_by' => 'country', 'sort_order' => $query::SORT_ASC);
				break;

			case 'year':
				$sort_options[1] = array('sort_by' => 'country', 'sort_order' => $query::SORT_ASC);
				$sort_options[2] = array('sort_by' => 'title', 'sort_order' => $query::SORT_ASC);
				break;

			case 'rank':
			case 'relevance':
				$sort_options[1] = array('sort_by' => 'year', 'sort_order' => $query::SORT_DESC);
				$sort_options[2] = array('sort_by' => 'title', 'sort_order' => $query::SORT_ASC);
				break;
		}

		foreach($sort_options as $sort) {
			$query->addSort($this->sort_allowed_fields[$sort['sort_by']], $sort['sort_order']);
		}
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


	private function map_fields_back_to_old_names($documents)
	{
		if (empty($documents)) {
			return $documents;
		}
		
		$mapped_documents = array();
		
		foreach ($documents as $doc) {
			$mapped_doc = array();
			
			foreach ($doc as $field => $value) {
				if (isset($this->field_mapping_new_to_old[$field])) {
					$mapped_doc[$this->field_mapping_new_to_old[$field]] = $value;
				} else {
					$mapped_doc[$field] = $value;
				}
			}
			
			$mapped_documents[] = $mapped_doc;
		}
		
		return $mapped_documents;
	}

	private function parse_field_specific_query($keywords, $search_type = 'survey')
	{
		$allowed_fields = ($search_type === 'variable') ? 
			$this->allowed_variable_search_fields : 
			$this->allowed_search_fields;
		
		$parsed = array(
			'field_queries' => array(),
			'general_terms' => array(),
			'operators' => array()
		);
		
		$tokens = $this->tokenize_with_quotes($keywords);
		$boolean_operators = array('AND', 'OR', 'NOT');
		
		foreach ($tokens as $index => $token) {
			$token_upper = strtoupper(trim($token));
			
			if (in_array($token_upper, $boolean_operators)) {
				$parsed['operators'][] = array('position' => $index, 'operator' => $token_upper);
			} elseif (preg_match('/^([a-zA-Z_]+):(.+)$/', $token, $matches)) {
				$field_name = strtolower($matches[1]);
				$field_value = trim($matches[2], '"\'');
				
				if (array_key_exists($field_name, $allowed_fields)) {
					$solr_field = $allowed_fields[$field_name];
					$parsed['field_queries'][$solr_field][] = $field_value;
				} else {
					$parsed['general_terms'][] = $token;
				}
			} else {
				$parsed['general_terms'][] = $token;
			}
		}
		
		return $parsed;
	}

	private function tokenize_with_quotes($query)
	{
		$tokens = array();
		$current_token = '';
		$in_quotes = false;
		
		for ($i = 0; $i < strlen($query); $i++) {
			$char = $query[$i];
			
			if ($char === '"' && !$in_quotes) {
				$in_quotes = true;
				$current_token .= $char;
			} elseif ($char === '"' && $in_quotes) {
				$in_quotes = false;
				$current_token .= $char;
			} elseif ($char === ' ' && !$in_quotes) {
				if (trim($current_token) !== '') {
					$tokens[] = trim($current_token);
				}
				$current_token = '';
			} else {
				$current_token .= $char;
			}
		}
		
		if (trim($current_token) !== '') {
			if ($in_quotes) {
				$current_token = substr($current_token, 1);
			}
			$tokens[] = trim($current_token);
		}
		
		return $tokens;
	}

	private function build_field_specific_solr_query($parsed_query, $helper)
	{
		if (!empty($parsed_query['operators'])) {
			return $this->build_field_specific_query_with_operators($parsed_query, $helper);
		}
		
		$query_parts = array();
		
		foreach ($parsed_query['field_queries'] as $solr_field => $values) {
			foreach ($values as $value) {
				$escaped_value = $helper->escapeTerm($value);
				$query_parts[] = $solr_field . ':' . $escaped_value;
			}
		}
		
		if (!empty($parsed_query['general_terms'])) {
			$general_query = implode(' ', $parsed_query['general_terms']);
			$escaped_general = $this->escape_general_keywords($general_query, $helper);
			$query_parts[] = $escaped_general;
		}
		
		return implode(' AND ', $query_parts);
	}
	
	private function build_field_specific_query_with_operators($parsed_query, $helper)
	{
		$tokens = $this->tokenize_with_quotes($parsed_query['original_query'] ?? '');
		$search_type = $parsed_query['search_type'] ?? 'survey';
		$allowed_fields = ($search_type === 'variable') ? 
			$this->allowed_variable_search_fields : 
			$this->allowed_search_fields;
		
		$result_parts = array();
		
		foreach ($tokens as $token) {
			$token_upper = strtoupper(trim($token));
			
			if ($token_upper === 'AND' || $token_upper === 'OR' || $token_upper === 'NOT') {
				$result_parts[] = $token_upper;
			} elseif (preg_match('/^([a-zA-Z_]+):(.+)$/', $token, $matches)) {
				$field_name = strtolower($matches[1]);
				$field_value = trim($matches[2], '"\'');
				
				if (isset($allowed_fields[$field_name])) {
					$solr_field = $allowed_fields[$field_name];
					$escaped_value = $helper->escapeTerm($field_value);
					$result_parts[] = $solr_field . ':' . $escaped_value;
				} else {
					$result_parts[] = $token;
				}
			} else {
				$escaped = $this->escape_general_keywords($token, $helper);
				$result_parts[] = $escaped;
			}
		}
		
		return implode(' ', $result_parts);
	}

	private function escape_keywords($keywords, $helper, $search_type = 'survey')
	{
		if (empty($keywords)) {
			return '';
		}
		
		$trimmed = trim($keywords);
		
		if (preg_match('/[a-zA-Z_]+:/', $trimmed)) {
			$parsed = $this->parse_field_specific_query($trimmed, $search_type);
			$parsed['original_query'] = $trimmed;
			$parsed['search_type'] = $search_type;
			return $this->build_field_specific_solr_query($parsed, $helper);
		}
		
		return $this->escape_general_keywords($keywords, $helper);
	}

	private function escape_general_keywords($keywords, $helper)
	{
		if (empty($keywords)) {
			return '';
		}
		
		$trimmed = trim($keywords);
		
		if (preg_match('/^".*"$/', $trimmed)) {
			$escaped = preg_replace('/([\\+&\\-\\|\\(\\)\\{\\}\\[\\]\\^~\\*\\?\\:\\/\\\\])/', '\\\\$1', $trimmed);
			return $escaped;
		}
		
		if (preg_match('/[+\-]/', $trimmed)) {
			return $this->process_advanced_query($trimmed);
		}
		
		$escaped = preg_replace('/([\\+\\-&\\|\\(\\)\\{\\}\\[\\]\\^"~\\*\\?\\:\\/\\\\])/', '\\\\$1', $keywords);
		return $escaped;
	}

	private function process_advanced_query($query)
	{
		$tokens = $this->tokenize_advanced_query($query);
		$processed_tokens = array();
		
		foreach ($tokens as $token) {
			$processed_tokens[] = $this->process_query_token($token);
		}
		
		return implode(' ', $processed_tokens);
	}

	private function tokenize_advanced_query($query)
	{
		$tokens = array();
		$current_token = '';
		$in_quotes = false;
		
		for ($i = 0; $i < strlen($query); $i++) {
			$char = $query[$i];
			
			if ($char === '"' && !$in_quotes) {
				$in_quotes = true;
				$current_token .= $char;
			} elseif ($char === '"' && $in_quotes) {
				$in_quotes = false;
				$current_token .= $char;
			} elseif ($char === ' ' && !$in_quotes) {
				if (trim($current_token) !== '') {
					$tokens[] = trim($current_token);
				}
				$current_token = '';
			} else {
				$current_token .= $char;
			}
		}
		
		if (trim($current_token) !== '') {
			$tokens[] = trim($current_token);
		}
		
		return $tokens;
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

		$result=array();
        $query = $this->solr_client->createSelect();
        $facetSet = $query->getFacetSet();
        $facetSet->createFacetField('dataset_types')->setField('dataset_type')->getLocalParameters()->addExcludes(['tag_dataset_type']);
		$edismax = $query->getEDisMax();		
		$query->createFilterQuery('published')->setQuery('published:1');
		$helper = $query->getHelper();

		if($dataset_types){
            $query->createFilterQuery('dataset_type')->addTag('tag_dataset_type')->setQuery($dataset_types);
		}

		if($this->study_keywords){
			$escaped_keywords = $this->escape_keywords($this->study_keywords, $helper);
			$query->setQuery($escaped_keywords);
		}

		$this->apply_repository_filter($query, $repository, $helper);
		$this->apply_single_filter_query($query, 'region', $regions);
		$this->apply_single_filter_query($query, 'topics', $topics);
		$this->apply_user_facet_filters($query);
		$this->apply_sorting($query);

		$this->apply_multi_filter_query($query, 'years', $years);
		$this->apply_single_filter_query($query, 'dtype', $dtype);

		$created_range = $this->_build_created_query();
		$this->apply_single_filter_query($query, 'created', $created_range !== false ? $created_range : false);

		$this->apply_single_filter_query($query, 'countries', $countries);
		$this->apply_single_filter_query($query, 'collections', $collections);
		$this->apply_single_filter_query($query, 'varcount', $varcount);
				
        $edismax->setQueryFields($this->solr_options['qf']);
        $edismax->setMinimumMatch($this->solr_options['mm']);

        $query->createFilterQuery('study_search')->setQuery('doctype:1');
        $query->setStart($offset)->setRows($limit);
        $query->setFields(array('survey_uid'));

        if ($this->debug){
            $debug = $query->getDebug();
        }

        $resultset = $this->execute_solr_query($query, null, 'Solr search failed');
        if ($resultset === null) {
            $result['found'] = 0;
            $result['total'] = 0;
            $result['limit'] = $limit;
            $result['offset'] = $offset;
            $result['search_counts_by_type'] = array();
            $result['rows'] = array();
            $result['citations'] = array();
            return $result;
        }
        $facetSet = $resultset->getFacetSet();
        $facet = $facetSet->getFacet('dataset_types');
        $dataset_types_facet_counts=array();
        
        if ($facet) {
            foreach ($facet as $value => $count) {
                $dataset_types_facet_counts[$value]=$count;
            }
        }
        
        if (empty($dataset_types_facet_counts)) {
            $total_count = $this->solr_total_count($doctype=1);
            $dataset_types_facet_counts['survey'] = $total_count;
        }
        
        if (!array_key_exists('survey', $dataset_types_facet_counts)) {
            $dataset_types_facet_counts['survey'] = $this->solr_total_count($doctype=1);
        }
        
        if (empty($dataset_types_facet_counts) || count($dataset_types_facet_counts) < 2) {
            $dataset_types_facet_counts = $this->get_dataset_type_counts_from_solr();
        }
        
        if ($this->debug) {
            log_message('debug', 'Solr facets returned: ' . json_encode($dataset_types_facet_counts));
            log_message('debug', 'Facet object: ' . ($facet ? 'exists' : 'null'));
            if ($facet) {
                log_message('debug', 'Facet count: ' . count($facet));
            }
        }

		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			$debug_result = $resultset->getDebug();
			$result['debug'] = array(
				'request_uri' => $request->getUri(),
				'request_uri_decoded' => urldecode($request->getUri()),
				'solr_debug' => $this->extract_debug_info($debug_result),
				'facets_returned' => $dataset_types_facet_counts,
				'facet_object_exists' => ($facet ? true : false),
				'facet_count' => $facet ? count($facet) : 0
			);
		}
		

		$this->search_found_rows=$resultset->getNumFound();
		$this->total_surveys=$this->solr_total_count($doctype=1);
		$solr_data=$resultset->getData();
		$solr_docs=$solr_data['response']['docs'];
		
		$result['found']=$this->search_found_rows;
		$result['total']=$this->total_surveys;
		$result['limit']=$limit;
		$result['offset']=$offset;
        $result['search_counts_by_type']=$dataset_types_facet_counts;
        
		if ($result['found']>0){
			$ordered_ids = array();
			foreach ($solr_docs as $doc) {
				if (isset($doc['survey_uid'])) {
					$ordered_ids[] = (int)$doc['survey_uid'];
				}
			}
			
			if (count($ordered_ids) > 0) {
				$this->search_result = $this->fetch_survey_rows_from_db($ordered_ids);
				
				$id_list = array_column($this->search_result, "id");
				
				if(count($id_list)>0){
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
			} else {
				$this->search_result = array();
			}
		} else {
			$this->search_result = array();
		}

		$result['rows']=$this->search_result;
		$result['citations']=$this->get_survey_citation();
		return $result;
    }

	function get_var_count_by_surveys($survey_arr,$variable_keywords)
	{
		if(empty($variable_keywords)){
			return array();
		}

		if (!is_array($survey_arr) || empty($survey_arr)) {
			return array();
		}

		if ($this->debug) {
			log_message('debug', 'Variable search - Keywords: ' . $variable_keywords . ', Surveys: ' . implode(',', $survey_arr));
		}

		$query = $this->solr_client->createSelect();
		$helper = $query->getHelper();
		
		$survey_arr = array_filter(array_map('intval', $survey_arr));
		if (empty($survey_arr)) {
			return array();
		}
		
		$escaped_keywords = $this->escape_keywords($variable_keywords, $helper, 'variable');
		if (empty($escaped_keywords)) {
			return array();
		}
		
		$query->setQuery(sprintf('doctype:2 AND var_survey_id:(%s) AND (var_label:(%s) OR var_name:(%s) OR var_question:(%s))', 
			implode(" OR ", $survey_arr),
			$escaped_keywords,
			$escaped_keywords,
			$escaped_keywords
		));
		
		$query->setStart(0)->setRows(100);

		if ($this->debug){
			$debug = $query->getDebug();
		}
	
		$groupComponent = $query->getGrouping();
		$groupComponent->addField('var_survey_id');
		$groupComponent->setLimit(0);
		$groupComponent->setNumberOfGroups(true);

		$resultset = $this->execute_solr_query($query, null, 'Variable search failed');
		if ($resultset === null) {
			return array();
		}
	
		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			$debug_info = array(
				'request_uri' => $request->getUri(),
				'request_uri_decoded' => urldecode($request->getUri()),
				'variable_keywords' => $variable_keywords,
				'survey_count' => count($survey_arr)
			);
			log_message('debug', 'Variable search query: ' . $request->getUri());
		}

		$groups = $resultset->getGrouping();
		$output=array();

		foreach ($groups as $groupKey => $fieldGroup)
		{
			foreach ($fieldGroup as $valueGroup)
			{
				$output[(int)$valueGroup->getValue()]=(int)$valueGroup->getNumFound();
			}
		}

		if ($this->debug) {
			log_message('debug', 'Variable search results: ' . json_encode($output));
			$output['_debug'] = $debug_info;
		}
		


		return $output;
	}

	function get_survey_citation()
	{
		if (!is_array($this->search_result)) {
			return array();
		} else if (count($this->search_result) == 0) {
			return array();
		}

		$survey_id_list = array();
		foreach($this->search_result as $row) {
			if (isset($row['id'])) {
				$survey_id_list[] = $row['id'];
			}
		}

		$survey_id_list = array_filter(array_map('intval', $survey_id_list));
		
		if (empty($survey_id_list)) {
			return array();
		}

		$this->ci->db->select('sid,count(sid) as total');
		$this->ci->db->where_in('sid', $survey_id_list);
		$this->ci->db->group_by('sid');
		$query = $this->ci->db->get('survey_citations');

		if ($query) {
			$citation_rows = $query->result_array();

			$result = array();

			foreach($citation_rows as $row) {
				$result[$row['sid']] = $row['total'];
			}
			return $result;
		}

		return array();
	}

	function solr_total_count($doctype=1)
	{
		$repository=!empty($this->repo) ? (string)$this->repo : false;
		$query = $this->solr_client->createSelect();
		$query->setQuery('doctype:'.$doctype);

		if ($repository) {
			$helper = $query->getHelper();
			$this->apply_repository_filter($query, $repository, $helper);
		}

		if ($doctype == 1) {
			$query->createFilterQuery('published')->setQuery('published:1');
		}
		
		$query->setStart(0)->setRows(0);
		
		$resultset = $this->execute_solr_query($query, null, 'Failed to get Solr total count');
		if ($resultset === null) {
			return 0;
		}
		return $resultset->getNumFound();
	}


	function _build_study_query()
	{
		if (!$this->study_keywords){
			return false;
		}

		return array('title'=>$this->study_keywords);
	}


	function _build_topics_query()
	{
		return $this->build_numeric_field_query('topics_id', $this->topics);
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
		return $this->build_numeric_field_query('regions', $this->regions);
	}

	function _build_countries_query()
	{
		$countries = $this->countries;

		if (!is_array($countries)) {
			return false;
		}

		if (isset($countries[0]) && !is_numeric($countries[0])) {
			$countries = $this->get_country_id_by_name($countries);
		}

		return $this->build_numeric_field_query('countries', $countries);
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
		$types=(array)$this->type;

		if (!is_array($types)){
			return FALSE;
		}
		
		$types_list=array();
				
		foreach($types  as $type){
			if(!empty($type)){
				$type_trimmed = trim($type);
				if (!empty($type_trimmed)) {
					$types_list[] = $type_trimmed;
				}
			}
        }

		if (empty($types_list)){
			return FALSE;
		}

		$types_str = implode(' OR ', $types_list);

		if ($types_str!=''){
            return sprintf('dataset_type:(%s)', $types_str);
		}
		
		return FALSE;
    }
    


	function _build_sid_query()
	{
		if (empty($this->sid)) {
			return FALSE;
		}
		
		$sid=explode(",",$this->sid);

		$sid_list=array();
		foreach($sid as $item)
		{
			$item_trimmed = trim($item);
			if (is_numeric($item_trimmed) && $item_trimmed > 0)
			{
				$sid_list[] = (int)$item_trimmed;
			}
		}

		if (count($sid_list) > 0)
		{
			return sprintf('survey_uid:(%s)', implode(" OR ", $sid_list));
		}

		return FALSE;
	}


	


	function _build_collections_query()
	{
		$params=$this->collections;

		if (!is_array($params) || empty($params))
		{
			return FALSE;
		}

		$param_list=array();
		$query = $this->solr_client->createSelect();
		$helper = $query->getHelper();

		foreach($params  as $param){
			$trimmed = trim($param);
			if (!empty($trimmed)){
				$param_list[] = $helper->escapeTerm($trimmed);
			}
		}

		if (count($param_list) > 0){
			$params_str = implode(' OR ', $param_list);
			return sprintf(' repositories:(%s)', $params_str);
		}
		
		return FALSE;
	}

	function get_variable_search_field($is_fulltext=TRUE)
	{
		$index=array();
		$variable_fields=$this->variable_fields();

		if( in_array('var_name',$variable_fields) )
		{
			$index[]='var_name';
		}
		if( in_array('var_label',$variable_fields) )
		{
			$index[]='var_label';
		}
		if( in_array('var_question',$variable_fields) )
		{
			$index[]='var_question';
		}

		if (count($index)==0)
		{
			$index[]='var_name,var_label,var_question';
		}

		if ($is_fulltext==TRUE)
		{
			return implode(',',$index);
		}
		else
		{
			return 'concat(' . implode(",' ',",$index) .')';
		}
	}

	function variable_fields()
	{
		$vf=$this->variable_fields;

		if (!is_array($vf))
		{
			return array('var_label,var_question');
		}

		$tmp=NULL;
		foreach($vf as $field)
		{
			if (in_array($field,$this->variable_allowed_fields))
			{
				$tmp[]=$field;
			}
		}

		if ($tmp==NULL)
		{
			return array('var_label');
		}
		else
		{
			return $tmp;
		}
	}

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
				'fid',
				'var_label',
				'var_name',
				'var_question',
				'var_survey_id',
				'title',
				'nation',
				'idno',
				'year_start',
				'year_end',
				'dataset_type',
				'repositories'
			));
		
		$edismax = $query->getEDisMax();
		$helper = $query->getHelper();

		$query->createFilterQuery('doctype_vsearch')->setQuery('doctype:2');
		
		$this->apply_single_filter_query($query, 'countries', $countries);
		$this->apply_multi_filter_query($query, 'years', $years);
		$this->apply_single_filter_query($query, 'dtype', $dtype);
		$this->apply_repository_filter($query, $repository, $helper);
		$this->apply_single_filter_query($query, 'collections', $collections);
		$this->apply_single_filter_query($query, 'dataset_type', $dataset_types);
		$this->apply_user_facet_filters($query);
		
		$edismax->setQueryFields($this->solr_variable_options['qf']);
        $edismax->setMinimumMatch($this->solr_variable_options['mm']);
		
		if($this->study_keywords){
			$escaped_keywords = $this->escape_keywords($this->study_keywords, $helper, 'variable');
			$query->setQuery($escaped_keywords);
		}

		$query->setStart($offset)->setRows($limit);

		$resultset = $this->execute_solr_query($query, null, 'Solr variable search failed');
		if ($resultset === null) {
			$tmp['total'] = 0;
			$tmp['found'] = 0;
			$tmp['limit'] = $limit;
			$tmp['offset'] = $offset;
			$tmp['rows'] = array();
			if ($this->debug) {
				$tmp['debug'] = array('error' => 'Query execution failed');
			}
			return $tmp;
		}
		$found_rows = $resultset->getNumFound();
		$this->search_result = $resultset->getData();

		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			$debug_info = array(
				'request_uri' => $request->getUri(),
				'request_uri_decoded' => urldecode($request->getUri()),
				'study_keywords' => $this->study_keywords,
				'escaped_keywords' => isset($escaped_keywords) ? $escaped_keywords : null
			);
			$tmp['debug'] = $debug_info;
		}
		
		$this->search_result['response']['docs'] = $this->map_fields_back_to_old_names($this->search_result['response']['docs']);

		$tmp['total']=$this->solr_total_count($doctype=2);
		$tmp['found']=$found_rows;
		$tmp['limit']=$limit;
		$tmp['offset']=$offset;
		$tmp['rows']=$this->search_result['response']['docs'];
		
		if($this->debug){
			$tmp['debug'] = $debug_info;
		}
		
		return $tmp;		
	}

	function v_quick_search($surveyid=NULL,$limit=50,$offset=0)
	{
		$query = $this->solr_client->createSelect();

		$query->setFields(array(
				'vid',
				'var_label',
				'var_name',
				'var_survey_id',
				'fid',
				'title',
				'nation',
				'year_start',
				'year_end',
				'idno'
			));

		$surveyid = (int)$surveyid;
		if ($surveyid <= 0) {
			return array();
		}
		
		$edismax = $query->getEDisMax();
		$edismax->setQueryFields($this->solr_variable_options['qf']);
		$edismax->setMinimumMatch($this->solr_variable_options['mm']);
		
		$helper = $query->getHelper();
		
		if ($this->variable_keywords) {
			$escaped_keywords = $this->escape_keywords($this->variable_keywords, $helper, 'variable');
			$query->setQuery($escaped_keywords);
		}
		
		$query->createFilterQuery('doctype')->setQuery('doctype:2');
		$query->createFilterQuery('survey')->setQuery('var_survey_id:' . $surveyid);
		
		$query->setStart($offset)->setRows($limit);

		if($this->debug){
			$request = $this->solr_client->createRequest($query);
			$debug_info = array(
				'request_uri' => $request->getUri(),
				'request_uri_decoded' => urldecode($request->getUri()),
				'variable_keywords' => $this->variable_keywords,
				'escaped_keywords' => isset($escaped_keywords) ? $escaped_keywords : null,
				'survey_id' => $surveyid
			);
		}

		$helper = $query->getHelper();
		$resultset = $this->execute_solr_query($query, null, 'Solr variable quick search failed');
		if ($resultset === null) {
			$this->search_found_rows = 0;
			$this->search_result = array('response' => array('docs' => array()));
			$mapped_docs = array();
			if ($this->debug) {
				$mapped_docs['_debug'] = array('error' => 'Query execution failed');
			}
			return $mapped_docs;
		}
		$this->search_found_rows = $resultset->getNumFound();
		$this->search_result = $resultset->getData();
		
		$mapped_docs = $this->map_fields_back_to_old_names($this->search_result['response']['docs']);
		
		if($this->debug){
			$mapped_docs['_debug'] = $debug_info;
		}
		
		return $mapped_docs;
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
		$varcount=$this->varcount;

		if ($varcount=='>0'){
			return sprintf('varcount:[%s TO %s]',1, '*');
		}
		else if ($varcount=='0'){
			return sprintf('varcount:%s',0);
		}

		return FALSE;
	}

	protected function get_dataset_type_counts_from_solr()
	{
		$query = $this->solr_client->createSelect();
		$query->setQuery('doctype:1 AND published:1');
		$query->setRows(0);
		
		$facetSet = $query->getFacetSet();
		$facetSet->createFacetField('dataset_types')->setField('dataset_type');
		
		$resultset = $this->execute_solr_query($query, null, 'Failed to get dataset type counts from Solr');
		if ($resultset === null) {
			return array('survey' => $this->solr_total_count($doctype=1));
		}
		
		$facet = $resultset->getFacetSet()->getFacet('dataset_types');
		
		$counts = array();
		if ($facet) {
			foreach ($facet as $value => $count) {
				$counts[$value] = $count;
			}
		}
		
		if (empty($counts)) {
			$counts['survey'] = $this->solr_total_count($doctype=1);
		}
		
		return $counts;
	}

	
    private function fetch_survey_rows_from_db($survey_ids)
	{
		if (empty($survey_ids) || !is_array($survey_ids)) {
			return array();
		}
		
		$valid_ids = array();
		foreach ($survey_ids as $id) {
			$id_int = (int)$id;
			if ($id_int > 0) {
				$valid_ids[] = $id_int;
			}
		}
		
		if (empty($valid_ids)) {
			return array();
		}
		
		$study_fields = 'surveys.id as id, surveys.type, surveys.idno as idno, surveys.doi, surveys.title, surveys.subtitle, nation, authoring_entity';
		$study_fields .= ', forms.model as form_model, data_class_id, surveys.year_start, surveys.year_end, surveys.thumbnail';
		$study_fields .= ', surveys.repositoryid as repositoryid, link_da, repositories.title as repo_title, surveys.created, surveys.changed, surveys.total_views, surveys.total_downloads, varcount';
		
		$this->ci->db->select($study_fields, FALSE);
		$this->ci->db->from('surveys');
		$this->ci->db->join('forms', 'surveys.formid=forms.formid', 'left');
		$this->ci->db->join('repositories', 'surveys.repositoryid=repositories.repositoryid', 'left');
		$this->ci->db->where_in('surveys.id', $valid_ids);
		$this->ci->db->where('surveys.published', 1);
		
		$db_results = $this->ci->db->get()->result_array();
		
		if (empty($db_results)) {
			return array();
		}
		
		$lookup = array();
		foreach ($db_results as $row) {
			$lookup[$row['id']] = $row;
		}
		
		$ordered_results = array();
		foreach ($valid_ids as $id) {
			if (isset($lookup[$id])) {
				$ordered_results[] = $lookup[$id];
			}
		}
		
		return $ordered_results;
	}

	private function sanitize_query($query)
	{
		if (empty($query)) {
			return '';
		}
		
		$query = preg_replace('/[\x{200B}\x{00AD}\p{C}]+/u', '', $query);
		$query = preg_replace('/\s+/u', ' ', $query);
		return trim($query);
	}

	private function apply_user_facet_filters($query)
	{
		foreach ($this->user_facets as $fc) {
			if (array_key_exists($fc['name'], $this->params)) {
				$filter_ = $this->_build_facet_query('fq_' . $fc['name'], $this->params[$fc['name']]);
				if ($filter_) {
					$query->createFilterQuery('fq_' . $fc['name'])->setQuery($filter_);
				}
			}
		}
	}

	private function apply_repository_filter($query, $repository, $helper)
	{
		if ($repository) {
			$query->createFilterQuery('repo')->setQuery('repositories:' . $helper->escapeTerm($repository));
		}
	}

	private function apply_single_filter_query($query, $filter_name, $filter_value)
	{
		if ($filter_value) {
			$query->createFilterQuery($filter_name)->setQuery($filter_value);
		}
	}

	private function apply_multi_filter_query($query, $filter_prefix, $filter_values)
	{
		if ($filter_values && is_array($filter_values)) {
			foreach ($filter_values as $key => $value) {
				$query->createFilterQuery($filter_prefix . $key)->setQuery($value);
			}
		}
	}

	private function execute_solr_query($query, $default_return, $error_message)
	{
		try {
			$resultset = $this->solr_client->select($query);
			
			$solr_qtime = $resultset->getQueryTime();
			
			if ($solr_qtime > 5000) {
				$this->log_slow_query($query, $solr_qtime, $resultset, $error_message);
			}
			
			return $resultset;
		} catch (Exception $e) {
			log_message('error', $error_message . ': ' . $e->getMessage());
			if ($this->debug) {
				throw new Exception($error_message . ': ' . $e->getMessage());
			}
			return $default_return;
		}
	}

	private function log_slow_query($query, $solr_qtime, $resultset, $error_message)
	{
		if (!isset($this->ci->db_logger)) {
			$this->ci->load->library('db_logger');
		}
		
		$query_string = $query->getQuery() ?: '*:*';
		$num_found = $resultset ? $resultset->getNumFound() : 0;
		
		$filters = array();
		try {
			$filter_queries = $query->getFilterQueries();
			foreach ($filter_queries as $filter) {
				$filters[] = $filter->getQuery();
			}
		} catch (Exception $e) {
			$filters = array('unable to extract filters');
		}
		
		$query_type = 'unknown';
		foreach ($filters as $filter) {
			if (strpos($filter, 'doctype:1') !== false) {
				$query_type = 'survey_search';
				break;
			} elseif (strpos($filter, 'doctype:2') !== false) {
				$query_type = 'variable_search';
				break;
			}
		}
		
		$filters_str = !empty($filters) ? implode('; ', $filters) : 'none';
		$query_str = substr($query_string, 0, 80);
		$filters_str = substr($filters_str, 0, 80);
		
		$message = sprintf(
			'QTime: %dms, Results: %d, Type: %s, Query: %s, Filters: %s',
			$solr_qtime,
			$num_found,
			$query_type,
			$query_str,
			$filters_str
		);
		
		$this->ci->db_logger->write_log(
			'solr-slow-query',
			$message,
			$query_type,
			0
		);
	}

	private function build_numeric_field_query($field_name, $values, $prefix = '')
	{
		if (!is_array($values) || empty($values)) {
			return false;
		}

		$clean_list = array();
		foreach ($values as $value) {
			if (is_numeric($value)) {
				$clean_list[] = (int)$value;
			}
		}

		if (count($clean_list) > 0) {
			$field = $prefix ? $prefix . $field_name : $field_name;
			$values_str = implode(' OR ', $clean_list);
			return sprintf('%s:(%s)', $field, $values_str);
		}

		return false;
	}

	private function extract_debug_info($debug_result)
	{
		if (!$debug_result) {
			return array();
		}
		
		$debug_info = array(
			'query_string' => $debug_result->getQueryString(),
			'parsed_query' => $debug_result->getParsedQuery(),
			'query_parser' => $debug_result->getQueryParser(),
			'other_query' => $debug_result->getOtherQuery()
		);
		
		$explain = $debug_result->getExplain();
		if ($explain) {
			$explain_docs = array();
			foreach ($explain->getDocuments() as $key => $doc) {
				$doc_info = array(
					'key' => $doc->getKey(),
					'match' => $doc->getMatch(),
					'value' => $doc->getValue(),
					'description' => $doc->getDescription()
				);
				
				$details = array();
				foreach ($doc->getDetails() as $detail) {
					$details[] = array(
						'match' => $detail->getMatch(),
						'value' => $detail->getValue(),
						'description' => $detail->getDescription()
					);
				}
				$doc_info['details'] = $details;
				$explain_docs[$key] = $doc_info;
			}
			$debug_info['explain'] = $explain_docs;
		}
		
		$explain_other = $debug_result->getExplainOther();
		if ($explain_other) {
			$explain_other_docs = array();
			foreach ($explain_other->getDocuments() as $key => $doc) {
				$doc_info = array(
					'key' => $doc->getKey(),
					'match' => $doc->getMatch(),
					'value' => $doc->getValue(),
					'description' => $doc->getDescription()
				);
				
				$details = array();
				foreach ($doc->getDetails() as $detail) {
					$details[] = array(
						'match' => $detail->getMatch(),
						'value' => $detail->getValue(),
						'description' => $detail->getDescription()
					);
				}
				$doc_info['details'] = $details;
				$explain_other_docs[$key] = $doc_info;
			}
			$debug_info['explain_other'] = $explain_other_docs;
		}
		
		$timing = $debug_result->getTiming();
		if ($timing) {
			$timing_phases = array();
			foreach ($timing->getPhases() as $phase_key => $phase) {
				$timing_phases[$phase_key] = array(
					'name' => $phase_key,
					'time' => $phase->getTime(),
					'timings' => $phase->getTimings()
				);
			}
			$debug_info['timing'] = array(
				'time' => $timing->getTime(),
				'phases' => $timing_phases
			);
		}
		
		return $debug_info;
	}

}