<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Datadeposits extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('datadeposit');
		datadeposit_require_enabled();
		$this->load->model('Catalog_model'); 	
		$this->load->helper("date");
        $this->load->model('DD_project_model');
        $this->load->model('DD_resource_model');
		$this->load->model('DD_citation_model');
		$this->load->library('Deposit_depositor');
		$this->is_authenticated_or_die();
		$this->_apply_datadeposit_acl();
	}

	private function _apply_datadeposit_acl()
	{
		$method = strtolower((string) $this->router->fetch_method());
		if (preg_match('/delete/', $method)) {
			$this->require_access('datadeposit', 'delete');
			return;
		}
		if (preg_match('/_(post|put)$/', $method)) {
			$this->require_access('datadeposit', 'edit');
			return;
		}
		$this->require_access('datadeposit', 'view');
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
		try {
			$this->require_access('datadeposit', 'edit');

			if (!is_numeric($project_id) || !is_numeric($resource_id)) {
				throw new Exception('FILE_NOT_FOUND');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$file = $this->deposit_depositor->admin_download_file($project_id, $resource_id, $actor);
			$this->load->helper('download');
			force_download3($file['path'], $file['filename']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'error',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * GET /api/datadeposits/export/{format}/{id}
	 * GET /api/datadeposits/{id}/export/{format}
	 * v2 export (same files as /api/admin/datadeposit). No longer reads dd_study.
	 */
	public function export_get($format = null, $id = null)
	{
		if (is_numeric($format) && $id !== null && !is_numeric($id)) {
			$tmp = $format;
			$format = $id;
			$id = $tmp;
		}

		try {
			if (!is_numeric($id)) {
				throw new Exception('Project was not found');
			}

			$user = $this->api_user();
			$actor = ($user && isset($user->email)) ? (string) $user->email : '';
			$file = $this->deposit_depositor->admin_export_project($id, $format, $actor);
			$this->load->helper('download');
			force_download($file['filename'], $file['body']);
		} catch (Deposit_depositor_exception $e) {
			$this->set_response($e->payload, $e->http);
		} catch (Exception $e) {
			$this->set_response(array(
				'status' => 'error',
				'message' => $e->getMessage(),
			), REST_Controller::HTTP_BAD_REQUEST);
		}
	}
}
