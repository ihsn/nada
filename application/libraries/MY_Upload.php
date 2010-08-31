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
 * @filesource: 
 */

// ------------------------------------------------------------------------

/**
 * Upload Class - exended to use disallowed_types
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Upload
 * @author		http://www.blueemberdesign.com/blog/2009/07/29/codeigniter-file-upload-setting-disallowed-file-types/
 * @link		http://www.blueemberdesign.com/blog/2009/07/29/codeigniter-file-upload-setting-disallowed-file-types/
 *
 * only the create_links method has been modified
 */
class MY_Upload extends CI_Upload {
 
  // declare disallowed types variable
  var $disallowed_types = '';
 
  // add in the 'disallowed_types' default during initialization
  function initialize($config = array()) {
    $defaults = array(
      'max_size'         => 0,
      'max_width'        => 0,
      'max_height'       => 0,
      'max_filename'     => 0,
      'allowed_types'    => "",
      'disallowed_types' => "",
      'file_temp'        => "",
      'file_name'        => "",
      'orig_name'        => "",
      'file_type'        => "",
      'file_size'        => "",
      'file_ext'         => "",
      'upload_path'      => "",
      'overwrite'        => FALSE,
      'encrypt_name'     => FALSE,
      'is_image'         => FALSE,
      'image_width'      => '',
      'image_height'     => '',
      'image_type'       => '',
      'image_size_str'   => '',
      'error_msg'        => array(),
      'mimes'            => array(),
      'remove_spaces'    => TRUE,
      'xss_clean'        => FALSE,
      'temp_prefix'      => "temp_file_"
    );
 
    foreach ($defaults as $key => $val) {
      if (isset($config[$key])) {
        $method = 'set_'.$key;
        if (method_exists($this, $method)) {
          $this->$method($config[$key]);
        }
        else {
          $this->$key = $config[$key];
        }
      }
      else {
        $this->$key = $val;
      }
    }
  }
 
  // set disallowed filetypes
  function set_disallowed_types($types) {
    $this->disallowed_types = explode('|', $types);
  }
 
  // adapted to not require allowed_types and to check for disallowed types if it exists
  function is_allowed_filetype() {
    // if allowed file type list is not defined
    if (count($this->allowed_types) == 0 OR ! is_array($this->allowed_types)) {
      // if disallowed file type list is not defined
      if (count($this->disallowed_types) == 0 OR ! is_array($this->disallowed_types))
        return TRUE;
 
      // check for disallowed file types and return
      // negated because is_disallowed_filetype returns opposite result as this function
      return ! $this->is_disallowed_filetype();
    }
 
    // proceed as usual with allowed file type list check
    return parent::is_allowed_filetype();
  }
 
  // check for disallowed file types
  function is_disallowed_filetype() 
  {
    // no file types provided
    if (count($this->disallowed_types) == 0 OR ! is_array($this->disallowed_types))
	{
      return FALSE;
	}  

    // search through disallowed for this file type
    foreach ($this->disallowed_types as $key=>$val) 
	{
		$mime = $this->mimes_types(strtolower($val));
		
		//block files by extension, otherwise it does not work in IE
		//by M1
		if (strtolower('.'.$val)==strtolower($this->file_ext) )
		{	
			log_message('info', 'File upload blocked: '. $this->file_ext);	  
			return true;
		}
	  
		if (is_array($mime)) 
		{
			if (in_array($this->file_type, $mime, TRUE)) 
			{
			  return TRUE;
			}
		}
		else 
		{
			if ($mime == $this->file_type) 
			{
			  return TRUE;
			}
		}
    }//end-foreach
	 
    return FALSE;
  }//end func
  
}
// END Upload Class

/* End of file MY_Upload.php */
/* Location: ./application/libraries/My_Upload.php */