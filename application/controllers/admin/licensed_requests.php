<?php
class Licensed_requests extends MY_Controller {
 
    public function __construct()
    {
		//skip authentication
        parent::__construct();   
       	
        $this->load->helper(array('url'));		
		$this->load->library( array('pagination') );
		
        $this->load->model('Licensed_model');
		//$this->load->model('Catalog_model');
       	
       	$this->template->set_template('admin');
		
		$this->lang->load('general');
		$this->lang->load('licensed_request');
		//$this->output->enable_profiler(TRUE);
    }
    
    public function index()
    {    	
    	$content=NULL;

    	//get array of db rows		
		$result['rows']=$this->_search();
		
    	//show listing
		$content=$this->load->view('access_licensed/index', $result,true);
		    	
    	//set page title
		$this->template->write('title', t('title_licensed_request'),true);				
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
    }
	
	function edit($id)
	{	
		$this->template->add_css('javascript/jquery/themes/base/ui.all.css');
		$this->template->add_js('javascript/jquery/ui/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/ui.tabs.js');	
		$this->template->add_js('javascript/jquery/ui/ui.datepicker.js');	
		$this->template->add_js('javascript/expand.js');
		
		//get licensed request information		
		$result=$this->Licensed_model->get_request_by_id($id);
		$result['survey_title']=$result['titl'];
		$result['survey_id']=$result['surveyid'];

		//licensed files by request and surveyid
		$result['files']=$this->Licensed_model->get_request_files($result['surveyid'], $requestid=$id);
		
		//monitoring information
		$result['download_log']=$this->monitor($id,TRUE);
		
    	//show listing
		$content=$this->load->view('access_licensed/edit', $result,true);
		    	
		$this->template->write('title', t('title_licensed_request'),true);				
		$this->template->write('content', $content,true);
	  	$this->template->render();		
	}
	
	function _status_check($str)
	{
		$status_allowed=array('APPROVED','DENIED','MOREINFO','CANCELLED');
			
		if (!in_array($str,$status_allowed))
		{
			$this->form_validation->set_message('_status_check', 'The %s code is incorrect.');
			return FALSE;
		}		
			return TRUE;
	}


	/**
	* validates a date format (mm/dd/yyyy)
	*
	*/
	function _date_check($str)
	{
		$str_array=explode("/",$str);

		if (count($str_array)<3)
		{
			$this->form_validation->set_message('_date_check', 'The %s is incorrect. Use the format (mm/dd/yyyy).');
			return FALSE;
		}
				
		foreach($str_array as $value)
		{
			if (!is_numeric($value))
			{
				return FALSE;
			}
		}
		
		if( !checkdate($str_array[0],$str_array[1],$str_array[2]))
		{
			$this->form_validation->set_message('_date_check', 'The %s is incorrect. Use the format (mm/dd/yyyy).');
			return FALSE;
		}

		return TRUE;	
	}
	
	function _valid_ip($str)
	{
		$ip_array=explode(',',$str);

		foreach($ip_array as $ip)
		{
			$ip=trim($ip);
			if ( $ip!='')
			{
				if (!$this->form_validation->valid_ip($ip))
				{
					$this->form_validation->set_message('_valid_ip', 'The %s field must contain a valid IP.');
					return FALSE;
				}
			}		
		}
		return TRUE;
	}
		
	/**
	* Update request info using AJAX/JSON
	* to be used by the EDIT controller
	*
	* returns JSON object
	*/
	function update($requestid)
	{	
		$this->form_validation->set_rules('status', 'Status', 'trim|required|xss_clean|callback__status_check');
		$this->form_validation->set_rules('comments', 'Comments', 'trim|xss_clean');		
		$this->form_validation->set_rules('ip_limit', 'IP address', 'trim|xss_clean|callback__valid_ip');		
		/*if ($this->input->post("ip_limit")!=''){
		
		}*/

		$post=$_POST;
		foreach($post as $key=>$value)
		{
			$post[$key]=$this->input->xss_clean($value);
		}		
		
		$file_options=NULL;
		//create an array of selected files and options		
		foreach($post as $key=>$value)
		{
			//find the selected files and the options set on them
			if ( substr($key,0,7) =='fileid-')
			{
				$index=str_replace('fileid-','',$key);
				
				//add validation
				$this->form_validation->set_rules('download-limit-'.$index, t('download_limit'), 'trim|required|xss_clean|integer');
				$this->form_validation->set_rules('expiry-'.$index, t('expiry_date'), 'trim|required|xss_clean|callback__date_check');
				
				$options=array(
					'download_limit'=>$this->input->post('download-limit-'.$index),
					//covert to unix timestamp + add time =23:59	
					'expiry'=> strtotime($this->input->post('expiry-'.$index)) +(24*3600)-60
				);
								
				$file_options[$index]=$options;				
			}
		}
		
		//process form				
		if ($this->form_validation->run() == FALSE)
		{
			echo '<div class="error">'.validation_errors().'</div>';
		}
		else
		{
			//get current user info
			$user=$this->ion_auth->get_users_array();
			
			//check for request ip limits, if any
			$ip_limit=isset($post['ip_limit']) ? $post['ip_limit'] : '';
		
			//update request status and comments
			$this->Licensed_model->update_request_status($requestid,$user[0]['username'],$post['status'],$post['comments'],$ip_limit);
			
			if ($file_options!=NULL)
			{
				//update file options (if set)
				$this->Licensed_model->update_request_files($requestid,$file_options);
			}	
			
			//delete files that were unchecked
			$excluded=NULL;
			
			if ( is_array($file_options))
			{
				$excluded=array_keys($file_options);				
			}
				
			$this->Licensed_model->delete_request_files($requestid,$excluded);			
			
			echo '<div class="success">'.t('form_update_success').'</div>';
		}
		
		//notify user
		$this->_notify_user($requestid);
	}
	
	/**
	*
	* Notify the user via email of the request status change
	*
	*/
	function _notify_user($requestid)
	{
		if ($this->input->post("notify")!=='1')
		{
			return;
		}
		
		//get user info
		$user=$this->ion_auth->current_user();

		//get request data
		$data=$this->Licensed_model->get_request_by_id($requestid);
		$data=(object)$data;	
		//set data to be passed to the view
		$data->user_id=$user->id;
		$data->fname=$user->first_name;
		$data->lname=$user->last_name;
		$data->email=$user->email;
		$data->survey_title=$data->titl;
		$data->survey_id=$data->surveyid;
		$data->requestid=$requestid;
					
		$message=$this->load->view('access_licensed/user_notification_email', $data,true);	

		$this->load->library('email');
		$this->email->clear();
		$config['mailtype'] = "html";
		$this->email->initialize($config);
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_webmaster_name'));
		$this->email->to($user->email);
		$this->email->bcc($this->config->item('website_webmaster_email'), $this->config->item('website_webmaster_name'));
		$this->email->subject('[#'.$requestid.'] - Request status updated for '.$data->survey_title );
		$this->email->message($message);
		
		if ($this->email->send())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	* Monitor licensed data files downloads
	*/
	function monitor($requestid,$output=FALSE)
	{	
		//get request summary statistics
		$data['summary_rows']=$this->Licensed_model->get_request_summary($requestid);

		//get request download log
		$data['log_rows']=$this->Licensed_model->get_request_log($requestid);
		
    	//show listing
		$content=$this->load->view('access_licensed/download_history', $data,true);
		
		if ($output==TRUE)
		{
			return $content;
		}			    	
		$this->template->write('title', t('title_licensed_request'),true);				
		$this->template->write('content', $content,true);
	  	$this->template->render();	
	}
	
	/**
	*
	* Send email message
	*
	*/
	function send_mail($requestid=NULL)
	{
		if (!is_numeric($requestid))
		{
			show_404();
		}
		
		$this->form_validation->set_rules('to', t('to'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('cc', t('cc'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('subject', t('subject'), 'trim|xss_clean|required');		
		$this->form_validation->set_rules('body', t('body'), 'required|trim|xss_clean');		

		//process form				
		if ($this->form_validation->run() == FALSE)
		{
			echo '<div class="error">'.validation_errors().'</div>';
			return;
		}

		//$to=explode(',',$this->input->post("to"));
		//$cc=explode(',',$this->input->post("cc"));
		
		$this->load->library('email');
		$this->email->clear();
		$config['mailtype'] = "html";
		$this->email->initialize($config);
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_webmaster_name'));
		$this->email->to($this->input->post("to"));
		$this->email->bcc($this->input->post("bcc"));
		$this->email->subject($this->input->post("subject") );
		$this->email->message($this->input->post("body"));
		
		if ($this->email->send())
		{
			echo '<div class="success">'.t('email_sent').'</div>';
		}
		else
		{
			echo '<div class="error">'.t('email_not_sent').'</div>';
		}
				
	}

	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 * @return string
	 * @author IHSN
	 **/
	function _search()
	{
		//records to show per page
		$per_page = 10;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'lic_requests.created';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->Licensed_model->search_requests($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->Licensed_model->search_requests_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Licensed_model->search_requests($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/licensed_requests');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field'));//pass any additional querystrings
		$config['num_links'] = 1;
		//$config['next_link'] = 'Next';		
		//$config['prev_link'] = 'Prev';
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}


	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id)
	{			
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/licensed_requests',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/licensed_requests');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->Licensed_model->delete($item);
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/licensed_requests');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}

}