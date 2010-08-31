<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Send notification emails to the site admins
 *
 *
 * @access	public
 * @param	string
 * @param	string
 */	
if ( ! function_exists('notify_admin'))
{
function notify_admin($subject,$message)
{	
	$ci =& get_instance();
	
	$ci->load->library('email');
	
	//get user info
	$user=$ci->ion_auth->current_user();

	//get array of all site adminstrators
	$admin_emails=$ci->ion_auth->get_admin_emails();		

	//configure mail
	$ci->email->clear();
	$config['mailtype'] = "html";
	$ci->email->initialize($config);
	$ci->email->set_newline("\r\n");
	$ci->email->from($ci->config->item('website_webmaster_email'), $ci->config->item('website_title'));
	//$ci->email->to($ci->config->item('website_webmaster_email'));
	
	$ci->email->bcc($admin_emails);
	
	$ci->email->subject($subject);
	$ci->email->message($message);
	
	if ($ci->email->send())
	{
		return TRUE;
	}
	else
	{
		return FALSE;
	}
}

}

/* End of file search_helper.php */
/* Location: ./application/helpers/search_helper.php */