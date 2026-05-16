<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Collections extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->load->model("Repository_model");
		$this->lang->load('collection');
		$this->is_authenticated_or_die();
	}

	/**
	 * 
	 * 
	 * Get all Collections
	 * 
	 * 
	 */
	function index_get($repo_id=null)
	{			
		if($repo_id){
			return $this->single_get($repo_id);
		}

		$published=$this->input->get("published");

		try{
			$this->has_access($resource_='collection',$privilege='view');

			if (is_numeric($published) && ($published==0 || $published==1)){
				$repos=$this->Repository_model->select_all($published, true);
			}else{
				$repos=$this->Repository_model->select_all(null, true);
			}

			$output=array();
			$fields=array(
				'id'=>'id',
				'repositoryid'=>'repositoryid',
				'title'=>'title',
				'thumbnail'=>'thumbnail',
				'short_text'=>'short_text',
				'long_text'=>'long_text',
				'ispublished'=>'ispublished',
				'weight'=>'weight',
				'section'=>'section',
				'study_count'=>'study_count',
				'section_title'=>'section_title'
			);

			foreach($repos as $row){
				$tmp=array();
				foreach($fields as $idx=>$name){
					$tmp[$name]=$row[$idx];
				}

				$output[]=$tmp;
			}

			$response=array(
				'status'=>'success',
				'total'=>count($repos),
				'collections'=>$output
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
	 * Create new collection
	 * 
	 * 
	 */
	function index_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();

			if (isset($options['long_text'])) {
				$options['long_text'] = $this->Repository_model->sanitize_collection_long_text($options['long_text']);
			}
			
			//validate
			$this->Repository_model->validate($options);

			$upload_result=null;

			if(!empty($_FILES)){
				//upload file?
				$upload_result=$this->Repository_model->upload_thumbnail('thumbnail');
				//set path to uploaded file
				$options['thumbnail']=$upload_result['rel_path'];
			}

			$collection=$this->Repository_model->insert($options);

			if(!$collection){
				throw new Exception("FAILED_TO_CREATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($options['repositoryid']),
				'upload'=>$upload_result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * Update collection
	 * 
	 * 
	 */
	function update_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();

			if(!isset($options['repositoryid'])){
				throw new Exception("parameter `repositoryid` is missing");
			}

			$posted_long = array_key_exists('long_text', $options);

			$repository=$this->Repository_model->get_repository_by_repositoryid($options['repositoryid']);
			
			if(!$repository){
				throw new Exception("Repository not found:: " .$options['repositoryid']);
			}

			$options=array_merge($repository,$options);

			if ($posted_long && isset($options['long_text'])) {
				$options['long_text'] = $this->Repository_model->sanitize_collection_long_text($options['long_text']);
			}

			//validate
			$this->Repository_model->validate($options);

			$upload_result=null;

			if(!empty($_FILES)){
				//upload file?
				$upload_result=$this->Repository_model->upload_thumbnail('thumbnail');
				//set path to uploaded file
				$options['thumbnail']=$upload_result['rel_path'];
			}

			$collection=$this->Repository_model->update($options['id'],$options);

			if(!$collection){
				throw new Exception("FAILED_TO_UPDATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($repository['id']),
				'upload'=>$upload_result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors(),
				'options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * Rename collection ID
	 * 
	 * 
	 */
	function rename_post()
	{
		//multipart/form-data
		$options=$this->input->post(null, true);

		//raw json input
		if (empty($options)){
			$options=$this->raw_json_input();
		}

		try{
			$this->has_access($resource_='collection',$privilege='edit');
			$user_id=$this->get_api_user_id();
			
			if(!isset($options['old_repositoryid'])){
				throw new Exception("parameter `old_repositoryid` is missing");
			}

			if(!isset($options['new_repositoryid'])){
				throw new Exception("parameter `new_repositoryid` is missing");
			}

			$repository=$this->Repository_model->get_repository_by_repositoryid($options['old_repositoryid']);

			if(!$repository){
				throw new Exception("Repository not found:: " .$options['repositoryid']);
			}

			//set repositoryid to new id
			$repository['repositoryid']=$options['new_repositoryid'];			

			//validate
			$this->Repository_model->validate($repository);

			$result=$this->Repository_model->rename_repository($options['old_repositoryid'],$options['new_repositoryid']);

			if(!$result){
				throw new Exception("FAILED_TO_UPDATE_COLLECTION");
			}

			$response=array(
				'status'=>'success',
				'collection'=>$this->Repository_model->select_single($options['new_repositoryid'])
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>(array)$e->GetValidationErrors(),
				'options'=>$options
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
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
	 * Get a single collection
	 * 
	 */
	function single_get($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='view');
			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$repo=array(
				'id'=>$repo['id'],
				'repositoryid'=>$repo['repositoryid'],
				'title'=>$repo['title'],
				'short_text'=>$repo['short_text'],
				'long_text'=>$repo['long_text'],
				'thumbnail'=>$repo['thumbnail'],
				'weight'=>$repo['weight'],
				'ispublished'=>$repo['ispublished']
			);

			$response=array(
				'status'=>'success',
				'collection'=>$repo,
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
	 * Get catalog entries by collection
	 * 
	 */
	function datasets_get($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='view');

			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$datasets=$this->Repository_model->get_all_repo_studies($repo_id);
			$sid_arr=array_values(array_column($datasets,'id'));
			$linked_collections=$this->Repository_model->linked_repos_by_studies($sid_arr);

			foreach($datasets as $idx=>$row){
				if(isset($linked_collections[$row['id']])){
					$datasets[$idx]['linked_collections']=$linked_collections[$row['id']];
				}
			}

			$response=array(
				'status'=>'success',
				'total'=>count($datasets),
				'datasets'=>$datasets
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
	 * Delete collection
	 * 
	 * 
	 */	
	function delete_delete($repo_id=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='delete');

			if(!($repo_id)){
				throw new Exception("MISSING_PARAM: repositoryId");
			}			
			
			$repo=$this->Repository_model->get_repository_by_repositoryid($repo_id);
			
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$this->Repository_model->delete($repo['id']);

			$response=array(
				'status'=>'success'				
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

	function index_delete($repo_id=null)
	{
		return $this->delete_delete($repo_id);
	}

	function delete_post($repo_id=null)
	{
		return $this->delete_delete($repo_id);
	}

	
	/**
	 * GET {apiBase}history/{repo_id} — return studies in a collection
	 */
	function history_get($repositoryid=null)
	{
		try{
			$this->has_access($resource_='collection',$privilege='view');

			if(!$repositoryid){
				throw new Exception("MISSING_PARAM: repositoryid");
			}

			$repo=$this->Repository_model->get_repository_by_repositoryid($repositoryid);
			if(!$repo){
				throw new Exception("REPOSITORY-NOT-FOUND");
			}

			$page   = max(1, (int)$this->input->get('page'));
			$ps     = min(200, max(1, (int)($this->input->get('ps') ?: 25)));
			$offset = ($page - 1) * $ps;

			$total  = $this->Repository_model->repo_survey_count($repositoryid);
			$rows   = $this->Repository_model->repo_survey_list($repositoryid, NULL, $ps, $offset);

			$this->set_response(array(
				'status'     => 'success',
				'total'      => $total,
				'page'       => $page,
				'page_size'  => $ps,
				'pages'      => $total > 0 ? (int)ceil($total / $ps) : 1,
				'collection' => array('repositoryid'=>$repo['repositoryid'],'title'=>$repo['title']),
				'rows'       => $rows,
			), REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$this->set_response(array('status'=>'failed','message'=>$e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * GET {apiBase}sections — return all repository sections for dropdowns
	 */
	function sections_get()
	{
		try{
			$user = $this->api_user();
			if (! $user) {
				$this->set_response(array('status' => 'failed', 'message' => 'AUTH_REQUIRED'), REST_Controller::HTTP_UNAUTHORIZED);
				return;
			}
			$this->has_access($resource_='collection',$privilege='view');
			$sections=$this->Repository_model->get_repository_sections();
			$this->set_response(array('status'=>'success','sections'=>$sections), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS_DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch(Exception $e){
			$this->set_response(array('status'=>'failed','message'=>$e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * GET {apiBase}repository_acl/{repository_pk}
	 *
	 * Optional query: user_q (min 2 chars) — username/email search for the add-user bar.
	 */
	function repository_acl_get($repository_pk = null)
	{
		try {
			$this->has_access('user', 'edit');

			if ($repository_pk === null || $repository_pk === '') {
				throw new Exception('MISSING_PARAM: repository_pk');
			}

			$user_q = trim((string) $this->input->get('user_q'));
			$payload = $this->_repository_acl_payload((int) $repository_pk, $user_q);

			$this->set_response($payload, REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * POST {apiBase}repository_acl/{repository_pk}
	 *
	 * JSON: { "user_id": int, "permissions": ["study_view", ...] }
	 */
	function repository_acl_post($repository_pk = null)
	{
		$options = $this->input->post(null, true);
		if (empty($options)) {
			$options = $this->raw_json_input();
		}
		if (is_object($options)) {
			$options = (array) $options;
		}
		if ( ! is_array($options)) {
			$options = array();
		}

		try {
			$this->has_access('user', 'edit');

			if ($repository_pk === null || $repository_pk === '') {
				throw new Exception('MISSING_PARAM: repository_pk');
			}

			$pk = (int) $repository_pk;

			$user_id = isset($options['user_id']) ? (int) $options['user_id'] : 0;
			if ($user_id < 1) {
				throw new Exception('INVALID_USER_ID');
			}

			$perms = isset($options['permissions']) && is_array($options['permissions']) ? $options['permissions'] : array();

			$this->acl_manager->repositories_acl_replace_user_repository_managed_grants($user_id, $pk, $perms);

			$this->set_response($this->_repository_acl_payload($pk, ''), REST_Controller::HTTP_OK);
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			$this->set_response(array('status' => 'failed', 'message' => 'ACCESS-DENIED'), REST_Controller::HTTP_FORBIDDEN);
		}
		catch (Exception $e) {
			$this->set_response(array('status' => 'failed', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
		}
	}


	/**
	 * @param int    $pk repositories.id (0 = central)
	 * @param string $user_q optional search string for user autocomplete
	 * @return array
	 */
	private function _repository_acl_payload($pk, $user_q = '')
	{
		if ($pk === 0) {
			$repo = $this->Repository_model->get_central_catalog_array();
		} else {
			$repo = $this->Repository_model->select_single($pk);
		}

		if (empty($repo) || empty($repo['repositoryid'])) {
			throw new Exception('REPOSITORY-NOT-FOUND');
		}

		$white_list   = $this->acl_manager->get_manageable_repositories_acl_permission_whitelist_map();
		$managed_keys = array_keys($white_list);

		$this->db->select('u.id, u.username, u.email, ra.permission');
		$this->db->from('repositories_acl ra');
		$this->db->join('users u', 'u.id = ra.user_id', 'inner');
		$this->db->where('ra.repository_id', $pk);
		$this->db->where_in('ra.permission', $managed_keys);
		$acl_rows = $this->db->get()->result_array();

		$users_map = array();
		foreach ($acl_rows as $row) {
			$uid = (int) $row['id'];
			if ( ! isset($users_map[$uid])) {
				$users_map[$uid] = array(
					'user_id'     => $uid,
					'username'    => $row['username'],
					'email'       => isset($row['email']) ? $row['email'] : '',
					'permissions' => array(),
				);
			}
			$users_map[$uid]['permissions'][] = $row['permission'];
		}
		foreach ($users_map as $uid => $u) {
			$users_map[$uid]['permissions'] = array_values(array_unique($u['permissions']));
		}

		$user_search = array();
		if (strlen($user_q) >= 2) {
			$this->db->select('id, username, email');
			$this->db->from('users');
			$this->db->group_start();
			$this->db->like('username', $user_q);
			$this->db->or_like('email', $user_q);
			$this->db->group_end();
			$this->db->order_by('username', 'ASC');
			$this->db->limit(40);
			$user_search = $this->db->get()->result_array();
		}

		return array(
			'status'               => 'success',
			'repository'           => array(
				'id'           => isset($repo['id']) ? (int) $repo['id'] : $pk,
				'repositoryid' => $repo['repositoryid'],
				'title'        => isset($repo['title']) ? $repo['title'] : $repo['repositoryid'],
			),
			'study_permissions'    => $this->acl_manager->get_manageable_study_repositories_acl_rows(),
			'licensed_permissions' => $this->acl_manager->get_manageable_licensed_request_repositories_acl_rows(),
			'users'                => array_values($users_map),
			'user_search'          => $user_search,
		);
	}


	//override authentication to support both session authentication + api keys
	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}
	
}
