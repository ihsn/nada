<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Catalog extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Catalog_model');
		$this->load->model('Search_helper_model');
		$this->load->model('Dataset_model');
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');
		$this->load->model("Form_model");
		$this->load->library('Dataset_manager');
		$this->load->model('Facet_model');
		$this->load->config("facets");
	}

	/**
	 * 
	 * Get page size
	 * 
	 */
	private function get_page_size()
	{
		$page_size_min=1;
		$page_size_max=300;

		$page_size=(int)$this->input->get('ps');

		if($page_size>=$page_size_min && $page_size<=$page_size_max){
			return $page_size;
		}

		return 15;//default page size
	}

	/**
	 * Same rules as web Catalog: keyword listing → relevance desc; browse → site default / title.
	 *
	 * @return array{0:string,1:string}
	 */
	private function _normalize_catalog_sort_for_listing($fulltext_keywords, $sort_by, $sort_order)
	{
		$allowed_fields = array('year','title','nation','country','popularity','rank','relevance');
		$allowed_order  = array('asc','desc');
		$sb = trim((string) $sort_by);
		$so = strtolower(trim((string) $sort_order));
		if (! in_array($sb, $allowed_fields, true)) {
			$sb = '';
		}
		if (! in_array($so, $allowed_order, true)) {
			$so = '';
		}
		if (! class_exists('Catalog_study_sort', false)) {
			require_once APPPATH . 'libraries/Catalog_study_sort.php';
		}
		return Catalog_study_sort::resolve(
			trim((string) $fulltext_keywords),
			$sb,
			$so,
			$this->config->item('catalog_default_sort_by'),
			$this->config->item('catalog_default_sort_order')
		);
	}
	
	/**
	 * 
	 * Get a single dataset
	 * @copy of datasets/single_get
	 * 
	 */
	function index_get($idno=null)
	{	
		try{

			if(empty($idno)){
				return $this->search_get();
			}

			$sid=$this->get_sid_from_idno($idno);

			$result=$this->Dataset_model->get_row($sid);
			array_walk($result, 'unix_date_to_gmt_row',array('created','changed'));
				
			if(!$result){
				throw new Exception("DATASET_NOT_FOUND");
			}

			$result['metadata']=$this->Dataset_model->get_metadata($sid);
			
			$response=array(
				'status'=>'success',
				'dataset'=>$result
			);			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Error $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * DDI documentation PDF on-disk status (same rules as Catalog_admin::get_study_pdf).
	 *
	 * GET /api/catalog/pdf_documentation/{idno}
	 * GET /api/catalog/pdf_documentation?idno=...
	 *
	 * Query id_format=id when using numeric surveys.id. Response pdf_documentation.status: uptodate | outdated | na.
	 */
	function pdf_documentation_get($idno = null)
	{
		try {
			if ($idno === null || $idno === '') {
				$q = $this->input->get('idno');
				$idno = ($q !== null && trim((string) $q) !== '') ? trim((string) $q) : null;
			}

			if (! $idno) {
				throw new Exception('IDNO-NOT-PROVIDED');
			}

			$sid = $this->get_sid_from_idno($idno);

			$this->load->library('catalog_admin');
			$info = $this->catalog_admin->get_study_pdf($sid);

			if (isset($info['path'])) {
				$info['filename'] = basename($info['path']);
				unset($info['path']);
			}

			$this->set_response(
				array(
					'status'             => 'success',
					'pdf_documentation'  => $info,
				),
				REST_Controller::HTTP_OK
			);
		}
		catch (Exception $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
		catch (Error $e) {
			$this->set_response(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 * 
	 * Return a list of all study IDNOs in the catalog
	 * 
	 * 
	 */
	function list_idno_get($type=null)
	{	
		try{

			$result=$this->Dataset_model->get_list_all($type,$published=1);

			$response=array(
				'status'=>'success',
				'total'=>count($result),
				'records'=>$result				
			);			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Error $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
	
	
	/**
	 * 
	 * Search catalog
	 * 
	 */
	function search_get()
	{
		$include_facets = $this->input->get('include_facets');
		$catalog_browse = $this->input->get('catalog_browse');
		if ($include_facets === '1' || $include_facets === 1 || $catalog_browse === '1' || $catalog_browse === 1) {
			return $this->_browse_search_get($include_facets === '1' || $include_facets === 1);
		}

		$search_options=new StdClass;
		$limit=$this->get_page_size();

		//page parameters
		$search_options->collection		=xss_clean($this->input->get("collection"));
		$search_options->sk				=trim(xss_clean($this->input->get("sk")));
		$search_options->country		=xss_clean($this->input->get("country"));
		$search_options->view			=xss_clean($this->input->get("view"));
		$search_options->topic			=xss_clean($this->input->get("topic"));
		$search_options->from			=xss_clean($this->input->get("from"));
		$search_options->to				=xss_clean($this->input->get("to"));
		$search_options->sort_by		=xss_clean($this->input->get("sort_by"));
		$search_options->sort_order		=xss_clean($this->input->get("sort_order"));
		$search_options->page			=(int)xss_clean($this->input->get("page"));
		$search_options->page			=($search_options->page >0) ? $search_options->page : 1;		
		$search_options->dtype			=xss_clean($this->input->get("dtype"));
		$search_options->tag			=xss_clean($this->input->get("tag"));
		$search_options->sid			=xss_clean($this->input->get("sid"));
		$search_options->type			=xss_clean($this->input->get("type"));
		$search_options->country_iso3	=xss_clean($this->input->get("country_iso3"));
		$search_options->tab_type		=xss_clean($this->input->get("tab_type"));
		$search_options->ps				=xss_clean($this->input->get("ps"));
		$offset=						($search_options->page-1)*$limit;

		$repo_for_tab = xss_clean($this->input->get("repo"));
		$search_options->tab_type = $this->Search_helper_model->validate_catalog_tab_type(
			$search_options->tab_type,
			($repo_for_tab !== false && $repo_for_tab !== null && trim((string) $repo_for_tab) !== '') ? $repo_for_tab : null
		);

		if($search_options->tab_type!=''){
			$search_options->type=$search_options->tab_type;
		} else {
			$search_options->type = $this->Search_helper_model->filter_catalog_type_param($search_options->type);
		}

		list($sort_by_resolved, $sort_order_resolved) = $this->_normalize_catalog_sort_for_listing(
			$search_options->sk,
			$search_options->sort_by,
			$search_options->sort_order
		);

		$params=array(
			'collections'		=> $search_options->collection,
			'study_keywords'	=> $search_options->sk,
			'countries'			=> $search_options->country,
			'topics'			=> $search_options->topic,
			'regions'			=> $this->_parse_search_ids_param($this->input->get('region')),
			'data_class'		=> $this->_parse_search_ids_param($this->input->get('data_class')),
			'from'				=> $search_options->from,
			'to'				=> $search_options->to,
			'tags'				=> $search_options->tag,
			'sort_by'			=> $sort_by_resolved,
			'sort_order'		=> $sort_order_resolved,
			//'repo'=>$search_options->filter->repo,
			'repo'				=> $this->security->xss_clean($this->input->get("repo")),
			'dtype'				=> $this->Form_model->map_name_to_id($search_options->dtype),
			'sid'				=> $search_options->sid,
			'type'				=> $search_options->type,
			'country_iso3'		=> $search_options->country_iso3,
			'ps'				=> $this->security->xss_clean($this->input->get("ps")),
			'created'			=> $this->security->xss_clean($this->input->get("created")),
		);

		$varcount_param=xss_clean($this->input->get('varcount'));
		if ($varcount_param!=='' && $varcount_param!==false){
			$params['varcount']=$varcount_param;
		}

		$this->db_logger->write_log($log_type='api-search',$log_message=http_build_query($params),$log_section='api-search-v1',$log_survey=0);		

		//convert country names or iso codes into country IDs
		$params['countries']=$this->get_countries_id($params['countries']);

		//collections to array — support both ?collection=a,b and ?collection[]=a&collection[]=b
		$col = $this->input->get('collection');
		if (is_array($col)) {
			$params['collections'] = array_values(array_filter(array_map('xss_clean', $col)));
		} elseif ($col === false || $col === null || $col === '') {
			$params['collections'] = array();
		} else {
			$params['collections'] = explode(',', xss_clean($col));
		}		

		//custom facet filters by data type	
		$custom_filters_by_data_type=(array)json_decode($this->config->item('facets_'.'all'),true);

		//list of user defined enabled filters
		$custom_filters_list_active=array_keys($this->Facet_model->select_all($facet_type='user', $is_enabled=1));

		foreach($custom_filters_by_data_type as $custom_filter){			
			if ($this->input->get($custom_filter)){
				$params[$custom_filter]=xss_clean($this->input->get($custom_filter));
			}
		}
		
		//default page size
		$limit=15;

		if (is_numeric($params['ps']) && $params['ps']>0){
			$limit=$params['ps'];
		}

		$page=(int)$this->input->get('page');		
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);

		try{

			$result=$this->catalog_search->search($limit,$offset);			

			if(isset($result['rows'])){
				
				$result['page']=$page;

				$iso3_codes=array();

				if ($this->input->get("inc_iso")){
					//iso3 codes
					$iso3_codes=$this->Dataset_model->get_dataset_country_codes(array_column($result['rows'],'id') );	
				}

				//include countries list
				if ($this->input->get("inc_countries")){					
					$country_names=$this->Survey_country_model->get_survey_country_names(array_column($result['rows'],'id'));
				}

				//include external resources
				$include_resources=$this->input->get("include_resources");
				$resources=array();
				if ($include_resources=='true'){
					if (isset($result['rows'][0]['idno'])){
						$resources_iterator=$this->Survey_resource_model->get_resources_by_studies(array_column($result['rows'],'idno'),array("resources.title","resources.dcformat"));
						foreach($resources_iterator as $resource){
							$resources[$resource['idno']][]=array(
								'resource_id'=>$resource['resource_id'],
								'link'=>$resource['link'],
								'ext'=>$resource['ext'],
								'title'=>$resource['title']
							);
						}						
					}
				}
				
				//convert date format
				array_walk($result['rows'], 'unix_date_to_gmt',array('created','changed'));
				
				foreach($result['rows'] as $idx=>$row)
				{
					//add study link
					$result['rows'][$idx]['url'] = site_url('catalog/'.$row['id']);
					
					//attach iso3 codes to study
					if (isset($iso3_codes[$row['id']])){
						$result['rows'][$idx]['iso3'] = implode(",",$iso3_codes[$row['id']]);
					}

					//attach country names
					if (isset($country_names[$row['id']])){
						$result['rows'][$idx]['countries'] = $country_names[$row['id']];
					}					

					//attach external resources
					if ($include_resources=='true'){
						if (isset($resources[$row['idno']])){
							$result['rows'][$idx]['resources']=$resources[$row['idno']];
						}	
					}
				}

				//unset
				if(isset($result['citations'])){
					unset($result['citations']);
				}

				$response=array(
					'status' => 'success',
					'result'=>$result,
					'params'=>$params
				);

				if (!empty($result['semantic_note'])) {
					$response['semantic_note'] = $result['semantic_note'];
				}
				if (!empty($result['semantic_fallback'])) {
					$response['semantic_fallback'] = $result['semantic_fallback'];
				}
			}
			else{
				$response=array(
					'status' => 'success',
					'found'=>0,
					'rows'=>array()
				);
			}

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$error_output = array_merge($error_output, $this->semantic_search_debug_for_exception($e));
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	/**
	 * Interactive catalog browse/search (Vue UI): same behavior as legacy HTML catalog.
	 * GET /api/catalog/search?include_facets=1  — results + sidebar facets
	 * GET /api/catalog/search?catalog_browse=1 — results only (facets cached client-side)
	 */
	private function _browse_search_get($load_facets = true)
	{
		$this->load->library('catalog_browse_service');

		$repo = $this->input->get('repo');
		$this->catalog_browse_service->set_active_repo($repo ? $repo : 'central');
		$this->catalog_browse_service->active_tab = $this->catalog_browse_service->validate_tab_type(
			(string) $this->input->get('tab_type')
		);

		try {
			$data = $this->catalog_browse_service->run_search($load_facets);

			if ($data['search_type'] === 'variable') {
				$result = isset($data['variables']) ? $data['variables'] : array('found' => 0, 'rows' => array());
			} else {
				$result = isset($data['surveys']) ? $data['surveys'] : array('found' => 0, 'rows' => array());
			}

			if (isset($result['rows'])) {
				$result['page'] = $data['current_page'];
				array_walk($result['rows'], 'unix_date_to_gmt', array('created', 'changed'));
				foreach ($result['rows'] as $idx => $row) {
					$result['rows'][$idx]['url'] = site_url('catalog/' . $row['id']);
				}
			}

			if (isset($result['semantic_facets'])) {
				unset($result['semantic_facets']);
			}
			if (isset($result['facet_mode'])) {
				unset($result['facet_mode']);
			}

			$this->load->helper('catalog');
			$result = catalog_browse_sanitize_search_result($result);

			$response = array(
				'status' => 'success',
				'result' => $result,
				'search_type' => $data['search_type'],
				'tab_type' => $this->catalog_browse_service->active_tab,
				'tabs' => $this->catalog_browse_service->build_tabs($data),
				'site' => $this->catalog_browse_service->site_config_for_client(),
				'enabled_filters' => $this->catalog_browse_service->enabled_filters,
			);

			if ($load_facets) {
				$response['facets'] = $this->catalog_browse_service->facets;
			}

			if (isset($data['featured_studies'])) {
				$featured = $data['featured_studies'];
				if (is_array($featured)) {
					foreach ($featured as $idx => $study) {
						$featured[$idx]['url'] = site_url('catalog/' . $study['id']);
						if (!isset($featured[$idx]['form_model']) && isset($study['model'])) {
							$featured[$idx]['form_model'] = $study['model'];
						}
						array_walk($featured[$idx], 'unix_date_to_gmt_row', array('created', 'changed'));
					}
				}
				$response['featured_studies'] = $featured;
			}
			if (isset($data['related_collections'])) {
				$response['related_collections'] = $data['related_collections'];
			}
			if (!empty($result['semantic_note'])) {
				$response['semantic_note'] = $result['semantic_note'];
			}
			if (!empty($result['semantic_fallback'])) {
				$response['semantic_fallback'] = $result['semantic_fallback'];
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		} catch (RuntimeException $e) {
			$response = array('status' => 'failed', 'message' => $e->getMessage());
			$response = array_merge($response, $this->semantic_search_debug_for_exception($e));
			$this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
		} catch (Exception $e) {
			$response = array('status' => 'failed', 'message' => $e->getMessage());
			$response = array_merge($response, $this->semantic_search_debug_for_exception($e));
			$this->set_response($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * When semantic_search_debug is enabled, attach API request/response to error payloads.
	 *
	 * @param Exception $e
	 * @return array<string, mixed>
	 */
	private function semantic_search_debug_for_exception($e)
	{
		$this->config->load('semantic_search');
		if (!$this->config->item('semantic_search_debug')) {
			return array();
		}

		require_once APPPATH . 'libraries/Semantic_search_api_exception.php';
		if (!($e instanceof Semantic_search_api_exception)) {
			return array();
		}

		return array('semantic_debug' => $e->debug_payload());
	}

	


	/**
	 * 
	 * Get catalog entries by collection
	 * 
	 */
	function datasets_get($repo_id=null)
	{
		try{
			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$datasets=$this->Repository_model->get_all_repo_studies($repo_id);

			$response=array(
				'status'=>'success',
				'total'=>count($datasets),
				'datasets'=>$datasets
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	
	
	/**
	*
	* Returns all country names from db
	*
	**/
	function country_codes_get()
	{
		try{
			$this->db->select("countries.countryid,name,iso");
			$query=$this->db->get("countries");
			$content=NULL;
			
			if ($query){
				$content=$query->result_array();
			}
					
			if (!$content){
				$content=array('error'=>'NO_RECORDS_FOUND');    	
			}

			$response=array(
					'status'=>'success',					
					'country_codes'=>$content
				);
				
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	*
	* Returns data access codes
	*
	**/
	function data_access_codes_get()
	{
		try{			
			$content=$this->Form_model->get_all();

			$output=array();
			foreach($content as $row){
				$output[]=array(
					'id'=>$row['formid'],
					'type'=>$row['model'],
					'title'=>$row['fname']
				);
			}
					
			if (!$output){
				$output=array('error'=>'NO_RECORDS_FOUND');    	
			}

			$response=array(
					'codes'=>$output
				);
				
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	*
	* Returns the most recent studies
	*
	* @country	string	filter by single country name
	* @order	bit		order by date created 0=desc;1=asc
	*
	*
	**/
	function latest_get()
	{
		$country=$this->get("country");
		$limit=(int)$this->get("limit");
		
		if ($limit<1 ){
			$limit=15;
		}
		
		try{
			if ($country){
				$this->db->where("nation",$country);
			}
			
			$this->db->select("id,idno,title,nation,created,changed");
			$this->db->where("published",1);
			$this->db->limit($limit);
			$this->db->order_by("created","desc");
			
			$query=$this->db->get("surveys");
			$content=NULL;
			
			if ($query){
				$content=$query->result_array();
			}
					
			if (!$content){
				$content=array('error'=>'NO_RECORDS_FOUND');    	
			}
			else{
				foreach($content as $key=>$value){
					$content[$key]['url']=site_url().'/catalog/'.$value['id'];
					$content[$key]['created']=date("M-d-Y",$value["created"]);
					$content[$key]['changed']=date("M-d-Y",$value["changed"]);
				}		
			}
			
			$response=array(
				'limit'=>$limit,
				'found'=>count($content),
				'result'=>$content
			);
		
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}



	/**
	 * Parse comma- or pipe-separated positive integers for search filters
	 * (e.g. region IDs, data_class_id values)
	 *
	 * @param mixed $raw GET parameter (string, array, or false)
	 * @return int[]
	 */
	private function _parse_search_ids_param($raw)
	{
		if ($raw===false || $raw===null || $raw===''){
			return array();
		}
		if (is_array($raw)){
			$raw=implode(',',$raw);
		}
		$raw=(string)$raw;
		$parts=preg_split('/[|,\s]+/',$raw,-1,PREG_SPLIT_NO_EMPTY);
		$out=array();
		foreach ($parts as $p){
			$p=trim($p);
			if ($p!=='' && ctype_digit($p)){
				$n=(int)$p;
				if ($n>0){
					$out[]=$n;
				}
			}
		}
		return $out;
	}

	/**
	 * 
	 * 
	 * find country id for country names and iso codes
	 * 
	 * @countries - string - pipe separated
	 * @todo - move to model
	 */
	private function get_countries_id($countries,$delimited='|')
	{
		if(empty($countries)){
			return false;
		}

		if(!is_array($countries)){
			$countries=explode($delimited,$countries);
		}

		//map iso2 to iso3
		$countries=$this->map_iso2_to_iso3($countries);

		$this->db->select("countries.countryid");
		$this->db->join('country_aliases','country_aliases.countryid=countries.countryid','left');
		$this->db->where_in('name',$countries);
		$this->db->or_where_in('alias',$countries);
		$this->db->or_where_in('iso',$countries);
		$result=$this->db->get("countries")->result_array();
		$output=array();

		foreach($result as $row){
			$output[]=$row['countryid'];
		}

		//if no matches found, return -1
		//this is needed to return no results when no matching countries 
		//are found otherwise filter is ignored
		if(count($output)<1){
			return array(-1);
		}

		return $output;
	}


	/**
	 * 
	 * list study data files
	 * 
	 */
	function data_files_get($idno=null, $fid=null)
	{
		if($fid)
		{
			return $this->data_file_single_get($idno, $fid);
		}

		try{			
			$sid=$this->get_sid_from_idno($idno);

			$user_id=$this->get_api_user_id();        
			$survey=$this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$survey_datafiles=$this->Data_file_model->get_all_by_survey($sid);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'datafiles'=>$survey_datafiles
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Return a single data file by file ID
	 * 
	 */
	function data_file_single_get($idno=null, $fid=null)
	{

		try{			
			$sid=$this->get_sid_from_idno($idno);

			$user_id=$this->get_api_user_id();        
			$survey=$this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$file=$this->Data_file_model->get_file_by_id($sid,$fid);

			if(!$file){
				throw new exception("ID-NOT-FOUND");
			}
			
			$response=array(
				'datafile'=>$file
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * List variables by data file
	 * 
	 */
	function data_file_variables_get($idno=null,$file_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$user_id=$this->get_api_user_id();        
			$survey=$this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			if($file_id==null){
				throw new exception("FILE-ID-REQUIRED");
			}

			$survey_variables=$this->Variable_model->list_by_dataset($sid,$file_id,$metadata_detailed=$this->input->get("metadata_detailed")=='true');
			
			$response=array(
				'total'=> count($survey_variables),
				'variables'=>$survey_variables
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 *
	 * List or search variables.
	 *
	 * idno and var_id may be supplied as URL segments or querystring params:
	 *   /api/catalog/{idno}/variables/{var_id}
	 *   /api/catalog/variables?idno=xyz&var_id=v1
	 *
	 * Behaviour:
	 *   idno + var_id  → single variable
	 *   idno + ?sk=    → per-study keyword search (v_quick_search)
	 *   idno           → list all variables for study
	 *   (no idno)      → catalog-wide variable search (vsearch)
	 *
	 */
	function variables_get($idno=null,$var_id=null)
	{
		// Accept idno and var_id from querystring when not in URL
		if (empty($idno)) {
			$idno = xss_clean($this->input->get('idno')) ?: null;
		}
		if (empty($var_id)) {
			$var_id = xss_clean($this->input->get('var_id')) ?: null;
		}

		// Single variable
		if ($var_id) {
			return $this->variable_get($idno, $var_id);
		}

		// Catalog-wide variable search
		if (empty($idno)) {
			return $this->_vsearch_get();
		}

		// Per-study: list or keyword search
		try{
			$sid    = $this->get_sid_from_idno($idno);
			$survey = $this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new Exception("STUDY_NOT_FOUND");
			}

			$sk = trim(xss_clean($this->input->get('sk')));

			if ($sk !== '') {
				$limit  = $this->get_page_size();
				$page   = max(1, (int)$this->input->get('page'));
				$offset = ($page - 1) * $limit;

				list($vsb, $vso) = $this->_normalize_catalog_sort_for_listing(
					$sk,
					xss_clean($this->input->get('sort_by')),
					xss_clean($this->input->get('sort_order'))
				);

				$params = array(
					'study_keywords'    => $sk,
					'variable_keywords' => $sk,
					'sort_by'           => $vsb,
					'sort_order'        => $vso,
				);
				$this->load->library('catalog_search', $params);
				$variables = $this->catalog_search->v_quick_search($sid, $limit, $offset);

				$response = array(
					'found'     => $variables['found']  ?? 0,
					'total'     => $variables['total']  ?? 0,
					'limit'     => $variables['limit']  ?? $limit,
					'offset'    => $variables['offset'] ?? $offset,
					'page'      => $page,
					'variables' => $variables['rows']   ?? array(),
				);
			} else {
				$variables = $this->Variable_model->list_by_dataset($sid);
				$response  = array(
					'found'     => count($variables),
					'total'     => count($variables),
					'variables' => $variables,
				);
			}

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response(
				array('status' => 'failed', 'message' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}


	/**
	 *
	 * Catalog-wide variable search — called by variables_get() when no idno is provided.
	 *
	 * Query parameters:
	 *   sk          keyword(s)
	 *   country     pipe-separated country names / ISO codes
	 *   from, to    year range
	 *   dtype       data access type (model name)
	 *   type        dataset type
	 *   sort_by, sort_order
	 *   page, ps    pagination
	 *
	 */
	private function _vsearch_get()
	{
		$limit  = $this->get_page_size();
		$page   = max(1, (int)$this->input->get('page'));
		$offset = ($page - 1) * $limit;

		$sk      = trim(xss_clean($this->input->get('sk')));
		$country = xss_clean($this->input->get('country'));
		$from    = xss_clean($this->input->get('from'));
		$to      = xss_clean($this->input->get('to'));
		$dtype   = xss_clean($this->input->get('dtype'));
		$type    = $this->Search_helper_model->filter_catalog_type_param(xss_clean($this->input->get('type')));

		list($vsb, $vso) = $this->_normalize_catalog_sort_for_listing(
			$sk,
			xss_clean($this->input->get('sort_by')),
			xss_clean($this->input->get('sort_order'))
		);

		$params = array(
			'study_keywords'    => $sk,
			'variable_keywords' => $sk,
			'countries'         => $this->get_countries_id($country),
			'from'              => $from,
			'to'                => $to,
			'dtype'             => $this->Form_model->map_name_to_id($dtype),
			'type'              => $type,
			'sort_by'           => $vsb,
			'sort_order'        => $vso,
		);

		try{
			$this->load->library('catalog_search', $params);
			$result         = $this->catalog_search->vsearch($limit, $offset);
			$result['page'] = $page;

			// Nest study context fields — keep sid at row level, move title/idno/nation under study{}
			if (!empty($result['rows'])) {
				foreach ($result['rows'] as &$row) {
					$row['study'] = array(
						'id'     => $row['sid'],
						'idno'   => $row['idno'],
						'title'  => $row['title'],
						'nation' => $row['nation'],
					);
					unset($row['idno'], $row['title'], $row['nation']);
				}
				unset($row);
			}

			$response = array(
				'result' => $result,
				'params' => array(
					'sk'      => $sk,
					'country' => $country,
					'from'    => $from,
					'to'      => $to,
					'dtype'   => $dtype,
					'type'    => $type,
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response(
				array('status' => 'failed', 'errors' => $e->getMessage()),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * 
	 *  Return a single variable with full metadata
	 * 
	 */
	function variable_get($idno=null,$var_id=null)
	{
		try{						
			if(!$var_id){
				throw new Exception("MISSING_PARAM::VAR_ID");
			}

			$sid=$this->get_sid_from_idno($idno);
			$user_id=$this->get_api_user_id();        
			$variable=$this->Variable_model->get_var_by_vid($sid,$var_id);

			if(!$variable){
				throw new Exception("VARIABLE-NOT-FOUND");
			}
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
				'variable'=>$variable
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}




	/**
	 * 
	 * 
	 * Get all Collections
	 * 
	 * 
	 */
	function collections_get($repo_id=null)
	{	
		if($repo_id){
			return $this->single_collection_get($repo_id);
		}

		try{			
			$repos=$this->Repository_model->select_all($published=1);

			$output=array();
			$fields=array(
				'id'=>'id',
				'repositoryid'=>'repositoryid',
				'title'=>'title',
				'thumbnail'=>'thumbnail',
				'short_text'=>'short_text',
				'long_text'=>'long_text',
			);

			foreach($repos as $row){
				$tmp=array();
				foreach($fields as $idx=>$name){
					$tmp[$name]=$row[$idx];
				}

				$output[]=$tmp;
			}

			$response=array(
				'status'=>'success',
				'total'=>count($repos),
				'collections'=>$output
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	

	/**
	 * 
	 * Get a single collection
	 * 
	 */
	function single_collection_get($repo_id=null)
	{
		try{
			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$repo=array(
				'id'=>$repo['id'],
				'repositoryid'=>$repo['repositoryid'],
				'title'=>$repo['title'],
				'short_text'=>$repo['short_text'],
				'long_text'=>$repo['long_text'],
				'thumbnail'=>$repo['thumbnail']
			);
			
			$this->set_response($repo, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}



	/**
	 * 
	 * Map iso2 country codes to iso3
	 * 
	 */
	private function map_iso2_to_iso3($countries = array() )
	{
		$iso2_codes=array(
			'af'=>'afg',
			'al'=>'alb',
			'dz'=>'dza',
			'as'=>'asm',
			'ad'=>'and',
			'ao'=>'ago',
			'ai'=>'aia',
			'aq'=>'ata',
			'ag'=>'atg',
			'ar'=>'arg',
			'am'=>'arm',
			'aw'=>'abw',
			'au'=>'aus',
			'at'=>'aut',
			'az'=>'aze',
			'bs'=>'bhs',
			'bh'=>'bhr',
			'bd'=>'bgd',
			'bb'=>'brb',
			'by'=>'blr',
			'be'=>'bel',
			'bz'=>'blz',
			'bj'=>'ben',
			'bm'=>'bmu',
			'bt'=>'btn',
			'bo'=>'bol',
			'bq'=>'bes',
			'ba'=>'bih',
			'bw'=>'bwa',
			'bv'=>'bvt',
			'br'=>'bra',
			'io'=>'iot',
			'bn'=>'brn',
			'bg'=>'bgr',
			'bf'=>'bfa',
			'bi'=>'bdi',
			'cv'=>'cpv',
			'kh'=>'khm',
			'cm'=>'cmr',
			'ca'=>'can',
			'ky'=>'cym',
			'cf'=>'caf',
			'td'=>'tcd',
			'cl'=>'chl',
			'cn'=>'chn',
			'cx'=>'cxr',
			'cc'=>'cck',
			'co'=>'col',
			'km'=>'com',
			'cd'=>'cod',
			'cg'=>'cog',
			'ck'=>'cok',
			'cr'=>'cri',
			'hr'=>'hrv',
			'cu'=>'cub',
			'cw'=>'cuw',
			'cy'=>'cyp',
			'cz'=>'cze',
			'ci'=>'civ',
			'dk'=>'dnk',
			'dj'=>'dji',
			'dm'=>'dma',
			'do'=>'dom',
			'ec'=>'ecu',
			'eg'=>'egy',
			'sv'=>'slv',
			'gq'=>'gnq',
			'er'=>'eri',
			'ee'=>'est',
			'sz'=>'swz',
			'et'=>'eth',
			'fk'=>'flk',
			'fo'=>'fro',
			'fj'=>'fji',
			'fi'=>'fin',
			'fr'=>'fra',
			'gf'=>'guf',
			'pf'=>'pyf',
			'tf'=>'atf',
			'ga'=>'gab',
			'gm'=>'gmb',
			'ge'=>'geo',
			'de'=>'deu',
			'gh'=>'gha',
			'gi'=>'gib',
			'gr'=>'grc',
			'gl'=>'grl',
			'gd'=>'grd',
			'gp'=>'glp',
			'gu'=>'gum',
			'gt'=>'gtm',
			'gg'=>'ggy',
			'gn'=>'gin',
			'gw'=>'gnb',
			'gy'=>'guy',
			'ht'=>'hti',
			'hm'=>'hmd',
			'va'=>'vat',
			'hn'=>'hnd',
			'hk'=>'hkg',
			'hu'=>'hun',
			'is'=>'isl',
			'in'=>'ind',
			'id'=>'idn',
			'ir'=>'irn',
			'iq'=>'irq',
			'ie'=>'irl',
			'im'=>'imn',
			'il'=>'isr',
			'it'=>'ita',
			'jm'=>'jam',
			'jp'=>'jpn',
			'je'=>'jey',
			'jo'=>'jor',
			'kz'=>'kaz',
			'ke'=>'ken',
			'ki'=>'kir',
			'kp'=>'prk',
			'kr'=>'kor',
			'kw'=>'kwt',
			'kg'=>'kgz',
			'la'=>'lao',
			'lv'=>'lva',
			'lb'=>'lbn',
			'ls'=>'lso',
			'lr'=>'lbr',
			'ly'=>'lby',
			'li'=>'lie',
			'lt'=>'ltu',
			'lu'=>'lux',
			'mo'=>'mac',
			'mg'=>'mdg',
			'mw'=>'mwi',
			'my'=>'mys',
			'mv'=>'mdv',
			'ml'=>'mli',
			'mt'=>'mlt',
			'mh'=>'mhl',
			'mq'=>'mtq',
			'mr'=>'mrt',
			'mu'=>'mus',
			'yt'=>'myt',
			'mx'=>'mex',
			'fm'=>'fsm',
			'md'=>'mda',
			'mc'=>'mco',
			'mn'=>'mng',
			'me'=>'mne',
			'ms'=>'msr',
			'ma'=>'mar',
			'mz'=>'moz',
			'mm'=>'mmr',
			'na'=>'nam',
			'nr'=>'nru',
			'np'=>'npl',
			'nl'=>'nld',
			'nc'=>'ncl',
			'nz'=>'nzl',
			'ni'=>'nic',
			'ne'=>'ner',
			'ng'=>'nga',
			'nu'=>'niu',
			'nf'=>'nfk',
			'mp'=>'mnp',
			'no'=>'nor',
			'om'=>'omn',
			'pk'=>'pak',
			'pw'=>'plw',
			'ps'=>'pse',
			'pa'=>'pan',
			'pg'=>'png',
			'py'=>'pry',
			'pe'=>'per',
			'ph'=>'phl',
			'pn'=>'pcn',
			'pl'=>'pol',
			'pt'=>'prt',
			'pr'=>'pri',
			'qa'=>'qat',
			'mk'=>'mkd',
			'ro'=>'rou',
			'ru'=>'rus',
			'rw'=>'rwa',
			're'=>'reu',
			'bl'=>'blm',
			'sh'=>'shn',
			'kn'=>'kna',
			'lc'=>'lca',
			'mf'=>'maf',
			'pm'=>'spm',
			'vc'=>'vct',
			'ws'=>'wsm',
			'sm'=>'smr',
			'st'=>'stp',
			'sa'=>'sau',
			'sn'=>'sen',
			'rs'=>'srb',
			'sc'=>'syc',
			'sl'=>'sle',
			'sg'=>'sgp',
			'sx'=>'sxm',
			'sk'=>'svk',
			'si'=>'svn',
			'sb'=>'slb',
			'so'=>'som',
			'za'=>'zaf',
			'gs'=>'sgs',
			'ss'=>'ssd',
			'es'=>'esp',
			'lk'=>'lka',
			'sd'=>'sdn',
			'sr'=>'sur',
			'sj'=>'sjm',
			'se'=>'swe',
			'ch'=>'che',
			'sy'=>'syr',
			'tw'=>'twn',
			'tj'=>'tjk',
			'tz'=>'tza',
			'th'=>'tha',
			'tl'=>'tls',
			'tg'=>'tgo',
			'tk'=>'tkl',
			'to'=>'ton',
			'tt'=>'tto',
			'tn'=>'tun',
			'tr'=>'tur',
			'tm'=>'tkm',
			'tc'=>'tca',
			'tv'=>'tuv',
			'ug'=>'uga',
			'ua'=>'ukr',
			'ae'=>'are',
			'gb'=>'gbr',
			'um'=>'umi',
			'us'=>'usa',
			'uy'=>'ury',
			'uz'=>'uzb',
			'vu'=>'vut',
			've'=>'ven',
			'vn'=>'vnm',
			'vg'=>'vgb',
			'vi'=>'vir',
			'wf'=>'wlf',
			'eh'=>'esh',
			'ye'=>'yem',
			'zm'=>'zmb',
			'zw'=>'zwe',
			'ax'=>'ala'
		);
		
		$output=array();
		foreach($countries as $country){
			if( strlen($country)==2 && array_key_exists($country, $iso2_codes)){
				$output[]=$iso2_codes[$country];
			}
			else{
				$output[]=$country;
			}
		}
		return $output;
	}



	/**
	 * 
	 * Get DDI
	 * 
	 */
	function ddi_get($idno=null)
	{
		try{			
			$sid=$this->get_sid_from_idno($idno);
			$dataset=$this->Dataset_model->get_row($sid);

			if (!$dataset){
				throw new Exception("IDNO_NOT_FOUND");
			}

			if($dataset['type']!='survey'){
				throw new Exception("DDI is only available for Survey/MICRODATA types");
			}
            $this->Dataset_model->download_metadata_ddi($sid);
			die();
        }		
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}



	/**
	 * 
	 * Get JSON or JSON Lines
	 * 
	 * Query parameters:
	 *   format - 'json' (default) or 'jsonl' for JSON Lines format
	 *   pretty - 'true' to pretty print JSON (only for JSON format)
	 *   download - 'true' to download the file instead of streaming
	 *   dsd_export - for timeseries only: 'reference' (default) or 'inline' — full DSD + components + codelists
	 *   include_resources - 'true' to embed external resources as `external_resources` (same row shape as resources_get + url)
	 * 
	 */
	function json_get($idno=null)
	{
		try{			
			$sid=$this->get_sid_from_idno($idno);
			
			if (!$sid){
				throw new Exception("IDNO_NOT_FOUND");
			}

			$this->load->library('JSON_Writer');
			
			$format = strtolower($this->input->get('format'));
			if (!in_array($format, array('json', 'jsonl'))) {
				$format = 'json';
			}

			$pretty = $this->input->get('pretty') === 'true' || $this->input->get('pretty') === '1';
			$download = $this->input->get('download') === 'true' || $this->input->get('download') === '1';
			$include_resources = $this->input->get('include_resources') === 'true' || $this->input->get('include_resources') === '1';
			$dsd_export = strtolower(trim((string) $this->input->get('dsd_export'))) === JSON_Writer::DSD_EXPORT_INLINE
				? JSON_Writer::DSD_EXPORT_INLINE
				: JSON_Writer::DSD_EXPORT_REFERENCE;

			if ($download) {
				$this->json_writer->download($sid, $format, $pretty, false, $dsd_export, $include_resources);
			} else {
				$this->json_writer->stream($sid, $format, $pretty, $dsd_export, $include_resources);
			}
        }		
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	/**
	 * 
	 * Download RDF for external resources
	 * 
	 */
	function rdf_get($idno=null)
	{
		try{			
			$sid=$this->get_sid_from_idno($idno);
			$this->load->model('Catalog_model');

			header("Content-Type: application/xml");
			header('Content-Encoding: UTF-8');
			header( "Content-Disposition: attachment;filename=study-$idno.rdf");

			echo $this->Catalog_model->get_survey_rdf($sid);
			die();
        }		
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}


	/**
	 * 
	 * Get study external resources
	 * 
	 */
	function resources_get($idno=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->load->model("Survey_resource_model");
			$resources=$this->Survey_resource_model->get_survey_resources($sid);
			array_walk($resources, 'unix_date_to_gmt',array('created','changed'));
			
			foreach($resources as $idx=>$resource){				
				if($this->form_validation->valid_url($resource['filename'])){
					$resources[$idx]['url']=$resource['filename'];
				}else{
					$resources[$idx]['url']=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
				}				
			}
			
			$response=array(
				'status'=>'success',
				'total'=>count($resources),
				'resources'=>$resources
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}


	/**
	 * Stream a local PDF resource inline for in-app viewing (e.g. PDF.js).
	 *
	 * GET /api/catalog/{idno}/resources/{resource_id}/pdf-stream
	 *
	 * Rejects external URL resources. Same access rules as file download.
	 */
	function pdf_stream_get($idno = null, $resource_id = null)
	{
		try {
			if (! $resource_id) {
				throw new Exception('RESOURCE_ID_NOT_PROVIDED');
			}

			$sid = $this->get_sid_from_idno($idno);
			$this->load->model('Survey_resource_model');
			$this->load->library('Downloads_service');

			$user = $this->api_user();
			$resource = $this->downloads_service->get_resource_info($sid, $resource_id);

			if (! $resource) {
				throw new Exception('RESOURCE_NOT_FOUND');
			}

			if (in_array($resource['data_access_type'], array('public', 'licensed')) && ! $user) {
				throw new Exception('LOGIN_REQUIRED');
			}

			$allow_download = $this->Survey_resource_model->user_has_download_access($user ? $user->id : false, $sid, $resource);

			if ($allow_download === false) {
				throw new Exception("You don't have permissions to access the file.");
			}

			$this->Survey_resource_model->stream_pdf_inline($user, $sid, $resource_id);
			die();
		}
		catch (Exception $e) {
			$error_output = array(
				'status'  => 'failed',
				'errors'  => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Get thumbnail
	 * 
	 */
	function thumbnail_get($idno=null)
	{
		try{			
			$sid=$this->get_sid_from_idno($idno);
			$thumbnail=$this->Dataset_model->get_thumbnail($sid);

			if (!$thumbnail){
				throw new Exception("THUMBNAIL_NOT_FOUND");
			}

			//thumbnail path
			$thumbnail_path=base_url().'files/thumbnails/'.$thumbnail;

			$response=array(
				'status'=>'success',
				'thumbnail'=>$thumbnail_path
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
        }		
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}



	/**
	 * 
	 * Get search filters
	 * 
	 * @filter_name - string - filter name
	 *  - countries
	 *  - years
	 *  - topics
	 * 
	 */
	function filters_get($filter_name=null)
	{
		try{
			$this->load->model('Facet_model');
			$this->load->model('Search_helper_model');
			
			$filters=$this->Facet_model->select_all();
			$filters_list=array_keys($filters);
			$result=[];

			if ($filter_name){
				if (!in_array($filter_name,$filters_list)){
					throw new Exception("FILTER_NOT_FOUND");
				}
				
				foreach($filters as $fc){
					if ($fc['title']==$filter_name && $fc['facet_type']=='user'){
						$result=array(
							'type'=>$fc['facet_type'],
							'title'=>$fc['title'],
							'values'=>$this->Facet_model->get_facet_values(
								$fc['id'],
								$published=1,
								$sort_='value',
								$sort_order_='ASC'
							)
						);
					}
				}

				//country
				if ($filter_name=='country'){
					$result=$this->Search_helper_model->get_active_countries();
				}

				//years
				if ($filter_name=='year'){
					$result=$this->Search_helper_model->get_min_max_years();
				}
				
				//collection
				if ($filter_name=='collection'){
					$result=$this->Search_helper_model->get_active_repositories();
				}

				//dtype
				if ($filter_name=='dtype'){
					$result=$this->Search_helper_model->get_active_data_types();
				}

				//region
				if ($filter_name=='region'){
					$result=$this->Search_helper_model->get_active_regions();
				}

			}
			else{
				$result=[
					'message'=>'Please provide a filter name',
					'filters'=>$filters_list
				];
			}
			
			$response=array(
				'status'=>'success',
				'values'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
        }		
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'errors'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	/**
	 * Public catalog headline stats (published content only, cached 10 minutes).
	 *
	 * GET /api/catalog/stats
	 */
	function stats_get()
	{
		try {
			$this->load->model('Stats_model');
			$stats = $this->Stats_model->get_public_catalog_stats_cached();

			$response = array(
				'status' => 'success',
				'values' => array(
					'studies'             => (int)$stats['studies'],
					'variables'           => (int)$stats['variables'],
					'citations'           => (int)$stats['citations'],
					'countries_with_data' => (int)$stats['countries_with_data'],
					'year_range'          => array(
						'min' => (int)$stats['min_year'],
						'max' => (int)$stats['max_year'],
					),
				),
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch (Exception $e) {
			$error_output = array(
				'status' => 'failed',
				'errors' => $e->getMessage(),
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
