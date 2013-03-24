<?php
class Regions extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct();   
		$this->load->model('country_region_model');
		$this->template->set_template('admin');
    	
		$this->lang->load('general');
		//$this->lang->load('country');	
	}
 
 
	function index()
	{
		$data['tree']=$this->country_region_model->get_tree();
		$content=$this->load->view('regions/index', $data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Regions'),true);
	  	$this->template->render();
	}


	
	

}    