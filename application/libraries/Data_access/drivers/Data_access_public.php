<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Public Data Access
 *
 * @package		Data Access
 * @subpackage	Libraries
 * @category	NADA Core
 * @author		IHSN
 * @link
 */

class Data_access_public extends CI_Driver {

	protected $CI;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Process public data access form
	 * 
	 * @param int $sid Survey ID
	 * @param object|bool $user User object or FALSE if not logged in
	 * @return string HTML content for the form or data files
	 */
	public function process_form($sid, $user = FALSE)
	{
		$this->CI->load->model('Form_model');
		$this->CI->load->model('Public_model');
		$this->CI->lang->load('public_access_terms');
		$this->CI->lang->load('public_request');
		$this->CI->lang->load('data_access');

		// Validate survey ID
		if (!is_numeric($sid) || $sid <= 0) {
			show_error("INVALID_STUDY_ID");
		}

		// Check if user is logged in
		if (!$user) {
			return $this->CI->load->view("access_public/login_message", array('sid' => $sid), true);
		}

		$survey = $this->CI->Catalog_model->select_single($sid);

		if (!$survey) {
			show_error("INVALID_STUDY_ID");
		}

		$data = new stdClass;
		$data->user_id = $user->id;
		$data->username = $user->username;
		$data->fname = $user->first_name;
		$data->lname = $user->last_name;
		$data->organization = $user->company;
		$data->email = $user->email;
		$data->survey_title = $survey["title"];
		$data->survey_id = $sid;
		$data->survey_uid = $survey["id"];
		$data->proddate = $survey["year_start"];
		$data->abstract = $this->CI->input->post("abstract", TRUE); // Enable XSS filtering
		$data->form_obj = $this->CI->Form_model->get_form_by_survey($sid);

		// Load custom fields configuration
		$data->custom_fields = $this->CI->Public_model->get_custom_fields_config();

		// Check if the user has requested this survey in the past
		$request_exists = $this->CI->Form_model->check_user_public_request($user->id, $sid);

		if ($request_exists > 0) {
			// Log the access
			$this->CI->db_logger->write_log('public-request', 'viewing public use files', 'public-request-view', $data->survey_uid);

			// Show survey data files
			return $this->get_data_files($sid);
		}

		// User has not submitted the public use form before
		// Ask user for data intended usage + show terms and conditions
		return $this->get_application_form($data);
	}

	/**
	 * Shows the Public Use Request Form + Terms & Conditions form.
	 * User must fill this form and agree to the terms to download survey files
	 *
	 * @param object $data Form data object
	 * @return string HTML content for the application form
	 */
	private function get_application_form($data)
	{
		// Validation rules for standard fields
		$this->CI->form_validation->set_rules('abstract', t('intended_use_of_data'), 'trim|required');

		// Add validation rules for custom fields
		if (!empty($data->custom_fields)) {
			foreach ($data->custom_fields as $field_key => $field_config) {
				// Use field name from config, fallback to field_key if not set
				$field_name = isset($field_config['name']) ? $field_config['name'] : $field_key;
				if (isset($field_config['required']) && $field_config['required']) {
					$validation_rules = 'trim|required';
					if (isset($field_config['validation']) && !empty($field_config['validation'])) {
						$validation_rules .= '|' . $field_config['validation'];
					}
					$this->CI->form_validation->set_rules($field_name, $field_config['title'], $validation_rules);
				} elseif (isset($field_config['validation']) && !empty($field_config['validation'])) {
					$this->CI->form_validation->set_rules($field_name, $field_config['title'], 'trim|' . $field_config['validation']);
				}
			}
		}
		

		// Process form
		if ($this->CI->form_validation->run() == TRUE) {
			// Collect custom field values
			$custom_fields = array();
			if (!empty($data->custom_fields)) {
				foreach ($data->custom_fields as $field_key => $field_config) {
					// Use field name from config, fallback to field_key if not set
					$field_name = isset($field_config['name']) ? $field_config['name'] : $field_key;
					$field_value = $this->CI->input->post($field_name, TRUE);
					if ($field_value !== FALSE) {
						$custom_fields[$field_name] = $field_value;
					}
				}
			}

			//survey title
			$title=$this->CI->Catalog_model->get_survey_title($data->survey_uid);

			// Insert request with custom fields
			$db_result = $this->CI->Public_model->insert_public_request(
				$data->survey_uid, 
				$data->user_id, 
				$title,
				$data->abstract,
				$custom_fields
			);

			// Log the request
			$this->CI->db_logger->write_log('public-request', 'request submitted for public use', 'public-request', $data->survey_uid);

			if ($db_result === TRUE) {
				$destination = current_url();

				if ($this->CI->input->get_post("ajax")) {
					$destination .= '/?ajax=true';
				}
				// Redirect back to the list on successful update
				redirect($destination, "refresh");
			} 
		}

		return $this->CI->load->view('access_public/request_form', $data, true);
	}

	/**
	 * Get study microdata files
	 *
	 * @param int $sid Survey ID
	 * @return string HTML content for microdata files
	 */
	public function get_data_files($sid)
	{
		$this->CI->load->model('Survey_resource_model');
		$this->CI->load->model('Dataset_model');
		
		$result['resources_microdata'] = $this->CI->Survey_resource_model->get_microdata_resources($sid);
		$result['sid'] = $sid;
		$result['storage_path'] = $this->CI->Dataset_model->get_storage_fullpath($sid);
		
		return $this->CI->load->view('catalog_search/survey_summary_microdata', $result, TRUE);
	}
}
