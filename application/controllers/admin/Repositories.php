<?php
class Repositories extends MY_Controller {

	var $errors='';
	var $search_fields=array('username','email','status'); 
	var $uploaded_thumbnail_path='';
	
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );
       	$this->load->model('repository_model');
		
		//collection settings
		$this->config->load('collections', TRUE);		
		//var_dump($this->config->item('collection_image_path', 'collections'));
		
		//language file
		$this->lang->load('collection');
		
		//set default template
		$this->template->set_template('admin');
		
		//$this->output->enable_profiler(TRUE);
	}
	
	
	
	//list repositories
	function index()
	{					
		$result['rows']=$this->_search();
		$content=$this->load->view('repositories/index-default', $result,true);	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('repositories_management'),true);
	  	$this->template->render();	
	}
	
	
	/**
	 * Search - internal method, supports pagination, sorting
	 *
	 **/
	function _search()
	{
		//records to show per page
		$per_page = 100;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'repositoryid';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->repository_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->repository_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->repository_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/repositories');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	
	/**
	* Add new repo
	*
	*/
	function add()
	{
		$this->edit();
	}
		
	//process thumbnail uploads
	private function process_file_uploads($file_name)
	{
		$config['upload_path'] = $this->config->item('collection_image_path', 'collections');
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size']	= '300';
		//$config['max_width']  = '300';
		//$config['max_height']  = '300';

		$this->load->library('upload', $config);
		
		$output=array();		
		if ( ! $this->upload->do_upload($file_name)){
			$error = array('error' => $this->upload->display_errors());
			$output=array(
				'status'=>'error',
				'data'=>$error,
				'upload_path'=>$config['upload_path']
			);
		}
		else{
			$data = array('upload_data' => $this->upload->data());
			$output=array(
				'status'	=>'success',
				'data'		=>$data,
				'file_name'	=>$config['upload_path'].'/'.$data['upload_data']['file_name']
			);
		}
		return $output;
	}	
		
	/**
	 * 
	 * 
	 * Callback for collection thumbnail uploads
	 * 
	 */
	function _thumbnail_upload()
	{
		if(!empty($_FILES['thumbnail_file']['name'])) {
			$thumbnail_storage=$this->config->item('collection_image_path', 'collections');

			if(!file_exists($thumbnail_storage)){
				$error=t('thumbnail_upload_folder_not_set').': '.$thumbnail_storage;
				$this->form_validation->set_message('_thumbnail_upload',$error);
				return false;
			}
			
			$fileupload_output=$this->process_file_uploads('thumbnail_file');
			
			if($fileupload_output['status']=='success'){
				$this->uploaded_thumbnail_path=$fileupload_output['file_name'];
			}
			else{
				$error=t('thumbnail_upload_failed').': '. $fileupload_output['data']['error'];
				$this->form_validation->set_message('_thumbnail_upload', $error);
				return false;
			}
		}		
		return true;
	}


	/**
	* Edit repo
	*
	* handles both add or edit
	*/
	function edit($id=NULL)	
	{
        $this->load->helper('security');

        if (!is_numeric($id)  && $id!==NULL){
			show_error('Invalid ID provided');exit;		
		}

		//set validation rules
		$this->form_validation->set_rules('repositoryid', t('repositoryid'), 'xss_clean|trim|required|max_length[255]|callback__repository_identity_check|callback__repository_id_format_check|alpha_dash');
		$this->form_validation->set_rules('title', t('title'), 'xss_clean|trim|required|max_length[255]');
		$this->form_validation->set_rules('short_text', t('short_text'), 'xss_clean|trim|required');
		$this->form_validation->set_rules('long_text', t('long_text'), 'trim');
		$this->form_validation->set_rules('weight', t('weight'), 'xss_clean|trim|max_length[5]|is_natural');
		$this->form_validation->set_rules('thumbnail', t('thumbnail'), 'xss_clean|trim|required');
		$this->form_validation->set_rules('section', t('section'), 'xss_clean|trim|max_length[3]|is_natural');
		$this->form_validation->set_rules('published', t('published'), 'xss_clean|trim|max_length[1]|is_natural');
		$this->form_validation->set_rules('thumbnailfile', 'thumbnail_upload', 'callback__thumbnail_upload');
		
		if (is_numeric($id)){
			$this->page_title=t('edit_repository');
		}
		else{
			$this->page_title=t('create_repository');		
		}	

		//initialize with defaults
		$this->row_data=array(
						'repositoryid'=>'',
						'title'=>'',
						'url'=>'',
						'organization'=>'',
						'country'=>'',
						'type'=>0,
						'short_text'=>'',
						'thumbnail'=>$this->config->item('collection_default_thumb', 'collections'),
						'long_text'=>'',
						'weight'=>0,
						'ispublished'=>0,
						'section'=>0,//default
						'group_da_public'=>0,
						'group_da_licensed'=>0
						);
												
		//process form
		if ($this->form_validation->run() == TRUE){
            $options=array(
				'group_da_public'=>0,
				'group_da_licensed'=>0
			);
			$post_arr=$_POST;
							
			//read post values to pass to db
			foreach($post_arr as $key=>$value){
				$options[$key]=$this->input->post($key);
			}

			//sanitize description html
			$options['long_text']=$this->sanitize_html_input($options['long_text']);

			//process thumbnail file uploads
			if(!empty($_FILES['thumbnail_file']['name']) && !empty($this->uploaded_thumbnail_path) ){
					$options['thumbnail']=$this->uploaded_thumbnail_path;
			}
								
			if ($id==NULL){
				$db_result=$this->repository_model->insert($options);
			}
			else{
				//update db
				$db_result=$this->repository_model->update($id,$options);
			}
							
			if ($db_result===TRUE){
				/*if (isset($options['ispublished']) && is_numeric($id)){
					//update collection studies status
					$this->publish($id,$options['ispublished']);
				}*/
			
				//update successful
				$this->session->set_flashdata('message', t('form_update_success'));
				redirect("admin/repositories", "refresh");				
			}
			else{
				//update failed
				$this->form_validation->set_error(t('form_update_fail'));
			}
		}
		else //first time page is loaded or validation failed
		{
			if ($id!=NULL){
				$row=$this->repository_model->select_single($id);				
				
				if(!$row){
					show_error('ID was not found');
				} 	
				
				$this->row_data=$row;
				
				//validate and clean up thumbnails
				$default_thumb=$this->config->item('collection_default_thumb', 'collections');					
				$thumb_ext=explode(".",basename($this->row_data['thumbnail']));
				
				$thumb_ext=$thumb_ext[count($thumb_ext)-1];
				
				if (!in_array($thumb_ext,array('png','gif','jpg'))){
					$this->row_data['thumbnail']=$default_thumb;
				}
			}
		}

		//textboxes
		$fields=array('repositoryid','title','url','organization','country','thumbnail','weight');
		
		foreach($fields as $field){
			$this->data[$field]= array(
				'name'	=> $field,
				'id'    => $field,
				'type'  => 'text',
				'class' => 'form-control',
				'value' => $this->form_validation->set_value($field,$this->row_data[$field])
			);
		}
		
		$this->data['type']=$this->form_validation->set_value('type',$this->row_data['type']);
		$this->data['short_text']=$this->form_validation->set_value('short_text',$this->row_data['short_text']);
		$this->data['long_text']=$this->form_validation->set_value('long_text',$this->row_data['long_text']);
		$this->data['ispublished']=$this->form_validation->set_value('ispublished',$this->row_data['ispublished']);
		$this->data['section_options']=$this->Repository_model->get_repository_sections();
		//$this->data['group_da_public']=$this->form_validation->set_value('group_da_public',$this->row_data['group_da_public']);
		//$this->data['group_da_licensed']=$this->form_validation->set_value('group_da_licensed',$this->row_data['group_da_licensed']);
		$this->data['section']=$this->form_validation->set_value('section',$this->row_data['section']);
		
		$content=$this->load->view('repositories/edit',NULL,true);									
		$this->template->write('content', $content,true);
	  	$this->template->render();								
	}



    /**
     *
     * Validation HTML
     */
    function sanitize_html_input($html)
    {
        $this->load->helper('kses');
        $string=$html;//'<span><p id="<script>alert(1);</script>">this is a test<div>!</div>';
        $allowed_tags = array('b' => array(),
            'h1'    => array("class"=>array()),
            'h2'    => array("class"=>array()),
            'h3'    => array("class"=>array()),
            'i'    => array("class"=>array()),
            'div'  => array("class"=>array()),
            'span' => array("class"=>array()),
            'a'    => array('href'=> array(),
                'title' => array('valueless' => 'n')),
            'p' => array('class' => array(),
                'id'=>array()),
            'img' => array('src' => array()),
            'font' => array('size' =>
                array('minval' => 4, 'maxval' => 20)),
            'br' => array()
        );

        //don't throw php warnings for non well formed html
        $libxml_error_setting= libxml_use_internal_errors(true);

        //fix the missing closing tags
        $doc = new DOMDocument();
        $doc->loadHTML($string);
        $html=$doc->saveHTML();

        //reset to whatever it was set before
        libxml_use_internal_errors($libxml_error_setting);

        return wp_kses($html, $allowed_tags);
        /*die();
        $doc = new DOMDocument();
        $doc->loadHTML($string);

        $doc->removeChild($doc->firstChild);
        echo $doc->saveXML();

           echo str_replace("<body>","",$doc->saveHTML());
        */

    }
	
	/**
	* check repositoryID
	*
	*/
	function _repository_identity_check($repositoryid)
	{
		$id=$this->uri->segment(4);

		if (!is_numeric($id) )
		{
			$id=NULL;
		}
		
		$exists=$this->Repository_model->repository_exists($repositoryid,$id);
				
		if ($exists >0)
		{
			$this->form_validation->set_message('_repository_identity_check', t('callback_error_repositoryid_exists'));
			return FALSE;
		}
			return TRUE;
	}


    /**
     *
     * The repository ID call back to validate ID is not numeric
     */
    function _repository_id_format_check($repositoryid)
    {
        $id=$this->uri->segment(4);

        if (!is_numeric($id) )
        {
            $id=NULL;
        }

        if (is_numeric($repositoryid))
        {
            $this->form_validation->set_message('_repository_id_format_check', t('callback_error_repositoryid_is_numeric'));
            return FALSE;
        }
        return TRUE;
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
				redirect('admin/repositories',"refresh");
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
				redirect('admin/repositories');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->repository_model->delete($item);
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
				redirect('admin/repositories');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('repositories/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	

	
	/**
	*
	* Manage users for a repository
	*
	* @id= repositoryid
	**/
	function users($id=NULL)
	{
			show_error("feature no longer supported");
/*		if (!is_numeric($id))
		{
			show_error("INVALID_ID");
		}
*/
		//get all repos
		$repos=$this->repository_model->get_repositories();
		
		//get repository info from db
		$repo=$this->repository_model->select_single($id);
		
		if (!$repo)
		{
			//show_error("NOT-FOUND");
			$repo=current($repos);
		}
		
		//get a list of all catalog-admins
		$users=$this->ion_auth_model->get_admin_users('catalog-admin');
		//$users=$this->repository_model->get_catalog_admins($id);
				
		//get user for the current repository
		$repo_users=$this->repository_model->get_repository_admins($repo['id']);

		$repo_user_list=array();
		foreach($repo_users as $row)
		{
			$repo_user_list[$row['userid']]=$row['roleid'];
		}

		$data['repo']=$repo;
		$data['users']=$users;
		$data['repos']=$repos;
		$data['repo_admins']=$repo_user_list;
		
		$this->page_title=t('manage_repository_users');
	
		$content= $this->load->view('repositories/manage_users',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	
	
	function assign_role($repositoryid,$userid,$roleid)
	{
		$this->repository_model->assign_role($repositoryid,$userid,$roleid);
		echo json_encode(array('success'=>"role updated"));
	}

	/**
	* select active repository for the current session
	**/
	function select()
	{
		$this->lang->load('collection');
		$this->page_title=t('select_active_repository');
		
		//get array of repos user has access to
		$data['repos']=$this->acl->get_user_repositories();
		$content=$this->load->view('repositories/select_active_repo',$data,TRUE);
		
		$this->template->write('content', $content,true);
		$this->template->render();
	}

	/**
	*
	*Set active repository for the user session
	**/
	function active($repositoryid=NULL)
	{
		if (!is_numeric($repositoryid))
		{
			show_error("INVALID_ID");
		}
		
		$result=$this->acl->set_active_repo($repositoryid);
		
		if ($result)
		{
			if ($this->input->get('destination'))
			{
				redirect($this->input->get('destination',true));return;
			}
		
			redirect("admin/catalog");
		}
		
		show_error("CANT_SET_ACTIVE_REPO");
	}
	
	/**
	* Clear active repo
	**/
	function reset_repo()
	{
		$this->acl->clear_active_repo();
	}
	
	
	/**
	*
	* Manage group permissions for the repository
	**/
	function permissions($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_error("INVALID_ID");
		}
		
		$data['repo']=$this->Repository_model->select_single($id);
		
		if (!$data['repo'])
		{
			show_error("INVALID-ID");
		}
		
		if ($this->input->post("group_id"))
		{
			$this->Permissions_model->update_repo_permissions($id,$this->input->post("group_id"));			
			$this->session->set_flashdata('message', t('form_update_success'));			
		}

		
		//get all user groups
		$data['user_groups']=$this->ion_auth_model->get_user_groups();
		
		//get all users accounts of type ADMIN with LIMITED access
		$data['limited_users']=$this->ion_auth_model->get_limited_admins();
		
		//echo '<pre>';
		//var_dump($data['limited_users']);exit;
		
		foreach($data['limited_users'] as $key=>$user)
		{
			//get user permissions for the current repository
			$data['limited_users'][$key]['permissions']=$this->ion_auth_model->get_user_perms_by_repo($id,$user['id']);
		}
		
		$data['users_by_repo']=$this->ion_auth_model->get_repo_users($id);
		
		foreach($data['users_by_repo'] as $key=>$user)
		{
			//get user permissions for the current repository
			$data['users_by_repo'][$key]['permissions']=$this->ion_auth_model->get_user_perms_by_repo($id,$user['user_id']);
		}
		
		
		//get existing group permissions assigned to the current repository
		$repo_user_groups=array();//$this->ion_auth_model->get_user_groups_by_repo($id);
		
		if(!$repo_user_groups)
		{
			//no user groups assigned to repo
			$data['repo_user_groups']=array();
		}
		else
		{		
			foreach($repo_user_groups as $group)
			{
				$data['repo_user_groups'][]=$group['group_id'];
			}
		}
				
		$content=$this->load->view('repositories/permissions',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}
	
	//publish/unpublish repository
	function publish($id,$status)
	{
		if(!is_numeric($id) || !in_array($status,array(0,1)))
		{
			show_error('INVALID-PARAMS');
		}
		
		$options=array(
			'ispublished'=>$status
		);
		
		$this->Repository_model->update($id,$options);

        //publish/unpublish studies in the collection based on the publish status of the collection
		//$this->Repository_model->update_repo_studies_status($id,$status);
	}
	
	

	//change repo weight
	function weight($id,$weight)
	{
		if(!is_numeric($id) || !is_numeric($weight))
		{
			show_error('INVALID-PARAMS');
		}
		
		$options=array(
			'weight'=>$weight
		);
		
		$this->Repository_model->update($id,$options);
	}
	
	/**
	*
	*Show collection history
	**/
	function history($repositoryid)
	{
		$data['rows']=$this->repository_model->repo_survey_list($repositoryid);		
		$content=$this->load->view('repositories/history', $data,true);	
		$this->template->write('content', $content,true);
		$this->template->write('title', t('collection_history'),true);
	  	$this->template->render();
	}
	
	
}

/* End of file repositories.php */
/* Location: ./system/application/controllers/repositories.php */