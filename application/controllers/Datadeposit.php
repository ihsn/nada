<?php

/**
* Data deposit
**/


class Datadeposit extends MY_Controller {
	
	private $_study_grid_ids       = array(); //Hold each grid id, to prevent duplicate id's
	private $_uploaded_files       = false;
	private $_upload_list          = array();
	public  $active_project        = false;
	public $resources_location     = false;	
	public  $active_datafiles      = false;	
	public $active_citations       = false;	
	public $active_citations_count = 0;
	public $user_projects=NULL;



	public function __construct($SKIP=FALSE, $is_admin=FALSE) 
	{
		parent::__construct($SKIP, $is_admin);

		$this->config->load('datadeposit');

		if ($this->config->item('enable_datadeposit','datadeposit')!==true){
			show_404();
		}

		$this->load->model('DD_project_model');
		$this->load->helper('date');
		$this->load->language('dd_projects');
		$this->load->language('dd_help');

		$this->template->add_css('themes/datadeposit/data-deposit.css');
		$this->_get_active_project();

		$this->user_projects = $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc');
		//$this->output->enable_profiler(TRUE);
	}


    public function index()
    {
        $this->projects();
    }

    public function projects()
    {
        $data['fields']   = array(
            'title'       => 'Title',
            'created_by'  => 'Created by',
            'status'      => 'Status'
        );

        $this->sort_by    = $this->input->get('sort_by')    ? $this->input->get('sort_by'): 'created_on';
        $this->sort_order = $this->input->get('sort_order') ? $this->input->get('sort_order'): 'desc';

        $data['projects'] = $this->DD_project_model->projects($this->session->userdata('user_id'), $this->sort_by, $this->sort_order);

        $content = $this->load->view('datadeposit/projects_by_user', $data,true);
        $this->template->write('title', t('datadeposit'), true);
        $this->template->write('content', $content, true);
        $this->template->render();
    }
    


    /**
	*
	* Get active project
	**/
	private function _get_active_project() 
	{
		$segments = array('submit_review', 'submit','update','edit', 'edit_citations', 'upload', 'study','datafiles', 'add_citations', 'citations','upload','managefiles','summary');

		$this->load->model('DD_resource_model');
		$this->load->model('managefiles_model');
		$this->load->model('DD_study_model');
		$this->load->model('DD_citation_model');
		
		$this->load->language("resource_manager");
		
		$this->resources_location = $this->config->item('datadeposit');
		$this->resources_location = $this->resources_location['resources'];

		if (in_array($this->uri->segment(2),$segments))
		{
			if ($this->uri->segment(3)) 
			{
				if ($this->uri->segment(2) == 'edit_citations') 
				{
					$citation   = $this->DD_citation_model->select_single($this->uri->segment(3));
					$project_id = $citation['pid'];
				} 
				else 
				{
					$project_id=$this->uri->segment(3);
				}
				
				$this->active_project=$this->DD_project_model->project_id($project_id, $this->session->userdata('email'));
				$this->active_project['study'] = array();
				$this->active_project['study'] = $this->DD_study_model->get_study($project_id);
			}


			// get active datafiles and citationss
			if (in_array($this->uri->segment(2), array('submit_review', 'update','edit', 'edit_citations', 'upload', 'add_citations', 'study', 'datafiles', 'citations', 'submit'))) 
			{	
				//test if project exists and user has access
				if (!$this->DD_project_model->has_access($project_id, $this->session->userdata('email'))) 
				{
                    $this->session->set_flashdata('error', t('Access Denied: You don\'t have access to the project'));
                    redirect('datadeposit/projects');return;
	   			}
				
				//test if project is locked for editing
				if ($this->DD_project_model->is_locked($project_id, $this->session->userdata('email'))) 
				{
    				redirect('datadeposit/summary/'.$project_id);
	   			}				
			}
		}
	}
	

	//edit project basic info
	public function edit($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_error('INVALID ID');                
		}
		
		//required fields
		$this->form_validation->set_rules('title','Title','trim|required|xss_clean|max_length[255]');
		$this->form_validation->set_rules('name', t('shortname'),'required|max_length[100]');
		$this->form_validation->set_rules('description', t('description'),'max_length[1000]');
		$this->form_validation->set_rules('collaborator[]', t('collaborator'), 'xss_clean|callback__email_check');
	
		$data=NULL;
		//get project by id
		$data['project']=(object)$this->DD_project_model->get_by_id($id);
	
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array(
				'id'			=>	$id,
				'title'			=>	$this->input->post('title'),
				'shortname'		=>	$this->input->post('name'),
				'description'	=>	$this->input->post('description'),
				'collaborators'	=>	$this->input->post('collaborator')
			);
			
			if ($this->DD_project_model->update($id,$options))
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				$this->_write_history_entry("Project updated", $id, $data['project']->status);
				redirect('datadeposit/update/'.$id);
			}
			else
			{
				$this->form_validation->set_error(t('form_update_fail'));
			}				
		}
		
		$content = $this->load->view('datadeposit/edit_project_info', $data , true);
		$this->render_project_tab($id,$content,t('project_info'));            
	}
		
	
	
	/**
	* callback function to validate collaborator email addresses
	*
	*/
	function _email_check($emails)
	{
		if(!$emails){
			return true;
		}

		if (!$this->form_validation->valid_email($emails))
		{
			$this->form_validation->set_message('_email_check', t('invalid_email'));
			return FALSE;
		}
		return true;
	}
		

	public function update($id)
	{
		$this->edit($id);
	}

	

	//render project tab by id
	public function render_project_tab($id,$page_content,$page_title)
	{
		$pending_tasks_arr=NULL;
		$pending_tasks=NULL;
		
		
		if (is_numeric($id))
		{
			$pending_tasks_arr=$this->DD_project_model->get_pending_tasks($id);
			$pending_tasks=$this->load->view('datadeposit/pending_tasks',$pending_tasks_arr,TRUE);
		}
		
		$layout    = $this->load->view('datadeposit/project_layout', array('content'=>$page_content,'pending_tasks'=>$pending_tasks,'pending_tasks_arr'=>$pending_tasks_arr),true);
			
		$this->template->write('title', t('Data Deposit - '). $page_title ,true);
		$this->template->write('content', $layout,true);
	  	$this->template->render();	
	}


	public function export($id) 
	{
		$this->load->library('DDI_Study_Export');
		$this->load->model('DD_study_model');
		$this->load->model('DD_resource_model');
		$this->load->helper('download');

		$data['project'] = $this->DD_project_model->project_id2($id, $this->session->userdata('email'));

		if ($this->input->get('format')) {
	
			if ($this->input->get('format') == 'ddi') {
				$data['record'] = $this->DD_study_model->get_study_array($id);
				$this->ddi_study_export->load_template('application/templates/ddi_export_template.xml');
				
				$data['data']   = $this->ddi_study_export->to_ddi($data['record']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->id}_".date('d_m_y')."_ddi.xml"));
				$this->_write_history_entry("Study {$data['project'][0]->title} Exported as DDI", $data['project'][0]->id, $data['project'][0]->status);
				force_download($title, $data['data']);
	
			} else if ($this->input->get('format') == 'rdf') {
				$data['files']  = $this->DD_resource_model->get_project_resources($id);
				$data['data']   = $this->_resources_to_RDF($data['files']);
				$title          = strtolower(str_replace(' ', '_', "{$data['project'][0]->id}_".date('d_m_y')."_rdf.xml"));
				$this->_write_history_entry("Study {$data['project'][0]->title} Exported as RDF", $data['project'][0]->id, $data['project'][0]->status);
				force_download($title, $data['data']);
			}
		} else {
			redirect('datadeposit/projects');
		}
	}
	
	private function _clean_files_array( $arr ){
		foreach( $arr as $key => $all ){
			foreach( $all as $i => $val ){
				$new[$i][$key] = $val;    
			}    
		}
		// return cleaned array
		return array_filter($new, create_function('$x', '
			if (isset($x["name"])) {
				if (!empty($x["name"])) {
					return 1;
				}
			}
		'));
	}
	
	
	public function process_normal_uploads($id) 
	{
		$this->config->load('config');		
   		$allowed   = $this->config->item('allowed_resource_types');
		$project   = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		//$location  = md5($project[0]->id . $project[0]->created_on);
		$location =$project[0]->data_folder_path;
		
		$targetDir = unix_path($this->resources_location . '/' . $location);

		// Create target dir
		if (!file_exists($targetDir))
		{
			$folder_created=@mkdir($targetDir);
			
			if(!$folder_created)
			{
				$this->db_logger->write_log('data-deposit-error','failed to create folder '.$targetDir,'data-deposit');
				show_error("Folder creation failed");
			}
		}

		$_FILES['file'] = $this->_clean_files_array($_FILES['file']);
		
		foreach($_FILES['file'] as $file) 
		{
			  if ($file["error"] > 0) 
			  {
				show_error("Return Code: " . $file["error"]);
			  } 
			  else 
			  {
				$extension = substr(strrchr($file['name'], '.'), 1, strlen(strrchr($file['name'], '.')));

				// file extension check
				if (!in_array($extension, explode(',', $allowed))) 
				{
					$this->session->set_flashdata('message', sprintf('File extension <b>%s</b> is not allowed', $extension));
					redirect('datadeposit/datafiles/' . $project[0]->id);
				}
				
				$moved = move_uploaded_file($file["tmp_name"], $targetDir . '/' . $file["name"]);
				
				if (!$moved) 
				{
					show_error($file['name']);
				} 
				else {
					$project_resource = array(
						'filename'   => $file['name'],
						'project_id' => $id,
						'created'    => date("U"),
						'author'     => $project[0]->created_by
					);
					$this->DD_resource_model->insert_project_resource($id,$project_resource);
					$this->_write_history_entry("File {$file['name']} uploaded", $id, $project[0]->status);
				}
			  }
		}
		redirect('datadeposit/datafiles/' . $project[0]->id);
	}

	/**
	*
	* Batch import project resources
	**/
	function process_batch_uploads($id)
	{
		$this->load->model("managefiles_model");
		$this->load->model("DD_resource_model");
		$this->load->model("form_model");
		$this->load->model("catalog_model");

		$this->config->load('config');		
   		$allowed   = $this->config->item('allowed_resource_types');

		$this->lang->load("general");
		$this->lang->load("resource_manager");
		
		$folder_exists=FALSE;
		
		//get project row
		$project= $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		
		if(!$project)
		{
			show_error('INVALID_PROJECT_ID');
		}
		
		//project data files folder path
		$project_folder=$this->resources_location . '/'.$project[0]->data_folder_path;
	
		// Create target dir
		if (!file_exists($project_folder))
		{
			$folder_exists=@mkdir($project_folder);
		}
		else
		{
			$folder_exists=TRUE;
		}
		
		if (!file_exists($project_folder))
		{
			show_error('FOLDER-NOT-SET');
		}
		
		if (!file_exists($project_folder))
		{
			$output=array(
					'status'=>'failed',
					'message'=>'FOLDER-NOT-FOUND'
				);
			die(json_encode($output));
		}
		
		$config = array(
				'max_tmp_file_age' 		=> 900,
				'max_execution_time' 	=> 300,
				'target_dir' 			=> $project_folder,
				'allowed_extensions'	=> str_replace(",","|",$allowed),
				'overwrite_file'		=>TRUE
				);
				
		$this->load->library('Chunked_uploader', $config, 'uploader');
	
		try
		{
			$this->uploader->upload();
			
			if ($this->uploader->is_completed())
			{
				$output=array(
					'status'=>'success',
					'message'=>'upload completed',
					'file'=>$this->uploader->get_file_path()
				);
				
				log_message('debug', "PLUPLOAD: file upload completed - ".$this->uploader->get_file_path());
								
				//add file to database
				$uploaded_file_name=$this->uploader->get_file_name();
				$project_resource = array(
					'filename'   => $uploaded_file_name,
					'project_id' => $id,
					'created'    => date("U")
				);
				
				if($project)
				{
					$project_resource['author'] = $project[0]->created_by;
				}

				if (file_exists($this->uploader->get_file_path()) )
				{
					$result=$this->DD_resource_model->insert_project_resource($id,$project_resource);
					
					if ($result)
					{
						$this->_write_history_entry("File {$uploaded_file_name} uploaded", $id);
					}
					else
					{
						$this->_write_history_entry("File {$uploaded_file_name} uploaded but DB insert failed", $id);

						$output=array(
							'status'=>'failed',
							'message'=>'Database error occured for file: '.$project_resource['filename'],
							'file'=>$this->uploader->get_file_name()
						);

					}	
				}
				
				//print success message
				die (json_encode($output));
			}
			else //still uploading
			{
				$output=array(
					'status'=>'success',
					'message'=>'chunk uploaded'
				);
				die ( json_encode($output));
			}			
		}
		catch (Exception $ex)
		{
			$this->session->set_flashdata('error', 'File upload failed: '.$ex->getMessage());
			$response = array('error'>$ex->getMessage());			
			echo json_encode($response);			
			exit;
		}
	}
	

	
	
	public function batch_delete_resource($fid) 
	{
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
		$fids = explode(',', $fid);
		
		foreach($fids as $fid) 
		{
			$this->delete_resource($fid);
		}
	}

	public function delete_resource($fid) 
	{
		$this->load->helper('form');
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
				
		$data['file'] = $this->DD_resource_model->get_project_resource($fid);
		
		// check that the user has access
		$project = $this->DD_project_model->project_id($data['file'][0]->project_id, $this->session->userdata('email'));
		
		//get project folder path
		$project_folder = $this->DD_project_model->get_project_fullpath($project[0]->id);
				
		if (!$project_folder)
		{
			show_error('PROJECT_FOLDER_NOT_SET');
		}
		
		if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) 
		{
    			 redirect('datadeposit/projects');
	   	}
		
		if ($this->input->post('delete')) 
		{
			if ($this->input->post('answer') == 'Yes') 
			{			
				//delete db record
				$this->DD_resource_model->delete_project_resource($fid);
				
				//delete file from filesystem
				$resource_filename=$data['file'][0]->filename;
				
				unlink(unix_path($project_folder.'/'.$resource_filename));
				$this->_write_history_entry("file {$data['file'][0]->filename} deleted", $project[0]->id, $project[0]->status);
				
				//redirect('datadeposit/datafiles/' . $data['file'][0]->project_id);
			} 
			else if ($this->input->post('answer') == 'No') 
			{
				redirect('datadeposit/datafiles/' . $data['file'][0]->project_id);
			}
		}
		
		$content = $this->load->view('datadeposit/delete_resource', $data, true);
		$this->template->write('title', 'Confirm delete', true);
		$this->template->write('content', $content, true);
		$this->template->render();
	}


	
	//submit project
	function submit_project($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_error('INVALID_PROJECT');
		}
		
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) {
				 show_error('ACCESS_DENIED');
		}
		
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email')); 
		
		
		$this->form_validation->set_rules('access_policy', 'Access Policy', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('to_catalog', 'Choose Catalog', 'xss_clean|trim|max_length[255]');
		
		if ($this->input->post('is_embargoed'))
		{
			$this->form_validation->set_rules('embargoed', 'Notes for Embargoed', 'xss_clean|trim|max_length[255]|required');
		}
			
		$this->form_validation->set_rules('disclosure_risk', 'Disclosure Risk', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('key_variables', 'Key variables', 'xss_clean|trim|max_length[500]');
		$this->form_validation->set_rules('sensitive_variables', 'Sensitive variables', 'xss_clean|trim|max_length[500]');
		$this->form_validation->set_rules('access_authority', 'Access Authority', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('library_notes', 'Library Notes', 'xss_clean|trim|max_length[255]');
		
		if ($this->input->post('cc'))
		{
			$this->form_validation->set_rules('cc', 'CC', 'xss_clean|trim|max_length[300]|valid_emails');
		}	
		
		if(!$this->form_validation->run())
		{
			return false;
		}
		
		//save to db
		$update = array(
			'access_policy'    => ($this->input->post('access_policy'))    ? $this->input->post('access_policy') : null,
			'to_catalog'       => ($this->input->post('to_catalog'))       ? $this->input->post('to_catalog') : null,
			'library_notes'    => ($this->input->post('library_notes'))    ? $this->input->post('library_notes') : null,
			//'submit_contact'   => ($this->input->post('submit_contact'))   ? $this->input->post('submit_contact') : null,
			//'submit_on_behalf' => ($this->input->post('submit_on_behalf')) ? $this->input->post('submit_on_behalf') : null,
			'cc'               => ($this->input->post('cc'))               ? $this->input->post('cc') : null,				
			'submitted_on'     => date('U'),
			'is_embargoed'     => ($this->input->post('is_embargoed'))     ? 1: 0,
			'embargoed'        => ($this->input->post('embargoed'))        ? $this->input->post('embargoed') : null,
			'disclosure_risk'  => ($this->input->post('disclosure_risk'))  ? $this->input->post('disclosure_risk') : null,
			'sensitive_variables'  => ($this->input->post('sensitive_variables'))  ? $this->input->post('sensitive_variables') : null,
			'key_variables'  => ($this->input->post('key_variables'))  ? $this->input->post('key_variables') : null,
			'status'  		   => 'submitted',
			//'access_authority' => ($this->input->post('access_authority'))  ? $this->input->post('access_authority') : null
		);
		
		//save and submit project
		if (!$this->DD_project_model->submit_project($id, $update))
		{	
			//failed
			$this->session->flashdata('error','Failed to submit project, please contact the web master.');
			redirect('datadeposit/submit_review/'.$data['project'][0]->id);
		}
		
		//success
		$this->session->set_flashdata('message', t('project_submitted'));
		$this->_write_history_entry("Project submitted", $data['project'][0]->id, 'submitted');
		
		//for the user
		$user=$this->ion_auth->current_user();
		$project_url=site_url().'/datadeposit/summary/'.$id;
		$subject='[Confirmation - #'.$id.'] - '.$data['project'][0]->title;		
		$message=sprintf(t('msg_project_submitted'),ucwords($user->username), ucwords($data['project'][0]->title),$project_url);		
		$to=$this->DD_project_model->get_project_owner_email($id);//email of project owner
		
		//cc + project collaborators
		$cc = $this->input->post('cc');
		$collabs = implode(',', $this->DD_project_model->get_collaborators($id));	
		
		if ($cc !== false) {
			$cc=implode(',', array_unique(explode(',', $cc . ',' . $collabs)));
		} else {
			$cc=$collabs;
		}

		//send email receipt to user
		$this->email_project($id, $to, $cc, $bcc=NULL, $subject,$message);
		
		//notify adminstrators
		$admin_email=$this->config->item('website_webmaster_email');
		$subject=sprintf(t('notice_project_submitted'),$data['project'][0]->title,$user->username);
		$message=sprintf(t('notify_admin_new_project_submitted'),$user->username,$data['project'][0]->title,site_url('admin/datadeposit/id/'.$id));
		
		//notify the site admin
		$this->email_project($id, $to=$admin_email, $cc=NULL, $bcc=NULL, $subject,$message);
		
		//redirect to project listing page
		redirect('datadeposit/projects');
	}
	
	
	
	public function submit_review($id) 
	{
		$this->template->add_css('javascript/jquery/ui/themes/base/jquery-ui.css');
		$this->template->add_js('javascript/jquery/ui/jquery.ui.js');

		$this->load->model('DD_resource_model');
		$this->load->model('DD_study_model');

        $this->config->load('datadeposit');

		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email')); 
			
		if (!$this->DD_project_model->has_access($data['project'][0]->id, $this->session->userdata('email'))) {
    			 redirect('datadeposit/projects');
		}
		
		//get project owner
		$data['project'][0]->owner=$this->DD_project_model->get_owner($id);
            
       //get project collaborators
        $data['project'][0]->collaborators=$this->DD_project_model->get_collaborators($id);
		
		//get all study metadata
		$data['row']             = $this->DD_study_model->get_study($data['project'][0]->id);
		
		$data['files']           = $this->DD_resource_model->get_project_resources_to_array($id);
		$data['citations']		 = $this->DD_citation_model->get_citations_by_project($id);
		$grids                   = array();
		$grids['methods']        = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_summary_study_grid($grid_id, $grid_data, true, true);
		}	
		$data['fields']  = $this->config->item('datadeposit');

		$data['study']   = $this->DD_study_model->get_study($data['project'][0]->id);
		
		//process project on submit
		$this->submit_project($id);
				
		$data['merged']         = $this->config->item('datadeposit');
		$data['files']    = $this->DD_resource_model->get_project_resources_to_array($id);
		$data['projects'] = $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc');

		//summary print preview - TODO: move to separate function
		if ($this->input->get('print') && $this->input->get('print') == 'yes') 
		{
			$this->template->set_template('blank');	
			$this->template->add_css('themes/datadeposit/styles.css');
			$content=$this->load->view('datadeposit/project_review', $data,TRUE);
			$this->template->write('title', 'Summary', true);
			$this->template->write('content', $content, true);
			$this->template->render();
		} 
		else 
		{
			$data['merged']         = $this->config->item('datadeposit');
			$data['projects'] 		= $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc;');
			$content     			= $this->load->view('datadeposit/project_review_submit', $data, true);
			$this->render_project_tab($id,$content,t('metadata_review'));
		}
	}
	
	public function summary($id) {
    		
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email')); 
		$data['id']=$id;
		
		if (!$this->DD_project_model->has_access($data['project'][0]->id, $this->session->userdata('email'))) {
    			 redirect('datadeposit/projects');
	   }
	   		
		if ($this->input->get('print') && $this->input->get('print') == 'yes') 
		{
			echo $this->_get_formatted_project_summary($id);
		} 
		else 
		{
			//$contents = $this->load->view('datadeposit/request_reopen',$data, true);
			$contents= $this->_get_formatted_project_summary($id);
			
			$this->template->write('title', 'Summary', true);
			$this->template->write('content', $contents, true);		
			$this->template->render();
		}
	}
	

	
	public function download($fid) {
		
		$this->load->helper('download');
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
				
		$data['file'] = $this->DD_resource_model->get_project_resource($fid);
		
		// check that the user has access
		$project = $this->DD_project_model->project_id($data['file'][0]->project_id, $this->session->userdata('email'));
		
		if (!$project)
		{
			show_error("PROJECT_NOT_FOUND");
		}
		
		if ($this->session->userdata('group_id') !== 1) 
		{
			if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) {
    			 redirect('datadeposit/projects');
	    	}	   
		}
		
		//get project data folder path
		$project_folder_path=$this->DD_project_model->get_project_fullpath($project[0]->id);
		
		if (!$project_folder_path)
		{
			show_error("PROJECT_DATA_FOLDER_NOT_SET");
		}
		
		$resource = $this->DD_resource_model->get_project_resource($fid);
		
		if (!$resource)
		{
			show_error("FILE_NOT_FOUND");
		}
		
		$resource_path=unix_path($project_folder_path.'/'.$resource[0]->filename);
		
		if (!file_exists($resource_path))
		{
			show_error("FILE_NOT_FOUND:".$resource_path);
		}
		
		$this->_write_history_entry(sprintf("file %s downloaded", $resource[0]->filename), $project[0]->id, $project[0]->status);
		force_download3($resource_path,$resource[0]->filename);
		
	}
	
	public function managefiles($fid) 
	{
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
		$data['file'] = $this->DD_resource_model->get_project_resource($fid);
		
		//get resource project
		$project = $this->DD_project_model->project_id($data['file'][0]->project_id, $this->session->userdata('email'));
		
		//check user hass access
		if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) {
    		 redirect('datadeposit/projects');
	   }
	   
		$data['id']   = $project[0]->id;
		
		if ($this->input->post('update')) 
		{
			$record = array(
				'title'       => ($this->input->post('title')) ? $this->input->post('title') : $data['file'][0]->title,
				'description' => ($this->input->post('description')) ? $this->input->post('description') : $data['file'][0]->description,
				'author'      => ($this->input->post('author')) ? $this->input->post('author') : $data['file'][0]->author,
				'dctype'      => ($this->input->post('dctype')) ? $this->input->post('dctype') : $data['file'][0]->dctype,
			);
			
			//sanitize
			foreach($record as $key=>$value)
			{
				$record[$key]=$this->security->xss_clean($value);
			}
			
			$this->DD_resource_model->update_project_resource($fid, $record);
			redirect("datadeposit/datafiles/{$data['file'][0]->project_id}");
		}
		
		$content = $this->load->view('datadeposit/managefiles', $data, true);	
		$this->template->write('content', $content, true);
		$this->template->write('title', 'Manage files', true);
		$this->template->render();
	}
	
	
	public function ajax_managefiles($fid) 
	{
		$this->load->model('DD_resource_model');
		$this->lang->load("resource_manager");
		$this->load->model('managefiles_model');
		$data['file'] = $this->DD_resource_model->get_project_resource($fid);
		$project = $this->DD_project_model->project_id($data['file'][0]->project_id, $this->session->userdata('email'));
			if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) {
    			 redirect('datadeposit/projects');
	  		 }
		$record = array(
			'dctype' =>	($this->input->post('dctype')) ? $this->input->post('dctype') : $data['file'][0]->dctype
		);
		$this->DD_resource_model->update_project_resource($fid, $record);
		
		echo preg_replace('#\[.*?\]#', '', ($this->input->post('dctype')) ? $this->input->post('dctype') : $data['file'][0]->dctype);
	}
	
	public function request_reopen($id) {
    				
		$this->load->library('email');

		$project_obj = $this->DD_project_model->project_id($id, $this->session->userdata('email')); 
		$user_obj=$this->ion_auth->current_user();
				
		if (!$project_obj)
		{
			show_error("PROJECT_NOT_FOUND");
		}

		$this->load->helper('email_notifications');
		$this->form_validation->set_rules('reason', 'Reason', 'required');
		
		if ($this->input->post('reopen')) 
		{
			if($this->form_validation->run() === TRUE)
			{			
				$subject = "#$id ".sprintf(t("notice_reopen_request"),$project_obj[0]->title);
				$email_options=array(
					'project_title'				=> $project_obj[0]->title,
					'project_admin_url'			=> site_url('admin/datadeposit/id/'.$id),
					'project_reopen_reason'		=> nl2br($this->input->post('reason',true)),
					'user_name'					=> $user_obj->first_name.' '.$user_obj->last_name
				);
				
				$message=$this->load->view('datadeposit/emails/email_project_reopen', $email_options,true);
				
				$this->DD_project_model->update($id, array(
					'requested_reopen' => 1,
					'requested_when'   => date('U')
				));
				
				$this->session->set_flashdata('message', t('reopen_requested'));
				dd_notify_admin($subject,$message,$notify_all_admins=false);
				$this->_write_history_entry("Requested reopen", $id, 'submitted/closed');
				redirect('datadeposit/projects');
			}
			
			$data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		}
		
		$data['id'] = $id;
		$data['project']=$project_obj;
		$content = $this->load->view('datadeposit/request_reopen', $data, true);
		
		$this->template->write('title', 'Request Reopen', true);
		$this->template->write('content', $content, true);				
		$this->template->render();
	}

			
	
	public function datafiles($id) 
	{
		//disable page if storage location in not accessible
		if (!file_exists($this->resources_location) || !$this->resources_location)
		{
			show_error('DATADEPOSIT_STORAGE_INACCESSIBLE');
		}		
	
		$this->config->load('config');		
		$data['allowed']   = $this->config->item('allowed_resource_types');
		
		$this->load->model('DD_resource_model');
		$this->load->model('managefiles_model');
		$this->lang->load("resource_manager");
		
		$this->template->add_css("themes/adminbt/plupload.css");
				
		$project                = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		$data['option_formats'] = $this->DD_resource_model->get_dc_types();
		$data['resources']    	= $this->DD_resource_model->get_project_resources_to_array($id);
		//$dir                    = $this->resources_location . '\\' ;
		//$dir                   .= md5($project[0]->id . $project[0]->created_on);
		
		//$data['records']        = $this->managefiles_model->get_files_non_recursive($dir, '');
		//$data['records']        = $data['records']['files'];
		
		//get project folder path
		$project_folder = $this->DD_project_model->get_project_fullpath($project[0]->id);

		if (!$project_folder)
		{
			show_error('PROJECT_FOLDER_NOT_SET');
		}

		$data['project_folder']	=$project_folder;
		$data['id']             = $id;
		
		if (!is_dir($this->resources_location))
		{
			show_error('ROOT_DATA_FOLDER_NOT_SET');
		}
		
		$project_folder_created=false;
		
		if (!file_exists($project_folder))
		{
			$project_folder_created=@mkdir($project_folder);
		}
				
		if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) 
		{
    			 redirect('datadeposit/projects');
		}
		
		$content = $this->load->view('datadeposit/datafiles', $data, true);
		$this->render_project_tab($id,$content,t('datafiles'));
	}
	
	
	
	public function add_citations($id) 
	{
		$this->load->model('DD_citation_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		$this->html_form_url=site_url().'/admin/citations';		    		
		$this->lang->load('citations');
	
		$data['merged']  = $this->config->item('datadeposit');
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email'));

		if (!$this->DD_project_model->has_access($data['project'][0]->id, $this->session->userdata('email'))) {
    		 redirect('datadeposit/projects');
		}

		$data['projects'] = $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc');

		$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		$this->template->add_js('javascript/jquery/ui/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
		
       	//validate form input
		$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('authors', 'Authors', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('volume', 'Volume', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('issue', 'Issue', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('pub_year', 'Year', 'xss_clean|trim|max_length[4]|is_numeric');
		$this->form_validation->set_rules('doi', 'DOI', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('flag', t('flag_as'), 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('published', t('published'), 'xss_clean|trim|is_numeric');
		
		//ignore the form submit if only changing the citation type
		if ($this->input->post("select")==FALSE)
		{		
			//add/update record
			if ($this->form_validation->run() == TRUE) 
			{ 
				$this->_citations_update($id);
			}
		}		

		//flash data message
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		
		$content = $this->load->view('datadeposit/add_citations', $data, true);
		$this->render_project_tab($id,$content,t('add_citations'));
	}
	
	
	function delete_citation($id=NULL)
	{	
		$this->load->model('DD_citation_model');

		if ($id==NULL)
		{
			return FALSE;
		}
				
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
				redirect('datadeposit/projects',"refresh");
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
				redirect('admin/citations');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//get citation info
				$citation=$this->DD_citation_model->select_single($item);
				if (!$this->DD_project_model->has_access($citation['pid'], $this->session->userdata('email'))) {
    		 		redirect('datadeposit/projects');
				}
				//delete if exists
				if ($citation)
				{
					//log to database
					$this->db_logger->write_log('delete',$citation['title'],'citations');
					
					//confirm delete	
					$this->DD_citation_model->delete($item);	
				}								
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
				redirect('datadeposit/citations/'.$citation['pid']);
			}	
		}
		else
		{
			
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);

		$tabs    = $this->load->view('datadeposit/tabs2', array('content' => $content), true);

			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	public function edit_citations($id,$pid) 
	{
		$this->load->model('DD_citation_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		$this->html_form_url=site_url().'/admin/citations';		    		
		$this->lang->load('citations');
					
		$data['merged']  = $this->config->item('datadeposit');
		$data['project'] = $this->DD_project_model->project_id($pid, $this->session->userdata('email'));

		if (!$this->DD_project_model->has_access($pid, $this->session->userdata('email'))) {
    		 redirect('datadeposit/projects');
		}

		
	    //validate form input
		$this->form_validation->set_rules('authors', 'Authors', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('volume', 'Volume', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('issue', 'Issue', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('pub_year', 'Year', 'xss_clean|trim|max_length[4]|is_numeric');
		$this->form_validation->set_rules('doi', 'DOI', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('flag', t('flag_as'), 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('published', t('published'), 'xss_clean|trim|is_numeric');
		
		//ignore the form submit if only changing the citation type
		if ($this->input->post("select")==FALSE)
		{		
			//add/update record
			if ($this->form_validation->run() == TRUE) 
			{ 
				$this->_citations_update($pid, $id);
			}
			else
			{
				//loading the form for the first time			
				if ($id!=NULL && !$this->input->post("submit"))
				{
					//load data from database
					$data['citation']=$this->DD_citation_model->select_single($id);

					if (!$data['citation'])
					{
						show_error('INVALID ID');
					}
					
					if ($data['citation']['authors'])
					{
						$this->_set_post_from_db($data['citation']);
					}
					
					$data=array_merge($data,$data['citation']);
				}
			}
		}		

		//flash data message
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		
		$content = $this->load->view('datadeposit/edit_citations', $data, true);
		$this->render_project_tab($id,$content,t('edit_citations'));
	}
	
	
	//load authors, editors, translators into the POST array
	function _set_post_from_db($db_row)
	{
		$keys=array(
				'author'=>'authors',
				'editor'=>'editors',
				'translator'=>'translators',
				);
				
		foreach($keys as $key=>$value)
		{
			$authors=($db_row[$value]);//unserialize authors,editors,translators
		
			if (is_array($authors))
			{
				$fname=array();
				$lname=array();
				$initial=array();

				foreach($authors as $author)
				{
					$fname[]=$author['fname'];
					$lname[]=$author['lname'];
					$initial[]=$author['initial'];
				}
	
				$_POST[$key.'_fname']=$fname;
				$_POST[$key.'_lname']=$lname;
				$_POST[$key.'_initial']=$initial;
			}
		}	
	}

	private function _process_authors($key='author')
	{
		$list=array();

		$keys=array();
		$keys['fname']=$key.'_fname';
		$keys['lname']=$key.'_lname';
		$keys['initial']=$key.'_initial';
		
		//arrays of postback data
		$fname_array=$this->input->post($keys['fname']);
		$lname_array=$this->input->post($keys['lname']);
		$initial_array=$this->input->post($keys['initial']);
		
		//var_dump($this->input->post($keys['fname']));exit;
		if ($fname_array==FALSE || !is_array($fname_array))
		{
			return FALSE;
		}
		
		//combine the values for individual fiels into one array
		$authors=array();
		
		//iterate rows
		for($i=0;$i<count($fname_array);$i++)
		{
			if ($fname_array[$i]!='' || $lname_array[$i]!='' )
			{
				$authors[]=array(
						'fname'=>$fname_array[$i],
						'lname'=>$lname_array[$i],
						'initial'=>$initial_array[$i]
				);
			}
		}
		
		return $authors;
	}

	private function _citations_update($pid, $id=0)
	{
		$options=array();
		$post_arr=$_POST;
					
		//read post values to pass to db
		foreach($post_arr as $key=>$value)
		{
			$options[$key]=$this->security->xss_clean($this->input->post($key));
		}					

		//
		if (isset($options['pub_year']))
		{
			$options['pub_year']=(integer)$options['pub_year'];
		}	
		if (isset($options['pub_day']))
		{
			$options['pub_day']=$options['pub_day'];
		}	
		
		//process authors from the postback data
		$options['authors']=($this->_process_authors('author'));
		$options['editors']=($this->_process_authors('editor'));
		$options['translators']=($this->_process_authors('translator'));
		
		//reset fields for which there is not data posted
		$reset_fields=array(
					'subtitle','alt_title','authors','editors','translators','volume',
					'issue', 'idnumber', 'edition', 'place_publication', 'place_state',
					'publisher', 'url','page_from', 'page_to',
					'data_accessed','organization', 'pub_day','pub_month', 'pub_year','abstract');
		
		foreach($reset_fields as $field)
		{
			//check if the field is not in the postback data
			if (!array_key_exists($field,$options))
			{
				//add a null value for the non-existent field
				$options[$field]='';
			}
		}
		
		try
		{
			if ($id==NULL)
			{	
				$options['pid']=$pid;
				//insert record, returns the new id
				$id=$this->DD_citation_model->insert($options);
				$db_result=FALSE;
				
				if($id!==FALSE)
				{
					$db_result=TRUE;
					
					//log to database
					$this->db_logger->write_log('new',$options['title'],'citations');
				}	
			}
			else
			{				
				//update record
				$db_result=$this->DD_citation_model->update($id,$pid,$options);	
				
				//log to database
				$this->db_logger->write_log('change',$options['title'],'citations');
			}		
		}
		catch(Exception $e)
		{
			//insert/update failed
			$this->form_validation->set_error($e->message_detailed());
			$db_result=FALSE;
		}
			
		if ($db_result!==FALSE)
		{
			//update successful
			$this->session->set_flashdata('message', t('form_update_success'));
			
			//redirect back to the list
			redirect("datadeposit/citations/{$pid}","refresh");
		}
	}

	private function _citations_search($pid)
	{	
		$this->load->library( array('form_validation','pagination') );
		$this->load->model('DD_citation_model');
		//citations session id
		$session_id="citations";

		//reset if reset param is set
		if ($this->input->get('reset'))
		{
			$this->session->unset_userdata($session_id);
		}
	
		//all keys that needs to be persisted
		$get_keys_array=array('ps','offset','sort_order','sort_by','keywords','field');
		
		//session array
		$sess_data=array();
		
		//add get values to session array
		foreach($get_keys_array as $key)
		{
				$value=get_post_sess('citations',$key);
				if ($value)
				{
					$sess_data[$key]=$value;
				}	
		}
				
		//store values to session
		$this->session->set_userdata(array($session_id=>$sess_data));
		
		$this->per_page = 	get_post_sess($session_id,"ps");
		$this->field=		get_post_sess($session_id,'field');
		$this->keywords=	get_post_sess($session_id,'keywords');
		$this->offset=		get_post_sess($session_id,'offset');//current page
		$this->sort_order=	get_post_sess($session_id,'sort_order') ? get_post_sess($session_id,'sort_order') : 'desc';
		$this->sort_by=		get_post_sess($session_id,'sort_by') ? get_post_sess($session_id,'sort_by') : 'changed';
		
		if (!is_numeric($this->per_page))
		{
			$this->per_page=20;
		}
				
		//filter
		$filter=NULL;

		//simple search
		if ($this->keywords){
			$filter[0]['field']=$this->field;
			$filter[0]['keywords']=$this->keywords;
		}		
		
		//records
		$rows=$this->DD_citation_model->search($pid, $this->per_page, $this->offset,$filter, $this->sort_by, $this->sort_order);

		//total records in the db
		$this->total = $this->DD_citation_model->search_count();

		if ($this->offset>$this->total)
		{
			$this->offset=$this->total-$this->per_page;
			
			//search again
			$rows=$this->DD_citation_model->search($pid, $this->per_page, $this->offset,$filter, $this->sort_by, $this->sort_order);
		}
		
		//set pagination options
		$base_url = site_url('/datadeposit/citations/').$pid.'/';
		$config['base_url'] = $base_url;
		$config['total_rows'] = $this->total;
		$config['per_page'] = $this->per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	public function citations($id) 
	{
		$this->load->model('DD_citation_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		$this->html_form_url=site_url().'/datadeposit/citations/'.$id;		    		
		$this->lang->load('citations');
		$this->load->model('DD_study_model');
		$data['merged']  = $this->config->item('datadeposit');
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email'));

		if (!$this->DD_project_model->has_access($data['project'][0]->id, $this->session->userdata('email'))) 
		{
    			 redirect('datadeposit/projects');
	   	}
		
		$data['study']   = $this->DD_study_model->get_study($data['project'][0]->id);
		$data['rows']=$this->DD_citation_model->get_citations_by_project($id);//$this->_citations_search($id);
		$data['projects'] = $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc');
		
		$content = $this->load->view('datadeposit/citations', $data, true);		
		$this->render_project_tab($id,$content,t('citations'));
	}

	
	public function import_from_project() 
	{
		$from = (int)$this->input->get('from');
		$to   = (int)$this->input->get('to');
		$insert = $this->DD_project_model->import_from_project($this->session->userdata('user_id'), $from, $to); 
		
		if ($insert) {
			$this->_write_history_entry("Imported metadata from external project", $to, 'draft');
		}
		echo ($insert) ? 'success' : 'fail';
	}
	
	
	
	/**
	*
	* Edit project study description
	**/
	public function study ($id) 
	{
		$this->load->helper('url');
		$this->load->helper('form');
		$this->config->load('datadeposit');
		$this->load->model('DD_study_model');
		$this->load->model('DD_resource_model');
		$this->lang->load('dd_help');
		
		$message            = '';
		$data['fields']     = $this->config->item('datadeposit');
		$data['project']    = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		$data['studytype']  = $this->config->item('study_types','datadeposit');
		$data['kindofdata'] = $this->DD_resource_model->get_kind_of_data();
		$data['projects']   = $this->DD_project_model->projects($this->session->userdata('user_id'));
		$data['methods']    = $this->DD_resource_model->get_overview_methods();
		$data['row']        = $this->DD_study_model->get_study($data['project'][0]->id);
		$data['merged']     = $this->config->item('datadeposit');
		
		//show error if project is missing the record for study description part
		if (!$data['row'])
		{
			show_error('PROJECT_MISSING_STUDY_DESCRIPTION');
		}
			
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) 
		{
            $this->session->set_flashdata('error', t('Access Denied: You don\'t have access to the project'));
            redirect('datadeposit/projects');
	   	}
		
		//Prepare our grid data for presentation 
		
		$grids                         = array();
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['scope_keywords']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_keywords) ? $data['row'][0]->scope_keywords : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => json_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Agency'       => 'agency', 
				'Abbreviation' => 'abbr',
				'Grant Number' => 'grant',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			if (empty($grid_data['data'])) {
				// If a grid is empty, provide an empty row
				$count                 = $grid_data['titles'];
				$grid_data['data']    = array();
				$grid_data['data'][0] = array();
				foreach($grid_data['titles'] as $titles) {
					$grid_data['data'][0][] = ' ';
				}
			}
			$data[$grid_id] = $this->_study_grid($grid_id, $grid_data);
		}	

		// Here we provide a map for each grid and their corresponding field
		$data['map'] = array(
			'scope_class' => 'topic_class',
			'coverage_country' => 'country',
			'prod_s_investigator' => 'prim_investigator',
			'prod_s_other_prod' => 'other_producers',
			'prod_s_funding' => 'funding',
			'prod_s_acknowledgements' => 'acknowledgements',
			'coll_dates' => 'dates_datacollection',
			'coll_periods' => 'time_periods',
			'coll_collectors' => 'data_collectors',
			'access_authority' => 'access_authority',
			'contacts_contacts' => 'contacts',
			'scope_keywords' => 'scope_keywords',
			'impact_wb_lead' => 'impact_wb_lead',
			'impact_wb_members' => 'impact_wb_members'
		);

		//$this->form_validation->set_rules('ident_id', 'ID', 'numeric');

		$study = array();
		
		if($this->input->post('study') || $_SERVER['REQUEST_METHOD'] == 'POST') 
		{
			foreach($_POST as $key => $value) 
			{				
				if (!in_array($key, array_keys($grids))) {
					$study[$key] = $this->security->xss_clean($value);
				}
			}
			
			if ($study['ident_title'] != $data['project'][0]->title) {
				if (isset($study['ident_title']) && !empty($study['ident_title'])) {
					$ident = array('title' => $study['ident_title']);
					$this->DD_project_model->update($id, $ident);
				}
			}
			
		//	unset($study['ident_title']);
		//	$study['ident_title'] = $data['project'][0]->title; 
			unset($study['study']); // <-- submit button
			$study['id']= $data['project'][0]->id;
			
			// date field
			if (isset($study['ver_prod_date_year'])) {
				$study['ver_prod_date'] = $this->input->post('ver_prod_date_year') . '-' .
					$this->input->post('ver_prod_date_month') . '-' .
					$this->input->post('ver_prod_date_day');
				
				$study['ver_prod_date'] = mktime(0,0,0, 
											intval($this->input->post('ver_prod_date_month')),
											intval($this->input->post('ver_prod_date_day')),
											intval($this->input->post('ver_prod_date_year'))
											);
				
				//var_dump($study['ver_prod_date']);
				
				//if not valid, change to NULL
				if(!$study['ver_prod_date'])
				{
					$study['ver_prod_date']=NULL;
				}
			}

			unset($study['ver_prod_date_year']);
			unset($study['ver_prod_date_month']);
			unset($study['ver_prod_date_day']);
			unset($study['submit']);
			
			// grids
			$study['scope_class']             = $this->_grid_data_encode($this->input->post('topic_class'));
			$study['coverage_country']        = $this->_grid_data_encode($this->input->post('country'));
			$study['prod_s_investigator']     = $this->_grid_data_encode($this->input->post('prim_investigator'));
			$study['prod_s_other_prod']       = $this->_grid_data_encode($this->input->post('other_producers'));
			$study['prod_s_funding']          = $this->_grid_data_encode($this->input->post('funding'));
			$study['prod_s_acknowledgements'] = $this->_grid_data_encode($this->input->post('acknowledgements'));
			$study['coll_dates']              = $this->_grid_data_encode($this->input->post('dates_datacollection'));
			$study['coll_periods']            = $this->_grid_data_encode($this->input->post('time_periods'));
			$study['coll_collectors']         = $this->_grid_data_encode($this->input->post('data_collectors'));
			$study['access_authority']        = $this->_grid_data_encode($this->input->post('access_authority'));
			$study['contacts_contacts']       = $this->_grid_data_encode($this->input->post('contacts'));
			$study['scope_keywords']          = $this->_grid_data_encode($this->input->post('scope_keywords'));
			$study['impact_wb_lead']          = $this->_grid_data_encode($this->input->post('impact_wb_lead'));
			$study['impact_wb_members']       = $this->_grid_data_encode($this->input->post('impact_wb_members'));
			//var_dump($this->input->post('country'));
			//var_dump($study['coverage_country']);
			//var_dump($this->_grid_data_decode($study['coverage_country']));
			//exit;
			
			$recommended = current($data['fields']);
			$filled      = true;
			foreach ($recommended as $needed) {
				if (!isset($study[$needed])) {
					$filled = false;
					break;
				} else if (empty($study[$needed])) {
					$filled = false;
				}
				else if ($study[$needed] == '[]') { // empty grids
					$filled = false;
				}
			}
			
			if(empty($message))
			{
				$d = true;
				$study_exists=$this->DD_study_model->get_study($data['project'][0]->id);
				
				if ($study_exists) 
				{
					$d = $this->DD_study_model->update_study($data['project'][0]->id, $study);

                    //update last modified date
                    $this->DD_project_model->update ($data['project'][0]->id, array('last_modified'=>date('U')));
					
				} else 
				{
					$d = $this->DD_study_model->insert_study($study);
				}
				
				if ($d === false) 
				{
					$this->session->set_flashdata('message', t('study_submission_failed'));
					redirect("datadeposit/study/{$data['project'][0]->id}");
				}
				
				$this->_write_history_entry("Study description updated", $data['project'][0]->id, $data['project'][0]->status);
                $this->session->set_flashdata('message', t('Your changes were saved successfully!'));

				/*if (!$filled)
				{
					$this->session->set_flashdata('message', t('submitted_but_omitted'));
				} 
				else 
				{
					$this->session->set_flashdata('message', t('submitted'));
				}*/
				
				redirect("datadeposit/study/{$data['project'][0]->id}");
			}
		}

		$data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		$data['projects'] = $this->DD_project_model->projects($this->session->userdata('user_id'), 'title', 'asc');

		$content = $this->load->view('datadeposit/study', $data, true);
		$this->render_project_tab($id,$content,t('study_description'));
	}

	


	public function create() 
	{
		$this->form_validation->set_rules('title','Title','trim|required|xss_clean|max_length[255]');
		$this->form_validation->set_rules('name', t('shortname'),'required|max_length[100]');
		$this->form_validation->set_rules('description', t('description'),'max_length[1000]');
		$this->form_validation->set_rules('collaborator[]', t('collaborator'), 'xss_clean|callback__email_check');
	
		$data=NULL;
		
		//process form				
		if ($this->form_validation->run() == TRUE)
		{
			$options=array(
				'title'			=>	$this->input->post('title'),
				'shortname'		=>	$this->input->post('name'),
				'description'	=>	$this->input->post('description'),
				'collaborators'	=>	$this->input->post('collaborator'),
				'created_on'    => date("U"),
				'last_modified' => date("U"),
				'created_by'    => ucwords($this->session->userdata('username')),
				'status'		=>'draft'
			);
			
			//insert
			$new_id=$this->DD_project_model->insert($options,$this->session->userdata('email'));
			
			if ($new_id)
			{
				$this->session->set_flashdata('message', t('form_update_success'));
				$this->_write_history_entry("Project created", $new_id, 'draft');
				redirect('datadeposit/update/'.$new_id);
			}
			else
			{
				$this->form_validation->set_error(t('form_update_fail'));
			}				
		}
		
		$content = $this->load->view('datadeposit/edit_project_info', $data , true);
		$this->render_project_tab(NULL,$content,t('project_info'));
	}



	//alias for the confirm() function
    //TODO rename confirm function
    public function delete($id){
        $this->confirm($id);
    }

    //confirm deleting a project
	public function confirm($id) 
	{	    		
		$this->load->helper('form');
		$project = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		
		if (!$this->DD_project_model->has_access($project[0]->id, $this->session->userdata('email'))) 
		{
    			 redirect('datadeposit/projects');
	    }
	   
	   	if ($project[0]->access != 'owner') 
		{
    		redirect('datadeposit/projects');
		}
		
		if ($project[0]->status != 'draft') 
		{
			redirect('datadeposit/projects');
		}
		
		if ($this->input->post('submit') == 'Yes') 
		{
			$this->DD_project_model->delete($id);
			$this->_write_history_entry("Project deleted", $project[0]->id, $project[0]->status);
			redirect('datadeposit/projects');
		} 
		else if ($this->input->post('cancel') == 'No') 
		{
			 redirect('datadeposit/projects');
		}
		
		$data['id'] = $id;
		$content    = $this->load->view('datadeposit/confirm', $data, true);
		$this->template->write('title', 'Confirm delete', true);
		$this->template->write('content', $content, true);		
		$this->template->render();
	}
	

	private function _study_validate_date($date) {
		$date = DateTime::createFromFormat('Y-m-d', $date);
		$date = explode('-', $date);
		return @checkdate($date[1], $date[2], $date[0]);
	}
	

	/**
	*
	* Email project summary
	**/
	public function email_summary()
	{
		$id=(int)$this->input->post("pid");
		$email=$this->input->post("email");
	
		//$id=27;
		//$email='';
		
		if (!$this->DD_project_model->has_access($id, $this->session->userdata('email'))) {
			//redirect('datadeposit/projects');
			show_error("PERMISSIONS_DENIED");
		}
		
		$this->load->helper('email');
		$this->load->library('email');
		
		if (!valid_email($email))
		{
			show_error("INVALID_EMAIL_ADDRESS");
		}

		$project_title=$this->DD_project_model->get_title_by_id($id);
		$user_name=$this->session->userdata('username');

		//get formatted project summary
		$data['content']=$this->_get_formatted_project_summary($id);		
		$data['message']=sprintf(t('email_user_shared_project'),$user_name,$project_title);

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
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		$this->email->to($email);
		$this->email->subject($project_title);
		$this->email->message($contents);
		
		if (!@$this->email->send()) 
		{
			die ("EMAIL_FAILED");
			//echo $this->email->print_debugger();
		}
		else
		{
			die ("EMAIL_SENT");
		}	
	}
	
	
	
	//get project summary
	private function _get_formatted_project_summary($id)
	{		
		$this->load->model('DD_resource_model');
		$this->load->model('DD_study_model');
		$this->load->helper('admin_notifications');

		//get user info
		$user=$this->ion_auth->current_user();

		//get request data
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		$data['row']     = $this->DD_study_model->get_study($data['project'][0]->id);
		$data['files']   = $this->DD_resource_model->get_project_resources_to_array($id);
		$data['fields']  = $this->config->item('datadeposit');
		
		//get project owner
		$data['project'][0]->owner=$this->DD_project_model->get_owner($id);
            
       //get project collaborators
        $data['project'][0]->collaborators=$this->DD_project_model->get_collaborators($id);
		
        $this->_study_grid_ids         = array();
		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
			);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);

		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_summary_study_grid($grid_id, $grid_data, true);
		}	
		
		$content     = $this->load->view('datadeposit/project_review', $data, true);
		
		return $content;
	}
	
	

	function test_email($id)
	{
		//$this->_email(28);exit;
        $to='masghar@worldbank.org';
        $cc=$to;
        $bcc=$to;
        $subject='testing data deposit email';
        $message='test message here';
        $this->email_project($id, $to, $cc, $bcc, $subject,$message,$debug=TRUE);
	}

	
	//send project confirmation notification to the user and site admins
	private function _email($id, $cc=false, $to=false)
	{		
		//$this->output->enable_profiler(TRUE);
		$this->load->model('DD_resource_model');
		$this->load->model('DD_study_model');
		$this->load->helper('admin_notifications');
		//get user info
		$user=$this->ion_auth->current_user();
		//get request data
		$data['project'] = $this->DD_project_model->project_id($id, $this->session->userdata('email'));
		$data['row']     = $this->DD_study_model->get_study($data['project'][0]->id);
		$data['files']   = $this->DD_resource_model->get_project_resources_to_array($id);
		$data['fields']  = $this->config->item('datadeposit');
        $this->_study_grid_ids         = array();
		$grids                         = array();
		$grids['methods']              = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['topic_class']          = array(
			'titles' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['country']              = array(
			'titles' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prim_investigator']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['other_producers']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['funding']              = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['acknowledgements']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['dates_datacollection'] = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['time_periods']         = array(
			'titles' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['data_collectors']      = array(
			'titles' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts']             = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
			);
		$grids['impact_wb_lead']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'titles' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		// add our grids to the data variable with their html representation
		foreach ($grids as $grid_id => $grid_data) {
			$data[$grid_id] = $this->_study_grid($grid_id, $grid_data, true);
		}	
		
		//for the user
		$subject='[Confirmation - #'.$id.'] - '.$data['project'][0]->title;
		$project_url=site_url().'/datadeposit/summary/'.$id;		
		$message=sprintf(t('msg_project_submitted'),ucwords($this->session->userdata('username')), ucwords($data['project'][0]->title),$project_url);
		$project_summary=$this->load->view('datadeposit/summary_email', $data,true);
		$message.=$project_summary;

		$this->load->library('email');
		$this->email->clear();		
		$config['mailtype'] = 'html';
		$this->email->initialize($config);//intialize using the settings in mail
		$this->email->set_newline("\r\n");
		$this->email->from($this->config->item('website_webmaster_email'), $this->config->item('website_title'));
		if ($to !== false) {
			$this->email->to($to);
		} else {
			$this->email->to($user->email);
		}
		
		$collabs = implode(',', $this->DD_project_model->get_collaborators($id));
		if ($cc !== false) {
			$this->email->cc(implode(',', array_unique(explode(',', $cc . ',' . $collabs))));
		} else {
			$this->email->cc($collabs);
		}

		$this->email->subject($subject );
		$this->email->message($message);
		//echo $message;exit;
		
		if (@$this->email->send()) {
			if ($to === false) {
				//for the adminstrators
				$subject=sprintf(t('notice_project_submitted'),$data['project'][0]->title,$user->username);
				$project_url=site_url().'/datadeposit/summary/'.$id;		
				$message=sprintf(t('notify_admin_new_project_submitted'),$user->username,$data['project'][0]->title,site_url('admin/datadeposit/id/'.$id));
				$message.=$project_summary;

				//notify the site admin
				notify_admin($subject,$message,$notify_all_admins=false);
	    	}
			return true;
		} else {
			return false;
		}
	}

	private function _write_history_entry($comment, $project_id, $status='') {
		$data = array(
			'project_id'     => (int) $project_id,
			'user_identity'  => $this->session->userdata('email'),
			'created_on'     => date("U"),
			'project_status' => $status,
			'comments'       => $comment,
		);
		$this->DD_project_model->log_history($data);
	}
			
	private function _resources_to_RDF($resources) {
		$content = '';;
		$RDF     = "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
		$RDF    .= "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:dcterms=\"http://purl.org/dc/terms/\">" . PHP_EOL;
		foreach ($resources as $resource) {
			$content .= "<rdf:Description rdf:about=\"{$resource['filename']}\">\n<rdf:label>\n{$resource['title']}\n</rdf:label>\n<rdf:title>\n{$resource['title']}\n</rdf:title>\n<dc:format>\n{$resource['dcformat']}\n</dc:format>\n<dc:creator>\n{$resource['author']}\n</dc:creator>\n<dc:type>\n{$resource['dctype']}\n</dc:type>\n<dcterms:created>\n{$resource['created']}\n</dcterms:created>\n<dc:description>\n{$resource['filename']}\n</dc:description>\n</rdf:Description>\n";
		}
		$RDF    .= $content;
		$RDF    .= "</rdf:RDF>";
		return $RDF;
	}
	
	private function _grid_check_rules($input, $column, $rule) {
		$input = grid_data_encode($input, false);
		switch ($rule) {
			case 'uri':
				foreach ($input as $field) {
					if (!preg_match("/^((ht|f)tp(s?)\:\/\/|~/|/)?([w]{2}([\w\-]+\.)+([\w]{2,5}))(:[\d]{1,5})?/", $field[$column])) {
						return false;
					}
				}
			case 'date':
				foreach($input as $field) {
					if (!preg_match("/^(?:\d{4})\/\[1-12]\/\[1-31]$/", $field[$column])) {
						return false;
					}
				}
		}
		return true;
	}
	

	private function is_array_empty($value){
		if (is_array($value)) {
			foreach ($value as $vals) {
				if (!empty($vals)) return 1;
			}
			// is empty
			return 0;
		}	
	}

	private function _grid_data_encode($input, $json=true) {
		if (!is_array($input)) {
			return '';
		}
		$array = array();
		$x     = 0;
		// here we prepare the post data array back to our documented format
		foreach($input as $columns) {
			foreach($columns as $rows) {
				$array[$x++][] = current($rows);
			}
			$x = 0;
		}

		// if an array (row) has all empty elements, remove it; do this for the entire grid.
		$array = array_filter($array, array($this, 'is_array_empty'));
		return ($json) ? json_encode($array) : $array;
	}

	private function _grid_data_decode($data) {
		$data = ($data) ? json_decode($data) : null;
		if ($data === null)  return null;
		return $data;
	}

	
	/* Grid data array:
	 //                      col 1 header, html class
	 $data['titles'] = array('title 1' => 'class1', ...); 
	 $data['data']   = array(
	    // row 1   col 1, col 2, col 3
	 	0 => array($var1, $var2, $var3),
	    // row 2   col 1,  col 2, col 3
	 	1 => array($var1, $var2, $var3)
	  );
	*/	 
	private function _study_grid($id, array $data, $disabled = false) {
		// validate id
		/* for now */ if (!isset($data['data'])) $data['data'] = array();
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $id)) {
			throw new Exception("id `{$id}' is invalid");
		}
		// prevent duplicate id's
		if (in_array($id, $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s detected');
		} else {
			$this->_study_grid_ids[] = $id;
		}

		// each grid has a unique javascript 'counter' for field additions, along with an id
		$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof((array)$data['data']) . '; </script>' . PHP_EOL;
		$grid   .= '<div class="grid_three">' . PHP_EOL . '		<table class="grid left" id="' . $id . '" name="' . $id . '" width="100%">';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		/* Add the javascript event code */
		$new_cols   = $data['titles'];
		$javascript = '<tr>';
		$x          = 1;
		$count      = sizeof($data['titles']);
		foreach ($new_cols as $title) {
			if ($x === $count) {
				//use javascript to automate the increment counter for each row
				$index .= '++';
			}
			$is = ($disabled) ? 'disabled="disabled"' : null;
			$javascript .= '<td width="25%"><input ' . $is  .'  name="' . $id . '[' . $title . '][\'+'.$index.'+ \'][]" onkeypress="keyPressTest(event, this);" value="" type="text"></td>';
			$x++;
		}
		if ($disabled !== true) {
			$javascript .= '<td class="last"><div onclick="$(this).parent().parent().remove();" class="button-del">-</div></td></tr>';
		}
		// every grid is custom, so to each respectively thier own javascript 'add row' function
		$grid   .= '<script type="text/javascript"> function ' . $id . '_add() { $(\''. $javascript . '\').insertAfter($(\'#' . $id . ' tbody tr\').last()); }</script>' . PHP_EOL;
		if ($disabled !== true) {
			$grid   .= '<th onclick=\'' . $id . '_add();\' class="last"><div class="button-add overviewaddRow">+</div></th>';	
		}
		$grid   .= PHP_EOL;
	
		$grid   .= '</tr>' . PHP_EOL;
		/* load the data from the database into the grid, if any */
		$check = sizeof($data['titles']) && sizeof(current($data['data']));
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
		if (empty($data)) {
			// empty grid
			$grid .= '</tbody></table></div>' . PHP_EOL;
			return $grid;
		}
		// otherwise, present the data in tabular grid
		$titles = $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {	
				$is      = ($disabled) ? 'disabled="disabled"' : null;
				$grid   .= "<td width='25%'><input ". $is ." name='" . $id . "[" . array_shift($titles) . "][" . $y . "][]' onkeypress='keyPressTest(event, this);' value='".htmlspecialchars($cols, ENT_QUOTES)."' type='text'></td>";
				$grid   .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($disabled !== true) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}

	private function _summary_study_grid($id, array $data, $disabled = true) {
		// validate id
		/* for now */ if (!isset($data['data'])) $data['data'] = array();
		if (!preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $id)) {
			throw new Exception("id `{$id}' is invalid");
		}
		// prevent duplicate id's
		if (in_array($id, $this->_study_grid_ids)) {
			throw new Exception('duplicate grid id\'s detected');
		} else {
			$this->_study_grid_ids[] = $id;
		}
		// each grid has a unique javascript 'counter' for field additions, along with an id
		$grid    = '<script type="text/javascript">var index_' . $id . ' = ' . sizeof($data['data']) . '; </script>' . PHP_EOL;
		$grid   .= '<div class="">' . PHP_EOL . '		<table cellspacing="0" cellpadding="0" class="left" id="' . $id . '" name="' . $id . '" >';
		$grid   .= PHP_EOL . '<tbody><tr>' . PHP_EOL; 
		$index   = 'index_' . $id;
		
		foreach ($data['titles'] as $title => $class) {
			$grid  .= '<th cellspacing="0" cellpadding="0"  style="border: 1px solid gainsboro;" class="' . $class .'">' . $title . '</th>' . PHP_EOL;
		}
		
		$grid   .= PHP_EOL;
	
		$grid   .= '</tr>' . PHP_EOL;

		
		if(current($data['data'])){
		/* load the data from the database into the grid, if any */
		$check = sizeof($data['titles']) && sizeof(current($data['data']));
		if (!empty($data) && !$check) {
			throw new Exception("title columns and data columns do not match in length");
		}
	}
		if (empty($data['data'])) {
			// This is an empty grid, so allow for user to add data with 0 rows
			$grid .= '</tbody></table></div>' . PHP_EOL;
			// If $empty is true and there is no data, just return an empty string
			return '';
		}
		// otherwise, present the data in our tabular grid
		$titles = $data['titles'];
		$temp   = $titles;
		$y      = 0;
		foreach ($data['data'] as $rows) {
			$grid       .= '<tr>' . PHP_EOL;
			foreach ($rows as $cols) {	
				$is      = ($disabled) ? 'disabled="disabled"' : null;
				$grid   .= "<td cellspacing=\"0\" cellpadding=\"0\" style='border: 1px solid gainsboro;' width='10%'>{$cols}</td>";
				$grid   .= PHP_EOL;  
			}
			$titles = $temp;
			$y++;
			if ($disabled !== true) {
				$grid       .= '<td class="last"><div class="button-del">-</div></td>' . PHP_EOL;
			}
			$grid       .= '</tr>' . PHP_EOL;
		}
	$grid .= '</tbody></table></div>' . PHP_EOL;
	return $grid;
	}
	
	
	function print_grid($row=NULL)
	{

		$row= $this->DD_study_model->get_study(28);

		$grids=array();
		
		$grids['overview_methods'] = array(
			'columns' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			'title' => 'overview'
			//$this->_grid_data_decode((isset($data['row'][0]->overview_methods) ? $data['row'][0]->overview_methods : null))
		);
		$grids['scope_class']     = array(
			'columns' => array (
				'Text'           => 'text', 
				'Vocabulary'     => 'vocab',
				'Vocabulary URI' => 'uri'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->scope_class) ? $data['row'][0]->scope_class : null))
		);
		$grids['coverage_country']              = array(
			'columns' => array (
				'Name'           => 'name', 
				'Abbreviation'   => 'abbr'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->coverage_country) ? $data['row'][0]->coverage_country : null))
		);
		$grids['prod_s_investigator']    = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_investigator) ? $data['row'][0]->prod_s_investigator : null))
		);
		$grids['prod_s_other_prod']      = array(
			'columns' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_other_prod) ? $data['row'][0]->prod_s_other_prod : null))
		);
		$grids['prod_s_funding']              = array(
			'columns' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_funding) ? $data['row'][0]->prod_s_funding : null))
		);
		$grids['prod_s_acknowledgements']     = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Role'         => 'role'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->prod_s_acknowledgements) ? $data['row'][0]->prod_s_acknowledgements : null))
		);
		$grids['coll_dates'] = array(
			'columns' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_dates) ? $data['row'][0]->coll_dates : null))
		);
		$grids['coll_periods']         = array(
			'columns' => array (
				'Start'     => 'start', 
				'End'       => 'end',
				'Cycle'     => 'cycle'
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_periods) ? $data['row'][0]->coll_periods : null))
		);
		$grids['coll_collectors']      = array(
			'columns' => array (
				'Name'         => 'name', 
				'Abbreviation' => 'abbr',
				'Affiliation'  => 'affiliation',
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->coll_collectors) ? $data['row'][0]->coll_collectors : null))
		);
		$grids['access_authority']     = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->access_authority) ? $data['row'][0]->access_authority : null))
		);
		$grids['contacts_contacts']             = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->contacts_contacts) ? $data['row'][0]->contacts_contacts : null))
		);
		$grids['impact_wb_lead']    = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_lead) ? $data['row'][0]->impact_wb_lead : null))
		);
		$grids['impact_wb_members']    = array(
			'columns' => array (
				'Name'         => 'name', 
				'Affiliation'  => 'affiliation',
				'Email'        => 'email',
				'URI'          => 'uri',
			),
			//'data'  => $this->_grid_data_decode((isset($data['row'][0]->impact_wb_members) ? $data['row'][0]->impact_wb_members : null))
		);
		
		foreach($row[0] as $field_name=>$value)
		{
			if (array_key_exists($field_name,$grids))
			{
				echo $field_name;
				echo '<BR>';
				$this->print_single_grid($grids[$field_name]['columns'],$value);			
			}
		
		}
		
	}//end-print grid
	
	private function print_single_grid($columns,$data)
	{
		if (!$data)
		{
			return;
		}
	
		echo '<table border="1">';
		echo '<tr>';
		foreach($columns as $column)
		{
			echo '<td>'.$column.'</td>';
		}
		echo '</tr>';
		
		$data=json_decode($data);
		
		foreach($data as $row)
		{
			echo '<tr>';
			foreach($row as $value)
			{
				echo '<td>'.$value.'</td>';
			}
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	
	
	/**
	*
	* Email project summary
     *
     * @debug=true - print the email message on the screen
	**/
	private function email_project($id, $to, $cc, $bcc, $subject,$message,$debug=FALSE)
	{
		$this->load->helper('email');
		$this->load->library('email');
		
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

        if ($debug==TRUE)
        {
            echo $contents;
        }
		
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
			return false;
		}
		else
		{
			return true;
		}	
	}
}
