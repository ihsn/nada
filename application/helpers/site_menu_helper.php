<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Back-end site menu helper functions
 *
 */

// ------------------------------------------------------------------------

/**
 * Load menu items per user access
 *
 *
 * @access	public
 * @return	array
 */	
if ( ! function_exists('get_site_menu'))
{  
	function get_site_menu()
	{
		$ci =& get_instance();
		//$ci->load->model("Catalog_model");
		
		$ci->load->library('site_menu');
		return $ci->site_menu->get_formatted_menu_tree();
	}
  
}

/* End of file site_menu_helper.php */
/* Location: ./system/helpers/site_menu_helper.php */