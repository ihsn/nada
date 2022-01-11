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
		if ($this->session->userdata('user_id')){
			return true;
		}

		parent::_auth_override_check();
	}
	


	//datasets published per month
	/**
	 * 
	 * Reports by published datasets
	 * 
	 * 
	 * # of datasets published per month & year
	 * # of published datasets by catalog collection
	 * # of published datasets by country
	 * # of published datasets by topic
	 * 
	 * 
	 * @type - month, collection, country, topic
	 * 
	**/
	function datasets_published_get($type='month')
	{
		try{
			if ($type=='month'){
				$result=$this->Reports_model->get_datasets_published();
			}
			else if($type=='collection'){
				$result=$this->Reports_model->get_datasets_published_by_collection();
			}
			else if($type=='country'){
				$result=$this->Reports_model->get_datasets_published_by_country();
			}
			else if($type=='topic'){
				throw new exception("not implemented yet!");
			}
			else{
				throw new Exception("Valid options for 'type' param are: month, collection, country");
			}
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'type'=>$type,
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

	

	/**
	 * 
	 * 
	 * Reports for Licensed Requests
	 * 
	 * - type - month, country, collection
	 * 
	 * 
	 */
	function licensed_requests_get($type='month')
	{
		try{
			if ($type=='month'){
				$result=$this->Reports_model->get_licensed_requests_by_month();
			}
			else if ($type=='country'){
				$result=$this->Reports_model->get_licensed_requests_by_country();
			}
			else if ($type=='collection'){
				$result=$this->Reports_model->get_licensed_requests_by_collection();
			}
			else{
				throw new Exception("Valid options for 'type' param are: month, collection, country");
			}
			
			if($this->input->get("format")=="csv"){
				$response=$result;
			}
			else{
				$response=array(
					'status'=> 'success',
					'type'=>$type,
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
