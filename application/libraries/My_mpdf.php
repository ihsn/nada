<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH."../modules/mpdf/vendor/autoload.php");

//require_once 'vendor/autoload.php';

//$mpdf = new \Mpdf\Mpdf();

/**
 * PDF Generation class wrapper
 * 
 *
 *
 *
 * @subpackage	Libraries
 * @category	PDF Generator
 *
 */ 
class MY_mPDF extends \Mpdf\Mpdf{
    
	function __construct($params=NULL)
	{
		if (is_array($params))
		{
			//set temp folder path
			$params['tempDir'] = FCPATH.'/datafiles/tmp';

			if (isset($params['codepage']))
			{
				parent::__construct($params);
				return;
			}	
		}
		
		parent::__construct($params);
	}

	/**
	 * mPDF stores inline objects as "type=…,objattr=SERIALIZED" wrapped in a
	 * 3-byte delimiter. Upstream _getObjAttr() leaves that delimiter on the
	 * payload; PHP 7+ then warns "unserialize(): Extra data starting at offset N of N+3".
	 * CodeIgniter surfaces that warning and aborts PDF output.
	 */
	function _getObjAttr($t)
	{
		$c = explode("\xbb\xa4\xac", $t, 2);
		if (!isset($c[1]) || $c[1] === '') {
			return array();
		}

		$c = explode(',', $c[1], 2);
		$sp = array();
		foreach ($c as $v) {
			$v = explode('=', $v, 2);
			if (!isset($v[0], $v[1])) {
				continue;
			}
			$sp[$v[0]] = $v[1];
		}

		if (!isset($sp['objattr'])) {
			return array();
		}

		$payload = $sp['objattr'];
		if (substr($payload, -3) === "\xbb\xa4\xac") {
			$payload = substr($payload, 0, -3);
		}

		$attr = unserialize($payload);
		return is_array($attr) ? $attr : array();
	}
}
// END MY_mPDF Class

/* End of file my_mpdf.php */
/* Location: ./application/libraries/my_mpdf.php */