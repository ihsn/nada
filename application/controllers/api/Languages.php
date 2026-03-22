<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Languages extends MY_REST_Controller
{
	private $api_user;
	private $user_id;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");		
		$this->load->library("Form_validation");
		
		$this->is_authenticated_or_die();
		$this->api_user=$this->api_user();
		$this->user_id=$this->get_api_user_id();
	}

	function _auth_override_check()
	{
		if ($this->session->userdata('user_id')){
			return true;
		}
		parent::_auth_override_check();
	}
	
	
	/**
	 * 
	 * 
	 * Return all Languages
	 * 
	 */
	function index_get($uid=null)
	{
		try{			
			$languages=$this->config->item("supported_languages");
			$language_codes=$this->config->item("language_codes");

			$lang=$this->session->userdata('language');

				$lang=!empty($lang) ? $lang : $this->config->item('language');

			$result=array();
			if (!$languages)
			{
				$this->set_response(array('status'=>'failed', 'message'=>'No languages available'),
					REST_Controller::HTTP_NOT_FOUND);
				return;
			}

			foreach ($language_codes as $language) {
				$result[] = array(
					'code' => $language['code'],
					'name' => $language['name'],
					'display' => $language['display'],
					'current' => ($language['code'] === $lang)
				);
			}

			$response = array(
				'status' => 'success',
				'languages' => $result,
				'current_language' => $lang,
				'current_language_title' => isset($language_codes[$lang]) ? $language_codes[$lang]['display'] : $lang
			);
						
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * 
	 * Switch user language
	 * 
	 * 
	 */
	function switch_post()
	{
		try{
			$lang = $this->input->post('language');
			
			if (empty($lang)) {
				$json_data = $this->raw_json_input();
				if ($json_data && isset($json_data['language'])) {
					$lang = $json_data['language'];
				}
			}
			
			if (empty($lang)) {
				$this->set_response(array(
					'status' => 'failed',
					'message' => 'Language parameter is required'
				), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			
			$valid_languages = $this->config->item("supported_languages");
			
			if (!in_array(strtolower($lang), $valid_languages)) {
				$this->set_response(array(
					'status' => 'failed',
					'message' => 'Invalid language selected'
				), REST_Controller::HTTP_BAD_REQUEST);
				return;
			}
			
			// Set language in the user session
			$this->session->set_userdata('language', strtolower($lang));
			
			$language_codes = $this->config->item("language_codes");
			$lang_display = isset($language_codes[strtolower($lang)]) 
				? $language_codes[strtolower($lang)]['display'] 
				: $lang;
			
			$response = array(
				'status' => 'success',
				'message' => 'Language switched successfully',
				'language' => strtolower($lang),
				'language_display' => $lang_display
			);
			
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e){
			$error_output = array(
				'status' => 'failed',
				'message' => $e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	
}
