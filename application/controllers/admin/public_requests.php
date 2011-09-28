<?php
class Public_requests extends MY_Controller {
 
    public function __construct()
    {
		//skip authentication
        parent::__construct();
       	
        $this->load->model('public_model');
       	$this->template->set_template('blank');
		
		$this->lang->load('general');
		$this->lang->load('public_request');
		//$this->output->enable_profiler(TRUE);
    }
    
	/**
	* Display Public Request Details
	*/
	function _remap($requestid=NULL)
	{	
		if (!is_numeric($requestid))
		{
			show_404();
		}
		
		//get request data
		$data=$this->public_model->select_single($requestid);
		
    	//show listing
		$content=$this->load->view('reports/public_request_view', $data,true);
		
		$this->template->write('title', t('public_request'),true);				
		$this->template->write('content', $content,true);
	  	$this->template->render();	
	}
	

}