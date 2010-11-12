<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Compare variables
 * 
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @author		Mehmood Asghar
 * @link		-
 *
 */ 
class Compare_variable{
    
	var $ci=NULL;
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->helper('xslt_helper');
		$this->ci->load->model('catalog_model');
		$this->ci->load->library('DDI_Browser');
		$this->ci->config->load('cache');
    }

	
	function get_ddi_path($survey_id)
	{
			return $this->ci->catalog_model->get_survey_ddi_path($survey_id);
	}
	
	/**
	 * Ouputs HTML for the variable
	 * using the XSLT. 
	 *
	 * @return array
	 **/
	function get_variable_html($survey_id,$variable_id)
	{	
		//get path to the ddi file
		$ddi_file=$this->get_ddi_path($survey_id);
		
		if (!file_exists($ddi_file))
		{
			return false;
		}

		//language
		$language=array('lang'=>$this->ci->config->item("language"));
		
		if(!$language)
		{
			//default language
			$language=array('lang'=>"english");
		}

		//get the xml translation file path
		$language_file=$this->ci->ddi_browser->get_language_path($language['lang']);
		
		if ($language_file)
		{
			//change to the language file (without .xml) in cache
			$language['lang']=unix_path(FCPATH.$language_file);
		}				
		return $this->ci->ddi_browser->get_variable_html($ddi_file,$variable_id,$language);
	}
	

	function get_survey_title($survey_id)
	{
		$survey=$this->ci->catalog_model->get_survey($survey_id);
		
		if ($survey)
		{
			return $survey['nation']. ' - ' . $survey['titl'];
		}
		
		return FALSE;
	}

	function get_variable_label($survey_id,$variable_id)
	{
		$variable=$this->ci->catalog_model->get_variable_by_vid($survey_id,$variable_id);
		
		if ($variable)
		{
			return $variable['labl'];
		}
		
		return FALSE;
	}

	function get_variable_name($survey_id,$variable_id)
	{
		$variable=$this->ci->catalog_model->get_variable_by_vid($survey_id,$variable_id);
		
		if ($variable)
		{
			return $variable['name'];
		}
		
		return FALSE;
	}
	
}
// END Compare_variable 
/* End of file Compare_variable.php */
/* Location: ./application/libraries/Compare_variable.php */