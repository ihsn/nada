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
	
	function index()
	{
		$this->load->helper('vite_helper');
		$this->lang->load('general');
		$this->lang->load('catalog_search');

		$catalog_sort_by_options = array(
			array('value' => '', 'label' => t('sort_default')),
			array('value' => 'relevance', 'label' => t('Relevance')),
			array('value' => 'popularity', 'label' => t('Popularity')),
			array('value' => 'year', 'label' => t('year')),
			array('value' => 'title', 'label' => t('title')),
			array('value' => 'country', 'label' => t('country')),
		);
		$catalog_sort_order_options = array(
			array('value' => '', 'label' => t('sort_default')),
			array('value' => 'desc', 'label' => t('sort_desc')),
			array('value' => 'asc', 'label' => t('sort_asc')),
		);

		$view_data = array(
			'site_url'       => site_url(),
			'base_url'       => base_url(),
			'api_base_url'   => site_url('api/admin/configurations/'),
			'csrf_token'     => $this->security->get_csrf_hash(),
			'assets_base'    => base_url('frontend/dist/'),
			'translations'   => $this->lang->language,
			'ui'             => array(
				'catalog_sort_by_options'    => $catalog_sort_by_options,
				'catalog_sort_order_options'   => $catalog_sort_order_options,
			),
		);

		$page = array(
			'title'             => t('site_configurations'),
			'content'           => $this->load->view('admin/site_configurations/index', $view_data, true),
			'hide_breadcrumb'   => true,
			'theme_folder'      => 'adminvue',
		);

		$this->load->view('layouts/admin_vue', $page);
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
	 * Legacy URL — redirect into Vue site configurations (test email section).
	 */
	function test_email()
	{
		$target = site_url('admin/configurations').'#/test_email';
		$this->output->set_content_type('text/html', 'UTF-8');
		$this->output->set_output(
			'<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
			.'<title>'.htmlspecialchars(t('test_email_configurations'), ENT_QUOTES, 'UTF-8').'</title></head><body>'
			.'<script>window.location.replace('.json_encode($target).');</script>'
			.'<p><a href="'.htmlspecialchars($target, ENT_QUOTES, 'UTF-8').'">'
			.htmlspecialchars(t('site_configurations'), ENT_QUOTES, 'UTF-8').' — '.htmlspecialchars(t('test_email_configurations'), ENT_QUOTES, 'UTF-8')
			.'</a></p></body></html>'
		);
	}
	
}

/* End of file configurations.php */
/* Location: ./system/application/controllers/configurations.php */