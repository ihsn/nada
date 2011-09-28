<?php
class Repository extends MY_Controller {

	var $filter;//stores all search options
	
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
		
       	$this->template->set_template('default');
		$this->template->write('sidebar', $this->_menu(),true);	
		$this->load->model('Search_helper_model');
		$this->load->model('Catalog_model');
		$this->load->library('pagination');
	 	//$this->output->enable_profiler(TRUE);
    		
		//language files
		$this->lang->load('general');
		$this->lang->load('catalog_search');		
	}


	function index()
	{
		//reset any search options selected
		$this->session->unset_userdata('search');
		
		$this->load->model("repository_model");
		
		//get a list of all repositories
		$repo_arr=$this->repository_model->get_repositories();
		
		$content=$this->load->view("repositories/index_public",array('rows'=>$repo_arr),TRUE);
		
		//set page title
		$this->template->write('title', t('home'),true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
}
/* End of file catalog.php */
/* Location: ./controllers/catalog.php */