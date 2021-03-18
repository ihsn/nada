<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Embed extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->helper("date");
        $this->load->model('Embed_model');
		$this->load->library("Dataset_manager");
		$this->is_admin_or_die();
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

			$result=$this->Embed_model->select_all();

			$response=array(
				'total'=>count($result),
				'records'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
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
			$result=$this->Embed_model->find($uuid);

			if (!$result){
				throw new Exception("NOT_FOUND");
			}

			$response=array(
				'record'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response($e->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
		}	
    }
    


    /**
	 * 
	 * Create Embed 
	 * 
	 **/ 
	function index_post($uuid=null)
	{		
		try{
			$overwrite=$this->input->post("overwrite");

			if($overwrite=='yes'){
				$overwrite=true;
			}

			$options=$this->input->post(null, true);
			$result=$this->Embed_model->create_project($uuid,$options);

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
	function delete_delete($uuid=null)
	{
		
		try{
			$this->Embed_model->delete($uuid);

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
	 * Attach Embed link to a study
	 * 
	 **/ 
	function attach_to_study_post()
	{		
		try{			
			$options=$this->raw_json_input();

			if (!isset($options['uuid'])){
				throw new Exception("uuid is required");
			}

			if (!isset($options['idno'])){
				throw new Exception("idno is required");
			}

			$sid=$this->get_sid_from_idno($options['idno']);

			$result=$this->Embed_model->attach_to_study($sid,$options['uuid']);

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
	 * Embeds by dataset
	 * 
	 **/
	function by_dataset_get($idno=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);
			$result=$this->Embed_model->embeds_by_study($sid);
			
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
