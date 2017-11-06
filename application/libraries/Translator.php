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
		$file=$file.'_lang.php';
	
		if ( ! file_exists(APPPATH.'language/'.$file))
		{
				return FALSE;
		}
	

		include(APPPATH.'language/'.$file);

		if ( ! isset($lang) OR ! is_array($lang))
		{
				return FALSE;
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
	
	
	/**
	* Returns an array of available languages
	*
	**/
	function get_languages_array()
	{
		$lang_folder=APPPATH.'/language';
		
		$languages=scandir($lang_folder);
		
		//get rid of the .,.. entries
		$languages=array_diff($languages,array(".","..","base"));
		
		return $languages;
	}
	
	//check if a language folder exists
	function language_exists($lang_name)
	{
		$languages=$this->get_languages_array();
		
		if (in_array($lang_name,$languages))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	
	function translation_file_exists($language,$translation_file)
	{
		$fullpath=APPPATH.'/'.$language.'/'.$translation_file;
		if (file_exists($fullpath))
		{
			return true;
		}
		
		return false;
	}
	
	
	/**
	*
	* Export language package as zip
	**/
	function export($language)
	{
		//application language folder
		$language_folder=APPPATH.'language/';
		
		//system language folder
		$system_language_folder='system/language/';
		
		//array of available languages
		$languages=$this->get_languages_array();
		
		//check if valid language name
		if (!in_array($language, $languages))
		{
			show_error('INVALID LANGUAGE');
		}
		
		//full path to the language folder
		$language_path=$language_folder.$language;
		
		//check if the language folder exist
		if (!file_exists($language_path))
		{
			show_error("NOT FOUND");
		}
		
		//list of files found in the language folder
		$files=$this->get_language_files_array($language_path);
		
		//load zip library
		$this->ci->load->library('zip');

		//create application language folder
		$this->ci->zip->add_dir('application/language/'.$language);

		//add language files
		foreach ($files as $file)
		{
			$this->ci->zip->read_file($language_path.'/'.$file,TRUE); 
		}
		
		$system_files=array();
		$system_language_path='';
		
		if (file_exists($system_language_folder.$language))
		{
			//$this->zip->read_dir($system_language_folder.$language.'/',FALSE);
			$system_language_path=$system_language_folder.$language.'/';
			$system_files=$this->get_language_files_array($system_language_path);
		}
		else
		{
			//$this->zip->read_dir($system_language_folder.'english/');
			$system_language_path=$system_language_folder.'english/';
			$system_files=$this->get_language_files_array($system_language_path);			
		}

		//create system language folder
		$this->ci->zip->add_dir('system/language/'.$language);
		
		//add system language files
		foreach($system_files as $file)
		{
			//read file contents
			$contents=file_get_contents($system_language_path.$file);
			
			$this->ci->zip->add_data($name="system/language/$language/$file", $contents);
			//$this->zip->read_file($system_language_path.'/'.$file,TRUE); 
		}		

		//download file
		$this->ci->zip->download($language."-".date("m-d-Y").'.zip');

	}
	
}// END Translator Class

/* End of file Translator.php */
/* Location: ./application/libraries/translator.php */