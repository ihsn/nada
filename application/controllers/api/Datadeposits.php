<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Datadeposits extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Catalog_model'); 	
		$this->load->helper("date");
        $this->load->model('DD_project_model');
		$this->load->model('DD_resource_model');
		$this->load->model('DD_citation_model');
		$this->is_admin_or_die();
	}



	/** 
	 * 
	 * List all projects or return a single project if @pid is provided
	 * 
	 * @pid - project id
	 * 
	*/
    public function index_get($pid=null)
    {
		//return a single project if project id is passed
		if ($pid){
			return $this->project_get($pid);
		}

		$user_id=$this->get_api_user_id();   

		//get all projects for current user
        $projects=$this->DD_project_model->all_projects($user_id);
        
        $response=array(
            'items'=>$projects
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
    }


	/** 
	*
	* Return a single project 
	*
	* @pid - project ID
	*/
	public function project_get($pid=null,$user_id=null)
    {
		try{

			if (!$pid){
				throw new Exception("MISSING_PARAM: PID"); 
			}

			$project=$this->DD_project_model->get_by_id($pid);

			//format dates
			array_walk($project, 'unix_date_to_gmt_row',array('created_on','last_modified','submitted_date','administer_date'));

			$response=array(
				'project'=>$project
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
	 * 
	 * Get all resources by the project
	 * 
	 * @pid - (required) project ID
	 * @resource_id	- (optional) resource ID
	 * 
	 **/ 
    public function resources_get($pid=null, $resource_id=null)
    {
		//get single resource if resource id is provided
		if(is_numeric($pid) && is_numeric($resource_id)){
			return $this->resource_get($pid,$resource_id);
		}		
		
        try{
			$project=$this->DD_project_model->get_by_id($pid);
            $resources=$this->DD_resource_model->get_project_resources($pid);
			
			array_walk($resources, 'unix_date_to_gmt',array('created','changed'));
			
			$response=array(
                'items'=>$resources
            );

            $this->set_response($response, REST_Controller::HTTP_OK);    
        }
        catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }


	/**
	 * 
	 * Get a single resource
	 * 
	 * 
	 * 
	 */
	public function resource_get($project_id=null,$resource_id=null)
	{
		try{
			$project=$this->DD_project_model->get_by_id($project_id);

			if(!$project){
				throw new Exception("Project not found");
			}
			
			$resource=$this->DD_resource_model->get_project_resource($resource_id);
			$this->set_response($resource, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'error',
				'message'=>$e->getMessage()			
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
    }

	function download_get($project_id=null,$resource_id=null)
	{
		try{
			$project=$this->DD_project_model->get_by_id($project_id);

			if(!$project){
				throw new Exception("Project not found");
			}

			$user=$this->api_user();

			if(!$user){
				throw new Exception("USER_NOT_LOGGEDIN");
			}

			if(!$resource_id){
				throw new Exception("PARAM_NOT_SET: resource_id");
			}

				//get project data folder path
			$project_folder_path=$this->DD_project_model->get_project_fullpath($project_id);
			
			if (!$project_folder_path){
				throw new Exception("PROJECT_DATA_FOLDER_NOT_SET");
			}
			
			$this->load->helper('download');
			$this->load->model('DD_resource_model');
			$this->lang->load("resource_manager");
			$this->load->model('managefiles_model');
					
			$resource = $this->DD_resource_model->get_project_resource($resource_id);
			
			if (!$resource){
				throw new Exception("FILE_NOT_FOUND");
			}
			
			$resource_path=unix_path($project_folder_path.'/'.$resource[0]->filename);
			
			if (!file_exists($resource_path)){
				throw new Exception("FILE_NOT_FOUND:".$resource_path);
			}

			force_download3($resource_path,$resource[0]->filename);
		}
		catch(Exception $e){
			$output=array(
				'status'=>'error',
				'message'=>$e->getMessage()
			);
			$this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}    

	
}
