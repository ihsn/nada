<?php
class Configurations extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );
       	$this->load->model('Configurations_model');		
		$this->template->set_template('admin5');
		
		$this->lang->load("configurations");
		
		//initialize db with default config values		
		$this->_init_default_configs();
		
		//$this->output->enable_profiler(TRUE);

		$this->acl_manager->has_access_or_die('configurations', 'edit');
	}
	
	private function _skip_field($field) {
		return form_error($field) !== '';
	}
	
	function index()
	{	
		$this->form_validation->set_rules('catalog_root', t('catalog_folder'), 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('ddi_import_folder', t('ddi_import_folder'), 'xss_clean|trim|max_length[255]');		
		$this->form_validation->set_rules('ddi_import_folder', t('ddi_import_folder'), 'xss_clean|trim|max_length[255]|callback_check_folder_exists');
		$this->form_validation->set_rules('catalog_root', t('catalog_folder'), 'xss_clean|trim|max_length[255]|callback_check_folder_exists');
		$this->form_validation->set_rules('website_title', t('website_title'), 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('language', t('language'), 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('cache_default_expires', t('cache_expiry'), 'xss_clean|trim|max_length[10]|numeric');
		$this->form_validation->set_rules('catalog_records_per_page', t('catalog_records_per_page'), 'xss_clean|trim|max_length[10]|numeric');
			
		$settings=NULL;
		if ($this->form_validation->run() === TRUE){
			$this->update();
			$settings=$this->Configurations_model->get_config_array();
		}
		else
		{
			if ($this->input->post("submit")!==false)
			{			
				// changed:
				// Do the same as if all validation returned true, to prevent possibly deleted data
				// HOWEVER: erroneous fields will NOT be saved
				$check_if_failed = array(
					'catalog_root',
					'ddi_import_folder',
					'ddi_import_folder',
					'catalog_root',
					'website_title',
					'language',
					'cache_default_expires',
					'catalog_records_per_page',
				);
				
				// Check, and unset if failed validation test
				foreach($check_if_failed as $test) {
					if ($this->_skip_field($test)) {
						if (isset($_POST[$test])) {
							unset($_POST[$test]);
						}
					}
				}
				$settings=$this->Configurations_model->get_config_array();	
			}
			else
			{
				$settings=$this->Configurations_model->get_config_array();//array('title','url','html_folder');
			}	
		}

		// Language settings for the view: available folders, current mapping, ISO list
		$this->load->library('translator');
		$settings['available_folders'] = $this->translator->get_languages_array();
		$settings['lang_mapping']       = $this->config->item('language_codes');
		if (!is_array($settings['lang_mapping']))
		{
			$settings['lang_mapping'] = array();
		}
		$this->config->load('iso_languages');
		$settings['iso_languages'] = $this->config->item('iso_languages');
		if (!is_array($settings['iso_languages']))
		{
			$settings['iso_languages'] = array();
		}

		$content=$this->load->view('site_configurations/index', $settings,true);
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Site configurations'),true);
	  	$this->template->render();
	}

	/**
	 * Check if a language folder exists in application or userdata.
	 */
	private function _language_folder_exists($folder)
	{
		if (empty($folder) || !is_string($folder)) return FALSE;
		if (file_exists(APPPATH.'language/'.$folder)) return TRUE;
		$userdata = $this->config->item('userdata_path');
		if (!empty($userdata) && is_dir($userdata.'/language/'.$folder)) return TRUE;
		return FALSE;
	}

	function update()
	{
		$post = $_POST;
		$options = array();

		// Build supported_languages from lang_enabled + lang_code
		$lang_enabled = $this->input->post('lang_enabled');
		$lang_code   = $this->input->post('lang_code');
		$this->config->load('iso_languages');
		$iso_languages = $this->config->item('iso_languages');
		if (!is_array($iso_languages)) $iso_languages = array();

		$supported_languages = array();
		if (is_array($lang_enabled))
		{
			foreach ($lang_enabled as $folder => $enabled)
			{
				if (empty($enabled)) continue;
				$folder = trim($folder);
				if ($folder === '') continue;
				$code = isset($lang_code[$folder]) ? trim($lang_code[$folder]) : '';
				$display  = ucfirst($folder);
				$direction = 'ltr';
				if ($code !== '' && isset($iso_languages[$code]))
				{
					$display  = $iso_languages[$code]['display'];
					$direction = isset($iso_languages[$code]['direction']) ? $iso_languages[$code]['direction'] : 'ltr';
				}
				$supported_languages[] = array(
					'folder'    => $folder,
					'display'   => $display,
					'code'      => $code,
					'direction' => $direction,
				);
			}
		}
		// Upsert so the key is created if missing (model update() returns after first add)
		$this->Configurations_model->upsert('supported_languages', json_encode($supported_languages));

		foreach ($post as $key => $value)
		{
			if ($key === 'submit' || $key === 'lang_enabled' || $key === 'lang_code')
			{
				continue;
			}
			if (is_array($value)) continue;

			$value = $this->security->xss_clean($value);

			if ($key === 'language')
			{
				if ($this->_language_folder_exists($value))
				{
					$options[$key] = $value;
				}
				continue;
			}
			$options[$key] = $value;
		}

		$result = $this->Configurations_model->update($options);
		if ($result)
		{
			$this->message = t('form_update_success');
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
	
	function increment_js_css_ver()
	{
		$options=array();
		$options['js_css_version']=date("U");		
		$result=$this->Configurations_model->update($options);
		var_dump($result);
	}


	/**
	 * 
	 * Test email configurations form
	 * 
	 */
	function test_email()
	{
		$this->config->load('email');

		$email_config=array(
			'smtp_host'=>$this->config->item('smtp_host'),
			'smtp_auth'=>$this->config->item('smtp_auth'),
			'smtp_crypto'=>$this->config->item('smtp_crypto'),
			'smtp_user'=>$this->config->item('smtp_user'),
			'mail_from'=>$this->config->item('smtp_user'),
			'smtp_pass'=>'',
			'smtp_port'=>$this->config->item('smtp_port'),
			'useragent'=>$this->config->item('useragent')
		);

		$content=$this->load->view('site_configurations/test_email', $email_config,true);
		$this->template->write('content', $content,true);
		$this->template->write('title', t('Site configurations'),true);
	  	$this->template->render();	
	}

	/**
	 * 
	 * Send test email
	 * 
	 * @input = $_POST
	 * 
	 */
	function send_test_email()
	{	
		$this->config->load('email');
		$this->load->library('email');		

		$config = Array(
			'protocol'  => 'smtp',
			'useragent' =>$this->input->post('useragent'),
			'smtp_host' => $this->input->post('smtp_host'),
			'smtp_port' => $this->input->post('smtp_port'),
			'smtp_user' => $this->input->post('smtp_user'),
			'smtp_pass' => $this->input->post('smtp_pass'),
			'mailtype'  => 'html',
			'smtp_debug'  => 2,
			'smtp_auth' =>$this->input->post('smtp_auth'),
			'smtp_crypto' =>$this->input->post('smtp_crypto'),
		);

		//password
		if($config['smtp_pass']==''){
			//use password from the config file
			$config['smtp_pass']=$this->config->item("smtp_pass");
		}

		// Initialize email with test config
		$this->email->initialize($config);
		
		// Override FROM if provided
		$email_sender = $this->input->post("mail_from");
		if (!empty($email_sender)) {
			$this->email->from($email_sender);
		}
		
		$this->email->to($this->input->post('mail_to'));		
		$this->email->subject('NADA test email');
		$this->email->message('NADA test email message body');
		$this->email->send();
		echo $this->email->print_debugger();
	}
	
}

/* End of file configurations.php */
/* Location: ./system/application/controllers/configurations.php */