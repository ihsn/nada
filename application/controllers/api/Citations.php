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
	function single_get($id=null)
	{
		try{
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
			if ( isset($options['related_surveys'])){
				$surveys=array();
				foreach($options['related_surveys'] as $survey){
					$surveys[]=$this->Dataset_model->find_by_idno($survey['idno']);
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



	
}
