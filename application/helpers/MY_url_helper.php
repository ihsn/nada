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
        	return force_proxy_ssl(JS_BASE_URL);
		}
		
		return force_proxy_ssl(base_url());
	}
} 

/**
 * Anchor Link
 *
 * Creates an anchor based on the local URL.
 *
 * @access	public
 * @param	string	the URL
 * @param	string	the link title
 * @param	mixed	any attributes
 * @return	string
 */
if ( ! function_exists('anchor'))
{
	function anchor($uri = '', $title = '', $attributes = '')
	{
		$title = (string) $title;

		if ( ! is_array($uri))
		{
			$site_url = ( ! preg_match('!^\w+://! i', $uri)) ? site_url($uri) : $uri;
		}
		else
		{
			$site_url = site_url($uri);
		}

		if ($title == '')
		{
			$title = $site_url;
		}

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		$site_url=force_proxy_ssl($site_url);		
		return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
	}
}

	function force_proxy_ssl($site_url)
	{
		//check if proxy_ssl =TRUE
		$CI =& get_instance();
		if ($CI->config->item("proxy_ssl")===TRUE)
		{
			//Force SSL for URLs containing /auth/
			if (strpos(current_url(),"/auth/")!==FALSE)
			{
				$site_url=str_replace("http:","https:",$site_url);
			}
		}
		
		return $site_url;
	}


/*
if ( ! function_exists('sanitize_url'))
{
	function sanitize_url($url)
	{
		$url_parts=explode("/",$url);
		foreach($url_parts as $key=>$value)
		{
			$url_parts[$key]=filter_var($value,FILTER_SANITIZE_ENCODED, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}

		$url=implode("/",$url_parts);		
		return $url;
	}
}
*/


/* End of file MY_url_helper.php */
/* Location: ./application/helpers/MY_url_helper.php */