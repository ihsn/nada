<?php

class Translate extends MY_Controller 
{
	//list of all languages in the language folder
	var $languages=array();
	
	//list of translation files in the select Language file
	var $files=array();
	
	var $master_lang=array(); //translation key/values from master file	
	var $slave_lang=array();  //translation key/values from slave file
	
	var $master =''; 	//master selected language
	var $slave ='';		//slave selected language
	var $file='';		//translation file
	
	var $fill_missing=FALSE;//fill missing slave messages with master translation
	
	var $success=FALSE;
	var $error=FALSE;
	
	function __construct()
	{
		parent::__construct();
		$this->load->library('translator');
		$this->load->helper('form');
		//$this->output->enable_profiler(TRUE);
	}
	
	function index()
	{
		//postback values
		if ($this->input->post("master"))
		{
			$this->master=$this->input->post("master");
		}

		if ($this->input->post("slave"))
		{
			$this->slave=$this->input->post("slave");
		}
		
		if ($this->input->post("file"))
		{
			$this->file=$this->input->post("file");
		}
		
		$this->master_lang=$this->translator->load($this->master.'/'.$this->file);
		$this->slave_lang=$this->translator->load($this->slave.'/'.$this->file);
		
		if ($this->master_lang===FALSE)
		{
			$this->master_lang=array();
		}
		
		if ($this->slave_lang===FALSE)
		{
			$this->slave_lang=array();
		}

		//load languages
		$this->languages=$this->_list_languages(APPPATH.'language/');

		//load translation files
		$this->files=$this->_list_language_files(APPPATH.'language/'.$this->master);
		
		if (!$this->input->post('select'))
		{
			//reload data from POST for slave
			foreach($this->master_lang as $key=>$value)
			{
				if ($this->input->post(md5($key)))
				{
					$this->slave_lang[$key]=$this->input->post(md5($key));
				}
			}
		}
		
		//save
		$this->_save();
		
		//var_dump($lang);
		$this->load->view('translator/index');
	}
	
	function _save()
	{
		if (!$this->input->post('save'))
		{
			return FALSE;
		}
		
		//save the file
		$output_file=APPPATH.'language/'.$this->slave.'/'.$this->file;
		
		$eval= eval($this->load->view('translator/preview',NULL,TRUE));
		
		if ($eval===NULL)
		{
			$file_contents="<?php \n"; 
			$file_contents.=($this->load->view('translator/preview',NULL,TRUE));
			$result=@file_put_contents($output_file, $file_contents);
			if (!$result)
			{
				$this->error='Could not save file. '.$output_file;
			}
			else
			{
				$this->success='File has been saved. '.$output_file;
			}	
		}
	
	}
	
	
	/**
	 * List language files
	 *
	 * @return array
	 */
	function _list_language_files($language_path ) {
		
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

		sort( $modules );
		
		return $modules;		
	}
	
	/**
	 * List languages in working directory
	 *
	 * @return array
	 */
	function _list_languages($language_path, $ignore = NULL ) {

		$languages = array();
		
		$dir = $language_path;

		$d = @dir( $dir );
		if ( $d ) {
			while (false !== ($entry = $d->read())) {
			   if ( ( $entry != $ignore ) && ( $entry != '.' )  && ( $entry != '..' ) && ( $entry != 'CVS' ) && is_dir( $language_path . '/' . $entry) ) {
				$language = $entry;
				$languages[$language] = $language;
			   }
			}
			$d->close();
		} else {
			return FALSE;
		}

		return $languages;		
	}
	
	
	/**
	*
	* Export language package as zip
	**/
	function export($language=NULL)
	{
		//application language folder
		$language_folder=APPPATH.'language/';
		
		//system language folder
		$system_language_folder='system/language/';
		
		//array of available languages
		$languages=$this->_list_languages($language_folder);
		
		if ($language==NULL)
		{
			$this->load->view("translator/export",array('languages'=>$languages));
			return;
		}	

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
		$files=$this->_list_language_files($language_path);
		
		//load zip library
		$this->load->library('zip');

		//create application language folder
		$this->zip->add_dir('application/language/'.$language);

		//add language files
		foreach ($files as $file)
		{
			$this->zip->read_file($language_path.'/'.$file,TRUE); 
		}
		
		$system_files=array();
		$system_language_path='';
		if (file_exists($system_language_folder.$language))
		{
			//$this->zip->read_dir($system_language_folder.$language.'/',FALSE);
			$system_language_path=$system_language_folder.$language.'/';
			$system_files=$this->_list_language_files($system_language_path);
		}
		else
		{
			//$this->zip->read_dir($system_language_folder.'english/');
			$system_language_path=$system_language_folder.'english/';
			$system_files=$this->_list_language_files($system_language_path);			
		}

		//create system language folder
		$this->zip->add_dir('system/language/'.$language);
		
		//add system language files
		foreach($system_files as $file)
		{
			//read file contents
			$contents=file_get_contents($system_language_path.$file);
			
			$this->zip->add_data($name="system/language/$language/$file", $contents);
			//$this->zip->read_file($system_language_path.'/'.$file,TRUE); 
		}		

		//download file
		$this->zip->download($language."-".date("m-d-Y").'.zip');

	}
	
}

/* End of file translate.php */
/* Location: ./system/application/controllers/translate.php */