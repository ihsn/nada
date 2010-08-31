<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Search Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/array_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Element
 *
 * Returns the sort order of the field. Return ASC or DESC depending on if the field was already 
 * sorted to what order. e.g. if current page was sorted ASC on the TITLE field, passing TITLE to 
 * the function will return DESC
 *
 * @access	public
 * @param	string
 * @param	string
 * @param	string
 * @return	string	returns the sort order ASC or DESC
 */	
if ( ! function_exists('set_sort_order'))
{
	//set field's sort order
	function set_sort_order($fieldname, $sort_by, $sort_order){
		//already sorted
		if ($fieldname==$sort_by){
			if ($sort_order=='asc'){
				return 'desc';
			}
			else{
				return 'asc';
			}
		}
		return 'asc';
	}
}

/* End of file search_helper.php */
/* Location: ./application/helpers/search_helper.php */