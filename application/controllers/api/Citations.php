<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Citations extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Citation_model"); 
	}
	
	/**
	 * 
	 * 
	 * Get all citations
	 * 
	 * 
	 */
	function index_get($uuid=null)
	{	
		try{
			if($uuid){
				return $this->single_get($uuid);
			}
			
			$per_page = $this->get_page_size($this->input->get("ps"));
		
			$offset=(int)$this->input->get('offset');
			if ($offset<0){
				$offset=0;
			}

			//$collection=$this->input->get('collection');
			
			$published=1; //show only published citations by default

			try{
				if($this->is_admin()){
					$published=null; //show all

					if($this->input->get("published")!==false){
						$published=(int)$this->input->get("published") == 1 ? 1:0;
					}
				}
			}
			catch(Exception $e){
				$published=1;
			}
	
			//sort order
			$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
			$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'rank';
	
			$filter=array(
				'keywords'=>$this->input->get("keywords"),
				'from'=>$this->input->get("from"),
				'to'=>$this->input->get("to"),
				'ctype'=>array_filter(explode(",",$this->input->get("ctype")))
			);			
	
			$rows=$this->Citation_model->search(
				$per_page, 
				$offset,
				$filter, 
				$sort_by, 
				$sort_order,
				$published
				//$repository=$collection
			);

			$total = $this->Citation_model->search_count();

			$response=array(
				'published' =>$published,
				'status'	=> 'success',
				'total'		=> $total,
				'found'		=> count($rows),
				'offset'	=> $offset,
				'per_page'	=> $per_page,
				'citations'	=> $rows,
				
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Get page size
	 * 
	 */
	private function get_page_size($page_size)
	{
		$page_size_min=1;
		$page_size_max=300;

		if($page_size>=$page_size_min && $page_size<=$page_size_max){
			return $page_size;
		}

		return 50;//default page size
	}


	/**
	 * 
	 * Get a single citation
	 * 
	 */
	function single_get($uuid=null)
	{
		try{

			$id=$this->get_id_from_uuid($uuid);

			if(!is_numeric($id)){
				throw new Exception("MISSING_PARAM: citationId");
			}
			
			$citation=$this->Citation_model->select_single($id);
			
			if(!$citation){
				throw new Exception("CITATION_NOT_FOUND");
			}	
			
			$this->set_response($citation, REST_Controller::HTTP_OK);			
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
	 * Get citations by Study
	 * 
	 */
	function by_dataset_get($sid=null)
	{
		try{
			if(!is_numeric($sid)){
				$sid=$this->Dataset_model->find_by_idno($sid);				
			}

			if (!$sid){
				throw new Exception("MISSING_PARAM: DatasetId");
			}
			
			$citations=$this->Citation_model->get_citations_by_survey($sid);
			
			$output=array(
				'status'=>'success',
				'found'=>count($citations),
				'records'=>$citations
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
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
	 * Add new citation
	 * 
	 **/ 
	function index_post()
	{
		$this->has_access($resource_='citation',$privilege='edit');
		$this->load->model("Dataset_model");

		try{
			$options=$this->raw_json_input();
			$user_id=$this->get_api_user_id();
			
			$options['created_by']=$user_id;
			$options['changed_by']=$user_id;
			$options['created']=date("U");
			$options['changed']=date("U");

			//remove empty values
			foreach($options as $key=>$value){
				if (empty($value)){
					unset($options[$key]);
				}
			}

			if (!isset($options['overwrite'])) {
				$options['overwrite']=0;
			}

			//validate & create dataset
			//$dataset_id=$this->dataset_manager->create_dataset($type,$options);

			$this->Citation_model->validate_schema($options);
			
			$citation_id=$this->Citation_model->uuid_exists($options['uuid']);

			if($citation_id>0 && $options['overwrite']==0){
				throw new Exception("CITATION_ALREADY_EXISTS::".$citation_id);
			}

			if ($citation_id>0){
				$this->Citation_model->update($citation_id,$options);
			}
			else{
				$citation_id=$this->Citation_model->insert($options);
			}

			//attach related studies
			if ( isset($options['related_studies'])){
				$surveys=array();
				foreach($options['related_studies'] as $survey){
					$surveys[]=$this->Dataset_model->find_by_idno($survey);
				}

				$surveys=array_filter($surveys);

				if (count($surveys)>0){
					$this->Citation_model->attach_related_surveys($citation_id,$surveys);
				}
			}

			$output=array(
				'citation_id'=>$citation_id,
				'uuid'=>$options['uuid'],
				'options'=>$options,
				'status'=>'success'
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Find similar citations
	 * 
	 */
	function find_similar_get()
    {
        try{
			$keywords=array();

			//key variables to search on
			$search_keys=explode(",","title,author_fname,author_lname,doi");

			foreach($search_keys as $key){
				if (isset($_GET[$key])){
					$value=$this->get($key,true);

					if (is_array($value)){
						$keywords[]=implode(" ",$value);
					}
					else{
						$keywords[]=$value;
					}
				}
			}

			$keywords= implode(" ",$keywords);

			$citations=$this->Citation_model->search_duplicates($keywords);

			$output=array(
				'citations'=>$citations,				
				'status'=>'success'
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 *  Delete citation
	 * 
	 */
	public function delete_delete($uuid=null)
	{
		try{
			$this->has_access($resource_='citation',$privilege='delete');
			$id=$this->get_id_from_uuid ($uuid);			
			$this->Citation_model->delete($id);
			$this->events->emit('db.after.delete', 'citations', $id);
		
			$response=array(
				'status'=>'success',
				'message'=>'DELETED'
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
	
	public function index_delete($uuid=null)
	{
		return $this->delete_delete($uuid);
	}


	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		if ($this->input->method()=='get'){
			return true;
		}

		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}


	/**
	 * 
	 * 
	 * Return ID by UUID
	 * 
	 * 
	 * @uuid 		- Citation UUID
	 * @id_format 	- ID | IDNO
	 * 
	 * Note: to use ID instead of UUID, must pass id_format in querystring
	 * 
	 */
	public function get_id_from_uuid($uuid=null)
	{		
		if(!$uuid){
			throw new Exception("UUID-NOT-PROVIDED");
		}

		$id_format=$this->input->get("id_format");

		if ($id_format=='id'){
			return $uuid;
		}

		$id=$this->Citation_model->get_id_by_uuid($uuid);

		if(!$id){
			throw new Exception("IDNO-NOT-FOUND");
		}

		return $id;
	}
	
}
