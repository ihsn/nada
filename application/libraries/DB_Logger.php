<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Database logger
 * 
 * Log entries in database
 *
 *
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @category	logging
 * @author		Mehmood
 * @link		-
 *
 */
class DB_Logger{    	
	
	var $ci;
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
    }

	/**
	* log
	*
	*	@type		search,survey,login,logout,register,forgot-pass,reset-pass,change-pass
	*	@section	ddibrowser sections (overview, sampling,datafile,download,public-form,direct-form)
	*
	* @return boolean
	*/
	function write_log($type, $message=NULL, $section=NULL,$surveyid=0)
	{	
		$username='guest';
		
		//check if user is logged in
		if ($this->ci->ion_auth->logged_in()) 
		{
			$user=$this->ci->ion_auth->current_user();
			$username=$user->email;
    	}
	
		$log=array(
				'url'=>$this->current_page_url(),//current_url(),
				'logtime'=>date("U"),
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'sessionid'=>$this->ci->session->userdata('session_id'),
				'logtype'=>$type,
				'surveyid'=>$surveyid,
				'keyword'=>$message,
				'username'=>$username,
				'section'=>$section
				);
		
		return $this->ci->db->insert("sitelogs",$log);
	}
	
	/*get page url with querystrings */
	function current_page_url() 
	{
		$get=$_GET;
		$querystring=array();
		
		foreach($get as $key=>$value)
		{
			if (is_array($value))
			{
				$value_=implode(',',$value);
			}
			else
			{
				$value_=$value;
			}
			$querystring[]="$key=$value_";
		}
		
		$url=uri_string().'?'.implode('&',$querystring);
	 	return $url;
	}
}// END DB_Logger

/* End of file DB_Logger.php */
/* Location: ./application/libraries/DB_Logger.php */