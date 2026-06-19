<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/hooks.html
|
*/

$hook['post_controller_constructor'][] = array(
	'class'    => '',
	'function' => 'disable_annonymous_access',
	'filename' => '',
	'filepath' => 'hooks',
	'params'   => array()
);

/**
 * When site_password_protect is enabled, require login for all pages
 * except auth, api, and viewer routes.
 */
function disable_annonymous_access($params)
{
	$CI =& get_instance();

	if ($CI->config->item('site_password_protect') !== 'yes')
	{
		return;
	}

	$CI->load->helper('url');

	if ($CI->uri->segment(1) === 'auth' || $CI->uri->segment(1) === 'api' || $CI->uri->segment(1) === 'viewer')
	{
		return;
	}

	$destination = $CI->uri->uri_string();
	$CI->session->set_userdata('destination', $destination);

	if (!$CI->ion_auth->logged_in())
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
		{
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}

		redirect('auth/login/?destination=' . $destination, 'refresh');
	}
}

/* End of file hooks.php */
/* Location: ./system/application/config/hooks.php */
