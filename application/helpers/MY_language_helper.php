<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Language Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/language_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Lang
 *
 * Fetches a language variable and optionally outputs a form label
 *
 * @access	public
 * @param	string	the language line
 * @param	string	the id of the form element
 * @return	string
 */	
if ( ! function_exists('t'))
{
	function t($line, $id = '')
	{
		$CI =& get_instance();
		$str = $CI->lang->line($line);

		if ($id != '')
		{
			$str = '<label for="'.$id.'">'.$str."</label>";
		}
		if ($str=='')
		{
			//log missing translations
			log_message('DEBUG','Missing Translation - '. $line);
			return $line;
			//return '<span style="color:red">'.$line.'</span>';
		}
		//return '<span style="color:orange">'.$str.'</span>';
		//return '@'.$str;
		return $str;
	}
}

if ( ! function_exists('tt'))	
{
	function tt($line,$default=null)
	{
		$CI =& get_instance();
		$use_template_translation = $CI->config->item('use_template_translation');

		//use template translation [default value]
		if ($use_template_translation==true && $default!=null){
			$line = $default;			
		}
		
		$str = $CI->lang->line($line);

		if ($str=='')
		{
			//log missing translations
			log_message('DEBUG','Missing Translation - '. $line);

			if ($default==null){
				return $line;
			}

			return t($default);
		}
		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * ci_lang_to_iso
 *
 * Converts a CodeIgniter language folder name (e.g. 'english', 'french')
 * to an ISO 639-1 code (e.g. 'en', 'fr') using the iso_languages config.
 * Falls back to 'en' if no match is found.
 *
 * @param  string $ci_lang  CI language name (lowercase), defaults to config 'language'
 * @return string           ISO 639-1 code
 */
if ( ! function_exists('ci_lang_to_iso'))
{
    function ci_lang_to_iso($ci_lang = null)
    {
        $CI =& get_instance();
        if ($ci_lang === null) {
            $ci_lang = $CI->config->item('language') ?: 'english';
        }
        $ci_lang = strtolower($ci_lang);
        $CI->config->load('iso_languages', true);
        $iso_langs = $CI->config->item('iso_languages', 'iso_languages');
        if (is_array($iso_langs)) {
            foreach ($iso_langs as $code => $info) {
                if (strtolower($info['name']) === $ci_lang) {
                    return $code;
                }
            }
        }
        return 'en';
    }
}

// ------------------------------------------------------------------------
/* End of file language_helper.php */
/* Location: ./system/helpers/language_helper.php */