<?php
class Datadeposit extends MY_Controller {

	private $storage_location=NULL;

	public function __construct() 
	{
		parent::__construct();
		$this->load->model('DD_project_model');
		$this->load->model('DD_study_model');
		$this->lang->load("dashboard");
		$this->lang->load('general');
		$this->load->library('form_validation');
		$this->lang->load('licensed_request');
		$this->load->language('dd_projects');
		$this->template->set_template('admin');	
		//$this->_get_active_project();
		
		$this->load->config("datadeposit");
		
		//location where data is stored
		$this->storage_location= $this->config->item('datadeposit');
		$this->storage_location = $this->storage_location['resources'];

		//$this->output->enable_profiler(TRUE);
	}
	
	public function index() 
	{
		$result['fields']   = array(
			'title'		 => 'Title',
			'shortname'  => 'Short name',
			'created_on' => 'Created on',    
			'created_by' => 'Created by',
			'status'     => 'Status'
		);

		$this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
		$this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';
        $search_keywords=$this->input->get("keywords",true);
		
		//get array of db rows
		if ($this->input->get('filter') && $this->input->get('filter')!='all' ) 
		{
			if ($this->input->get('filter') == 'requested') {
				$result['projects'] = $this->Projects_model->all_projects_requested_reopen();
			} 
			else if (in_array($this->input->get('filter'), array('submitted', 'accepted', 'draft', 'processed','closed'))) {
				$result['projects']=$this->DD_project_model->all_projects_by_filter($this->input->get('filter'), $this->sort_by, $this->sort_order,$search_keywords);
			} 
		}
		else 
		{
			$result['projects']=$this->DD_project_model->all_projects_by_filter(NULL,$this->sort_by, $this->sort_order,$search_keywords);
		}

        $this->load->model('DD_tasks_team_model');
        $result['tasks_team']=$this->DD_tasks_team_model->get_tasks_team_array();

		//$result['stats'] = $this->DD_project_model->stats();
		
		$this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
		$this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';
		
		//get array of db rows		
		//$result['submitted']=$this->DD_project_model->all_projects_by_filter('submitted', $this->sort_by, $this->sort_order);
		//$result['requested']=$this->DD_project_model->all_projects_requested_reopen();
				
		//load the contents of the page into a variable
		$content=$this->load->view('datadeposit/admin_index', $result,true);

		$this->template->write('content', $content,true);
		$this->template->write('title', t('title_project_management'),true);
	  	$this->template->render();	
	}	
	
	function id($id=null)
	{
		if (!is_numeric($id))
        {
            show_404();
        }

        /*$this->template->add_css('javascript/jquery/themes/base/jquery.ui.all.css');
		$this->template->add_js('javascript/jquery/ui/minified/jquery.ui.core.min.js');
		$this->template->add_js('javascript/jquery/ui/minified/jquery.ui.widget.min.js');
		$this->template->add_js('javascript/jquery/ui/minified/jquery.ui.tabs.min.js');	*/
		//$this->template->add_css('themes/datadeposit/styles-admin.css');

		$data['project_id']=$id;
		$data['project']=(object)$this->DD_project_model->get_by_id($id);
		$data['project_summary']=$this->DD_project_model->get_project_summary($id);
		$data['study_id']=$this->DD_project_model->get_study_id($id);
		$content=$this->load->view('datadeposit/admin_process_project',$data,true);
	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('title_project_management'),true);
	  	$this->template->render();
	}
	
	//process project e.g. change project status
	function tab_process($id)
	{
		if ($this->input->post("status"))
		{
			$options=array(
				'status'=>$this->input->post("status",true),
				'comments'=>$this->input->post("comments",true)		
			);
						
			$result=$this->DD_project_model->update($id,$options);
			$this->DD_project_model->write_history($id, $status=$options['status'],$comment=$options['comments']);
			
			if ($result && $this->input->post('assign_study_id'))
			{
				//update study id
				$this->DD_project_model->set_study_id($id,$this->input->post('assign_study_id',true));
			}
			
			//email notifications
			if ($result && (int)$this->input->post("notify")===1)
			{
				$this->send_project_update_notification($id,$options['comments']);
			}
			
			$output=NULL;
			
			if ($result)
			{
				$output=array(
					'status'=>'success',
					'message'=>'Project status updated successfully!'
				);				
			}
			else
			{
				$output=array(
					'status'=>'error',
					'message'=>'Failed to update project status'
				);				
			}
			
			die(json_encode($output));			
		}
	
		 $data['project']=(object)$this->DD_project_model->get_by_id($id);
		 $data['study_id']=$this->DD_project_model->get_study_id($id);
		 $this->load->view('datadeposit/tab_process',$data);
	}
	
	
	private function send_project_update_notification($id,$comments=NULL)
	{
		$project=(object)$this->DD_project_model->get_by_id($id);
		$project_url=site_url().'/datadeposit/summary/'.$id;
		$subject='[Status updated - #'.$id.'] - '.$project->title;		
		$message='Project status was updated, to see the project visit: '.$project_url;
		
		if ($comments)
		{
			$message.='<div style="margin-top:15px;">Admin comments:</div>';
			$message.='<div style="font-weight:bold;margin-top:10px;">'.$comments.'</div>';
		}
		
				
		$to=$this->DD_project_model->get_project_owner_email($id);//email of project owner
		$collabs = implode(',', $this->DD_project_model->get_collaborators($id));
		$cc=$collabs;

		$this->email_project($id, $to, $cc, $bcc=NULL, $subject,$message);
	}
	
	
	function tab_files($id)
	{
		$this->load->model('DD_resource_model');
		$data['files']= $this->DD_resource_model->get_project_resources_to_array($id);
		$data['storage_location']=$this->storage_location;
		$data['project_storage_location']=$this->DD_project_model->get_project_fullpath($id);
		$this->load->view('datadeposit/tab_files',$data);
	}
	
	function tab_history($id)
	{
		$data['history']=$this->DD_project_model->history_id($id);
		$this->load->view('datadeposit/tab_history',$data);
	}
	
	
	function tab_communicate($id)
	{
		if ($this->input->post("body"))
		{
			$this->load->helper('email');
			$this->load->library('email');
		
			$options=array(
				'to'		=>	$this->input->post("to",true),
				'cc'		=>	$this->input->post("cc",true),
				'subject'	=>	$this->input->post('subject',true),
				'body'		=>	$this->input->post('body',true),
			);
			
			$errors=array();
			
			//validation
			if (!$options['body']){
				$errors[]="Message body is required.";			
			}
			
			if (!$options['subject']){
				$errors[]="Message body is required.";			
			}
			
			if (!valid_email($options['to'])){
				$errors[]="Email recipient (TO) is required.";			
			}
			
			$options['body']=nl2br($options['body']);
			
			$result=false;
			$output=NULL;

			if (count($errors)==0)
			{
				$this->DD_project_model->write_history($id, $status="",$comment='<i>Email:</i>'.$options['body']);
				
				//send email			
				$result= $this->email_project($id, $options['to'], $options['cc'], $bcc=NULL, $options['subject'],$options['body']);
			}
				
			if (count($errors)==0 && $result==true)
			{
				$output=array(
					'status'=>'success',
					'message'=>'Email was sent!'
				);				
			}
			else
			{
				$output=array(
					'status'=>'error',
					'message'=>'Failed to send email. Check all form fields and try again.'
				);				
			}
			
			die(json_encode($output));
		}
		
		$data['project']=(object)$this->DD_project_model->get_by_id($id);
		$this->load->view('datadeposit/tab_communicate',$data);
	}
	
	/**
	*
	* Email project summary
	* Note: Duplicate function - move to model/library
	**/
	private function email_project($id, $to, $cc, $bcc, $subject,$message)
	{
		//$id=27;
		//$to='';
		
		$this->load->helper('email');
		$this->load->library('email');
		
		//$project_title=$this->DD_project_model->get_title_by_id($id);
		//$current_user_name=$this->session->userdata('username');

		//get formatted project summary
		$data['content']=$this->DD_project_model->get_project_summary($id);
		$data['message']=$message;

		//format html for email
		$css= file_get_contents(APPPATH.'../themes/datadeposit/email.css');
		$contents=$this->load->view('datadeposit/emails/template', $data,TRUE);
		
		//convert external styles to inline styles
		$this->load->library('CssToInlineStyles');
		$this->csstoinlinestyles->setCSS($css);
		$this->csstoinlinestyles->setHTML($contents);
		$contents=$this->csstoinlinestyles->convert();
		
		$this->email->clear();		
		$config['mailtype'] = 'html';
		$this->email->initialize($config);
		$this->email->CharSet = 'UTF-8';
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		$this->email->to($to);
		
		if ($cc){
			$this->email->cc($cc);
		}
		if ($bcc){
			$this->email->bcc($bcc);
		}
		
		$this->email->subject($subject);
		$this->email->message($contents);
		
		if (!@$this->email->send()) 
		{
			/*echo ("EMAIL_FAILED");
			echo $this->email->print_debugger();
			exit;*/
			return false;
		}
		else
		{
			//die ("EMAIL_SENT");
			return true;
		}	
	}
	
	function download($resource_id,$project_id)
	{
		//get project data folder path
		$project_folder_path=$this->DD_project_model->get_project_fullpath($project_id);
		
		if (!$project_folder_path)
		{
			show_error("PROJECT_DATA_FOLDER_NOT_SET");
		}
		
		$this->load->helper('download');
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
				
		$resource = $this->DD_resource_model->get_project_resource($resource_id);
		
		if (!$resource)
		{
			show_error("FILE_NOT_FOUND");
		}
		
		$resource_path=unix_path($project_folder_path.'/'.$resource[0]->filename);
		
		if (!file_exists($resource_path))
		{
			show_error("FILE_NOT_FOUND:".$resource_path);
		}
		
		force_download3($resource_path,$resource[0]->filename);

	}
	
	
	//prints folders using older method
	function old_folder_paths()
	{
		$this->db->select("id,created_on,title");
		$projects=$this->db->get('DD_projects')->result_array('DD_projects');
		
		$storage_location=$this->storage_location;
	
		echo '<pre>';
		foreach($projects as $project)
		{
			$md5=md5($project['id'] . $project['created_on']);
			$folder=$storage_location.'/'.$md5;
			echo $project['id']."\t".$project['created_on'];
			echo "\t" .$md5;
			echo "\t". $folder;
			if (file_exists($folder)){
				echo "\t YES";
			}
			echo "\n";
		}
		echo '</pre>';		
	}
	
	function update_folder_paths()
	{
		$this->db->select("id,created_on,title,data_folder_path");
		$projects=$this->db->get('DD_projects')->result_array('dd_projects');
		
		$storage_location=$this->storage_location;
		
		foreach($projects as $project)
		{
			if (!$project['data_folder_path'])
			{
				$md5=md5($project['id'] . $project['created_on']);
				$folder=$storage_location.'/'.$md5;
			
				if (file_exists($folder))
				{
					$this->DD_project_model->set_project_folder($project['id'],$md5);
					echo "updated project " .$project['id']."<BR>";
				}
			}
		}
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
				redirect('admin/datadeposit',"refresh");
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
				redirect('admin/datadeposit');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//log
				$this->db_logger->write_log('data-deposit',$this->session->userdata('username'). ' deleted project '.$item. ' - '.$this->DD_project_model->get_title_by_id($item) ,'delete');

				
				//delete
				$this->DD_project_model->delete($item);
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
				redirect('admin/datadeposit');
			}	
		}
		else
		{
			$items=array(); //list of deleted items
			
			foreach($delete_arr as $item)
			{
				//get project title
				$project_title=$this->DD_project_model->get_title_by_id($item);
				
				//exists
				if ($project_title)
				{
					$items[]=$project_title;
				}	
			}
			
			//ask for confirmation
			$content=$this->load->view('resources/delete', array('deleted_items'=>$items),true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}


    public function summary($id) {

        $this->template->set_template('blank');
        //$this->template->add_css('themes/datadeposit/styles-admin.css');
        //$this->template->add_css('body{padding:20px;}','embed');

        $content=$this->DD_project_model->get_project_summary($id);
        $this->template->write('content', $content,true);
        $this->template->write('title', t('title_project_management'),true);
        $this->template->render();
    }



    function assign($project_id)
    {
        $this->load->model('user_model');
        $this->load->model('DD_tasks_model');
        $this->load->model('DD_tasks_team_model');

        //validation rules
        $this->form_validation->set_rules('user_id', t('User'), 'xss_clean|required|max_length[10]|numeric');

        //process form
        if ($this->form_validation->run() == TRUE) {

            $user_id=$this->input->post("user_id");
            $current_user_id=$this->session->userdata('user_id');

            $db_result = $this->DD_tasks_model->assign_task($project_id,$user_id,$current_user_id);

            if ($db_result === TRUE)
            {
                $task_options=array(
                    'assigned_by'=>$current_user_id,
                    'assigned_to'=>$user_id,
                    'project_id'=>$project_id,
                    'status'=>0
                );

                //send email notification
                $this->DD_tasks_model->send_status_notification($task_options);

                //redirect
                $this->session->set_flashdata('message', t('form_update_success'));
                redirect("admin/datadeposit", "refresh");
            }
            else
            {
                //update failed
                $this->form_validation->set_error(t('form_update_fail'));
            }
        }


        $data['project_id']=$project_id;
        $data['project']=(object)$this->DD_project_model->get_by_id($project_id);

        $data['tasks_team']=$this->DD_tasks_team_model->get_tasks_team_array();
        $content=$this->load->view('datadeposit/assign_task',$data,true);

        $this->template->write('content', $content,true);
        $this->template->write('title', t('title_project_management'),true);
        $this->template->render();
    }



}	