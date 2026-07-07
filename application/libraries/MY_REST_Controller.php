<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

/**
 * 
 * Extends REST_CONTROLLER
 * 
 */
abstract class MY_REST_Controller extends REST_Controller {
    
    /**
     * 
     * Allow only admin users to access the API
     * 
     */
    public function is_admin_or_die()
    {
        try{
            if (!$this->is_admin()){
                throw new Exception("ACCESS-DENIED");
            }
        }   
        catch(Exception $e){
            $this->access_denied_response();
		}
    }


    /**
     * 
     * Allow only admin users to access the API
     * 
     */
    public function is_authenticated_or_die()
    {
        if(!$this->get_api_user_id()){
            $response=array(
                'status'=>'ACCESS-DENIED',
                'message'=>'Access denied'
            );
            $this->response($response, REST_Controller::HTTP_BAD_REQUEST,false);
            die();
        }
    }

    

    /**
     * Check if logged in user has admin rights
     */
    public function is_admin()
    {
        return $this->ion_auth->is_admin($this->get_api_user_id());
    }
    

    /**
     * 
     * Return user info
     * 
     */
    public function api_user()
    {
        if(isset($this->_apiuser) && isset($this->_apiuser->user_id)){
			return $this->ion_auth->get_user($this->_apiuser->user_id);
		}

        //session user id
		if ($this->session->userdata('user_id')){
			return $this->ion_auth->get_user($this->session->userdata('user_id'));
		}

		return false;
    }


    /**
     * 
     * Get logged in API user ID
     * 
     */
    public function get_api_user_id()
	{
        //session user id
		if ($this->session->userdata('user_id')){
			return $this->session->userdata('user_id');
		}

		// Check _apiuser (set by REST_Controller::_detect_api_key())
		if(isset($this->_apiuser) && isset($this->_apiuser->user_id)){
			return $this->_apiuser->user_id;
		}
		
		// Fallback: Check rest->user_id (also set by _detect_api_key())
		if(isset($this->rest->user_id) && $this->rest->user_id){
			return $this->rest->user_id;
		}

        // Some admin API controllers can skip early API-key detection due to auth overrides.
        // Attempt on-demand key detection only for /api/admin controllers to avoid broad side effects.
        $router_dir = isset($this->router->directory) ? strtolower((string) $this->router->directory) : '';
        if (strpos($router_dir, 'api/admin') === 0 && $this->_detect_api_key() === TRUE) {
            if (isset($this->_apiuser) && isset($this->_apiuser->user_id)) {
                return $this->_apiuser->user_id;
            }

            if (isset($this->rest->user_id) && $this->rest->user_id) {
                return $this->rest->user_id;
            }
        }

		return false;
    }



	/**
     * 
     * 
     * return raw json input
     * 
     **/
	public function raw_json_input()
	{
		$data=$this->input->raw_input_stream;				
		//$data = file_get_contents("php://input");

		if(!$data || trim($data)==""){
			return null;
		}
		
		$json=json_decode($data,true);

		if (!$json){
			throw new Exception("INVALID_JSON_INPUT");
		}

		return $json;
	}



    /**
	 * 
	 * 
	 * Return ID by IDNO
	 * 
	 * 
	 * @idno 		- ID | IDNO
	 * @id_format 	- ID | IDNO
	 * 
	 * Note: to use ID instead of IDNO, must pass id_format in querystring
	 * 
	 */
	public function get_sid_from_idno($idno=null)
	{		
		if(!$idno){
			throw new Exception("IDNO-NOT-PROVIDED");
		}

		$id_format=$this->input->get("id_format");

		if ($id_format=='id'){
			$s = is_scalar($idno) ? trim((string) $idno) : '';
			if ($s === '' || ! ctype_digit($s) || (int) $s <= 0) {
				throw new Exception('INVALID_ID_FORMAT_ID');
			}
			return (int) $s;
		}

        $this->load->library("Dataset_manager");
		$sid=$this->dataset_manager->find_by_idno($idno);

		if(!$sid){
			throw new Exception("IDNO-NOT-FOUND");
		}

		return $sid;
	}


    public function early_checks()
    {
        //disable API?
        if ($this->config->item('api_disabled')==true){
            show_404();
        }

        //apply ip whitelisting
        if ($this->config->item('rest_ip_whitelist_enabled') === TRUE)
        {
            $this->_check_whitelist_auth();
        }

    }

    function has_access($resource, $privilege, $repositoryid = null, $study_id = null)
    {
        return $this->acl_manager->check_access(
            $resource,
            $privilege,
            $this->get_api_user_id(),
            $study_id,
            $repositoryid
        );
    }

    /**
     * Enforce ACL; respond with 403 JSON and exit on denial.
     *
     * @param string      $resource
     * @param string      $privilege
     * @param string|null $repositoryid catalog slug
     * @param int|null    $study_id     repo resolved from study when repositoryid omitted
     */
    protected function require_access($resource, $privilege, $repositoryid = null, $study_id = null)
    {
        try {
            $this->acl_manager->check_access(
                $resource,
                $privilege,
                $this->get_api_user_id(),
                $study_id,
                $repositoryid
            );
        }
        catch (AclAccessDeniedException $e) {
            $this->access_denied_response();
        }
    }

    /**
     * Enforce study ACL (see Acl_manager::check_study_access).
     *
     * @param string      $privilege
     * @param int|null    $sid
     * @param string|null $repositoryid
     */
    protected function require_study_access($privilege, $sid = null, $repositoryid = null)
    {
        try {
            $this->acl_manager->check_study_access(
                $privilege,
                $this->get_api_user_id(),
                $sid,
                $repositoryid
            );
        }
        catch (AclAccessDeniedException $e) {
            $this->access_denied_response();
        }
    }

    /**
     * Analytics API ACL (see Acl_manager::check_analytics_access).
     *
     * @param int|string|null $study_id           numeric sid or idno; empty for site-wide endpoints
     * @param bool            $allow_study_scope  false for writes / site-admin-only operations
     */
    protected function require_analytics_access($study_id = null, $allow_study_scope = true)
    {
        try {
            $sid = null;
            if ($study_id !== null && $study_id !== '') {
                $sid = is_numeric($study_id) ? (int) $study_id : $this->get_sid_from_idno($study_id);
            }
            $this->acl_manager->check_analytics_access(
                $this->get_api_user_id(),
                $sid,
                $allow_study_scope
            );
        }
        catch (AclAccessDeniedException $e) {
            $this->access_denied_response();
        }
    }

    /**
     * Standard 403 for ACL denial on admin APIs (matches is_admin_or_die JSON shape).
     */
    protected function access_denied_response()
    {
        $this->response(
            array(
                'status'  => 'failed',
                'message' => 'ACCESS-DENIED',
            ),
            REST_Controller::HTTP_FORBIDDEN,
            false
        );
        exit;
    }

    function has_dataset_access($privilege, $sid = null, $repositoryid = null)
    {
        return $this->acl_manager->check_study_access(
            $privilege,
            $this->get_api_user_id(),
            $sid,
            $repositoryid
        );
    }

	/**
	 * Bulk resources export guard: caller must have study view on every idno (used by download_links_post).
	 *
	 * @param array $idno_list Study idnos (or numeric ids when request uses id_format=id consistently).
	 * @throws Exception                 empty or invalid list
	 * @throws AclAccessDeniedException  view denied on any study
	 */
	protected function assert_idno_list_has_dataset_view(array $idno_list)
	{
		if (count($idno_list) === 0) {
			throw new Exception('idno_list must be a non-empty array');
		}
		$study_ids = array();
		foreach ($idno_list as $idno) {
			if ($idno === null || $idno === '') {
				continue;
			}
			$study_ids[] = $this->get_sid_from_idno($idno);
		}
		$this->acl_manager->assert_study_view_on_study_ids($study_ids, $this->get_api_user_id());
	}


    /**
     * Parse optional featured flag from study options JSON (`featured`: bool|int|string).
     *
     * @param array $input Decoded request body
     * @return int|null     1 or 0 when client requests a change; null when key absent or null
     * @throws Exception    INVALID_FEATURED_VALUE
     */
    protected function featured_option_from_input(array $input)
    {
        if (!array_key_exists('featured', $input)) {
            return null;
        }
        $raw = $input['featured'];
        if ($raw === null) {
            return null;
        }
        if (is_bool($raw)) {
            return $raw ? 1 : 0;
        }
        if (is_int($raw) || is_float($raw)) {
            return ((int) $raw) !== 0 ? 1 : 0;
        }
        if (is_string($raw)) {
            $lr = strtolower(trim($raw));
            if (in_array($lr, array('1', 'true', 'yes', 'on'), true)) {
                return 1;
            }
            if (in_array($lr, array('0', 'false', 'no', 'off', ''), true)) {
                return 0;
            }
        }
        throw new Exception('INVALID_FEATURED_VALUE');
    }

    public function _auth_override_check()
    {
        if ($this->session->userdata('user_id')){
            return TRUE;
        }

        $result = parent::_auth_override_check();
        return $result === TRUE;
    }

}