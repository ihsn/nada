<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH."libraries/Parsedown.php";

/**
 * 
 * 
 * Markdown parser
 *
 */ 
class MarkdownParser{

	private $parsedown;
    
	function __construct()
	{
		$this->parsedown = new Parsedown();
		$this->parsedown->setBreaksEnabled(true);
	}	

	function parse_markdown($string)
	{
		if (is_array($string)){
			$string=implode("\n",$string);
		}
		
		$this->parsedown->setSafeMode(true);
		$this->parsedown->setMarkupEscaped(true);
		return $this->parsedown->text($string);
	}

	/**
	 * Markdown with embedded HTML allowed (input must already be sanitized, e.g. xss_clean).
	 *
	 * @param string|array $string
	 * @return string
	 */
	function parse_richtext($string)
	{
		if (is_array($string)){
			$string=implode("\n",$string);
		}

		$this->parsedown->setSafeMode(false);
		$this->parsedown->setMarkupEscaped(false);
		return $this->parsedown->text($string);
	}
}
// END MarkdownParser Class

/* End of file MarkdownParser.php */
/* Location: ./application/libraries/MarkdownParser.php */