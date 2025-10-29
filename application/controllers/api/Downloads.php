<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Downloads extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model('Dataset_model');
		$this->load->model("Survey_resource_model");
		$this->load->library("Dataset_manager");
		$this->load->library("Downloads_service");
	}


	/**
	 * 
	 * 
	 * Search downloadable files across all studies
	 * 
	 * GET params:
	 *  - limit: Number of results per page (default: 15)
	 *  - offset: Starting position (default: 0)
	 *  - type: Resource type filter - Category (microdata, data, documentation, doc) or specific code (doc/qst, dat/micro, tbl, aud, vid, etc.)
	 *  - countries: Country filter (array or single value)
	 *  - years: Year filter (array or single value)
	 *  - data_access_type: Access type filter
	 *  - idno: Study IDNO filter
	 *  - sort_by: Sort field
	 *  - sort_order: Sort order (asc/desc)
	 * 
	 */
	function index_get($idno=null,$resource_id=null)
	{	
		try{
		$limit = $this->get('limit') ? (int)$this->get('limit') : 15;
		$offset = $this->get('offset') ? (int)$this->get('offset') : 0;
		
		$filters = array();
		
		if ($this->get('countries')) {
			$filters['countries'] = $this->get('countries');
		}
		
		if ($this->get('years')) {
			$filters['years'] = $this->get('years');
		}
		
		if ($this->get('data_access_type')) {
			$filters['data_access_type'] = $this->get('data_access_type');
		}
		
		if ($this->get('type')) {
			$filters['type'] = $this->get('type');
		}
		
		if ($idno) {
			$filters['idno'] = $idno;
		} elseif ($this->get('idno')) {
			$filters['idno'] = $this->get('idno');
		}
			
			$sort_by = $this->get('sort_by') ? $this->get('sort_by') : '';
			$sort_order = $this->get('sort_order') ? $this->get('sort_order') : 'asc';
			
			$results = $this->downloads_service->search($limit, $offset, $filters, $sort_by, $sort_order);
			
			$response = array(
				'status' => 'success',
				'data' => $results
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
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
	 * Get a list of downloadable files by study IDNO
	 * 
	 * GET params:
	 *  - type: Resource type filter - Category (microdata, data, documentation, doc) or specific code (doc/qst, dat/micro, tbl, etc.)
	 * 
	 */
	function files_get($idno=null)
	{
		try{
		if (!$idno) {
			throw new Exception('IDNO is required');
		}

		$sid=$this->get_sid_from_idno($idno);

        
		$type = $this->get('type');

		$files = $this->downloads_service->list_downloads($sid, $type);

		$response = array(
			'status' => 'success',
			'files' => $files
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
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
	 * Get information for a single downloadable file
	 * 
	 * 
	 */
	function info_get($idno=null, $resource_id=null)
	{
		try{
			$sid=$this->get_sid_from_idno($idno);

			if (!$sid) {
				throw new Exception('Survey ID is required');
			}

			if (!$resource_id) {
				throw new Exception('Resource ID is required');
			}

			$resource = $this->downloads_service->get_resource_info($sid, $resource_id, $formatted=true);
			
			if (!$resource) {
				throw new Exception('Resource not found');
			}

            $response = array(
                'status' => 'success',
                'resource' => $resource
            );

			$this->set_response($response, REST_Controller::HTTP_OK);
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
	 * Download a file by survey ID and resource ID
	 * 
	 * 
	 */
	function download_get($idno=null, $resource_id=null)
	{
		try{
            $sid=$this->get_sid_from_idno($idno);

			if (!$resource_id) {
				throw new Exception('Resource ID is required');
			}

            $user = $this->api_user();
			$resource = $this->downloads_service->get_resource_info($sid, $resource_id);
			
			if (!$resource) {
				throw new Exception('Resource not found');
			}

            //login required for - public, licensed
            if (in_array($resource['data_access_type'], array('public', 'licensed')) && !$user) {                
                throw new Exception('LOGIN_REQUIRED');
            }
		
		    $allow_download = $this->Survey_resource_model->user_has_download_access($user->id, $sid, $resource, $skip_puf=true);

			if ($allow_download === false) {
				throw new Exception("You don't have permissions to access the file.");
			}

			$this->Survey_resource_model->download($user, $sid, $resource_id);
			die();
		}
		catch(Exception $e){
			$error_output = array(
				'status' => 'failed',
				'errors' => $e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	
	
	function _auth_override_check()
	{
		return true;
	}

}

