<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Math Captcha
 * 
 *
 *
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @author		Mehmood
 * @link		-
 *
 */
class Math_captcha{
	
	var $ci;
	var $num1=1;
	var $num2=2;
	
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
    }
	
	function create_question()
	{
		//random numbers between 0 - 10
		$this->num1=rand() % 10;
		$this->num2=rand() % 10;
		
		$answer=base64_encode(md5($this->num1 + $this->num2));

		$output="<div>$this->num1 + $this->num2 = ". '<input type="text" maxlength="2" size="5" name="math_question"/></div>';
		$output.='<input type="hidden" name="math_question_answer" value="'.$answer.'"/>';
		
		return $output;
	}
	

	/**
	*
	* validate math captcha
	*
	*/
	function validate_captcha()
	{
		$question=base64_encode(md5($this->ci->input->get_post('math_question')));
		$answer=$this->ci->input->get_post('math_question_answer');
		
		if ($question!==$answer)
		{
			$this->ci->form_validation->set_message('validate_captcha', t('captcha_answer_not_matched'));
			return FALSE;
		}
		return TRUE;	
	
	}
	
	
}// END class

/* End of file math_captch.php */
/* Location: ./application/libraries/math_captcha.php */