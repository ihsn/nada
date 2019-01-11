<?php
class Access_enclave extends MY_Controller {

	var $form_model='enclave'; 

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
       	
		$this->load->model('Catalog_model');       	
       	$this->template->set_template('default');
		
		//language
		$this->lang->load('data_enclave');		
		
		//$this->output->enable_profiler(TRUE);
    }
 
 
 	/**
	*
	*	Main controller for the DATA ENCLAVE
	*
	*	Shows the information on how to request access and a
	*	link to download the form for required information
	*
	*/
	function index($sid=NULL)
	{					
		if ( !is_numeric($sid))
		{
			show_404();return;
		}
				
		//get survey row
		$survey=$this->Catalog_model->select_single($sid);
		
		if ($survey==FALSE)
		{
			show_404();return;
		}
		
		//check if the survey has the correct form type
		if ($this->Catalog_model->get_survey_form_model($sid)!=$this->form_model)
		{
		//	show_404();
		//	return;
		}
		
		//show the information
		$content=$this->load->view('access_enclave/enclave_info', $data=NULL,true);							
		$this->template->write('title', 'Data Enclave',true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}


	

}
/* End of file access_enclave.php */
/* Location: ./controllers/access_enclave.php */