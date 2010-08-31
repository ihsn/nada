<?php
class Configurations extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );
       	$this->load->model('Configurations_model');		
		$this->template->set_template('admin');
		
		$this->lang->load("configurations");
		//$this->output->enable_profiler(TRUE);
	}
	
	function index()
	{	
		$this->form_validation->set_rules('catalog_root', 'Catalog Folder', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('ddi_import_folder', 'Import Folder', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('cache_folder', 'Cache Folder', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('website_title', 'Website Title', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('website_url', 'Website URL', 'xss_clean|trim|max_length[255]|required');
		
		$settings=NULL;
		if ($this->form_validation->run() === TRUE)
		{
			$this->update();
			$settings=$this->Configurations_model->get_config_array();
		}
		else
		{
			if ($this->input->post("submit")!==false)
			{
				$settings=$_POST;			
			}
			else
			{
				$settings=$this->Configurations_model->get_config_array();//array('title','url','html_folder');
			}	
		}

		$content=$this->load->view('site_configurations/index', $settings,true);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//set page title
		$this->template->write('title', t('Site configurations'),true);

		//render final output
	  	$this->template->render();	
	}
	
	function update()
	{
		$post=$_POST;
		$options=array();
		
		foreach($post as $key=>$value)
		{
			$options[$key]=$this->input->xss_clean($value);
			//$options[]=$setting;
		}
		
		$this->Configurations_model->update($options);
	}
	

}

/* End of file configurations.php */
/* Location: ./system/application/controllers/configurations.php */