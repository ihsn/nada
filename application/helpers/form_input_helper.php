<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Returns value of the $_POST by name
 *
 * @access	public
 * @param	string - post variable name 
 * @param	string - optional second value to return if POST is empty
 * @return	string	
 */	
if ( ! function_exists('get_form_value'))
{
	function get_form_value($field, $ofield='')
	{
		$ci=& get_instance();

		if ( $ci->input->get_post($field)!='' )
		{
			return $ci->input->xss_clean($ci->input->get_post($field));
		}
		return $ofield;
	}
}


/**
 * Generates a hidden field containing a nonce.
 *
 * @access	public
 * @return	string
 * @link	http://blog.streambur.se/2010/06/no-nonsense-protection-using-a-nonce	
 */
if ( ! function_exists('form_nonce'))
{
	function form_nonce()
	{
        $CI =& get_instance();
        //$CI->load->library('form_validation');
		$field = '<input type="text" name="nonce" value="'
            . $CI->form_validation->set_value('nonce', $CI->form_validation->create_nonce())
            . '" />';
        return $field;
	}
}

/* End of file form_input_helper.php */
/* Location: ./application/helpers/form_input_helper.php */