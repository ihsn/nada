<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MY_Email extends CI_Email {
	
	/**
	 * Constructor - Sets Email Preferences
	 *
	 * The constructor can be passed an array of config values
	 */
	function __construct($config = array())
	{
		if (count($config)==0)
		{
			//load default configuration
			parent::__construct($this->_load_settings());
		}
		else
		{
			//intialize with user config
			parent::initialize($config);
		}
	}

	/**
	 * Get settings already loaded from database
	 *
	 */	
	function _load_settings()
	{
		$ci =& get_instance();
		$config['protocol']  = $ci->config->item("mail_protocol");
		$config['smtp_host'] = $ci->config->item("smtp_host");
		$config['smtp_user'] = $ci->config->item("smtp_user");
		$config['smtp_pass'] = $ci->config->item("smtp_pass");
		$config['smtp_port'] = $ci->config->item("smtp_port");
		$config['mailtype']  = 'html';
		$config['charset']   = 'utf-8';
		return $config;
	}
}




