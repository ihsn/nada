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
	 * Files for one published study that this caller is allowed to download.
	 *
	 * GET params:
	 *  - type: Resource type filter - Category (microdata, data, documentation, doc) or specific code (doc/qst, dat/micro, tbl, etc.)
	 */
	function files_get($idno=null)
	{
		try{
			if (!$idno) {
				throw new Exception('IDNO is required');
			}

			$sid=$this->get_sid_from_idno($idno);
			$this->require_published_study($sid);

			$type = $this->get('type');
			$user = $this->api_user();
			$files = $this->downloads_service->list_downloads($sid, $type);
			$files = array_values(array_filter($files, function ($file) use ($user, $sid) {
				return $this->caller_may_see_download($user, $sid, $file);
			}));

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

			$this->require_published_study($sid);

			$resource = $this->downloads_service->get_resource_info($sid, $resource_id, $formatted=true);
			$user = $this->api_user();

			if (!$resource || !$this->caller_may_see_download($user, $sid, $resource[0])) {
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

            $this->require_published_study($sid);

            $user = $this->api_user();
			$resource = $this->downloads_service->get_resource_info($sid, $resource_id);
			
			if (!$resource) {
				throw new Exception('Resource not found');
			}

            //login required for - public, licensed
            if (in_array($resource['data_access_type'], array('public', 'licensed')) && !$user) {                
                throw new Exception('LOGIN_REQUIRED');
            }
		
		    $allow_download = $this->Survey_resource_model->user_has_download_access($user ? $user->id : false, $sid, $resource);

			if ($allow_download === false) {
				throw new Exception("You don't have permissions to access the file.");
			}

			if ($this->is_public_microdata($resource)) {
				$this->load->model('Public_model');
				if (!$user || !$this->Public_model->check_user_has_data_access($user->id, $sid)) {
					throw new Exception('TERMS_NOT_ACCEPTED');
				}
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


	private function require_published_study($sid)
	{
		$survey = $this->Dataset_model->get_row($sid);
		if (!$survey || (int) $survey['published'] !== 1) {
			throw new Exception('Resource not found');
		}
	}


	/**
	 * List and info show a file only when this caller could download it.
	 * Documentation is included. Microdata requires open/direct access, accepted
	 * public-use terms, or an approved licensed request for that file.
	 */
	private function caller_may_see_download($user, $survey_id, $file)
	{
		$dctype = isset($file['dctype']) ? $file['dctype'] : '';
		if (!$this->Survey_resource_model->is_microdata_resource($dctype)) {
			return true;
		}

		$access = isset($file['data_access_type']) ? strtolower(trim($file['data_access_type'])) : '';
		$user_id = ($user && isset($user->id)) ? $user->id : false;

		if ($access === 'open' || $access === 'direct') {
			return true;
		}

		if ($access === 'public') {
			if (!$user_id) {
				return false;
			}
			$this->load->model('Public_model');
			return (bool) $this->Public_model->check_user_has_data_access($user_id, $survey_id);
		}

		if ($access === 'licensed') {
			if (!$user_id) {
				return false;
			}
			return $this->has_approved_licensed_download($user_id, $file['resource_id']);
		}

		return false;
	}


	private function is_public_microdata($resource)
	{
		$access = isset($resource['data_access_type']) ? strtolower(trim($resource['data_access_type'])) : '';
		$dctype = isset($resource['dctype']) ? $resource['dctype'] : '';
		return $access === 'public' && $this->Survey_resource_model->is_microdata_resource($dctype);
	}


	private function has_approved_licensed_download($user_id, $resource_id)
	{
		$this->load->model('Licensed_model');
		$requests = $this->Licensed_model->get_requests_by_file($resource_id, $user_id);
		foreach ($requests as $req) {
			if (strtoupper(trim($req['status'])) == 'APPROVED'
				&& $req['expiry'] > date('U')
				&& (int) $req['downloads'] < (int) $req['download_limit']) {
				return true;
			}
		}
		return false;
	}

}

