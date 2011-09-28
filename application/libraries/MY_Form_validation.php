<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Adds URL validation functions ot the validation class
*
* source: http://codeigniter.com/forums/viewthread/111319/
*
*
*/
class MY_Form_validation extends CI_Form_validation {

    function valid_url($str){

           $pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
            if (!preg_match($pattern, $str))
            {
                return FALSE;
            }

            return TRUE;
    }

	/**
     * Real URL
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function real_url($url)
    {
        return @fsockopen("$url", 80, $errno, $errstr, 30);
    }
	
	function set_error($message,$field=NULL)
	{
		if ($field==NULL)
		{
			$this->_error_array[] = $message;
		}
		else
		{
			$this->_error_array[$field] = $message;
		}
	}	


    /**
     * Create a new unique nonce, save it to the current session and return it.
     *
     * @return string
	 * @link http://blog.streambur.se/2010/06/no-nonsense-protection-using-a-nonce
     */
    function create_nonce()
    {
        $nonce = md5('nonce' . $this->CI->input->ip_address() . microtime());
        $this->CI->session->set_userdata('nonce', $nonce);
		log_message('error', 'create_nonce: '.$nonce);
        return $nonce;
    }

    /**
     * Mark the nonce sent from the form as already used.
     */
    function save_nonce()
    {
        $this->CI->session->set_userdata('old_nonce', $this->set_value('nonce'));
		log_message('error', 'saving nonce (old): '.$this->CI->session->userdata('old_nonce'));
    }

    /**
     * Set form validation rules for the nonce.
     */
    function nonce()
    {
        $this->set_rules('nonce', 'Nonce', 'required|check_nonce');
    }

 	/**
	 * Validation rule for making sure the nonce is valid.
	 *
	 * @access	public
	 * @param	string
     * @param	last used nonce
	 * @return	bool
	 */
	function check_nonce($str)
	{
        log_message('error', 'check_nonce nonce: '.$this->CI->session->userdata('nonce'));
		log_message('error', 'check_nonce old nonce: '.$this->CI->session->userdata('old_nonce'));
		
		$result=($str == $this->CI->session->userdata('nonce') &&
                $str != $this->CI->session->userdata('old_nonce'));
		if ($result==false)
		{
			$this->set_message('check_nonce','%s is no longer valid.');
		}
		return $result;
	}

}




