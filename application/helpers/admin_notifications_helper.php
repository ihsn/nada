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
		//site web master only - use DB config
		$admin_emails[] = $ci->config->item('website_webmaster_email');
	}	

	log_message('debug', 'notify_admin() called');
	log_message('debug', 'Subject: ' . $subject);
	log_message('debug', 'Notify all: ' . ($notify_all ? 'Yes' : 'No'));
	log_message('debug', 'Admin emails: ' . print_r($admin_emails, true));
	log_message('debug', 'Webmaster email: ' . $ci->config->item('website_webmaster_email'));
	
	$ci->email->clear();
	$ci->email->initialize();
	
	// Set TO to webmaster (required by most email servers when using BCC)
	$webmaster_email = $ci->config->item('website_webmaster_email');
	$ci->email->to($webmaster_email);
	log_message('debug', 'Email TO set to: ' . $webmaster_email);
	
	// Send to all admins via BCC
	if (is_array($admin_emails) && count($admin_emails) > 0) {
		$ci->email->bcc($admin_emails);
		log_message('debug', 'Email BCC set to ' . count($admin_emails) . ' admin(s)');
	} else {
		log_message('error', 'No admin emails found for notification');
	}
	
	$ci->email->subject($subject);
	$ci->email->message($message);
	
	log_message('debug', 'Attempting to send admin notification email...');
	
	if ($ci->email->send())
	{
		log_message('info', 'Admin notification email sent successfully');
		return TRUE;
	}
	else
	{
		log_message('error', 'Admin notification email failed to send');
		log_message('error', 'Email debug info: ' . $ci->email->print_debugger());
		return FALSE;
	}
}

}

/* End of file admin_notifications_helper.php */
/* Location: ./application/helpers/admin_notifications_helper.php */