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
		
		//initialize db with default config values		
		$this->_init_default_configs();
		
		//$this->output->enable_profiler(TRUE);
	}
	
	function index()
	{	
		$this->form_validation->set_rules('catalog_root', t('catalog_folder'), 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('ddi_import_folder', t('ddi_import_folder'), 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('cache_path', t('cache_folder'), 'xss_clean|trim|max_length[255]|required|callback_check_folder_exists');
		$this->form_validation->set_rules('ddi_import_folder', t('ddi_import_folder'), 'xss_clean|trim|max_length[255]|required|callback_check_folder_exists');
		$this->form_validation->set_rules('catalog_root', t('catalog_folder'), 'xss_clean|trim|max_length[255]|required|callback_check_folder_exists');
		$this->form_validation->set_rules('website_title', t('website_title'), 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('language', t('language'), 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('cache_default_expires', t('cache_expiry'), 'xss_clean|trim|max_length[10]|numeric');
		$this->form_validation->set_rules('catalog_records_per_page', t('catalog_records_per_page'), 'xss_clean|trim|max_length[10]|numeric');
		
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
			$value=$this->security->xss_clean($value);

			if ($key=='language')
			{
				//if language folder exists
				if (file_exists(APPPATH.'language/'.$value))
				{
					$options[$key]=$value;
				}				
			}
			else
			{
				$options[$key]=$value;
			}
		}
		
		$result=$this->Configurations_model->update($options);
		
		if ($result)
		{
			$this->message= t('form_update_success');
		}
		else
		{
			$this->form_validation->set_error(t('form_update_fail'));
		}
	}
	
	/**
	*
	* Callback function to check if folder exists
	*/
	function check_folder_exists($folder=NULL)
	{
		if (!is_dir($folder))
		{
			$this->form_validation->set_message("check_folder_exists","Folder specified for <b>%s</b> [$folder] was not found.");
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}


	/*
	*
	* Add missing configuration values to DB
	*
	*/
	function _init_default_configs()
	{
		//get a list of configurations key/values
		$config_defaults=APPPATH.'/config/config.defaults.php';
		
		if (file_exists($config_defaults))
		{
				include $config_defaults;
		}
		else
		{
			return FALSE;
		}
		
		if (is_array($config) && count($config) >0)
		{
			//load settings from db
			$settings=$this->Configurations_model->get_config_array();
			
			foreach($config as $key=>$value)
			{
				//Config not found in db
				if (!array_key_exists($key,$settings))
				{
					//add configuration to db
					$this->Configurations_model->add($key, $value);
				}				
			}
		}
	}
	
	/**
	*
	* Print all configurations
	**/
	function export()
	{
		//load settings from db
		$settings=$this->Configurations_model->get_config_array();
		
		foreach($settings as $key=>$value)
		{
			echo "<b>\$config['$key']</b>= $value;<BR>";
		}
	}
	
}

/* End of file configurations.php */
/* Location: ./system/application/controllers/configurations.php */