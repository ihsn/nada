<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Collections extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model("Repository_model");
		$this->is_authenticated_or_die();
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

		$published=$this->input->get("published");

		try{
			$this->has_access($resource_='collection',$privilege='view');

			if (is_numeric($published) && ($published==0 || $published==1)){
				$repos=$this->Repository_model->select_all($published);
			}else{
				$repos=$this->Repository_model->select_all();
			}

			$output=array();
			$fields=array(
				'id'=>'id',
				'repositoryid'=>'repositoryid',
				'title'=>'title',
				'thumbnail'=>'thumbnail',
				'short_text'=>'short_text',
				'long_text'=>'long_text',
				'ispublished'=>'ispublished',
				'weight'=>'weight',
				'section'=>'section'
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
	 * 
	 * Create new collection
	 * 
	 * 
	 */
	function index_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();
			
			//validate
			$this->Repository_model->validate($options);

			$upload_result=null;

			if(!empty($_FILES)){
				//upload file?
				$upload_result=$this->Repository_model->upload_thumbnail('thumbnail');
				//set path to uploaded file
				$options['thumbnail']=$upload_result['rel_path'];
			}

			$collection=$this->Repository_model->insert($options);

			if(!$collection){
				throw new Exception("FAILED_TO_CREATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($options['repositoryid']),
				'upload'=>$upload_result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
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
	 * Update collection
	 * 
	 * 
	 */
	function update_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();
			
			if(!isset($options['repositoryid'])){
				throw new Exception("parameter `repositoryid` is missing");
			}

			$repository=$this->Repository_model->get_repository_by_repositoryid($options['repositoryid']);
			
			if(!$repository){
				throw new Exception("Repository not found:: " .$options['repositoryid']);
			}

			$options=array_merge($repository,$options);

			//validate
			$this->Repository_model->validate($options);

			$upload_result=null;

			if(!empty($_FILES)){
				//upload file?
				$upload_result=$this->Repository_model->upload_thumbnail('thumbnail');
				//set path to uploaded file
				$options['thumbnail']=$upload_result['rel_path'];
			}

			$collection=$this->Repository_model->update($options['id'],$options);

			if(!$collection){
				throw new Exception("FAILED_TO_UPDATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($repository['id']),
				'upload'=>$upload_result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors(),
				'options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * Rename collection ID
	 * 
	 * 
	 */
	function rename_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();
			
			if(!isset($options['old_repositoryid'])){
				throw new Exception("parameter `old_repositoryid` is missing");
			}

			if(!isset($options['new_repositoryid'])){
				throw new Exception("parameter `new_repositoryid` is missing");
			}

			$repository=$this->Repository_model->get_repository_by_repositoryid($options['old_repositoryid']);

			if(!$repository){
				throw new Exception("Repository not found:: " .$options['repositoryid']);
			}

			//set repositoryid to new id
			$repository['repositoryid']=$options['new_repositoryid'];			

			//validate
			$this->Repository_model->validate($repository);

			$result=$this->Repository_model->rename_repository($options['old_repositoryid'],$options['new_repositoryid']);

			if(!$result){
				throw new Exception("FAILED_TO_UPDATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($options['new_repositoryid'])
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors(),
				'options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * Get a single collection
	 * 
	 */
	function single_get($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='view');
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
				'ispublished'=>$repo['ispublished']
			);

			$response=array(
				'status'=>'success',
				'collection'=>$repo,
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
	 * Get catalog entries by collection
	 * 
	 */
	function datasets_get($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='view');

			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$datasets=$this->Repository_model->get_all_repo_studies($repo_id);
			$sid_arr=array_values(array_column($datasets,'id'));
			$linked_collections=$this->Repository_model->linked_repos_by_studies($sid_arr);

			foreach($datasets as $idx=>$row){
				if(isset($linked_collections[$row['id']])){
					$datasets[$idx]['linked_collections']=$linked_collections[$row['id']];
				}
			}

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
	 * 
	 * Delete collection
	 * 
	 * 
	 */	
	function delete_delete($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='delete');

			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$this->Repository_model->delete($repo['id']);

			$response=array(
				'status'=>'success'				
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

	function index_delete($repo_id=null)
	{
		return $this->delete_delete($repo_id);
	}

	
	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}
	
}
