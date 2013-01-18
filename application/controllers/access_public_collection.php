<?php
class Access_public_collection extends MY_Controller {
 
    var $form_model='public';
	
	
	public function __construct()
    {
        parent::__construct($skip=TRUE,$is_admin=FALSE);
       	
        $this->load->model('Public_model');
		$this->load->model('Form_model');
		$this->load->model('Catalog_model');
		//$this->load->model('Repository_model');
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
	function index($survey_id=NULL)
	{					
		if ( !is_numeric($survey_id))
		{
			show_404();
			return;
		}
		
		//check if user is logged in
		if (!$this->ion_auth->logged_in()) 
		{
			$this->session->set_flashdata('reason', t('reason_login_public_access'));
			$destination=$this->uri->uri_string();
			$this->session->set_userdata("destination",$destination);

			//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
		}
			
		//check if survey da is set by collection
		$collection_row=$this->Repository_model->survey_has_da_by_collection($survey_id);
		
		//check collection setting
		if (!$collection_row)
		{
			show_error('INVALID_REQUEST');
		}
		
		$collection_id=$collection_row['repositoryid'];
					
		//get user info
		$user=$this->ion_auth->current_user();
		
		//get survey row
		$survey=$this->Catalog_model->select_single($survey_id);
		
		if ($survey==FALSE)
		{
			show_404();return;
		}

		//check if the survey has the correct form type
		if ($this->Catalog_model->get_survey_form_model($survey_id)!=$this->form_model)
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
		$data->survey_id=$survey_id;
		$data->survey_uid=$survey["id"];
		$data->proddate=$survey["proddate"];
		$data->abstract=$this->input->post("abstract");
		$data->repositoryid=$collection_id;

		//check if the user has requested this survey in the past, if yes, don't show the request form
		$request_exists=$this->Public_model->check_user_public_request_by_collection($data->user_id,$collection_id);

		if ($request_exists>0)
		{
			//log
			$this->db_logger->write_log('public-request','viewing public use files','public-request-view',$data->survey_uid);
			
			$data=array();
			$data['collection_title']=$collection_row['title'];
			
			//get all PUF surveys
			$data['surveys']=$this->Public_model->get_surveys_by_collection($collection_id);
			
			//get data for each survey
			foreach($data['surveys'] as $key=>$survey)
			{
				//get microdata resources
				$data['surveys'][$key]['resources']=$this->managefiles_model->get_data_files($survey['id']);
				$data['surveys'][$key]['survey_folder']=$this->_get_survey_folder($survey['id']);
			}
			
			$content=$this->load->view('access_public_collection/public_downloads', $data,true);					
			$this->template->write('title', t('collection_data_files'.' - '.$collection_row['title']),true);
			$this->template->write('content', $content,true);
			$this->template->render();

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

		if ($this->config->item('puf_use_collection')=='1')
		{
			$data->surveys=$this->Public_model->get_all_public_use_surveys();
		}

		$content=$this->load->view('access_public_collection/request_form', $data,true);			
		$this->template->write('title', t('public_use_files'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	/**
	*
	* Shows a listing of downloadable data files
	*/
	function _show_data_files($surveyid)
	{
		//check if the survey form is set to LICENSED
		if ($this->Catalog_model->get_survey_form_model($surveyid)!='public')
		{
			show_error(t('form_already_saved'));
			return;
		}
						
		//get survey info
		$result['survey']=$this->Catalog_model->select_single($surveyid);
		
		//get files associated with the survey	
		$result['rows']=$this->managefiles_model->get_data_files($surveyid);
		
		//needed for download paths
		$result['survey_folder']=$this->_get_survey_folder($surveyid);
		
		//show listing
		return $this->load->view('access_public_collection/public_downloads', $result,true);		
	}
	

	/**
	*
	* Downloads survey data file
	*
	*/
	function download($survey_id,$file_id)
	{
		if (!is_numeric($survey_id) )
		{			
			show_404();
			return;
		}

		if (trim($file_id)=='')
		{			
			show_404();
			return;	
		}
		
		if ($this->Catalog_model->get_survey_form_model($survey_id)!=$this->form_model)
		{
			show_404();
			return;
		}
				
		//get file information
		$fileinfo=$this->managefiles_model->get_resource_by_id($file_id);
		
		//get survey folder path
		$survey_folder=$this->_get_survey_folder($survey_id);
				
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
		$this->db_logger->write_log('survey',$file_id,'public-download',$survey_id);
		
		//start download
		force_download2($file_path);
	}

	/**
	* Returns the survey folder path
	*
	*/
	function _get_survey_folder($surveyid)
	{
	
		//build fixed survey folder path
		$catalog_root=$this->config->item("catalog_root");
		$survey_folder=$this->Catalog_model->get_survey_path($surveyid);

		if($survey_folder===FALSE)
		{
			show_404();
		}
					
		//survey folder path
		$survey_folder=unix_path($catalog_root.'/'.$survey_folder);
		
		return $survey_folder;
	}	


}
/* End of file access_public.php */
/* Location: ./controllers/access_public.php */