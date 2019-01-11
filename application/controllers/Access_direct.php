<?php
class Access_direct extends MY_Controller {

	var $form_model='direct'; 

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
       	
		$this->load->model('Catalog_model');
		$this->load->model('managefiles_model');
		$this->lang->load('direct_access_terms');
       	$this->template->set_template('default');		
		
		//change template for ajax
		$ajax_params='';
		if ($this->input->get_post("ajax"))
		{
			$this->ajax=1;
			$this->template->set_template('blank');
			$ajax_params='/?ajax=1';
		}
		//$this->output->enable_profiler(TRUE);
    }
 
 
 	/**
	*
	*	Main controller for the DIRECT USE FILES
	*
	*	1. show the terms and conditions
	*	2. show the list of files for downloading	
	*
	*/
	function index($survey_id=NULL)
	{			
		if ( !is_numeric($survey_id))
		{
			show_404();return;
		}
				
		//get survey row
		$survey=$this->Catalog_model->select_single($survey_id);
		
		if ($survey==FALSE)
		{
			show_404();return;
		}
		
		//check if the survey has the correct form type
		if ($this->Catalog_model->get_survey_form_model($survey_id)!=$this->form_model)
		{
			show_404();
			return;
		}
		
		$data->survey_title=$survey["titl"];
		$data->survey_id=$survey["idno"];
		$data->survey_uid=$survey["id"];
		$data->proddate=$survey["proddate"];

		if ($this->_terms()===TRUE)
		{
			//show survey files 
			$this->_show_data_files($data->survey_uid);

			return;
		}

	}

	
	/**
	* 	Show the terms and conditions form before taking 
	*	the user to the download page
	*
	*/
	function _terms()
	{
		if ($this->input->post("accept"))
		{
			return TRUE;
		}
		
		//show the Terms and Conditions form
		$content=$this->load->view('request_forms/access_terms', $data=NULL,true);							
		$this->template->write('title', t('title_terms_and_conditions'),true);
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
		
		//check if the survey form is set to LICENSED
		if ($this->Catalog_model->get_survey_form_model($sid)!='direct')
		{
			show_error(t('form_removed_and_not_available'));
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
		$content=$this->load->view('managefiles/downloads_direct', $result,true);		
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
				
		//get file information
		$fileinfo=$this->managefiles_model->get_resource_by_id($file_id);
		
		//get survey folder path
		$survey_folder=$this->_get_survey_folder($sid);
				
		if (!$fileinfo)
		{
			show_error(t('file_not_found'));
		}
		
		//build complete filepath to be downloaded
		$file_path=unix_path($survey_folder.'/'.$fileinfo['filename']);
		
		if (!file_exists($file_path))
		{
			show_error(t('file_not_found'));
		}
		
		$this->load->helper('download');

		//log		
		log_message('info','Downloading file <em>'.$file_path.'</em>');
		$this->db_logger->write_log('survey',$file_id,'direct-download',$sid);

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
}
/* End of file access_direct.php */
/* Location: ./controllers/access_direct.php */