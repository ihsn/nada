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
	 * 
	 * Search catalog
	 * 
	 */
	function search_get()
	{
		$search_options=new StdClass;
		$limit=$this->get_page_size();

		//page parameters
		$search_options->collection		=xss_clean($this->input->get("collection"));
		$search_options->sk				=trim(xss_clean($this->input->get("sk")));
		$search_options->vk				=trim(xss_clean($this->input->get("vk")));
		$search_options->vf				=xss_clean($this->input->get("vf"));
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


		if($search_options->tab_type!=''){
			$search_options->type=$search_options->tab_type;
		}

		$params=array(
			'collections'=>$search_options->collection,
			'study_keywords'=>$search_options->sk,
			'variable_keywords'=>$search_options->vk,
			'variable_fields'=>$search_options->vf,
			'countries'=>$search_options->country,
			'topics'=>$search_options->topic,
			'from'=>$search_options->from,
			'to'=>$search_options->to,
			'tags'=>$search_options->tag,
			'sort_by'=>$search_options->sort_by,
			'sort_order'=>$search_options->sort_order,
			//'repo'=>$search_options->filter->repo,
			'dtype'=>$search_options->dtype,
			'sid'=>$search_options->sid,
			'type'=>$search_options->type,
            'country_iso3'=>$search_options->country_iso3,
		);

		$this->db_logger->write_log($log_type='api-search',$log_message=http_build_query($params),$log_section='api-search-v1',$log_survey=0);		

		//convert country names or iso codes into country IDs
		$params['countries']=$this->get_countries_id($params['countries']);

		//collections to array
		$params['collections']=explode(",",$params['collections']);		
		
		$limit=15;
		$page=$this->input->get('page');
		$page= ($page >0) ? $page : 1;
		$offset=($page-1)*$limit;

		$this->load->library('catalog_search',$params);

		try{
			$result=$this->catalog_search->search($limit,$offset);

			if(isset($result['rows'])){
				//convert date format
				array_walk($result['rows'], 'unix_date_to_gmt',array('created','changed'));

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
	 * Get a single dataset
	 * @copy of datasets/single_get
	 * 
	 */
	function record_get($idno=null)
	{
		try{
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

		if(trim($countries)==''){
			return false;
		}

		$countries=explode($delimited,$countries);

		$this->db->select("countries.countryid");
		$this->db->join('country_aliases','country_aliases.countryid=countries.countryid','left');
		$this->db->where_in('name',$countries);
		$this->db->or_where_in('alias',$countries);
		$this->db->or_where_in('iso',$countries);
		$result=$this->db->get("countries")->result_array();
		//echo $this->db->last_query();
		//var_dump($result);
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

	
	
	private function get_sid_from_idno($idno=null)
	{
		if(!$idno){
			throw new Exception("IDNO-NOT-PROVIDED");
		}

		$sid=$this->Dataset_model->find_by_idno($idno);

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
	function datafiles_get($idno=null)
	{
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
	 * List dataset variables
	 * 
	 */
	function variables_get($idno=null,$file_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$user_id=$this->get_api_user_id();        
			$survey=$this->Dataset_model->get_row($sid);

			if(!$survey){
				throw new exception("STUDY_NOT_FOUND");
			}

			$survey_variables=$this->Variable_model->list_by_dataset($sid,$file_id);
			
			//format dates
			//array_walk($project, 'unix_date_to_gmt_row',array('created','changed','submitted_date','administer_date'));

			$response=array(
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


}
