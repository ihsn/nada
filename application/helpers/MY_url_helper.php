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

	//check if SSL is available on the server
	function is_ssl_enabled()
	{
		$CI =& get_instance();
		if ($CI->config->item("proxy_ssl")===FALSE &&  $CI->config->item("enable_ssl")===FALSE)
		{
			//dont't check anything
			return FALSE;
		}
		
		$is_https=FALSE;
		
		if ($CI->config->item("proxy_ssl"))
		{
			//echo "using proxy";
			$proxy_ssl_header=$CI->config->item("proxy_ssl_header");
			
			//see if the variable is set
			if (isset($_SERVER[$proxy_ssl_header]))
			{
				$is_https=TRUE;
			}
		}
		//check SSL using server HTTPS variablbe
		else if ($CI->config->item("enable_ssl") && isset($_SERVER['HTTPS']))
		{
				$is_https=TRUE;
		}

		return $is_https;
	}
	
	//check if current page is acccessed using HTTPS
	function is_ssl_request()
	{	
		$CI =& get_instance();
		if ($CI->config->item("proxy_ssl")===FALSE &&  $CI->config->item("enable_ssl")===FALSE)
		{
			//dont't check anything
			return FALSE;
		}
		
		//current page is loaded using HTTPS?	
		$is_https=FALSE;
		
		if ($CI->config->item("proxy_ssl"))
		{
			//echo "using proxy";
			$proxy_ssl_header=$CI->config->item("proxy_ssl_header");
			$proxy_ssl_header_value=$CI->config->item("proxy_ssl_header_value");
			
			//check if using SSL/Proxy/Headers
			if ($proxy_ssl_header!='' && $proxy_ssl_header_value!='')
			{		
				//see if the variable is set
				if (isset($_SERVER[$proxy_ssl_header]))
				{
					if ($_SERVER[$proxy_ssl_header]==$proxy_ssl_header_value)
					{
						$is_https=TRUE;
					}
				}
			}
		}
		//check SSL using server HTTPS variablbe
		else if ($CI->config->item("enable_ssl") && isset($_SERVER['HTTPS']))
		{
			//echo "dfoudfdfdfd";
			if($_SERVER['HTTPS']=="on")
			{
				$is_https=TRUE;
			}	
		}

		return $is_https;		
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

		return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
	}
}

/*
//TODO:REMOVE
	function force_proxy_ssl($site_url)
	{
		//check if proxy_ssl =TRUE
		$CI =& get_instance();
		if ($CI->config->item("proxy_ssl")===TRUE || $CI->config->item("enable_ssl")===TRUE)
		{
			//Force SSL for URLs containing /auth/
			if (strpos(current_url(),"/auth/")!==FALSE)
			{
				$site_url=str_replace("http:","https:",$site_url);
			}
		}
		
		return $site_url;
	}
*/

/*
//TODO:REMOVE
	
	//force SSL for specific pages
	function force_ssl($url)
	{
		$CI =& get_instance();
		
		//no SSL support on server
		if (!is_ssl_enabled())
		{
			return $url;
		}
		return $url;
				
		//Force SSL for URLs containing /auth/
		if (strpos(current_url(),"/auth/")!==FALSE)
		{
			$url=str_replace("http:","https:",$url);
		}
		return $url;
	}
*/
	

/**
 * Base URL
 *
 * Returns the "base_url" item from your config file
 *
 * @access	public
 * @return	string
 */
if ( ! function_exists('base_url'))
{
	function base_url()
	{
		$CI =& get_instance();
		$base_url=$CI->config->slash_item('base_url');

		if (is_ssl_enabled() && strpos(current_url(),"/auth/")!==FALSE)
		{
			return $url=str_replace("http:","https:",$base_url);
		}
		
		return $base_url;
	}
}	


/**
 * Site URL
 *
 * Create a local URL based on your basepath. Segments can be passed via the
 * first parameter either as a string or an array.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('site_url'))
{
	function site_url($uri = '')
	{
		$CI =& get_instance();
		$url= $CI->config->site_url($uri);
		
		if (is_ssl_enabled() && strpos(current_url(),"/auth/")!==FALSE)
		{
			return $url=str_replace("http:","https:",$url);
		}
		
		return $url;

	}
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