<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Remote Data Access
 *
 * @package		Data Access
 * @subpackage	Libraries
 * @category	NADA Core
 * @author		IHSN
 * @link		
 */

class Data_access_remote extends CI_Driver {

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
		$this->CI->lang->load('direct_access_terms');		
	}
	
	function process_form($sid,$user=FALSE)
	{
		$link=$this->get_study_data_link($sid);
		if (!$link)
		{
			return t("No data is available");
		}
		
		$link=form_prep($link);
		return $this->CI->load->view('access_remote/data_access', array('link'=>$link),TRUE);		
	}
	
	
	private function get_study_data_link($sid)
	{
		$this->CI->db->select('link_da');
		$this->CI->db->where('id',$sid);
		$query=$this->CI->db->get('surveys');
		
		if($query)
		{
			$row=$query->row_array();
			
			if($row)
			{
				return $row['link_da'];
			}
		}
	}
	
	
}