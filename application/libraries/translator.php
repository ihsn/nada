<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA language translator (Beta)
 * 
 *
 *
 * @package		NADA 3.0
 * @subpackage	Libraries
 * @author		Mehmood
 * @link		-
 *
 */
class Translator{
	
	
	var $ci;
	var $config = array();
	var $is_loaded = array();
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
    }
	
	
	/**
	*
	* Loads a language file 
	*
 	* TODO:// copied from the CI translator, need to find the author to give credit
	**/
	function load($file)
	{		
		$fail_gracefully=TRUE;
		$file = ($file == '') ? 'language' : str_replace(EXT, '', $file);
	
		if (in_array($file, $this->is_loaded, TRUE))
		{
			return TRUE;
		}
		
		if ( ! file_exists(APPPATH.'language/'.$file.EXT))
		{
			if ($fail_gracefully === TRUE)
			{
				return FALSE;
			}
			show_error('The language file '.$file.EXT.' does not exist.');
		}
	
		include(APPPATH.'language/'.$file.EXT);

		if ( ! isset($lang) OR ! is_array($lang))
		{
			if ($fail_gracefully === TRUE)
			{
				return FALSE;
			}
			show_error('Your '.$file.EXT.' file does not appear to contain a valid language array.');
		}
		
		return $lang;
	}
	
}// END Translator Class

/* End of file Translator.php */
/* Location: ./application/libraries/translator.php */