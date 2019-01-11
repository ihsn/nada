<?php
class Access_public extends MY_Controller {
 
    var $form_model='public';
	
	
	public function __construct()
    {
        parent::__construct($skip=TRUE,$is_admin=FALSE);
       	
        $this->load->model('Form_model');
		$this->load->model('Public_model');
		$this->load->model('Catalog_model');
		$this->load->model('Repository_model');
		$this->load->model('managefiles_model');
       	$this->template->set_template('default');		
		//$this->output->enable_profiler(TRUE);
		
		//change template for ajax
		$ajax_params='';
		if ($this->input->get_post("ajax"))
		{
			$this->ajax=1;
			$this->template->set_template('blank');
			$ajax_params='/?ajax=1';
		}
		
		$this->lang->load('direct_access_terms');
		$this->lang->load('public_access_terms');
		$this->lang->load('public_request');
		
		
		//check if user is logged in
		if (!$this->ion_auth->logged_in()) 
		{
			$this->session->set_flashdata('reason', t('reason_login_public_access'));
			$destination=$this->uri->uri_string();
			$this->session->set_userdata("destination",$destination);

			//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
		}
    }
 
 
 	/**
	*
	*	Main controller for the PUBLIC USE FILES
	*
	* 	1. ask the user for login if not already logged in
	*	2. show the terms and conditions form if not already filled
	*	3. show the user list of files for downloading	
	*
	*/
	function index($sid=NULL)
	{					
		if ( !is_numeric($sid))
		{
			show_404();
			return;
		}
		
		/*
		//check if survey da is set by collection
		$da_by_collection=$this->Repository_model->survey_has_da_by_collection($sid);

		if ($da_by_collection)
		{
			redirect('access_public_collection/'.$sid);exit;
		}	
		*/	
		
		
			
		//get user info
		$user=$this->ion_auth->current_user();
		
		//get survey row
		$survey=$this->Catalog_model->select_single($sid);
		
		if ($survey==FALSE)
		{
			show_404();return;
		}

		//check if the survey has the correct form type
		if ($this->Catalog_model->get_survey_form_model($sid)!=$this->form_model)
		{
			show_404();return;
		}
		
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->survey_title=$survey["titl"];
		$data->sid=$sid;
		$data->survey_uid=$survey["id"];
		$data->proddate=$survey["proddate"];
		$data->abstract=$this->input->post("abstract");

		//check if the user has requested this survey in the past, if yes, don't show the request form
		$request_exists=$this->Form_model->check_user_public_request($data->user_id,$data->survey_uid);
		
		if ($request_exists>0)
		{
			//log
			$this->db_logger->write_log('public-request','viewing public use files','public-request-view',$data->survey_uid);

			//show survey data files 
			$this->_show_data_files($data->survey_uid);

			return;
		}

		//User has not submitted the public use form before
		//Ask user for data intended usage + show terms and conditions
		$this->_terms($data);
	}

	
	/**
	* 	Shows the Public Use Request Form + Terms & Conditions form.
	*	User must fill this form and agree to the terms to download survey files
	*	
	*/
	function _terms($data)
	{
		//validation rules
		$this->form_validation->set_rules('abstract', t('intended_use_of_data'), 'trim|required');
		
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			//insert
			$db_result=$this->Form_model->insert_public_request($data->survey_uid,$data->user_id,$data->abstract);
			
			//log
			$this->db_logger->write_log('public-request','request submitted for public use','public-request',$data->survey_uid);

			if ($db_result===TRUE)
			{
				$destination=current_url();
				
				if ($this->input->get_post("ajax"))
				{
					$destination.='/?ajax=true';
				}
				//redirect back to the list on successful update
				redirect($destination,"refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_failed'));
			}
		}

		$content=$this->load->view('access_public/request_form', $data,true);			
		$this->template->write('title', t('public_use_files'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	/**
	*
	* Shows a listing of downloadable data files
	*/
	function _show_data_files($sid)
	{
		//survey folder  path
		$this->survey_folder=$this->_get_survey_folder($sid);
		
		//get form information
		//$forminfo=$this->Datafiles_model->get_survey_access_form($sid);
		
		//check if the survey form is set to LICENSED
		if ($this->Catalog_model->get_survey_form_model($sid)!='public')
		{
			show_error(t('form_already_saved'));
			return;
		}
		
		/*if ($forminfo===FALSE)
		{					
			show_404();
		}*/
		
		//check survey model and make sure it is the correct type
		/*if ($forminfo['model']!='public')
		{
			show_404();
		}*/
				
		//get files associated with the survey	
		$result['rows']=$this->managefiles_model->get_data_files($sid);
		
		//show listing
		$content=$this->load->view('managefiles/downloads_public', $result,true);		
		$this->template->write('title', t('survey_data_files'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	

	/**
	*
	* Downloads survey data file
	*
	*/
	function download($sid,$file_id)
	{
		if (!is_numeric($sid) )
		{			
			show_404();
			return;
		}

		if (trim($file_id)=='')
		{			
			show_404();
			return;	
		}
		
		if ($this->Catalog_model->get_survey_form_model($sid)!=$this->form_model)
		{
			show_404();
			return;
		}
		
		//get current user
		$user=$this->ion_auth->current_user();
		
		//check if user has accepted the terms and conditions
		$request_exists=$this->Form_model->check_user_public_request($user->id,$sid);
		
		if (!$request_exists)
		{
			$this->index($sid);return;
		}
				
		//get file information
		$fileinfo=$this->managefiles_model->get_resource_by_id($file_id);
		
		//get survey folder path
		$survey_folder=$this->_get_survey_folder($sid);
				
		if (!$fileinfo)
		{
			show_error("file was not found");
		}
		
		//build complete filepath to be downloaded
		$file_path=unix_path($survey_folder.'/'.$fileinfo['filename']);
		
		if (!file_exists($file_path))
		{
			show_error("File was not found");
		}
		
		$this->load->helper('download');
		
		//log
		log_message('info','Downloading file <em>'.$file_path.'</em>');
		$this->db_logger->write_log('survey',$file_id,'public-download',$sid);
		
		//start download
		force_download2($file_path);
	}

	/**
	* Returns the survey folder path
	*
	*/
	function _get_survey_folder($sid)
	{
	
		//build fixed survey folder path
		$catalog_root=$this->config->item("catalog_root");
		$survey_folder=$this->Catalog_model->get_survey_path($sid);

		if($survey_folder===FALSE)
		{
			show_404();
		}
					
		//survey folder path
		$survey_folder=unix_path($catalog_root.'/'.$survey_folder);
		
		return $survey_folder;
	}	


	/**
	*
	* PUF access by collection
	*
	**/
	function by_collection($repositoryid=NULL)
	{
		if ($repositoryid==NULL)
		{
			show_404();
		}		
		
		$repo=$this->Repository_model->get_repository_by_repositoryid($repositoryid);

		if($repo['group_da_public']!=='1')
		{
			show_404();
		}
		
		$user=$this->ion_auth->current_user();
		
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->collection=$repo;
		$data->surveys=$this->Repository_model->repo_survey_list($repositoryid,array('public'));
		$data->abstract=$this->input->post("abstract");
				
		$has_access=$this->Public_model->check_user_public_request_by_collection($user->id,$repositoryid);
		
		if (!$has_access)
		{
			//show terms and conditions
			$this->collection_terms($data);
		}
		else
		{
			//show surveys from collection
			$content=$this->load->view('access_public/collection_surveys',$data,TRUE);
			$this->template->write('title', t('survey_data_files'),true);
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}
	}
	
	private function collection_terms($data=NULL)
	{
		//validation rules
		$this->form_validation->set_rules('abstract', t('intended_use_of_data'), 'trim|required');
		
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			//insert
			$db_result=$this->Public_model->insert_collection_request($data->collection['repositoryid'],$data->user_id,$data->abstract);
			
			//log
			$this->db_logger->write_log('public-request','request submitted for public use','public-request-coll',NULL);

			if ($db_result===TRUE)
			{
				redirect(current_url(),"refresh");
			}
			else
			{
				//update failed
				$this->form_validation->set_error(t('form_update_failed'));
			}
		}
		
		$content=$this->load->view('access_public/collection_request_form',$data,TRUE);
		$this->template->write('title', t('public_use_files'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

}
/* End of file access_public.php */
/* Location: ./controllers/access_public.php */