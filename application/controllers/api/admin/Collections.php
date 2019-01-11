<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Collections extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model("Repository_model");
		
		//allow only ADMIN users to access the API
		if(!$this->is_admin()){
			$response=array(
				'status'=>'ACCESS-DENIED',
				'message'=>'User does not have permissions to access the API'
			);
			$this->response($response, REST_Controller::HTTP_BAD_REQUEST,false);
			die();
		}
	}

	/**
	 * 
	 * 
	 * Get all Collections
	 * 
	 * 
	 */
	function index_get($repo_id=null)
	{	
		if($repo_id){
			return $this->single_get($repo_id);
		}

		try{			
			$repos=$this->Repository_model->list_all();

			$response=array(
				'status'=>'success',
				'total'=>count($repos),
				'collections'=>$repos
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
	function single_get($repo_id=null)
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
				'thumbnail'=>$repo['thumbnail'],
				'weight'=>$repo['weight'],
				'ispublished'=>$repo['ispublished'],
				'section'=>$repo['section']
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
	 * 
	 * create a new collection
	 * 
	 **/ 
	function index_post()
	{
		$options=$this->raw_json_input();

		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

			$options['survey_id']=$sid;

			//validate resource
			if ($this->Survey_resource_model->validate_resource($options)){
				$resource_id=$this->Survey_resource_model->insert($options);
				$resource=$this->Survey_resource_model->select_single($resource_id);
				
				$response=array(
					'status'=>'success',
					'resource'=>$resource
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'message'=>'VALIDATION_ERROR',
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}		
	}


	

	//update an existing resource
	function index_put()
	{
		$options=$this->raw_json_input();

		try{
			if(!is_numeric($sid)){
				throw new Exception("MISSING_PARAM: sid");
			}

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resourceId");
			}
			
			$resource=$this->Resource_model->get_single_resource_by_survey($sid,$resource_id);
			
			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}

			$options['survey_id']=$sid;
			$options['resource_id']=$resource_id;
			
			//validate resource
			if ($this->Survey_resource_model->validate_resource($options,$is_new=false)){
				$this->Survey_resource_model->update($resource_id,$options);
				$resource=$this->Survey_resource_model->select_single($resource_id);
				
				$response=array(
					'status'=>'success',
					'resource'=>$resource
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
		catch(ValidationException $e){
			$error_output=array(
				'message'=>'VALIDATION_ERROR',
				'errors'=>$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}		
	}






	//delete a single resource by resource id
	function index_delete($sid=null,$resource_id=null)
	{			
		try{
			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resource_id");
			}

			$this->Survey_resource_model->delete_single($sid,$resource_id);
			
			$response=array(
				'status'=>'success',
				'resource_id'=>$resource_id
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}		
	}




	
	
}
