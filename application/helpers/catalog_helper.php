<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Catalog Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
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
}
  
}

// returns a study year range
if ( ! function_exists('format_study_years'))
{
  
function format_study_years($start,$end)
{
	$study_years=array($start,$end);
	$study_years=array_unique($study_years);
	return implode(" - ",$study_years);
}
  
}


/* End of file catalog_helper.php */
/* Location: ./system/helpers/catalog_helper.php */