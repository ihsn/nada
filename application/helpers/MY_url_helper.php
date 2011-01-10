<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * URL helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		International household survey network
 * @link		---
 */

// ------------------------------------------------------------------------

/**
 * JS Redirect
 *
 * JS redirect
 *
 * @access	public
 * @param	string	the URL
 * @param	string	the URL
 * @return	string
 */
if ( ! function_exists('js_redirect'))
{
	function js_redirect($uri = '',$seconds=0)
	{
		$output= '<script type="text/javascript">';
		$output.= "var seconds=$seconds;";
		$output.= "var uri=\"$uri\";";
		$output.= 'setTimeout("window.location=\'"+uri+"\'",seconds*1000);';
		$output.= '</script>';
        $output.= '<noscript>';
        $output.= '<meta http-equiv="refresh" content="'.$seconds.';url='.$uri.'" />';
        $output.= '</noscript>';
        
        return $output;
	}
}

// ------------------------------------------------------------------------

/**
 * Prep URL extended to support ftp:// or https:
 *
 * Simply adds the http:// part if missing
 *
 * @access	public
 * @param	string	the URL
 * @return	string
 */

if ( ! function_exists('prep_url'))
{
	function prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			return '';
		}

		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://' && substr($str, 0, 6) != 'ftp://')
		{
			$str = 'http://'.$str;
		}

		return $str;
	}
}

/**
*
* Returns the site home page set by the user
*
**/
if ( ! function_exists('site_home'))
{
	function site_home()
	{
    	$CI =& get_instance();
		$home=$CI->config->item("default_home_page");
        if($home==false)
        {
        	return 'catalog';
        }
        return $home;
	}
} 

/**
*
* Returns JS/CSS base url
*
**/
if ( ! function_exists('js_base_url'))
{
	function js_base_url()
	{
    	if (defined('JS_BASE_URL'))
        {
        	return JS_BASE_URL;
		}
		
		return base_url();
	}
} 

/* End of file MY_url_helper.php */
/* Location: ./application/helpers/MY_url_helper.php */