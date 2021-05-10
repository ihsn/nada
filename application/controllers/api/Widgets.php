<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Widgets extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->helper("date");
        $this->load->model('Widget_model');
		$this->load->library("Dataset_manager");		
    }

	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}
    

    /**
	 * 
	 * list files
	 * 
	 **/
	function index_get($uuid=null)
	{
		try{

			if($uuid){
				return $this->single($uuid);
			}

			$result=$this->Widget_model->select_all();

			$response=array(
				'total'=>count($result),
				'records'=>$result
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
	 * Check a file exists
	 * 
	 **/
	private function single($uuid=null)
	{
		try{
			$result=$this->Widget_model->find($uuid);

			if (!$result){
				throw new Exception("NOT_FOUND");
			}

			$response=array(
				'record'=>$result
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
	 * Create Widget 
	 * 
	 **/ 
	function index_post($uuid=null)
	{
		try{
			$this->is_admin_or_die();
			$overwrite=$this->input->post("overwrite");

			if($overwrite=='yes'){
				$overwrite=true;
			}

			$options=$this->input->post(null, true);
			$result=$this->Widget_model->create_project($uuid,$options);

			$output=array(
				'status'=>'success',
				'options'=>$options,
				'result'=>$result
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
	 * delete 
	 * 
	 **/ 
	function index_delete($uuid=null)
	{
		
		try{
			$this->is_admin_or_die();
			$this->Widget_model->delete($uuid);

			$output=array(
				'status'=>'success'
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
	 * Attach Widget link to a study
	 * 
	 **/ 
	function attach_to_study_post()
	{		
		try{	
			$this->is_admin_or_die();		
			$options=$this->raw_json_input();

			if (!isset($options['uuid'])){
				throw new Exception("uuid is required");
			}

			if (!isset($options['idno'])){
				throw new Exception("idno is required");
			}

			$sid=$this->get_sid_from_idno($options['idno']);

			$result=$this->Widget_model->attach_to_study($sid,$options['uuid']);

			$output=array(
				'status'=>'success',
				'options'=>$options,
				'result'=>$result
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
	 * Dettach Widget link to a study
	 * 
	 **/ 
	function detach_study_post()
	{		
		try{	
			$this->is_admin_or_die();		
			$options=$this->raw_json_input();

			if (!isset($options['uuid'])){
				throw new Exception("uuid is required");
			}

			if (!isset($options['idno'])){
				throw new Exception("idno is required");
			}

			$sid=$this->get_sid_from_idno($options['idno']);

			$result=$this->Widget_model->remove_from_study($sid,$options['uuid']);

			$output=array(
				'status'=>'success',
				'options'=>$options,
				'result'=>$result
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
	 * Widgets by dataset
	 * 
	 **/
	function by_dataset_get($idno=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$result=$this->Widget_model->widgets_by_study($sid);
			
			$response=array(
				'record'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
    }


	private function get_sid_from_idno($idno=null)
	{		
		if(!$idno){
			throw new Exception("IDNO-NOT-PROVIDED");
		}

		$id_format=$this->input->get("id_format");

		if ($id_format=='id'){
			return $idno;
		}

		$sid=$this->dataset_manager->find_by_idno($idno);

		if(!$sid){
			throw new Exception("IDNO-NOT-FOUND");
		}

		return $sid;
	}

}	
