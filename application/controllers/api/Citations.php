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


	
	



	
}
