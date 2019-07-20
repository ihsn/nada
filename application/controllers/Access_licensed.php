<?php
class Access_licensed extends MY_Controller {
 
 	var $form_model='licensed';
	
    public function __construct()
    {
		//requires authentication, and user can be non-admin
		parent::__construct($SKIP=TRUE,$is_admin=FALSE);
			
		$this->load->model('Licensed_model');
		$this->load->model('Datafiles_model');
		$this->load->model('Dataset_model');
		$this->load->model('Catalog_model');
		$this->template->set_template('default');
		$this->load->helper('admin_notifications');
		
		$this->lang->load('general');
		$this->lang->load('licensed_request');
		$this->lang->load('licensed_access_form');
    	
		
		//check if user is logged in
		if (!$this->ion_auth->logged_in()) 
		{
			$this->session->set_flashdata('reason', t('reason_login_licensed_access'));
			$destination=$this->uri->uri_string();
			$this->session->set_userdata("destination",$destination);

			//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
		}	

    	//$this->output->enable_profiler(TRUE);
    }
 
    
	/**
     * Show the request form
     * 
     * @param $sid
     */
	function index($sid=NULL)
	{
		if ($sid==NULL)
		{
			show_404();
		}
		
		$this->request_form('study',$sid);
	}
	
	
	//shows a confirmation message
	function confirm()
	{
		//change template for ajax
		if ($this->input->get_post("ajax"))
		{
			$this->template->set_template('blank');
		}
		
		$content=$this->load->view('access_licensed/request_confirmation',NULL,TRUE);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	function _confirmation_email($requestid)
	{			
		//get user info
		$user=$this->ion_auth->current_user();

		//get request data
		$data=$this->Licensed_model->get_request_by_id($requestid);
		$data=(object)$data;
			
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		if ($data->request_type=='study')
		{
			$data->title=$data->surveys[0]['nation']. ' - '.$data->surveys[0]['titl'];
		}	
		else
		{
			$data->title='collection ['.$data->collection['title'].']';
		}

		$subject=t('confirmation_application_for_licensed_dataset').' - '.$data->title;
		$message=$this->load->view('access_licensed/request_form_printable', $data,true);

		$this->load->library('email');
		$this->email->clear();		
		$this->email->initialize();//intialize using the settings in mail
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		$this->email->to($data->email);
		$this->email->subject($subject);
		$this->email->message($message);
		
		if ($this->email->send())
		{
			//notification for the site admins
			$subject=t('notification_licensed_survey_request_received');
			$message=$this->load->view('access_licensed/admin_notification_email', $data,true);
			
			//notify the site admin
			notify_admin($subject,$message,$notify_all_admins=false);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	
	/*
	* Track licensed request by request id
	*
	* @request_id	integer
	*/
	function track($request_id)
	{
			if (!is_numeric($request_id)){
				show_404();
			}
		
			//get user info
			$user=$this->ion_auth->current_user();
			
			//get request from db
			$data=$this->Licensed_model->get_request_by_id($request_id);
			
			//find approved surveys with files for this request
			$data['surveys_with_files']=$this->Licensed_model->get_request_approved_surveys($request_id);
			
			if ($data===FALSE){
				show_404();
			}
				
			//user can only view his requests
			if ($data['userid']!=$user->id){
				show_404();
			}
			
			$contents=$this->load->view('access_licensed/request_status', $data,true);				
			
			if ($data['status']=='APPROVED'){		
				$contents.=$this->_get_licensed_files($request_id);
			}
			
			if ($this->input->get("print")=='yes' || $this->input->get("ajax")){
				$this->template->set_template('blank');	
			}
			else{
				$this->template->set_template('default');
			}

			$this->template->write('content', $contents,true);
			$this->template->render();
	}


	
	//returns a list of downloadable files for the request
	function _get_licensed_files($request_id)
	{				
			$this->load->helper('file');
			$this->load->helper("resource_helper");
			$this->load->model("Resource_model");
			
			$request=$this->Licensed_model->get_request_by_id($request_id);		
			$sid=$this->input->get("sid");
			
			//request has no surveys attached
			if (!isset($request['surveys']) || count($request['surveys'])==0){
				return FALSE;
			}
			
			if (!is_numeric($sid)){
				$first_survey=reset($request['surveys']);
				$sid=$first_survey['id'];
			}
			
			//survey must be part of the request
			if (!array_key_exists($sid,$request['surveys'])){
				show_error("INVALID-REQUEST");
			}
			
			//get files by request id and survey id
			$data['microdata_resources']=$this->Licensed_model->get_request_downloads_by_study($request_id,$sid);
			$data['external_resources']=$this->Resource_model->get_grouped_resources_by_survey($sid);		
			$data['request']=$request;
			$data['survey_folder']=$this->Dataset_model->get_storage_fullpath($sid);
			$data['sid']=$sid;
			$data['request_id']=$request_id;
			
			return $this->load->view("access_licensed/track_request_downloads",$data,TRUE);
	}
	

	/**
	* return resources by request id
	**/
	function get_resources($sid=NULL,$request_id=NULL)
	{
		$this->load->helper('file');
		$this->load->helper("resource_helper");
		$this->load->model("Resource_model");
		
		if (!is_numeric($sid) || !is_numeric($request_id))
		{
			show_error('INVALID-REQUEST');
		}
		
		$request=$this->Licensed_model->get_request_by_id($request_id);		
		
		//request has no surveys attached
		if (!isset($request['surveys']) || count($request['surveys'])==0)
		{
			return FALSE;
		}

		//survey must be part of the request
		if (!array_key_exists($sid,$request['surveys']))
		{
			show_error("INVALID-REQUEST");
		}
		
		//get files by request id and survey id
		$data['microdata_resources']=$this->Licensed_model->get_request_downloads_by_study($request_id,$sid);
		$data['external_resources']=$this->Resource_model->get_grouped_resources_by_survey($sid);		
		$data['request']=$request;
		$data['survey_folder']=$this->Dataset_model->get_storage_fullpath($sid);
		$data['sid']=$sid;
		$data['request_id']=$request_id;
		
		echo $this->load->view("access_licensed/request_resources",$data,TRUE);		
	}

	
	/**
	* Download licensed data files
	*		
	* Checks before a file can be downloaded
	*	- status=APPROVED
	*	- IP_LIMIT on request
	*	- Expiry date on file
	*	- Download Limits
	*
	* NOTE: Downloads are logged and stats are updated
	*/
	function download($request_id=NULL,$file_id=NULL)
	{
		$this->load->model('managefiles_model');
		
		if (!is_numeric($request_id) )
		{	
			show_404();return;
		}

		if ($file_id=='')
		{			
			show_404();return;	
		}
		
		//get currently logged-in user info
		$user=$this->ion_auth->current_user();

		//is licensed file available for the request
		$request_file_exists=$this->Licensed_model->exists_request_file($request_id,$file_id);
		
		if(!$request_file_exists)
		{
			show_404();
		}

		//get file information
		$fileinfo=$this->managefiles_model->get_resource_by_id($file_id);

		//get request info from db
		$request=$this->Licensed_model->get_request_by_id($request_id);
		
		if (!$fileinfo || !$request)
		{
			show404();
		}
		
		//disable downloads for requests not approved
		if($request['status']!=='APPROVED')
		{
			show_error("RESOURCE_NOT_FOUND");
		}
		
		//download stats data
		$download_stats=$this->Licensed_model->get_download_stats($request_id, $file_id);		
		
		if (!$download_stats)
		{
			show_error( 'File is no longer available for download');
		}
		
		//no. of times the file can be downloaded
		$download_limit=$download_stats['download_limit'];
		
		//how many times the files has been downloaded
		//download will stop once the limit is reached
		$download_times=$download_stats['downloads'];
				
		if ($download_times>=$download_limit)
		{			
			redirect('/access_licensed/expired/'.$request_id.'/'.$download_limit,"refresh");exit;
		}				
		
		//increment the download tick
		$this->Licensed_model->update_download_stats($file_id,$request_id,$user->email);
		
		//survey folder path
		$survey_folder=$this->Dataset_model->get_storage_fullpath($fileinfo['survey_id']);
		
		
		//build licensed file path
		$file_path=unix_path($survey_folder.'/'.$fileinfo['filename']);
		
		if (!file_exists($file_path))
		{
			show_error('The file was not found.');
		}
		
		//download file
		$this->load->helper('download');
		
		//log
		log_message('info','Downloading file <em>'.$file_path.'</em>');
		$this->db_logger->write_log('download',$fileinfo['filename'],'microdata',$fileinfo['survey_id']);
		
		force_download2($file_path);
	}


	
	
	
	private function request_form($request_type='study',$sid=NULL,$collection_id=NULL)
	{	
		if ( !is_numeric($sid) && !$collection_id)
		{
			show_404();return;
		}
		
		$repo=NULL;
		$surveys=NULL;
		
		if ($request_type=='collection')
		{
			$repo=$this->Repository_model->get_repository_by_repositoryid($collection_id);

			if($repo['group_da_licensed']!=='1')
			{
				show_404();
			}
			
			$surveys=$this->Repository_model->repo_survey_list($collection_id,array('licensed'));
		}
		else if ($request_type=='study')
		{
			$model=$this->Catalog_model->get_survey_form_model($sid);
			if ($model!=$this->form_model)
			{
				show_404();
			}

			$surveys[]=$this->Catalog_model->select_single($sid);
			
			if ($surveys==FALSE)
			{
				show_404();return;
			}
			
		}
				
		$user=$this->ion_auth->current_user();
		
		$content=NULL;
			
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->surveys=$surveys;
		$data->collection=$repo;
		$data->request_type=$request_type;		
		$data->abstract=$this->input->post("abstract");

		$this->load->library('form_validation');
		
		//validation rules
		$this->form_validation->set_rules('org_rec', t('receiving_organization_name'), 'trim|required|xss_clean|max_length[255]');
		//$this->form_validation->set_rules('address', t('postal_address'), 'trim|required|xss_clean|max_length[255]');
		$this->form_validation->set_rules('tel', t('telephone'), 'trim|required|xss_clean|max_length[14]');
		$this->form_validation->set_rules('datause', t('intended_use_of_data'), 'trim|required|xss_clean|max_length[1000]');
		$this->form_validation->set_rules('dataset_access', t('dataset_access'), 'trim|required|xss_clean|max_length[15]');
		
		//optional fields
		//$this->form_validation->set_rules('org_type', t('org_type'), 'trim|xss_clean|max_length[45]');
		$this->form_validation->set_rules('compdate', t('expected_completion'), 'trim|xss_clean|max_length[45]');
		$this->form_validation->set_rules('datamatching', t('data_matching'), 'trim|xss_clean|max_length[1]');
		$this->form_validation->set_rules('fax', t('fax'), 'trim|xss_clean|max_lengh[14]');		
	
		//process form				
		if ($this->form_validation->run() == TRUE)
		{			
			$post=$_POST;
			$options=array();
			
			foreach($post as $key=>$value)
			{
				$options[$key]=$this->security->xss_clean($value);
			}
		
			if ($request_type=='study')
			{
				$new_requestid=$this->Licensed_model->insert_request($sid,$data->user_id,$options);
			}
			else
			{
				$new_requestid=$this->Licensed_model->insert_collection_request($collection_id,$data->user_id,$options);
			}	
			
			if ($new_requestid!==FALSE)
			{
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				
				//send confirmation email to the user and the site admin
				$this->_confirmation_email($new_requestid);
								
				//redirect to the confirmation page
				redirect('access_licensed/confirm/'.$new_requestid,"refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
			
		//load the contents of the page into a variable
		$content.=$this->load->view('access_licensed/request_form', $data,true);			
			
		//set page title
		$this->template->write('title', t('application_access_licensed_dataset'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	/**
	*
	* Expired Download Links
	**/
	function expired($requestid=NULL,$download_limit=0)
	{
		$data['limit']=$download_limit;
		$data['email']=$this->config->item("website_webmaster_email");
		$contents=$this->load->view('access_licensed/download_limit_reached',$data,TRUE);
	
		$this->template->set_template('default');	
		$this->template->write('content', $contents,true);
		$this->template->render();
	}
	
	
	function additional_info($request_id=NULL)
	{
		if(!is_numeric($request_id))
		{
			show_404();
		}
		
		//get request info
		$data=$this->Licensed_model->get_request_by_id($request_id);
		
		//logged in user
		$user=$this->ion_auth->current_user();
		
		if ($data===FALSE)
		{
			show_error('INVALID-REQUEST');
		}
		
		//user can only view his requests
		if ($data['userid']!=$user->id)
		{
			show_error('INVALID-REQUEST!');
		}
		
		//only process MOREINFO requests
		if (strtoupper($data['status'])!=='MOREINFO')
		{
			show_error('INVALID-REQUEST!');
		}

		$options=array(
				'additional_info'	=> $this->input->post("moreinfo",true)
		);
		
		//update request
		$this->Licensed_model->update_request($request_id,$user->id,$options);
		
		//update history
		$options=array(
			'user_id'			=> $user->email,
			'logtype'			=> 'comment',
			'request_status'	=> $data['status'],
			'description'		=> $options['additional_info']
		);
		
		$this->Licensed_model->add_request_history($request_id,$options);
		$this->_confirmation_email($request_id);
		$this->session->set_flashdata('message', t('request_additional_info_submitted'));
		redirect('access_licensed/track/'.$request_id);
	
	}	
	
/*
	function preview($requestid)
	{
		//get user info
		$user=$this->ion_auth->current_user();

		//get request data
		$data=$this->Licensed_model->get_request_by_id($requestid);
		$data=(object)$data;
		
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->title=$data->request_title;

		$subject=t('confirmation_application_for_licensed_dataset').' - '.$data->title;
		$message=$this->load->view('access_licensed/request_form_printable', $data,true);
	
		echo $message;
	}
*/
	
		
}
/* End of file access_licensed.php */
/* Location: ./controllers/access_licensed.php */