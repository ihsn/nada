<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("modules/mpdf/mpdf_source.php");

/**
 * PDF Generation class wrapper
 * 
 *
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @category	PDF Generator
 * @author		Mehmood
 * @link		-
 *
 */ 
class MY_mPDF extends mPDF{
    
	function __construct($params=NULL)
	{
		if (is_array($params))
		{
			if (isset($params['encoding']))
			{
				parent::__construct($params['encoding']);
				return;
			}	
		}
		
		parent::__construct();
	}
}
// END MY_mPDF Class

/* End of file MY_mPDF.php */
/* Location: ./application/libraries/MY_mPDF.php */