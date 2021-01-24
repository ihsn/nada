<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Reports extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Reports_model'); 	
		$this->load->helper("date");
		$this->load->model('Dataset_model');//remove with Datasets library
		$this->load->library("Dataset_manager");
		//$this->is_admin_or_die();
	}

	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		//session user id
		if ($this->session->userdata('user_id'))
		{
			//var_dump($this->session->userdata('user_id'));
			return true;
		}

		parent::_auth_override_check();
	}
	

	/**
	 * 
	 * list files attached to a dataset
	 * 
	 * 
	 *	Published datasets
	 * # of datasets published per month & year
	 * # of published datasets by catalog (region)
	 * # of published datasets by country
	 * # of published datasets by topic
	**/
	function datasets_get($limit=10,$offset=0)
	{
		try{
			/*if($idno){
				return $this->single_get($idno);
			}*/

			$offset=(int)$offset;
			$limit=(int)$limit;
			
			$result=$this->dataset_manager->get_all($limit,$offset);
			array_walk($result, 'unix_date_to_gmt',array('created','changed'));				
			$response=array(
				'status'=> 'success',
				'total'=> $this->dataset_manager->get_total_count(),
				'found'=> count($result),
				'offset'=> $offset,
				'limit'=> $limit,
				'datasets'=> $result
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


	//datasets published per month
	function datasets_published_get()
	{
		try{

			$result=$this->Reports_model->get_datasets_published();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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

	//datasets summary by collection
	function datasets_by_collection_get()
	{
		try{

			$result=$this->Reports_model->get_datasets_published_by_collection();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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


	//datasets summary by country
	function datasets_by_country_get()
	{
		try{

			$result=$this->Reports_model->get_datasets_published_by_country();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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


	//datasets summary by country
	function licensed_requests_summery_get()
	{
		try{

			$result=$this->Reports_model->get_licensed_requests_by_month();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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


	//datasets summary by country
	function licensed_requests_by_collection_summary_get()
	{
		try{

			$result=$this->Reports_model->get_licensed_requests_by_collection();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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


	//datasets summary by country
	function licensed_requests_by_country_summery_get()
	{
		try{

			$result=$this->Reports_model->get_licensed_requests_by_country();
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'result'=> $result
				);	
			}
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
