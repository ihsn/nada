<?php
class Users extends MY_Controller {

	var $errors='';
	var $search_fields=array('username','email','status');
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );		
       	$this->load->model('User_model');
		
		//language files
		$this->lang->load('general');
		$this->lang->load('users');
		
		//set template to admin
		$this->template->set_template('admin5');
		
		//$this->output->enable_profiler(TRUE);
		$this->disable_page_cache();
	}

	/**
	 * @return list<int>
	 */
	private function role_ids_from_post()
	{
		$roles = $this->input->post('role');
		if ($roles === null || $roles === '') {
			return array();
		}
		if (!is_array($roles)) {
			$roles = array($roles);
		}

		$ids = array();
		foreach ($roles as $role_id) {
			if ($role_id !== '' && $role_id !== null && is_numeric($role_id)) {
				$ids[] = (int) $role_id;
			}
		}

		return $ids;
	}
	
	//expire page immediately
    private function disable_page_cache()
    {	
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
    }
	
	function index()
	{
		$this->acl_manager->has_access_or_die('user', 'view');

		$result['rows'] = $this->_search();

		$user_id_arr = array();
		foreach ($result['rows'] as $row) {
			$user_id_arr[] = $row['id'];
		}

		$result['user_groups'] = $this->User_model->get_user_roles($user_id_arr);
		$result['user_collections'] = $this->acl_manager->repositories_acl_collections_for_users($user_id_arr);
		$result['api_key_counts'] = $this->_get_api_key_counts($user_id_arr);
		$result['show_permissions_link'] = $this->acl_manager->user_is_admin();
		$result['roles'] = $this->acl_manager->get_roles();
		$result['can_bulk_edit'] = $this->acl_manager->user_has_access('user', 'edit');
		$result['can_bulk_delete'] = $this->acl_manager->user_has_access('user', 'delete');

		$content=$this->load->view('users/index', $result,true);
		$this->template->write('content', $content,true);
		$this->template->write('title', t('title_user_management'),true);
	  	$this->template->render();	
	}
	
	/**
	 * Get API key counts for multiple users
	 */
	private function _get_api_key_counts($user_ids)
	{
		if (empty($user_ids)) {
			return array();
		}
		
		$this->db->select('user_id, COUNT(*) as key_count');
		$this->db->from('api_keys');
		$this->db->where_in('user_id', $user_ids);
		$this->db->where('revoked_at', NULL);
		$this->db->group_by('user_id');
		$query = $this->db->get();
		
		$counts = array();
		if ($query) {
			foreach ($query->result() as $row) {
				$counts[$row->user_id] = (int)$row->key_count;
			}
		}
		
		return $counts;
	}
	
	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 * @return string
	 * @author IHSN
	 **/
	function _search()
	{
		$per_page = 15;
		$offset = $this->input->get('offset');
		$sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by = $this->input->get('sort_by') ? $this->input->get('sort_by') : 'username';

		$filter = array();
		$filter_count = 0;

		if ($this->input->get_post('keywords')) {
			$filter[$filter_count]['field'] = $this->input->get_post('field');
			$filter[$filter_count]['keywords'] = $this->input->get_post('keywords');
			$filter_count++;
		}

		$status_filter = $this->input->get('status_filter');
		if ($status_filter !== null && $status_filter !== '') {
			$filter[$filter_count]['field'] = 'status';
			$filter[$filter_count]['value'] = $status_filter;
			$filter_count++;
		}

		if ($this->input->get('last_login_filter')) {
			$filter[$filter_count]['field'] = 'last_login';
			$filter[$filter_count]['operator'] = $this->_get_login_filter_operator($this->input->get('last_login_filter'));
			$filter[$filter_count]['value'] = $this->_get_login_filter_value($this->input->get('last_login_filter'));
			$filter_count++;
		}

		$collection_access = $this->input->get('collection_access');
		if ($collection_access === 'has' || $collection_access === 'none') {
			$filter[$filter_count]['field'] = 'collection_access';
			$filter[$filter_count]['value'] = $collection_access;
			$filter[$filter_count]['managed_keys'] = array_keys($this->acl_manager->get_manageable_repositories_acl_permission_whitelist_map());
			$filter_count++;
		}

		$api_keys_filter = $this->input->get('api_keys_filter');
		if ($api_keys_filter === 'has' || $api_keys_filter === 'none') {
			$filter[$filter_count]['field'] = 'api_keys';
			$filter[$filter_count]['value'] = $api_keys_filter;
			$filter_count++;
		}

		$role_filter = (int) $this->input->get('role_filter');

		if ($this->input->get('user_group')) {
			$rows = $this->User_model->get_users_by_group((int) $this->input->get('user_group'), $per_page, $offset, $filter, $sort_by, $sort_order);
			$total = $this->User_model->search_count($filter);
		}
		elseif ($role_filter > 0) {
			$rows = $this->User_model->get_users_by_role($role_filter, $per_page, $offset, $filter, $sort_by, $sort_order);
			$total = $this->User_model->get_users_by_role_count($role_filter, $filter);
		}
		else {
			$rows = $this->User_model->search($per_page, $offset, $filter, $sort_by, $sort_order);
			$total = $this->User_model->search_count($filter);
		}

		if ($offset > $total) {
			$offset = max(0, $total - $per_page);

			if ($this->input->get('user_group')) {
				$rows = $this->User_model->get_users_by_group((int) $this->input->get('user_group'), $per_page, $offset, $filter, $sort_by, $sort_order);
			}
			elseif ($role_filter > 0) {
				$rows = $this->User_model->get_users_by_role($role_filter, $per_page, $offset, $filter, $sort_by, $sort_order);
			}
			else {
				$rows = $this->User_model->search($per_page, $offset, $filter, $sort_by, $sort_order);
			}
		}

		$base_url = site_url('admin/users');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment'] = 'offset';
		$config['page_query_string'] = true;
		$config['additional_querystring'] = get_querystring(array(
			'keywords', 'field', 'sort_by', 'sort_order',
			'status_filter', 'role_filter', 'collection_access', 'api_keys_filter', 'last_login_filter',
		));
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">';
		$config['full_tag_close'] = '</span>';

		$this->pagination->initialize($config);
		return $rows;
	}

	/**
	 * @param mixed $role_ids
	 * @return int[]
	 */
	private function _filter_assignable_role_ids($role_ids)
	{
		if ( ! is_array($role_ids)) {
			return array();
		}

		$allowed = array();
		foreach ($this->acl_manager->get_roles() as $role) {
			if ( ! $this->acl_manager->user_is_admin() && ! empty($role['is_admin'])) {
				continue;
			}
			$allowed[(int) $role['id']] = true;
		}

		$out = array();
		foreach ($role_ids as $role_id) {
			$role_id = (int) $role_id;
			if ($role_id > 0 && isset($allowed[$role_id])) {
				$out[] = $role_id;
			}
		}

		return $out;
	}

	function bulk_action()
	{
		$this->acl_manager->has_access_or_die('user', 'edit');

		$action = $this->input->post('action');
		$user_ids = json_decode($this->input->post('user_ids'), true);

		if (empty($user_ids) || ! is_array($user_ids)) {
			echo json_encode(array('success' => false, 'message' => t('please_select_users')));
			return;
		}

		$result = false;
		$message = '';

		switch ($action) {
			case 'delete':
				if ( ! $this->acl_manager->user_has_access('user', 'delete')) {
					echo json_encode(array('success' => false, 'message' => t('ACCESS_DENIED')));
					return;
				}
				$result = $this->_bulk_delete($user_ids);
				$message = $result ? t('users_deleted_successfully') : t('operation_failed');
				break;

			case 'activate':
				$result = $this->_bulk_activate($user_ids, 1);
				$message = $result ? t('users_activated_successfully') : t('operation_failed');
				break;

			case 'deactivate':
				$result = $this->_bulk_activate($user_ids, 0);
				$message = $result ? t('users_deactivated_successfully') : t('operation_failed');
				break;

			default:
				$message = t('operation_failed');
		}

		echo json_encode(array('success' => $result, 'message' => $message));
	}

	private function _bulk_delete($user_ids)
	{
		$success_count = 0;

		foreach ($user_ids as $user_id) {
			if (is_numeric($user_id) && $this->User_model->delete($user_id)) {
				$success_count++;
			}
		}

		return $success_count > 0;
	}

	private function _bulk_activate($user_ids, $status)
	{
		$success_count = 0;

		foreach ($user_ids as $user_id) {
			if (is_numeric($user_id) && $this->_update_user_status($user_id, $status)) {
				$success_count++;
			}
		}

		return $success_count > 0;
	}

	private function _update_user_status($user_id, $status)
	{
		$data = array('active' => (int) $status);
		if ((int) $status === 1) {
			$data['activation_code'] = '';
		}
		$this->db->where('id', (int) $user_id);
		return $this->db->update('users', $data);
	}

	function bulk_assign_roles()
	{
		$this->acl_manager->has_access_or_die('user', 'edit');

		$user_ids = $this->input->get('user_ids');
		if (empty($user_ids)) {
			$this->session->set_flashdata('error', t('please_select_users'));
			redirect('admin/users');
		}

		$user_ids_array = explode(',', $user_ids);
		$users = array();
		foreach ($user_ids_array as $user_id) {
			if (is_numeric($user_id)) {
				$user = $this->User_model->getSingle($user_id);
				if ($user && $user->num_rows() > 0) {
					$users[] = $user->row();
				}
			}
		}

		$assignable_roles = $this->acl_manager->get_roles();
		if ( ! $this->acl_manager->user_is_admin()) {
			$assignable_roles = array_values(array_filter($assignable_roles, function ($role) {
				return empty($role['is_admin']);
			}));
		}

		$data = array(
			'users'    => $users,
			'roles'    => $assignable_roles,
			'user_ids' => $user_ids,
		);

		if ($this->input->is_ajax_request()) {
			echo $this->load->view('users/bulk_assign_roles_modal', $data, true);
			return;
		}

		redirect('admin/users');
	}

	function process_bulk_assign_roles()
	{
		$this->acl_manager->has_access_or_die('user', 'edit');

		$user_ids = $this->input->post('user_ids');
		$role_ids = $this->_filter_assignable_role_ids($this->input->post('role_ids'));

		if (empty($user_ids) || empty($role_ids)) {
			$this->session->set_flashdata('error', t('please_select_users'));
			redirect('admin/users');
		}

		$user_ids_array = explode(',', $user_ids);
		$success_count = 0;

		foreach ($user_ids_array as $user_id) {
			if ( ! is_numeric($user_id)) {
				continue;
			}
			foreach ($role_ids as $role_id) {
				if ($this->acl_manager->set_user_role((int) $user_id, (int) $role_id)) {
					$success_count++;
				}
			}
		}

		if ($success_count > 0) {
			$this->session->set_flashdata('message', t('roles_assigned_successfully'));
		}
		else {
			$this->session->set_flashdata('error', t('operation_failed'));
		}

		redirect('admin/users');
	}

	function bulk_remove_roles()
	{
		$this->acl_manager->has_access_or_die('user', 'edit');

		$user_ids = $this->input->get('user_ids');
		if (empty($user_ids)) {
			$this->session->set_flashdata('error', t('please_select_users'));
			redirect('admin/users');
		}

		$user_ids_array = explode(',', $user_ids);
		$users = array();
		$user_roles = array();

		foreach ($user_ids_array as $user_id) {
			if ( ! is_numeric($user_id)) {
				continue;
			}
			$user = $this->User_model->getSingle($user_id);
			if ($user && $user->num_rows() > 0) {
				$users[] = $user->row();

				$this->db->select('ur.role_id, r.name, r.description, r.is_admin');
				$this->db->from('user_roles ur');
				$this->db->join('roles r', 'ur.role_id = r.id');
				$this->db->where('ur.user_id', (int) $user_id);
				$roles_query = $this->db->get();
				$roles = $roles_query->result_array();

				if ( ! $this->acl_manager->user_is_admin()) {
					$roles = array_values(array_filter($roles, function ($role) {
						return empty($role['is_admin']);
					}));
				}

				$user_roles[$user_id] = $roles;
			}
		}

		$data = array(
			'users'      => $users,
			'user_roles' => $user_roles,
			'user_ids'   => $user_ids,
		);

		if ($this->input->is_ajax_request()) {
			echo $this->load->view('users/bulk_remove_roles_modal', $data, true);
			return;
		}

		redirect('admin/users');
	}

	function process_bulk_remove_roles()
	{
		$this->acl_manager->has_access_or_die('user', 'edit');

		$user_ids = $this->input->post('user_ids');
		$role_ids = $this->_filter_assignable_role_ids($this->input->post('role_ids'));

		if (empty($user_ids) || empty($role_ids)) {
			$this->session->set_flashdata('error', t('please_select_users'));
			redirect('admin/users');
		}

		$user_ids_array = explode(',', $user_ids);
		$success_count = 0;

		foreach ($user_ids_array as $user_id) {
			if ( ! is_numeric($user_id)) {
				continue;
			}
			foreach ($role_ids as $role_id) {
				$this->db->where('user_id', (int) $user_id);
				$this->db->where('role_id', (int) $role_id);
				if ($this->db->delete('user_roles')) {
					$success_count++;
				}
			}
		}

		if ($success_count > 0) {
			$this->session->set_flashdata('message', t('roles_removed_successfully'));
		}
		else {
			$this->session->set_flashdata('error', t('operation_failed'));
		}

		redirect('admin/users');
	}

	private function _get_login_filter_operator($filter_value)
	{
		switch ($filter_value) {
			case 'today':
			case 'week':
			case 'month':
				return '>=';
			case 'never':
				return 'NEVER';
			default:
				return '>=';
		}
	}

	private function _get_login_filter_value($filter_value)
	{
		switch ($filter_value) {
			case 'today':
				return strtotime('today');
			case 'week':
				return strtotime('-1 week');
			case 'month':
				return strtotime('-1 month');
			case 'never':
				return null;
			default:
				return strtotime('today');
		}
	}
	
	function add() 
	{  
		$this->acl_manager->has_access_or_die('user', 'create');
		$this->data['page_title'] = t("create_user_account");

		$use_complex_password=$this->config->item("require_complex_password");

		//validate form input
		$this->form_validation->set_rules('username', t('username'), 'xss_clean|max_length[20]|required|callback_username_exists');
    	$this->form_validation->set_rules('email', t('email'), 'max_length[100]|required|valid_email|callback_email_exists');
    	$this->form_validation->set_rules('first_name', t('first_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('phone1', t('phone'), 'max_length[20]|xss_clean|trim');
    	$this->form_validation->set_rules('company', t('company'), 'max_length[255]|xss_clean');
		$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]|is_complex_password['.$use_complex_password.']');
    	$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');

		//phone is required for administrators
		/*
		if ($this->input->post("group_id")==1)
		{
	    	$this->form_validation->set_rules('phone1', t('phone'), 'xss_clean|trim|required|max_length[20]');
		}
		*/

        if ($this->form_validation->run() == true) 
		{ 
			//check to see if we are creating the user
			$username  = strtolower($this->input->post('username'));
        	$email     = $this->input->post('email');
        	$password  = $this->input->post('password');
        	
        	$additional_data = array('first_name' => $this->input->post('first_name'),
        							 'last_name'  => $this->input->post('last_name'),
        							 'company'    => $this->input->post('company'),
        							 'phone'      => $this->input->post('phone1'),// .'-'. $this->input->post('phone2') .'-'. $this->input->post('phone3'),
									 'active'     => $this->input->post('active'),
									 'country'    => $this->input->post('country'),
        							 'active'     => $this->input->post('active'),
									 'role_id'    => $this->role_ids_from_post()
        							);
        	
        	//register the user
			$user_created=$this->ion_auth_model->register($username, $password, $email, $additional_data);
			
        	if ($user_created)
        	{
				$data['username']=$username;
        		$data['active']=$additional_data['active'];
				$data['role_id']=$additional_data['role_id'];	
				
        		//get the user data by email
        		$user=$this->ion_auth->get_user_by_email($email);

				//update user group to ADMIN and ACTIVATE account
        		$this->ion_auth->update_user($user->id, $data);	        	
        	}  
        	
        	//redirect them back to the admin page
        	$this->session->set_flashdata('message', t("form_update_success") );
       		redirect("admin/users", 'refresh');
		} 
		else 
		{ 
			//display the create user form
	        
			//set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$this->data['first_name']          = array('name'   => 'first_name',
		                                              'id'      => 'first_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('first_name'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['last_name']           = array('name'   => 'last_name',
		                                              'id'      => 'last_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('last_name'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['email']              = array('name'    => 'email',
		                                              'id'      => 'email',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('email'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['username']           = array('name'    => 'username',
		                                              'id'      => 'username',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('username'),
													  'class'	=> 'form-control'
		                                             );

            $this->data['company']            = array('name'    => 'company',
		                                              'id'      => 'company',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('company'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['phone1']             = array('name'    => 'phone1',
		                                              'id'      => 'phone1',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('phone1'),
													  'class'	=> 'form-control'
		                                             );
		    $this->data['password']           = array('name'    => 'password',
		                                              'id'      => 'password',
		                                              'type'    => 'password',
		                                              'value'   => $this->form_validation->set_value('password'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['password_confirm']   = array('name'    => 'password_confirm',
                                                      'id'      => 'password_confirm',
                                                      'type'    => 'password',
                                                      'value'   => $this->form_validation->set_value('password_confirm'),
													  'class'	=> 'form-control'
                                                     );
            $this->data['active']=$this->form_validation->set_value('active',1);
			
			$this->data['roles']=array();

			if ($this->input->method() === 'post') {
				$this->data['user_role'] = $this->role_ids_from_post();
			}
			
			$this->data['roles']= $this->acl_manager->get_roles();//full list of roles
			$this->data['options_country']= $this->ion_auth_model->get_all_countries();
			
            $content=$this->load->view('users/create', $this->data,TRUE);
			$this->template->write('content', $content,true);
			$this->template->write('title', $this->data['page_title'],true);
			$this->template->render();	
		}
    }	
	


	function edit($id) 
	{  		
		$this->acl_manager->has_access_or_die('user', 'edit');
		$this->data['page_title'] = t("edit_user_account");	
		$use_complex_password=$this->config->item("require_complex_password");
	              		
        //validate form input
		$this->form_validation->set_rules('username', t('username'), 'trim|required|callback_username_exists');
    	$this->form_validation->set_rules('email', t('email'), 'max_length[100]|required|valid_email|callback_email_exists');		
    	$this->form_validation->set_rules('first_name', t('first_name'), 'trim|required|xss_clean');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'trim|required|xss_clean');
    	$this->form_validation->set_rules('phone1', t('phone'), 'trim|xss_clean');
    	$this->form_validation->set_rules('company', t('company_name'), 'trim|xss_clean');

		if ($this->input->post("password") || $this->input->post("password_confirm") )
		{
	    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]|is_complex_password['.$use_complex_password.']');
    		$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');
		}
				
        if ($this->form_validation->run() == true) 
		{ 
			$data = array(
				'username' => $this->input->post('username'),
				'email' 	=> $this->input->post('email'),
				'first_name' => $this->input->post('first_name'),
				'last_name'  => $this->input->post('last_name'),
				'company'    => $this->input->post('company'),
				'phone'      => $this->input->post('phone1'),
				'active'     => $this->input->post('active'),
				'role_id'     => $this->role_ids_from_post(),
				'country'     => $this->input->post('country'),
			);
						
			//change password, if not empty
			if ($this->input->post("password") ){
				$data['password']=$this->input->post('password');
			}

        	//update user 
        	$this->ion_auth->update_user($id,$data);
        	
        	$this->session->set_flashdata('message', "User updated");
       		redirect("admin/users", 'refresh');
		} 
		else 
		{ 
			//displaying the form for the first time
	        
			//get user id
			$db_data=$this->ion_auth->get_user($id);

			if (!$db_data){
				show_404();
			}
			
			//load data from post-back. need this for loading user group selection, 
			//other values are populated on postback			
			
			//set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$this->data['id']          			= array('name'   => 'id',
		                                              'id'      => 'id',
		                                              'type'    => 'hidden',
		                                              'value'   => $this->form_validation->set_value('id',$id),
		                                             );

			$this->data['first_name']          = array('name'   => 'first_name',
		                                              'id'      => 'first_name',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('first_name',$db_data->first_name),
													  'class'	=> 'form-control'
		                                             );
            $this->data['last_name']           = array('name'   => 'last_name',
		                                              'id'      => 'last_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('last_name',$db_data->last_name),
													  'class'	=> 'form-control'
													);
            $this->data['email']              = array('name'    => 'email',
		                                              'id'      => 'email',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('email',$db_data->email),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['username']           = array('name'    => 'username',
		                                              'id'      => 'username',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('username',$db_data->username),
													  'class'	=> 'form-control'													  
		                                             );

            $this->data['company']            = array('name'    => 'company',
		                                              'id'      => 'company',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('company',$db_data->company),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['phone1']             = array('name'    => 'phone1',
		                                              'id'      => 'phone1',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('phone1',$db_data->phone),
													  'class'	=> 'form-control'													  
		                                             );
		    $this->data['password']           = array('name'    => 'password',
		                                              'id'      => 'password',
		                                              'type'    => 'password',
													  'value'   => $this->form_validation->set_value('password'),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['password_confirm']   = array('name'    => 'password_confirm',
                                                      'id'      => 'password_confirm',
                                                      'type'    => 'password',
													  'value'   => $this->form_validation->set_value('password_confirm'),
													  'class'	=> 'form-control'													  
                                                     );
			$this->data['country']=$db_data->country;
			
			if($this->input->post('id')){
				$db_data->user_role=$this->role_ids_from_post();
			}else{				
				if (isset($db_data->groups) && count($db_data->groups) >0){
					$db_data->user_role=array_keys($db_data->groups);
				}
			}

            $this->data['active']= $this->form_validation->set_value('active',$db_data->active);
			
			$this->data['user_role']=array();
			if(isset($db_data->user_role)){
				$this->data['user_role']= $db_data->user_role;//roles assigned to user
			}

			$this->data['roles']= $this->acl_manager->get_roles();//full list of roles
			$this->data['options_country']= $this->ion_auth_model->get_all_countries();
			
			// Load user's API keys for admin management
			$this->data['api_keys'] = $this->ion_auth->get_api_keys($id);
			$this->data['user_id'] = $id;

			$content=$this->load->view('users/edit', $this->data,TRUE);						
			$this->template->write('content', $content,true);
			$this->template->write('title', $this->data['page_title'],true);
			$this->template->render();	
		}
    }
	
	/**
	 * Generate API key for a user (admin function)
	 */
	function generate_api_key($user_id)
	{
		$this->acl_manager->has_access_or_die('user', 'edit');
		
		if (!is_numeric($user_id)) {
			show_404();
		}
		
		// Verify user exists
		$user = $this->ion_auth->get_user($user_id);
		if (!$user) {
			show_404();
		}
		
		// Check if user already has 5 keys
		$api_keys = $this->ion_auth->get_api_keys($user_id);
		if (is_array($api_keys) && count($api_keys) >= 5) {
			$this->session->set_flashdata('error', t('maximum_api_keys_reached'));
			redirect('admin/users/edit/' . $user_id . '?tab=api_keys');
			return;
		}
		
		// Generate new key
		$key_result = $this->ion_auth->set_api_key($user_id);
		
		if ($key_result && isset($key_result['key'])) {
			// Store key in session to show it once
			$this->session->set_flashdata('admin_new_api_key', $key_result['key']);
			$this->session->set_flashdata('admin_new_api_key_user_id', $user_id);
			$this->session->set_flashdata('message', 'API key generated successfully. The key will be shown once.');
			
			// Redirect back to user edit page on API keys tab
			redirect('admin/users/edit/' . $user_id . '?tab=api_keys');
		} else {
			$this->session->set_flashdata('error', 'Failed to generate API key.');
			redirect('admin/users/edit/' . $user_id . '?tab=api_keys');
		}
	}
	
	/**
	 * Manage API key - view/edit page
	 */
	function manage_api_key($key_id)
	{
		$this->acl_manager->has_access_or_die('user', 'edit');
		
		if (!is_numeric($key_id)) {
			show_404();
		}
		
		// Load ion_auth config to get table names
		$this->load->config('ion_auth');
		$tables = $this->config->item('tables');
		
		// Get API key details with user info
		$this->db->select('api_keys.*, users.username, users.email, users.id as user_id, meta.first_name, meta.last_name');
		$this->db->from('api_keys');
		$this->db->join('users', 'api_keys.user_id = users.id', 'left');
		$this->db->join($tables['meta'] . ' meta', 'users.id = meta.user_id', 'left');
		$this->db->where('api_keys.id', $key_id);
		$query = $this->db->get();
		
		if (!$query || $query->num_rows() == 0) {
			show_404();
		}
		
		$key_data = $query->row_array();
		
		// Process form submission
		if ($this->input->post('action')) {
			$action = $this->input->post('action');
			
			if ($action == 'revoke') {
				$result = $this->ion_auth->delete_api_key($key_data['user_id'], $key_id);
				if ($result) {
					$this->session->set_flashdata('message', t('api_key_revoked_successfully'));
					redirect('admin/users/api_keys');
				} else {
					$this->session->set_flashdata('error', t('failed_to_revoke_api_key'));
				}
			} elseif ($action == 'extend_expiry') {
				$months = (int)$this->input->post('extend_months');
				if ($months > 0 && $months <= 60) {
					$current_expires = $key_data['expires_at'] ? $key_data['expires_at'] : time();
					$new_expires = $current_expires + ($months * 30 * 24 * 60 * 60); // Approximate months
					
					$this->db->where('id', $key_id);
					$this->db->update('api_keys', array('expires_at' => $new_expires));
					
					$this->session->set_flashdata('message', sprintf(t('api_key_expiry_extended'), $months));
					redirect('admin/users/manage_api_key/' . $key_id);
				} else {
					$this->session->set_flashdata('error', t('invalid_expiry_extension'));
				}
			} elseif ($action == 'delete') {
				// Permanently delete the API key from database
				$this->db->where('id', $key_id);
				$result = $this->db->delete('api_keys');
				
				if ($result) {
					$this->session->set_flashdata('message', t('api_key_deleted_successfully'));
					redirect('admin/users/api_keys');
				} else {
					$this->session->set_flashdata('error', t('failed_to_delete_api_key'));
				}
			}
		}
		
		// Check if this is a legacy key
		$key_data['is_legacy'] = empty($key_data['key_prefix']);
		
		// Calculate status
		$current_time = time();
		$key_data['is_expired'] = false;
		$key_data['is_revoked'] = !empty($key_data['revoked_at']);
		
		if ($key_data['is_legacy']) {
			// Legacy key: mask the api_key column
			$this->load->library('api_key_manager');
			$key_data['key_prefix'] = $this->api_key_manager->mask_legacy_key($key_data['api_key']);
		} else {
			// New secure key: check expiration
			if (!$key_data['is_revoked'] && $key_data['expires_at'] && $key_data['expires_at'] <= $current_time) {
				$key_data['is_expired'] = true;
			}
		}
		
		// Format user display name
		$key_data['user_display_name'] = trim($key_data['first_name'] . ' ' . $key_data['last_name']);
		if (empty($key_data['user_display_name'])) {
			$key_data['user_display_name'] = $key_data['username'];
		}
		
		$content = $this->load->view('users/manage_api_key', $key_data, true);
		$this->template->write('content', $content, true);
		$this->template->write('title', t('manage_api_key'), true);
		$this->template->render();
	}
	
	/**
	 * Revoke (delete) API key for a user (admin function)
	 * Kept for backward compatibility, but redirects to manage page
	 */
	function revoke_api_key($user_id, $key_id)
	{
		$this->acl_manager->has_access_or_die('user', 'edit');
		
		if (!is_numeric($user_id) || !is_numeric($key_id)) {
			show_404();
		}
		
		// Redirect to manage page
		redirect('admin/users/manage_api_key/' . $key_id);
	}
	
	/**
	 * API Keys Management Page
	 */
	function api_keys()
	{
		$this->acl_manager->has_access_or_die('user', 'view');
		
		// Get API keys with pagination and filtering
		$result = $this->_search_api_keys();
		
		$content = $this->load->view('users/api_keys', $result, true);
		$this->template->write('content', $content, true);
		$this->template->write('title', t('api_keys_management'), true);
		$this->template->render();
	}
	
	/**
	 * Search API keys - internal method, supports pagination, sorting, filtering
	 */
	private function _search_api_keys()
	{
		// Records to show per page
		$per_page = 25;
		
		// Current page
		$offset = $this->input->get('offset');
		
		// Sort order
		$sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$sort_by = $this->input->get('sort_by') ? $this->input->get('sort_by') : 'date_created';
		
		// Filters
		$user_search = $this->input->get('user_search'); // Username or email search
		$status_filter = $this->input->get('status'); // 'active', 'expired', 'revoked', 'all'
		$search_keyword = $this->input->get('keywords'); // Key prefix search
		
		// Load ion_auth config to get table names
		$this->load->config('ion_auth');
		$tables = $this->config->item('tables');
		
		// Build query - join with users and meta tables to get user details
		// Include api_key column for legacy keys
		$this->db->select('api_keys.*, users.username, users.email, meta.first_name, meta.last_name');
		$this->db->from('api_keys');
		$this->db->join('users', 'api_keys.user_id = users.id', 'left');
		$this->db->join($tables['meta'] . ' meta', 'users.id = meta.user_id', 'left');
		
		// Apply filters
		if ($user_search) {
			$this->db->group_start();
			$this->db->like('users.username', $user_search);
			$this->db->or_like('users.email', $user_search);
			$this->db->group_end();
		}
		
		if ($status_filter && $status_filter != 'all') {
			$current_time = time();
			switch ($status_filter) {
				case 'active':
					$this->db->where('api_keys.revoked_at', NULL);
					$this->db->where("(api_keys.expires_at IS NULL OR api_keys.expires_at > $current_time)", NULL, FALSE);
					break;
				case 'expired':
					$this->db->where('api_keys.revoked_at', NULL);
					$this->db->where('api_keys.expires_at IS NOT NULL', NULL, FALSE);
					$this->db->where("api_keys.expires_at <= $current_time", NULL, FALSE);
					break;
				case 'revoked':
					$this->db->where('api_keys.revoked_at IS NOT NULL', NULL, FALSE);
					break;
			}
		} else {
			// Default: show non-revoked keys only
			$this->db->where('api_keys.revoked_at', NULL);
		}
		
		if ($search_keyword) {
			$this->db->group_start();
			$this->db->like('api_keys.key_prefix', $search_keyword);
			$this->db->or_like('api_keys.api_key', $search_keyword);
			$this->db->group_end();
		}
		
		// Get total count before pagination
		$total_query = clone $this->db;
		$total = $total_query->count_all_results('', FALSE);
		
		// Apply sorting
		$allowed_sort_fields = array('date_created', 'expires_at', 'last_used_at', 'username', 'key_prefix');
		if (in_array($sort_by, $allowed_sort_fields)) {
			if ($sort_by == 'username') {
				$this->db->order_by('users.username', $sort_order);
			} else {
				$this->db->order_by('api_keys.' . $sort_by, $sort_order);
			}
		}
		
		// Apply pagination
		$this->db->limit($per_page, $offset);
		
		$query = $this->db->get();
		$rows = $query->result_array();
		
		// Process rows to add status information
		$current_time = time();
		$this->load->library('api_key_manager');
		
		foreach ($rows as &$row) {
			// Check if this is a legacy key (no key_prefix)
			$row['is_legacy'] = empty($row['key_prefix']);
			
			if ($row['is_legacy']) {
				// Legacy key: mask the api_key column
				$row['key_prefix'] = $this->api_key_manager->mask_legacy_key($row['api_key']);
				$row['is_expired'] = false; // Legacy keys don't expire
			} else {
				// New secure key: mask the prefix
				$row['key_prefix'] = $this->api_key_manager->mask_key($row['key_prefix']);
				$row['is_expired'] = false;
				if ($row['expires_at'] && $row['expires_at'] <= $current_time) {
					$row['is_expired'] = true;
				}
			}
			
			$row['is_revoked'] = !empty($row['revoked_at']);
			
			// Format user name
			$row['user_display_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
			if (empty($row['user_display_name'])) {
				$row['user_display_name'] = $row['username'];
			}
		}
		
		// Set pagination options
		$base_url = site_url('admin/users/api_keys');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment'] = "offset";
		$config['page_query_string'] = TRUE;
		$config['additional_querystring'] = get_querystring(array('keywords', 'user_id', 'status', 'sort_by', 'sort_order'));
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">';
		$config['full_tag_close'] = '</span>';
		
		// Initialize pagination
		$this->pagination->initialize($config);
		
		return array(
			'rows' => $rows,
			'user_search' => $user_search,
			'status_filter' => $status_filter ? $status_filter : 'active',
			'search_keyword' => $search_keyword,
			'sort_by' => $sort_by,
			'sort_order' => $sort_order
		);
	}
	
	
	//check if the email address exists in db
	function email_exists($email)
	{
		$user_data=$this->ion_auth->get_user_by_email($email);

		if (!$user_data)
		{
			RETURN TRUE;
		}

		//check if editing user, exclude the current user
		$userid=$this->input->post("id");
		
		if ($userid==$user_data->id)
		{
			return TRUE;
		}

		if ($user_data)
		{
			$this->form_validation->set_message('email_exists', t('callback_email_exists') );
			return FALSE;
		}
		return TRUE;
	}
	
	//check if the username exists in db
	function username_exists($username)
	{
		$user_data=$this->ion_auth->get_user_by_username($username);
		
		if (!$user_data)
		{
			RETURN TRUE;
		}

		//check if editing user, exclude the current user
		$userid=$this->input->post("id");
		
		if ($userid==$user_data->id)
		{
			return TRUE;
		}
		
		if ($user_data)
		{
			$this->form_validation->set_message('username_exists', t('callback_username_exists') );
			return FALSE;
		}
		return TRUE;
	}
		
	

	function _save_user($id=-1)
	{

		$this->session->set_flashdata('message', '<div class="success"><i>'.'user '.'</i> updated</div>' );
		redirect('admin/users');
		exit;

		$u= new User;
		
		if ($id>-1)
		{
			//edit user
			$u->id=$id;
		}

		//populate with post data
		$u->username =$this->input->post('username');
		$u->email = $this->input->post('email');
		
		//skip validation if editing and passwords are blank
		if ($id!=-1 && $this->input->post('password') =="" && $this->input->post('passconf')=="")
		
		//editing an existing user
		if ($id>-1)
		{
			if ($this->input->post('password')=="" && $this->input->post('passconf')=="")
			{
				//skip validation
			}
			else
			{
				$u->password = $this->input->post('password');
				$u->confirm_password = $this->input->post('passconf');
			}
		}
		//add a new record
		else if ($id==-1)
		{
			$u->password = $this->input->post('password');
			$u->confirm_password = $this->input->post('passconf');			
		}

		$u->title=$this->input->post('title');
		$u->fname=$this->input->post('fname');
		$u->lname=$this->input->post('lname');
		$u->organization=$this->input->post('organization');
		$u->address=$this->input->post('address');
		$u->country=$this->input->post('country');
		$u->telephone=$this->input->post('telephone');
		$u->fax=$this->input->post('fax');
		$u->status=$this->input->post('status');
		$u->role=$this->input->post('role');
		
		$u->validate();
		
		if ($u->valid)
		{
			// Validation Passed
			echo 'validation passed';
		}
		else
		{
			// Validation Failed
			$this->errors='<div class="error-box">'.$u->error->string.'</div>';
			return false;
		}	

		// Begin transaction
		$u->trans_begin();
			
		// Attempt to save user
		$u->save();

		// Check status of transaction
		if ($u->trans_status() === FALSE)
		{
			// Transaction failed, rollback
			$u->trans_rollback();
			
			$this->errors='<div class="error-box">'.$u->error->string.'</div>';
			return false;
		}
		else
		{
			// Transaction successful, commit
			$u->trans_commit();
			$this->session->set_flashdata('message', '<div class="success-box"><i>'.$u->username.'</i> updated</div>' );
			redirect('admin/users');
		}
			
		// Show all errors
		//echo $u->error->string;

		//success
		/*$success_msg['message']='Form updated successfully.';
		$this->session->set_flashdata('message', 'Form updated successfully-session flash.');
		$content=$this->load->view('success',$success_msg,true);*/

	}//end-function
	
	

	//validation for add/edit user	
	function _edit_validation($is_editing=FALSE)
	{	
		$this->form_validation->set_error_delimiters('<li>', '</li>');
		$this->form_validation->set_rules('username', t('username'), 'trim|required|min_length[5]|max_length[20]|alpha_numeric');
		
		//skip validation
		if ($is_editing==TRUE && !isset($_POST['password']) )
		{
			$this->form_validation->set_rules('password', 'Password', 'required|matches[passconf]|md5');
			$this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');
		}
		
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
	}


	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{			
		$this->acl_manager->has_access_or_die('user', 'delete');

		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/users',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/users');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->User_model->delete($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/users');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	/**
	 * Export all users as CSV or JSON (id, email, first_name, last_name).
	 *
	 * @param string|null $format csv|json
	 * @return void
	 */
	function export($format = null)
	{
		$this->acl_manager->has_access_or_die('user', 'view');

		$format = strtolower(trim((string) $format));
		if ( ! in_array($format, array('csv', 'json'), true)) {
			show_404();
		}

		$rows = $this->User_model->get_export_rows();
		$export = array();
		foreach ($rows as $row) {
			$export[] = array(
				'id'         => (int) $row['id'],
				'email'      => isset($row['email']) ? (string) $row['email'] : '',
				'first_name' => isset($row['first_name']) ? (string) $row['first_name'] : '',
				'last_name'  => isset($row['last_name']) ? (string) $row['last_name'] : '',
			);
		}

		if ($format === 'json') {
			$filename = 'users-' . date('Y-m-d-His') . '.json';
			header('Content-Type: application/json; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			return;
		}

		$filename = 'users-' . date('Y-m-d-His') . '.csv';
		header('Expires: Sat, 01 Jan 1980 00:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$outstream = fopen('php://output', 'w');
		// UTF-8 BOM for Excel compatibility
		fwrite($outstream, "\xEF\xBB\xBF");
		fputcsv($outstream, array('id', 'email', 'first_name', 'last_name'));
		foreach ($export as $row) {
			fputcsv($outstream, array($row['id'], $row['email'], $row['first_name'], $row['last_name']));
		}
		fclose($outstream);
	}

	/**
	*
	* Batch import users using CSV
	*
	**/
	function batch_import()
	{	
		$this->acl_manager->has_access_or_die('user', 'add');

		if ($this->input->post("csv")){
			$this->_do_batch_import($this->input->post("csv"));
		}
		
		$content=$this->load->view("users/batch_import",NULL,TRUE);		
		$this->template->write('content', $content,true);
		$this->template->render();		
	}
	
	function _do_batch_import($csv_data,$seperator=',')
	{
		$this->load->library('csvreader');		
		$this->csvreader->separator = $seperator;
		$users_arr=$this->csvreader->parse_string($csv_data, $p_NamedFields = true);
		
		if (count($users_arr)>0)
		{
			foreach($users_arr as $user)
			{
				//log
				$this->db_logger->write_log('user-batch-import',$user['email']);
	
				//check to see if we are creating the user
				$username  = strtolower($user['firstname']).' '.strtolower($user['lastname']);
				$email     = $user['email'];
				$password  = $user['password'];
				
				$additional_data = array('first_name' => $user['firstname'],
										 'last_name'  => $user['lastname'],
										 'company'    => 'N/A',
										 'phone'      => '0000',
										 'country'      => $user['country'],
										 'email'		=>	$email,
										 'identity'		=>$username
										);
				
				//register the user
				$result=$this->ion_auth->register($username,$password,$email,$additional_data);

				if ($result)
				{
					echo '<BR>user account created successfully for: '.$email;
				}
				else
				{
					echo '<BR>failed: '.$email;
				}
			}
			exit;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	*
	* Impersonate as other users
	* 
	* TODO: remove
	*/
	function impersonate()
	{
		show_error('This feature has been removed');

		/*//get admin accounts with limited access
		$data['roles']=$this->acl_manager->get_roles();
		
		if($this->input->post("user"))
		{
			$this->ion_auth_model->impersonate((int)$this->input->post("user"),$this->acl->current_user());
			redirect('admin');return;
		}
		
		$content=$this->load->view('users/impersonate',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();	
		*/
	}
	
	function exit_impersonate()
	{
		show_error('disabled');
		/*$this->ion_auth_model->exit_impersonate();
		redirect("admin");	
		*/
	}
	
	
	
}

/* End of file users.php */
/* Location: ./application/controllers/users.php */