<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Email Helper
 *
 * @package		  CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		IHSN
 * @link		
 */

// ------------------------------------------------------------------------

/**
 * Load email settings from database
 *
 *
 * @access	public
 * @return	array
 */	
if ( ! function_exists('load_email_settings'))
{
  
function load_email_settings()
{
  $ci =& get_instance();
  $config['protocol']  = $ci->config->item("mail_protocol");
  $config['smtp_host'] = $ci->config->item("smtp_host");
  $config['smtp_user'] = $ci->config->item("smtp_user");
  $config['smtp_pass'] = $ci->config->item("smtp_pass");
  $config['smtp_port'] = $ci->config->item("smtp_port");
  $config['mailtype']  = 'html';
  $config['charset']   = 'utf-8';
  
  if ($config['protocol']===FALSE)
  {
      return FALSE;
  }
  
  return $config;
}
  
}

/* End of file email_helper.php */
/* Location: ./system/helpers/email_helper.php */