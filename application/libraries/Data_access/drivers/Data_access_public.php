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

	function process_form($sid,$user=FALSE)
	{
		$this->CI->load->model('Form_model');
		$this->CI->lang->load('public_access_terms');
		$this->CI->lang->load('public_request');

		//check if user is logged in
		if (!$user)
		{
			$this->CI->session->set_flashdata('reason', t('reason_login_public_access'));
			$destination=$this->CI->uri->uri_string();
			$this->CI->session->set_userdata("destination",$destination);

			//redirect to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
		}

		$survey=$this->CI->Catalog_model->select_single($sid);

		if(!$survey)
		{
			show_ERROR("INVALID_STUDY_ID");
		}


		$data= new stdclass;
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->survey_title=$survey["title"];
		$data->survey_id=$sid;
		$data->survey_uid=$survey["id"];
		$data->proddate=$survey["year_start"];
		$data->abstract=$this->CI->input->post("abstract");

		//check if the user has requested this survey in the past, if yes, don't show the request form
		$request_exists=$this->CI->Form_model->check_user_public_request($user->id,$sid);

		if ($request_exists>0)
		{
			//log
			$this->CI->db_logger->write_log('public-request','viewing public use files','public-request-view',$data->survey_uid);

			//show survey data files
			//$this->_show_data_files($data->survey_uid);

			//return "data files";
			return $this->get_data_files($sid);
		}

		//User has not submitted the public use form before
		//Ask user for data intended usage + show terms and conditions
		return $this->get_application_form($data);
	}


	/**
	* 	Shows the Public Use Request Form + Terms & Conditions form.
	*	User must fill this form and agree to the terms to download survey files
	*
	*/
	private function get_application_form($data)
	{
		//validation rules
		$this->CI->form_validation->set_rules('abstract', t('intended_use_of_data'), 'trim|required');

		//process form
		if ($this->CI->form_validation->run() == TRUE)
		{
			//insert
			$db_result=$this->CI->Form_model->insert_public_request($data->survey_uid,$data->user_id,$data->abstract);

			//log
			$this->CI->db_logger->write_log('public-request','request submitted for public use','public-request',$data->survey_uid);

			if ($db_result===TRUE)
			{
				$destination=current_url();

				if ($this->CI->input->get_post("ajax"))
				{
					$destination.='/?ajax=true';
				}
				//redirect back to the list on successful update
				redirect($destination,"refresh");
			}
			else
			{
				//update failed
				$this->CI->form_validation->set_error(t('form_update_failed'));
			}
		}

		return $this->CI->load->view('access_public/request_form', $data,true);
	}

	//get study microdata files
	function get_data_files($sid)
	{
		$this->CI->load->model('Resource_model');
		$result['resources_microdata']=$this->CI->Resource_model->get_microdata_resources($sid);
		$result['sid']=$sid;
		$result['storage_path']=$this->CI->Dataset_model->get_storage_fullpath($sid);
		return $this->CI->load->view('catalog_search/survey_summary_microdata', $result,TRUE);
	}


}
