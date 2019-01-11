<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Direct Data Access
 *
 * @package		Data Access
 * @subpackage	Libraries
 * @category	NADA Core
 * @author		IHSN
 * @link		
 */

class Data_access_open extends CI_Driver {

	protected $CI;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		
		$this->CI->load->model('Catalog_model');
		$this->CI->load->model('managefiles_model');
		$this->CI->load->model('Resource_model');
		$this->CI->lang->load('open_data');		
	}
	
	function process_form($sid,$user=FALSE)
	{
		if ($this->CI->input->post("accept"))
		{
			//get study microdata files
			$result['resources_microdata']=$this->CI->Resource_model->get_microdata_resources($sid);//$this->CI->managefiles_model->get_data_files($sid);
			$result['sid']=$sid;
			$result['storage_path']=$this->CI->Dataset_model->get_storage_fullpath($sid);
			return $this->CI->load->view('catalog_search/survey_summary_microdata', $result,TRUE);		
		}
		
		//show the Terms and Conditions form
		return $this->CI->load->view('request_forms/open_access_terms',NULL,TRUE);							
		$this->CI->template->write('title', t('title_terms_and_conditions'),true);
	}
	
	
	/**
	* 
	* Get the request form
	**/
	private function get_application_form($sid,$userid)
	{
		return $this->CI->load->view("access_public/request_form",NULL,TRUE);
	}

}