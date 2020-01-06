<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Resources extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Resource_model");	//todo to be deleted
		$this->load->model("Survey_resource_model");	
		$this->is_authenticated_or_die();
	}
	


	/**
	 * 
	 * 
	 * Get all resources by Dataset
	 * 
	 * 
	 */
	function index_get($idno=null,$resource_id=null)
	{	
		try{
						
			if($resource_id){
				return $this->single_get($idno,$resource_id);
			}
			
			$sid=$this->get_sid_from_idno($idno);

			$resources=$this->Resource_model->get_resources_by_survey($sid);

			$response=array(
				'status'	=> 'success',
				'total'		=> count($resources),
				'resources'	=> $resources
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Get a single resource
	 * 
	 */
	function single_get($idno=null,$resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resourceId");
			}
			
			$resource=$this->Resource_model->get_single_resource_by_survey($sid,$resource_id);
			
			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}	
			
			$this->set_response($resource, REST_Controller::HTTP_OK);			
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
	 * create a new external resource
	 * 
	 **/ 
	function index_post($idno=null)
	{
		$this->is_admin_or_die();		
		$options=$this->raw_json_input();

		try{
			$sid=$this->get_sid_from_idno($idno);

			$options['survey_id']=$sid;

			//get dctype by code
			if(isset($options['dctype'])){ 
				$options['dctype']=$this->Survey_resource_model->get_dctype_label_by_code($options['dctype']);
			}

			if(isset($options['dcformat'])){ 
				$options['dcformat']=$this->Survey_resource_model->get_dcformat_label_by_code($options['dcformat']);
			}

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
	function index_put($idno=null,$resource_id=null)
	{
		$this->is_admin_or_die();
		$options=$this->raw_json_input();

		try{
			$sid=$this->get_sid_from_idno($idno);

			if(!is_numeric($resource_id)){
				throw new Exception("MISSING_PARAM: resourceId");
			}

			//get dctype by code
			if(isset($options['dctype'])){
				$options['dctype']=$this->Survey_resource_model->get_dctype_label_by_code($options['dctype']);
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
	function index_delete($idno=null,$resource_id=null)
	{
		$this->is_admin_or_die();
		try{
			$sid=$this->get_sid_from_idno($idno);

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



	//delete all resources by study
	public function delete_all_delete($idno=null)
	{	
		$this->is_admin_or_die();	
		try{
			$sid=$this->get_sid_from_idno($idno);
			$this->Survey_resource_model->delete_all_survey_resources($sid);

			$response=array(
				'status'=>'success'
			);

			$this->set_response($response, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	//import rdf file
	public function import_rdf_post($idno=NULL)
	{
		$this->is_admin_or_die();
		$this->load->model("Survey_model");	
		
		try {
			$sid=$this->get_sid_from_idno($idno);

			$result=$this->Survey_resource_model->upload_rdf('file');
			$uploaded_file_name=$result['file_name'];
			$uploaded_path=$result['full_path'];

			//import entries
			$imported_count=$this->Survey_resource_model->import_rdf($sid,$uploaded_path);

			//delete rdf
			@unlink($uploaded_path);

			$output=array(
				'status'=>'success',
				'entries_imported'=>$imported_count
			);

			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}		
	}

	/**
	 * 
	 * Fix broken links
	 * 
	 */
	function fix_links_put($idno=null)
	{
		$this->is_admin_or_die();
		$this->load->model("Survey_resource_model");		
		try{
			$sid=$this->get_sid_from_idno($idno);
			$links_fixed=$this->Survey_resource_model->fix_resource_links($sid);
			$output=array(
				'links_fixed'=>$links_fixed
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * 
	 * Download file
	 * 
	 */
	function download_get($dataset_idno=null,$resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($dataset_idno);
			$user_id=$this->get_api_user_id();

			if(!$resource_id){
				throw new Exception("PARAM_NOT_SET: resource_id");
			}

			$resource=$this->Survey_resource_model->get_single_resource_by_survey($sid,$resource_id);
			
			if(!$resource){
				throw new Exception("RESOURCE_NOT_FOUND");
			}			

			$allow_download=$this->Survey_resource_model->user_has_download_access($user_id,$sid,$resource);

			if($allow_download!==true){
				throw new Exception("You don't have permissions to access the file.");
			}

			$resource_filename=$this->Survey_resource_model->get_resource_filename($resource_id);
			return $this->Survey_resource_model->download_file($sid,base64_encode($resource_filename));	
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
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
	
}
