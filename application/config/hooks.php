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
*
* If annonymous access is set to false, ask users to login
*/
function disable_annonymous_access($params)
{
		$CI =& get_instance();
		
		if ($CI->config->item("site_password_protect")!=='yes')
		{
			return;
		}

        $CI->load->helper('url'); // to be on the safe side
			
		//disable rules for the auth/ url, otherwise user will never see the login page
        if($CI->uri->segment(1) !== 'auth')
        {
			//remember the page user was on
			$destination=$CI->uri->uri_string();
			$CI->session->set_userdata("destination",$destination);

			if (!$CI->ion_auth->logged_in()) 
			{
				//redirect to the login page
				redirect("auth/login/?destination=$destination", 'refresh');
			}
        }
}




/* End of file hooks.php */
/* Location: ./system/application/config/hooks.php */