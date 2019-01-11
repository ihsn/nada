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

	if ($ci->acl->user_has_unlimited_access())
	{
		return $ci->site_menu->get_formatted_menu_tree();
	}
	
	//parent and children menu items [level1 and level2 only]
	$menu_array=$ci->site_menu->get_menu_items_array();

	$items=array();

	//remove menu items that user has no access
	foreach($menu_array['parents'] as $item)
	{
		if ($ci->acl->user_has_url_access($user_id=NULL,$url=$item['url']))
		{
			$items['parents'][]=$item;
		}
	}
	
	foreach($menu_array['children'] as $item)
	{
		if ($ci->acl->user_has_url_access($user_id=NULL,$url=$item['url']))
		{
			$items['children'][]=$item;
		}
	}
	
	return $ci->site_menu->get_formatted_menu_tree($items);
}
  
}

/* End of file site_menu_helper.php */
/* Location: ./system/helpers/site_menu_helper.php */