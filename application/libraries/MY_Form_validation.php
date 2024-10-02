<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

    
    /**
     * Validate a password is complex
     *
     *	@str - password string
     *	@enforce - if set to FALSE then SKIP validation
     *
     *
     * password MUST have atleast one upper case letter
     * password MUST have atleast one lower case letter
     * password MUST have atleast one number
     * password MUST have atleast one special character
     *
     * Source: https://gist.github.com/davidstanley01/4161999
     **/
    function is_complex_password($str,$enforce=TRUE)
    {
	//skips complex password validation if config setting is set to not require strong password
	if (!$enforce)
	{
	    return TRUE;
	}
	
	$regex_upper='/[A-Z]/';  //Uppercase
	$regex_lower='/[a-z]/';  //lowercase
	$regex_special='/[!@#$%&*()^,._;:-]/';  //list of allowed special characters
	$regex_numbers='/[0-9]/';  //numbers
 
	$validate=TRUE;
 
	if(preg_match_all($regex_lower,$str, $o)<1) {
	    $validate=FALSE;
	    //$this->set_message('is_complex_password', 'Password must contain a LOWERCASE letter.');
	}
      
	if(preg_match_all($regex_upper,$str, $o)<1) {
	    $validate=FALSE;
	    //$this->set_message('is_complex_password', 'Password must contain an UPPERCASE letter.');
	}
      
	if(preg_match_all($regex_special,$str, $o)<1)  {
	    $validate=FALSE;
	    //$this->set_message('is_complex_password', 'Password must contain a special character. Allowed characters are: !@#$%&*()^,._;:-');
	}
      
	if(preg_match_all($regex_numbers,$str, $o)<1)  {
	    $validate=FALSE;
	    //$this->set_message('is_complex_password', 'Password must contain a Number.');
	}
      
	if (!$validate)
	{
	    $this->set_message('is_complex_password', t('Password must contain at least a number, an uppercase letter, a lowercase letter and a special character. Allowed special characters are:').' '. '!@#$%&*()^,._;:- ');
	}
	
	return $validate;
    }
    
    /**
    * Adds URL validation functions ot the validation class
    *
    * source: http://codeigniter.com/forums/viewthread/111319/
    */
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


    /**
     * Check if string contains html tags
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function disable_html_tags($str)
    {
        if (strip_tags($str) != $str)
        {
            $this->set_message('disable_html_tags', t('Invalid characters in %s.'));
            return FALSE;
        }
        return TRUE;
    }


    function validate_name($str)
    {
        //disallowed characters
        $pattern = "/[!'\"><@#$%&*()^;:]/";
        if (preg_match($pattern, $str))
        {
            $this->set_message('validate_name', t('Invalid characters in %s.'));
            return FALSE;
        }
        return TRUE;
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
    

    //convert an array of errors to string 
    public function error_array_to_string($errors)
    {
        $output=array();
        foreach($errors as $key=>$value){
            $output[]=$value;
        }
        return implode("<BR/>",$output);
    }


    //check if the email address exists in db
	function check_user_email_exists($email)
	{
		$user_data=$this->CI->ion_auth->get_user_by_email($email);

		if ($user_data)
		{
			$this->set_message('check_user_email_exists', t('callback_email_exists'));
			return FALSE;
		}
		return TRUE;
    }
    
    //check country name is selected
	function check_user_country_valid($country)
	{
		if (strlen($country)<4)
		{
			$this->set_message('check_user_country_valid', t('callback_country_invalid'));
			return FALSE;
		}
		return TRUE;
	}

}//end class



//Custom validation exception class
class ValidationException extends \Exception
{
    private $options;

    public function __construct($message,$options = array('params')) 
    {
        parent::__construct($message, $code=0, $previous=NULL);
        $this->options = $options; 
    }

    //get validation errors as array
    public function GetValidationErrors() 
    { 
        return $this->options; 
    }
}

/**
 * 
 * Custom exception for ACL access denied
 * 
 */
class AclAccessDeniedException extends \Exception
{
    private $options;

    public function __construct($message,$options = array('params')) 
    {
        parent::__construct($message, $code=0, $previous=NULL);
        $this->options = $options; 
    }

    //get errors as array
    public function GetErrors() 
    { 
        return $this->options; 
    }
}