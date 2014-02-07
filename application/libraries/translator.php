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
	
	
	/**
	 * List language files
	 *
	 * @return array
	 */
	function get_language_files_array($language_path ) {
		
		$modules = array();
		
		$dir = $language_path;

		$d = @dir( $dir );

		if ( $d ) {
			while (false !== ($entry = $d->read())) {
			   	$file = $dir . '/' . $entry;
				if ( is_file( $file ) ) {
					$path_parts = pathinfo( $file );
					if ( $path_parts[ 'extension' ] == 'php' ) {
						$modules[] = $entry;
					}
			   }
			}
			$d->close();
		} else {
				return FALSE;
		}

		sort($modules);		
		return $modules;		
	}
	
	
	//returns an array of merge source and target language translations
	function merge_language_keys($source_file,$target_file)
	{		
		//load the translations from language file into an array
		$source_lang_arr=(array)$this->get_translations_array($source_file);
		$target_lang_arr=(array)$this->get_translations_array($target_file);
		
		foreach($source_lang_arr as $key=>$value)
		{
			//add missing translations to the target file 
			if (!array_key_exists($key,$target_lang_arr))
			{
				$target_lang_arr[$key]=$value;
			}
		}
		
		return $target_lang_arr;		
	}
	
	
	//get language translations key/values as array
	function get_translations_array($file_path)
	{		
		$lang=NULL;
		
		if (file_exists($file_path))
		{
			//fills the values in a local variable $lang
			include $file_path;
		}	
		
		return $lang;
	}
	
	
}// END Translator Class

/* End of file Translator.php */
/* Location: ./application/libraries/translator.php */