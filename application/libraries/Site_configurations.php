<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Loads the configurations from the database
 *
 **/
class Site_configurations{

	/**
	 * __construct
	 *
	 * @return void
	 **/
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('configurations_model');

		$settings=$this->ci->configurations_model->load();
		
		//check if configurations were found in database, load from db
		if ($settings)
		{
			foreach($settings as $setting)
			{
				$this->ci->config->set_item($setting['name'], $setting['value']);
			}
		}
	}
	
}