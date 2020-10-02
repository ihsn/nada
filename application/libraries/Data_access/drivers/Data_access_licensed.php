<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Licensed Data Access
 *
 * @package		Data Access
 * @subpackage	Libraries
 * @category	NADA Core
 * @author		IHSN
 * @link
 */

class Data_access_licensed extends CI_Driver {

	protected $CI;
	private $licensed_access_config;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('Bulk_data_access');
		$this->CI->load->config("data_access");
		$this->licensed_access_config=$this->CI->config->item('licensed_access');
	}

	function process_form($sid,$user=FALSE)
	{
		$this->CI->load->model('Licensed_model');
		$this->CI->lang->load('licensed_request');
		$this->CI->lang->load('licensed_access_form');

		//check if user is logged in
		if (!$user)
		{
			$destination=$this->CI->uri->uri_string();
			$this->CI->session->set_flashdata('reason', t('reason_login_licensed_access'));
			$this->CI->session->set_userdata("destination",$destination);
			redirect("auth/login/?destination=$destination", 'refresh');
		}

		$survey=$this->CI->Catalog_model->select_single($sid);

		if(!$survey)
		{
			show_error("INVALID_STUDY_ID");
		}

		if ($this->CI->input->get("request")=="new")
		{
			//show application form
			return $this->request_form($sid,$user);
		}

		//check url for requestid
		$request_id=$this->CI->input->get("requestid");

		//check if a valid request
		if(is_numeric($request_id))
		{
			redirect('access_licensed/track/'.$request_id);
			exit;
		}

		//find existing requests by the user
		$requests=$this->CI->Licensed_model->get_requests_by_study($sid,$user->id,$active_only=FALSE);

		if($requests)
		{
			return $this->CI->load->view("access_licensed/select_request",array('rows'=>$requests,'survey_id'=>$sid),TRUE);
		}

		//show application form
		return $this->request_form($sid,$user);
	}


	/**
	* load the licensed form for single study and collections
	**/
	private function request_form($survey_id=NULL,$user)
	{

		if ( !is_numeric($survey_id) )
		{
			show_404();
		}

		$surveys=NULL;

		$surveys[]=$this->CI->Catalog_model->select_single($survey_id);

		if ($surveys==FALSE)
		{
			show_404("STUDY_NOT_FOUND");
		}

		$content=NULL;
		$data= new stdClass;

		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;
		$data->surveys=$surveys;
		$data->bulk_access=FALSE;
		$data->abstract=$this->CI->input->post("abstract");

		$this->CI->load->library('form_validation');

		//validation rules
		$this->CI->form_validation->set_rules('org_rec', t('receiving_organization_name'), 'trim|required|xss_clean|max_length[255]');
		//$this->form_validation->set_rules('address', t('postal_address'), 'trim|required|xss_clean|max_length[255]');
		$this->CI->form_validation->set_rules('tel', t('telephone'), 'trim|required|xss_clean|max_length[14]');
		$this->CI->form_validation->set_rules('datause', t('intended_use_of_data'), 'trim|required|xss_clean|max_length[1000]');
		//$this->CI->form_validation->set_rules('dataset_access', t('dataset_access'), 'trim|required|xss_clean|max_length[15]');

		//optional fields
		//$this->form_validation->set_rules('org_type', t('org_type'), 'trim|xss_clean|max_length[45]');
		$this->CI->form_validation->set_rules('datamatching', t('data_matching'), 'trim|xss_clean|max_length[1]');
		//$this->CI->form_validation->set_rules('fax', t('fax'), 'trim|xss_clean|max_lengh[14]');
		$this->CI->form_validation->set_rules('outputs', t('expected_output'), 'trim|xss_clean|max_length[1000]|required');
		$this->CI->form_validation->set_rules('compdate', t('expected_completion'), 'trim|xss_clean|max_length[10]|required');
		$this->CI->form_validation->set_rules('team', t('research_team'), 'trim|xss_clean|max_length[1000]|required');


		//process form
		if ($this->CI->form_validation->run() == TRUE)
		{
			if (!$this->CI->input->post("sid"))
			{
				show_error("NO_SURVEYS_SELECTED");
			}

			$post=$_POST;

			$options=array();

			foreach($post as $key=>$value)
			{
				$options[$key]=$this->CI->security->xss_clean($value);
			}

			if (isset($options['ds']) && intval($options['ds'])>0 )
			{
				$options['request_title']=$this->CI->Licensed_model->get_collection_title((int)$options['ds']) . ' - multi study request';
			}
			else
			{
				//study title for single study requests
				$options['request_title']=$surveys[0]['nation'].' - '.$surveys[0]['title'] . ' - '.$surveys[0]['year_start'];
			}

			//remove duplicate surveys
			$options['sid']=array_unique($options['sid']);

			//save/insert request
			$new_requestid=$this->CI->Licensed_model->insert_request($user->id,$options);

			if ($new_requestid!==FALSE)
			{
				//update successful
				$this->CI->session->set_flashdata('message', t('form_update_success'));

				//send confirmation email to the user and the site admin
				$this->send_confirmation_email($new_requestid);

				//show request tracking info+ confirmation
				return $this->CI->load->view('access_licensed/request_confirmation',array('request_id'=>$new_requestid),TRUE);

				//redirect to the confirmation page
				//redirect('access_licensed/confirm/'.$new_requestid,"refresh");
			}
			else
			{
				//update failed
				$this->CI->form_validation->set_error(t('form_update_fail'));
			}
		}


		//check study bulk access
		$bulk_access=$this->CI->bulk_data_access->study_has_bulk_access($survey_id);

		if ($bulk_access)
		{
			//get bulk access collections for the study
			$collections=$this->CI->bulk_data_access->get_study_bulk_access_sets($survey_id);

			foreach($collections as $key=>$collection)
			{
				$collections[$key]['studies']=$this->CI->bulk_data_access->get_study_list_by_set($collection['cid']);
			}

			$data->collections=$collections;
			$data->bulk_access=TRUE;
		}

		$data->form_options=$this->licensed_access_config;

		//load the contents of the page into a variable
		return $this->CI->load->view('access_licensed/request_form', $data,TRUE);
	}



	//get microdata files by request id
	function get_data_files($request_id)
	{
		$this->CI->load->model('Resource_model');
		$data['resources_microdata']=$this->CI->Licensed_model->get_request_downloads($request_id);//$this->CI->Resource_model->get_microdata_resources($sid);//$this->CI->managefiles_model->get_data_files($sid);
		$data['request_id']=$request_id;
		return $this->CI->load->view('access_licensed/data_files_per_request', $data,TRUE);
	}

	/**
	* Download licensed data files
	*
	* Checks before a file can be downloaded
	*	- status=APPROVED
	*	- IP_LIMIT on request - not implemented
	*	- Expiry date on file
	*	- Download Limits
	*
	* NOTE: Downloads are logged and stats are updated
	*/
	function download($request_id=NULL,$file_id=NULL,$user)
	{
		$this->CI->load->model('managefiles_model');

		if (!is_numeric($request_id) )
		{
			show_404();
		}

		if ($file_id=='')
		{
			show_404();
		}

		//get file information
		$fileinfo=$this->CI->managefiles_model->get_resource_by_id($file_id);

		if ($fileinfo['filename']=='')
		{
			show_error("INVALID_RESOURCE");
		}

		//todo: what about requests by collection
		//get the surveyid by requestid
		$survey_id=$this->CI->Licensed_model->get_surveyid_by_request($request_id);

		//check if the survey form is set to LICENSED
		if ($this->CI->Catalog_model->get_survey_form_model($survey_id)!='licensed')
		{
			show_404();
		}

		//get request info from db
		$request=$this->CI->Licensed_model->get_request_by_id($request_id);

		//download stats data
		$download_stats=$this->CI->Licensed_model->get_download_stats($request_id, $file_id);

		if (!$download_stats)
		{
			show_error( 'File is no longer available for download');
		}

		//no. of times the file can be downloaded
		$download_limit=$download_stats['download_limit'];

		//how many times the files has been downloaded
		//download will stop once the limit is reached
		$download_times=$download_stats['downloads'];

		if ($download_times>=$download_limit)
		{
			redirect('/access_licensed/expired/'.$request_id.'/'.$download_limit,"refresh");exit;
		}

		//increment the download tick
		$this->CI->Licensed_model->update_download_stats($file_id,$request_id,$user->email);

		//survey folder path
		$survey_folder=$this->CI->Catalog_model->get_survey_path_full($survey_id);

		//build licensed file path
		$file_path=$survey_folder.'/'.$fileinfo['filename'];

		if (!file_exists($file_path))
		{
			show_error('RESOURCE_NOT_FOUND');
		}

		//download file
		$this->CI->load->helper('download');

		//log
		log_message('info','Downloading file <em>'.$file_path.'</em>');
		$this->CI->db_logger->write_log('survey',$file_id,'licensed-download',$survey_id);

		force_download2($file_path);
	}

	function track_request($request_id,$user)
	{
		$data=$this->CI->Licensed_model->get_request_by_id($request_id);

		if (!$data)
		{
			return FALSE;
		}

		//user can only view his requests
		if ($data['userid']!=$user->id)
		{
			return FALSE;
		}

		return $this->CI->load->view('access_licensed/request_status', $data,true);
	}



	function send_confirmation_email($requestid)
	{
		//get user info
		$user=$this->CI->ion_auth->current_user();

		//get request data
		$data=$this->CI->Licensed_model->get_request_by_id($requestid);
		$data=(object)$data;

		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->username=$user->username;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->organization=$user->company;
		$data->email=$user->email;

		$data->title=$data->request_title;
		$subject=t('confirmation_application_for_licensed_dataset').' - '.$data->title;
		$message=$this->CI->load->view('access_licensed/request_form_printable', $data,true);

		$this->CI->load->helper('admin_notifications');
		$this->CI->load->library('email');
		$this->CI->email->clear();
		$this->CI->email->initialize();//intialize using the settings in mail
		$this->CI->email->set_newline("\r\n");
		$this->CI->email->from($this->CI->config->item('website_webmaster_email'), $this->CI->config->item('website_title'));
		$this->CI->email->to($data->email);
		$this->CI->email->subject($subject);
		$this->CI->email->message($message);

		if ($this->CI->email->send())
		{
			//notification for the site admins
			$subject=t('notification_licensed_survey_request_received').' - '.$data->title;
			$message=$this->CI->load->view('access_licensed/admin_notification_email', $data,true);

			//notify the site admin
			notify_admin($subject,$message,$notify_all_admins=false);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

/*
	//give user option to request access to a single study or bulk access
	function choose_form($sid,$user)
	{
		$data['collections']=$this->CI->bulk_data_access->get_study_bulk_access_sets($sid);

		foreach($data['collections'] as $key=>$collection)
		{
			$data['collections'][$key]['studies_found']=$this->CI->bulk_data_access->get_study_counts_by_collection($collection['cid']);
		}
		$data['sid']=$sid;

		return $this->CI->load->view('access_licensed_bulk/choose_access_type',$data,TRUE);
	}
*/


}
