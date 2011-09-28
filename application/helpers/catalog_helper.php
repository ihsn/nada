<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Catalog Filter Helper
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		Mehmood Asghar
 * @link		
 */

// ------------------------------------------------------------------------

/**
 * Load email settings from database
 *
 *
 * @access	public
 * @return	array
 */	
if ( ! function_exists('create_filter'))
{
  
function create_filter($fieldname)
{
  	$ci =& get_instance();
	$ci->load->model("Catalog_model");
	return $ci->Catalog_model->select_distinct_field($fieldname);
	return "works";
}
  
}

/* End of file email_helper.php */
/* Location: ./system/helpers/email_helper.php */