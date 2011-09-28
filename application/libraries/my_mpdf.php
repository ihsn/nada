<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH."../modules/mpdf/mpdf_source.php");

/**
 * PDF Generation class wrapper
 * 
 *
 *
 *
 * @package		NADA 3.0
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
			if (isset($params['codepage']))
			{
				parent::__construct($params['codepage']);
				return;
			}	
		}
		
		parent::__construct();
	}
}
// END MY_mPDF Class

/* End of file my_mpdf.php */
/* Location: ./application/libraries/my_mpdf.php */