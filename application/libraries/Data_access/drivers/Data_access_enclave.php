<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Data enclave
 *
 * @package		Data Access
 * @subpackage	Libraries
 * @category	NADA Core
 * @author		IHSN
 * @link		
 */

class Data_access_enclave extends CI_Driver {

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
		$this->CI->lang->load('data_enclave');		
	}
	
	function process_form($sid,$user=FALSE)
	{
		return $this->CI->load->view('access_enclave/enclave_info',NULL,TRUE);
	}
	
	
}