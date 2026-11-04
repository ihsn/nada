<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * External Resource Helper
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 */

// ------------------------------------------------------------------------

/**
 * removes the brackets added by nesstar to include the value of the field
 *
 * e.g. English [en] - removes [en]
 *
 * @access	public
 * @return	array
 */	
if ( ! function_exists('strip_brackets'))
{
  
function strip_brackets($value)
{
	if ($value === null || $value === '')
	{
		return $value;
	}

	$pos=strpos($value,"[");
	if ($pos > 0)
	{
		return substr($value,0,$pos-1);
	}
	
	return $value;
}
  
}

/**
 * Safe existence check for a study resource filename (DB/user-controlled relative name).
 *
 * @param string $survey_folder Trusted study folder
 * @param string $filename      Untrusted relative filename from DB
 * @return string|false Absolute path if safe and exists
 */
if ( ! function_exists('check_resource_file_under'))
{
	function check_resource_file_under($survey_folder, $filename)
	{
		if (! function_exists('resolve_under_directory')) {
			$CI =& get_instance();
			$CI->load->helper('file');
		}
		$path = resolve_under_directory($survey_folder, $filename);
		if ($path === false || ! is_file($path)) {
			return FALSE;
		}
		return $path;
	}
}
/* End of file email_helper.php */
/* Location: ./system/helpers/resource_helper.php */
