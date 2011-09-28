<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Send notification emails to the site admins
 *
 *
 * @access	public
 * @subject		string	- email subject
 * @message		string	- email message body
 * @notify_all	bool	- whether to notify all admins or just the site web master
 */	
if ( ! function_exists('notify_admin'))
{
function notify_admin($subject,$message,$notify_all=FALSE)
{	
	$ci =& get_instance();	
	$ci->load->library('email');
	
	//get user info
	$user=$ci->ion_auth->current_user();

	if ($notify_all==TRUE)
	{
		//get array of all site adminstrators
		$admin_emails=$ci->ion_auth->get_admin_emails();
	}
	else
	{
		//site web master only
		$admin_emails[]=$ci->config->item('website_webmaster_email');
	}	

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

/* End of file admin_notifications_helper.php */
/* Location: ./application/helpers/admin_notifications_helper.php */