<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Dataaccess_whitelist extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Survey_resource_model");
		$this->load->model("Data_access_whitelist_model");
		$this->is_admin_or_die();
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
	 * List
	 * 
	 **/
	function index_get()
	{
		try{
			$result=$this->Data_access_whitelist_model->select_all();

			$response=array(
				'status'=>'success',
				'result'=>$result
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
     * Create
     * 
     * 
     */
    function index_post()
	{
		try{
            $options=$this->raw_json_input();

			$collection_name=isset($options['collection_name']) ? $options['collection_name'] :null;
			$email=isset($options['email']) ? $options['email'] : null;

			$repository_id=$this->Data_access_whitelist_model->get_repo_id($collection_name);
			$user_id=$this->Data_access_whitelist_model->get_user_id($email);

			if(!$repository_id || !$user_id){
				throw new Exception("Invalid values for `collection_name` or `email`");
			}
			
            $result=$this->Data_access_whitelist_model->insert($repository_id,$user_id);

            $output=array(
                'status'=>'success',
                'result'=>$result
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
	}

	
	function index_delete()
	{
		try{
			
			$options=$this->raw_json_input();

			$collection_name=isset($options['collection_name']) ? $options['collection_name'] :null;
			$email=isset($options['email']) ? $options['email'] : null;

			$repository_id=$this->Data_access_whitelist_model->get_repo_id($collection_name);
			$user_id=$this->Data_access_whitelist_model->get_user_id($email);

			if(!$repository_id || !$user_id){
				throw new Exception("Invalid values for `collection_name` or `email`");
			}
		
			$result=$this->Data_access_whitelist_model->delete($repository_id,$user_id);

			$response=array(
				'status'=>'success',
				'message'=>'DELETED',
				'result'=>$result
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

	
}
