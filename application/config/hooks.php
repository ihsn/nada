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

$hook['post_controller'][] = array(
	'class'    => '',
	'function' => 'inject_site_notice',
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

/**
 * Add the site notice dialog and header bar (Site configurations > Site notice) to public
 * HTML pages. Injected into the final output so it works with any theme.
 */
function inject_site_notice($params)
{
	if (is_cli())
	{
		return;
	}

	$CI =& get_instance();

	$message = trim((string) $CI->config->item('site_notice_message'));
	$bar_text = trim((string) $CI->config->item('site_notice_bar_text'));

	$show_dialog = $CI->config->item('site_notice_enabled') === 'yes' && $message !== '';
	$show_bar = $CI->config->item('site_notice_bar_enabled') === 'yes' && $bar_text !== '';

	if (!$show_dialog && !$show_bar)
	{
		return;
	}

	if (in_array(strtolower((string) $CI->uri->segment(1)), array('admin', 'api', 'embed'), TRUE))
	{
		return;
	}

	if ($CI->output->get_content_type() !== 'text/html')
	{
		return;
	}

	$output = $CI->output->get_output();
	$body_end = strripos($output, '</body>');

	// AJAX fragments and other partial responses have no closing body tag
	if ($body_end === FALSE)
	{
		return;
	}

	if ($show_dialog)
	{
		$minutes = $CI->config->item('site_notice_remember_minutes');

		$notice = $CI->load->view('partials/site_notice', array(
			'title'   => trim((string) $CI->config->item('site_notice_title')),
			'message' => $message,
			'minutes' => is_numeric($minutes) ? max(0, (int) $minutes) : 10,
		), TRUE);

		$output = substr_replace($output, $notice, $body_end, 0);
	}

	if ($show_bar && preg_match('/<body\b[^>]*>/i', $output, $body_tag, PREG_OFFSET_CAPTURE))
	{
		$bar = $CI->load->view('partials/site_notice_bar', array('text' => $bar_text), TRUE);
		$output = substr_replace($output, $bar, $body_tag[0][1] + strlen($body_tag[0][0]), 0);
	}

	$CI->output->set_output($output);
}

/* End of file hooks.php */
/* Location: ./system/application/config/hooks.php */
