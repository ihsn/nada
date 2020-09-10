<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Catalog extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Catalog_model');
		$this->load->model('Dataset_model');
		$this->load->model('Data_file_model');
		$this->load->model('Variable_model');
		$this->load->library('Dataset_manager');
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
	 * 
	 * Search catalog
	 * 
	 */
	function search_get()
	{
		$params=array(
				'study_keywords'	=>	$this->security->xss_clean($this->input->get("sk")),
				'variable_keywords'	=>	$this->security->xss_clean($this->input->get("vk")),
				'variable_fields'	=>	array('name','labl'),
				'countries'			=>  $this->security->xss_clean($this->input->get("country")),
				'from'				=>	$this->security->xss_clean($this->input->get("from")),
				'to'				=>	$this->security->xss_clean($this->input->get("to")),
				'collections'		=>	$this->security->xss_clean($this->input->get("collection")),
				'dtype'				=>	$this->security->xss_clean($this->input->get("dtype")),
				'repo'				=>	$this->security->xss_clean($this->input->get("repo")),
				'ps'				=>	$this->security->xss_clean($this->input->get("ps")),
				'sid'				=>	$this->security->xss_clean($this->input->get("sid")),
				'topics'			=>  $this->security->xss_clean($this->input->get("topic")),
				'tags'				=>  $this->security->xss_clean($this->input->get("tag")),
				'sort_by'			=>  $this->security->xss_clean($this->input->get("sort_by")),
				'sort_order'		=>  $this->security->xss_clean($this->input->get("sort_order")),
				'type'				=>  $this->security->xss_clean($this->input->get("type")),
				'country_iso3'		=>  $this->security->xss_clean($this->input->get("country_iso3")),
				'created'			=>  $this->security->xss_clean($this->input->get("created")),
		);

		$this->db_logger->write_log($log_type='api-search',$log_message=http_build_query($params),$log_section='api-search-v1',$log_survey=0);		

		//convert country names or iso codes into country IDs
		$params['countries']=$this->get_countries_id($params['countries']);

		//collections to array
		$params['collections']=explode(",",$params['collections']);		
		
		//default page size
		$limit=15;

		if (is_numeric($params['ps']) && $params['ps']>0){
			$limit=$params['ps'];
		}

		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);

		try{
			$result=$this->catalog_search->search($limit,$offset);			

			if(isset($result['rows'])){
				
				$iso3_codes=array();

				if ($this->input->get("inc_iso")){
					//iso3 codes
					$iso3_codes=$this->Dataset_model->get_dataset_country_codes(array_column($result['rows'],'id') );	
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
				}

				//unset
				if(isset($result['citations'])){
					unset($result['citations']);
				}

				$response=array(
					'result'=>$result
				);
			}
			else{
				$response=array(
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
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}		
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
			$this->db->select("*");
			$query=$this->db->get("forms");
			$content=NULL;
			
			if ($query){
				$content=$query->result_array();
			}

			$output=array();
			foreach($content as $row){
				$output[]=array(
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
	 * 
	 * Return ID by IDNO
	 * 
	 * 
	 * @idno 		- ID | IDNO
	 * @id_format 	- ID | IDNO
	 * 
	 * Note: to use ID instead of IDNO, must pass id_format in querystring
	 * 
	 */
	private function get_sid_from_idno($idno=null)
	{		
		if(!$idno){
			throw new Exception("IDNO-NOT-PROVIDED");
		}

		$id_format=$this->input->get("id_format");

		if ($id_format=='id'){
			if(!is_numeric($idno)){
				throw new Exception("INVALID-IDNO-FORMAT");
			}
			return $idno;
		}

		$sid=$this->dataset_manager->find_by_idno($idno);

		if(!$sid){
			throw new Exception("IDNO-NOT-FOUND");
		}

		return $sid;
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

			$survey_variables=$this->Variable_model->list_by_dataset($sid,$file_id);
			
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
	 * List dataset variables
	 * 
	 */
	function variables_get($idno=null,$var_id=null)
	{

		if($var_id){
			return $this->variable_get($idno, $var_id);
		}

		try{
			$sid=$this->get_sid_from_idno($idno);
			$user_id=$this->get_api_user_id();        
			$survey=$this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$survey_variables=$this->Variable_model->list_by_dataset($sid);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

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
}
