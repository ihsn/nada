<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * Markdown parser
 *
 */

// ------------------------------------------------------------------------

/**
 * 
 * 
 * Parse markdown text
 *
 *
 */	
if ( ! function_exists('markdown_parse'))
{  
	function markdown_parse($string)
	{
		$ci =& get_instance();
		$ci->load->library('MarkdownParser');
		return $ci->markdownparser->parse_markdown($string);
	}
}

if ( ! function_exists('markdown_parse_richtext'))
{
	/**
	 * Parse sanitized rich text (markdown + allowed HTML).
	 *
	 * @param string|array $string
	 * @return string
	 */
	function markdown_parse_richtext($string)
	{
		$ci =& get_instance();
		$ci->load->library('MarkdownParser');
		return $ci->markdownparser->parse_richtext($string);
	}
}

/* End of file markdown_helper.php */
/* Location: ./system/helpers/markdown_helper.php */