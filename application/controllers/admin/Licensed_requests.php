<?php
class Licensed_requests extends MY_Controller {
 
    public function __construct()
    {
		//skip authentication
        parent::__construct();   
       	
        $this->load->helper(array('url'));		
		$this->load->library( array('pagination') );		
        $this->load->model('Licensed_model');
		$this->load->model('Repository_model');
       	$this->template->set_template('admin');		
		$this->lang->load('general');
		$this->lang->load('licensed_request');
		$this->lang->load('catalog_admin');

		//$this->output->enable_profiler(TRUE);

		// Optional ?collection= filter only. Default context is central. No cookie or sticky bar.
		if ($this->input->get("collection"))
		{
			$repo_uid=$this->Repository_model->get_repositoryid_uid($this->input->get("collection"));
			$repo_obj=$this->Repository_model->select_single($repo_uid);
		}
		else
		{
			$repo_obj=null;
		}

		if ($repo_obj)
		{
			$repo_obj=(object)$repo_obj;
		}

		if (!$repo_obj)
		{
			$data=$this->Repository_model->get_central_catalog_array();
			$this->active_repo=(object)$data;
		}
		else
		{
			$this->active_repo=$repo_obj;
		}
    }
    
    public function index()
    {
		$this->acl_manager->require_licensed_requests_access();
		$this->_render_licensed_requests_vue_shell(t('licensed_requests'));
	}


	/**
	 * Shared config for the licensed-requests Vue app.
	 *
	 * @return array
	 */
	private function _licensed_requests_vue_view_data()
	{
		$this->load->helper('vite_helper');
		$pu    = parse_url(site_url('admin/licensed_requests'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/licensed_requests';

		return array(
			'api_base_url'     => site_url('api/admin/licensed_requests/'),
			'site_url'         => site_url(),
			'base_url'         => base_url(),
			'csrf_token'       => $this->security->get_csrf_hash(),
			'csrf_token_name'  => $this->security->get_csrf_token_name(),
			'assets_base'      => base_url('frontend/dist/'),
			'translations'     => $this->lang->language,
			'router_path_base' => $rpath,
		);
	}


	/**
	 * Render Vue 3 shell for licensed survey requests (list).
	 *
	 * @return void
	 */
	private function _render_licensed_requests_vue_shell($html_title = null)
	{
		$view_data = $this->_licensed_requests_vue_view_data();
		$page      = array(
			'title'           => $html_title !== null ? $html_title : t('title_licensed_request'),
			'content'         => $this->load->view('admin/licensed_requests/index', $view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);
		$this->load->view('layouts/admin_vue', $page);
	}
	
	function export()
	{
		$this->acl_manager->require_licensed_requests_access();

		$user  = $this->ion_auth->current_user();
		$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
		if ($scope === false) {
			$this->acl_manager->show_access_denied('feature');
		}

		if ($scope === null) {
			$rows = $this->Licensed_model->select_all();
		}
		else {
			$rows = $this->Licensed_model->admin_search_requests(50000, 0, array(), 'created', 'desc', $scope, null);
		}

		return $this->Licensed_model->export_to_csv($rows);
	}
	
	function edit($id)
	{
		if (! is_numeric($id)) {
			show_404();
		}

		if (! $this->_admin_user_can_licensed_request((int) $id, 'edit')) {
			$this->acl_manager->show_access_denied('feature');
		}

		$this->_render_licensed_requests_vue_shell();
	}


	/**
	 * Whether the current admin may perform $privilege (view|edit) on this request (scope + per-repo ACL).
	 *
	 * @param int    $request_id
	 * @param string $privilege  view|edit
	 * @return bool
	 */
	private function _admin_user_can_licensed_request($request_id, $privilege)
	{
		$user = $this->ion_auth->current_user();
		if (! $user) {
			return false;
		}

		if ($this->acl_manager->user_is_admin($user)) {
			return true;
		}

		$scope = $this->acl_manager->get_licensed_request_repository_scope($user);
		if ($scope === false) {
			return false;
		}

		if (! $this->Licensed_model->request_matches_repository_scope($request_id, $scope)) {
			return false;
		}

		try {
			$this->acl_manager->has_access('licensed_request', $privilege, $user);

			return true;
		}
		catch (Exception $e) {
			// continue — try per-repo Zend roles + collection ACL in one pass
		}

		return $this->acl_manager->has_access_on_any_repository(
			'licensed_request',
			$privilege,
			$user,
			$this->Licensed_model->get_request_repository_ids($request_id)
		);
	}
	
	function _status_check($str)
	{
		$status_allowed=array('APPROVED','DENIED','MOREINFO','CANCELLED');
			
		if (!in_array($str,$status_allowed))
		{
			$this->form_validation->set_message('_status_check', 'The %s code is incorrect.');
			return FALSE;
		}		
			return TRUE;
	}


	/**
	* validates a date format (mm/dd/yyyy)
	*
	*/
	function _date_check($str)
	{
		$str_array=explode("/",$str);

		if (count($str_array)<3)
		{
			$this->form_validation->set_message('_date_check', 'The %s is incorrect. Use the format (mm/dd/yyyy).');
			return FALSE;
		}
				
		foreach($str_array as $value)
		{
			if (!is_numeric($value))
			{
				return FALSE;
			}
		}
		
		if( !checkdate($str_array[0],$str_array[1],$str_array[2]))
		{
			$this->form_validation->set_message('_date_check', 'The %s is incorrect. Use the format (mm/dd/yyyy).');
			return FALSE;
		}

		return TRUE;	
	}
	
	function _valid_ip($str)
	{
		$ip_array=explode(',',$str);

		foreach($ip_array as $ip)
		{
			$ip=trim($ip);
			if ( $ip!='')
			{
				if (!$this->form_validation->valid_ip($ip))
				{
					$this->form_validation->set_message('_valid_ip', 'The %s field must contain a valid IP.');
					return FALSE;
				}
			}		
		}
		return TRUE;
	}
		
	/**
	* Update request info using AJAX/JSON
	* to be used by the EDIT controller
	*
	* returns JSON object
	*/
	function update($requestid)
	{			
		if (! is_numeric($requestid) || ! $this->_admin_user_can_licensed_request((int) $requestid, 'edit')) {
			$this->acl_manager->show_access_denied('feature');
		}

		$this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean|callback__status_check');
		$this->form_validation->set_rules('comments', 'Comments', 'trim|xss_clean');		
		$this->form_validation->set_rules('ip_limit', 'IP address', 'trim|xss_clean|callback__valid_ip');		

		$post=$_POST;
		foreach($post as $key=>$value)
		{
			$post[$key]=$this->security->xss_clean($value);
		}		
		
		$file_options=NULL;
		//create an array of selected files and options		
		foreach($post as $key=>$value)
		{
			//find the selected files and the options set on them
			if ( substr($key,0,7) =='fileid-')
			{
				$index=str_replace('fileid-','',$key);
				
				//add validation
				$this->form_validation->set_rules('download-limit-'.$index, t('download_limit'), 'trim|required|xss_clean|integer');
				$this->form_validation->set_rules('expiry-'.$index, t('expiry_date'), 'trim|required|xss_clean|callback__date_check');
				
				$options=array(
					'download_limit'=>$this->input->post('download-limit-'.$index),
					//covert to unix timestamp + add time =23:59	
					'expiry'=> strtotime($this->input->post('expiry-'.$index)) +(24*3600)-60
				);
								
				$file_options[$index]=$options;				
			}
		}
		
		//process form				
		if ($this->form_validation->run() == FALSE)
		{
			echo '<div class="error">'.validation_errors().'</div>';
		}
		else
		{
			//get current user info
			$user=$this->ion_auth->get_user();
			
			//check for request ip limits, if any
			$ip_limit=isset($post['ip_limit']) ? $post['ip_limit'] : '';
		
			//update request status and comments
			$this->Licensed_model->update_request_status($requestid,$user->username,$post['status'],$post['comments'],$ip_limit);
			
			//update history
			$options=array(
				'user_id'			=> $user->email,
				'logtype'			=> 'comment',
				'request_status'	=> $post['status'],
				'description'		=> $post['comments']
			);
			
			$this->Licensed_model->add_request_history($requestid,$options);
			
			if ($file_options!=NULL)
			{
				//update file options (if set)
				$this->Licensed_model->update_request_files($requestid,$file_options);
			}	
			
			//delete files that were unchecked
			$excluded=NULL;
			
			if ( is_array($file_options))
			{
				$excluded=array_keys($file_options);				
			}
				
			$this->Licensed_model->delete_request_files($requestid,$excluded);			
			
			echo '<div class="success">'.t('form_update_success').'</div>';
		}
		
		//notify user
		$this->_notify_user($requestid);
	}
	
	/**
	*
	* Notify the user via email of the request status change
	*
	*/
	function _notify_user($requestid)
	{
		if ($this->input->post("notify")!=='1')
		{
			return;
		}
		
		//get request data
		$data=$this->Licensed_model->get_request_by_id($requestid);
		$data=(object)$data;	
		
		#get user info who request access
		$user=$this->ion_auth->get_user($data->userid);
		
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->email=$user->email;
		$data->title=$data->request_title;		
		$data->requestid=$requestid;
					
		$message=$this->load->view('access_licensed/user_notification_email', $data,true);	

		$this->load->library('email');
		$this->email->clear();
		$this->email->initialize();
		$this->email->to($user->email);
		$this->email->bcc($this->config->item('website_webmaster_email'), $this->config->item('website_webmaster_name'));
		$this->email->subject('[#'.$requestid.'] - Request status updated for '.$data->title );
		$this->email->message($message);
		
		if ($this->email->send())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	* Monitor licensed data files downloads
	*/
	function monitor($requestid,$output=FALSE)
	{	
		if (! is_numeric($requestid) || ! $this->_admin_user_can_licensed_request((int) $requestid, 'edit')) {
			$this->acl_manager->show_access_denied('feature');
		}

		//get request summary statistics
		$data['summary_rows']=$this->Licensed_model->get_request_summary($requestid);

		//get request download log
		$data['log_rows']=$this->Licensed_model->get_request_log($requestid);
		
    	//show listing
		$content=$this->load->view('access_licensed/download_history', $data,true);
		
		if ($output==TRUE)
		{
			return $content;
		}			    	
		$this->template->write('title', t('title_licensed_request'),true);				
		$this->template->write('content', $content,true);
	  	$this->template->render();	
	}
	
	/**
	*
	* Send email message
	*
	*/
	function send_mail($requestid=NULL)
	{
		if (!is_numeric($requestid)){
			show_404();
		}

		if (! $this->_admin_user_can_licensed_request((int) $requestid, 'edit')) {
			$this->acl_manager->show_access_denied('feature');
		}
		
		$this->form_validation->set_rules('to', t('to'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('cc', t('cc'), 'trim|xss_clean');
		$this->form_validation->set_rules('subject', t('subject'), 'trim|xss_clean|required');		
		$this->form_validation->set_rules('body', t('body'), 'required|trim|xss_clean');		

		//process form				
		if ($this->form_validation->run() == FALSE)
		{
			echo '<div class="error">'.validation_errors().'</div>';
			return;
		}

		$this->load->library('email');
		$this->email->clear();
		$this->email->initialize();
		$this->email->to($this->input->post("to"));
		$this->email->cc($this->input->post("cc"));
		$this->email->subject($this->input->post("subject") );
		$this->email->message($this->input->post("body"));
		
		
		//track history
		$user=$this->ion_auth->get_user();
		$request=$this->Licensed_model->get_request_by_id($requestid);
		$email=array(
			'to'		=> $this->input->post("to"),
			'cc'		=> $this->input->post("cc"),
			'subject'	=> $this->input->post("subject"),
			'body'		=> $this->input->post("body")
		);
		
		$options=array(
			'user_id'			=> $user->email,
			'logtype'			=> 'email',
			'request_status'	=> $request['status'],
			'description'		=> serialize($email)
		);
					
		if ($this->email->send())
		{
			//add to request history if email was sent
			$this->Licensed_model->add_request_history($requestid,$options);
			echo '<div class="success">'.t('email_sent').'</div>';
		}
		else
		{
			echo '<div class="error">'.t('email_not_sent').'</div>';
		}
	}
	
	
	/**
	*
	* Forward licensed request
	*
	*/
	function forward_request($requestid=NULL)
	{
		if (!is_numeric($requestid))
		{
			show_404();
		}
		
		//get request from db
		$request_data=$this->Licensed_model->get_request_by_id($requestid);
		
		if (!$request_data)
		{
			show_404("REQUEST_INVALID");
		}

		if (! $this->_admin_user_can_licensed_request((int) $requestid, 'edit')) {
			$this->acl_manager->show_access_denied('feature');
		}
		
		$this->form_validation->set_rules('to', t('to'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('cc', t('cc'), 'trim|xss_clean');
		$this->form_validation->set_rules('subject', t('subject'), 'trim|xss_clean|required');		
		$this->form_validation->set_rules('body', t('body'), 'required|trim|xss_clean');		

		//process form				
		if ($this->form_validation->run() == FALSE)
		{
			echo '<div class="error">'.validation_errors().'</div>';
			return;
		}

		//format request for email
		$request_formatted=$this->load->view('access_licensed/forward_request_email',$request_data,TRUE);
		
		$this->load->library('email');
		$this->email->clear();
		$this->email->initialize();
		$this->email->to($this->input->post("to"));
		$this->email->cc($this->input->post("cc"));
		$this->email->subject($this->input->post("subject") );
		$this->email->message($request_formatted);
		
		if ($this->email->send())
		{
			echo '<div class="success">'.t('email_sent').'</div>';
		}
		else
		{
			echo '<div class="error">'.t('email_not_sent').'</div>';
		}
		
		
		//track history
		$user=$this->ion_auth->get_user();
		$request=$this->Licensed_model->get_request_by_id($requestid);
		$email=array(
			'to'		=> $this->input->post("to"),
			'cc'		=> $this->input->post("cc"),
			'subject'	=> $this->input->post("subject"),
			'body'		=> $this->input->post("body")
		);
		
		$options=array(
			'user_id'			=> $user->email,
			'logtype'			=> 'forward',
			'request_status'	=> $request['status'],
			'description'		=> serialize($email)
		);
					
		$this->Licensed_model->add_request_history($requestid,$options);
		
	}

	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 * @return string
	 * @author IHSN
	 **/
	function _search()
	{
		//records to show per page
		$per_page = 30;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'created';

		$search_options=array(
				'keywords'=>$this->input->get_post("keywords"),
				'status'=>$this->input->get_post("status")
		);
		
		//records
		$rows=$this->Licensed_model->search_requests($per_page, $offset,$search_options, $sort_by, $sort_order,$this->active_repo->repositoryid);

		//total records in the db
		$total = $this->Licensed_model->search_requests_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Licensed_model->search_requests($per_page, $offset,$search_options, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/licensed_requests');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'status'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}


	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{			
		$this->acl_manager->require_licensed_requests_access();

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
				redirect('admin/licensed_requests',"refresh");
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
				redirect('admin/licensed_requests');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				if (! $this->_admin_user_can_licensed_request((int) $item, 'delete')) {
					$this->acl_manager->show_access_denied('feature');
				}

				//confirm delete	
				$this->Licensed_model->delete($item);
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
				redirect('admin/licensed_requests');
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

}
