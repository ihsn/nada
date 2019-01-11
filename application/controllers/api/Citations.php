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
		$this->is_admin_or_die();
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
			
			//records to show per page
			$per_page = 50;
		
			//current page
			$offset=(int)$this->input->get('offset');
			$collection=$this->input->get('collection');
	
			//sort order
			$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
			$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'rank';
	
			//filter
			$filter=NULL;
	
			$search_options=array(
				'keywords'=>$this->input->get("keywords"),
			);
	
			//records
			$rows=$this->Citation_model->search($per_page, $offset,$search_options, $sort_by, $sort_order,$published=1,$repository=$collection);
	
			//total records found
			$total = $this->Citation_model->search_count();
	
			if ($offset>$total){
				$offset=$total-$per_page;
	
				//search again
				$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order,$published=1,$repository=$collection);
			}

			$response=array(
				'status'	=> 'success',
				'total'		=> $total,
				'offset'	=> $offset,
				'per_page'	=> $per_page,
				'citations'	=> $rows
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * 
	 * Get a single citation
	 * 
	 */
	function single_get($idno=null)
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
		$options=$this->raw_json_input();

		try{
			$sid=$this->get_sid_from_idno($idno);

			$options['survey_id']=$sid;

			//get dctype by code
			if(isset($options['dctype'])){
				$options['dctype']=$this->Survey_resource_model->get_dctype_label_by_code($options['dctype']);
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



	
}
