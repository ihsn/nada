<?php
class Users extends MY_Controller {

	var $errors='';
	var $search_fields=array('username','email','status');
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('form', 'url'));		
		$this->load->library( array('form_validation','pagination') );		
       	$this->load->model('User_model');
		
		//language files
		$this->lang->load('general');
		$this->lang->load('users');
		
		//set template to admin
		$this->template->set_template('admin');
		
		//$this->output->enable_profiler(TRUE);
		$this->disable_page_cache();
	}
	
	//expire page immediately
    private function disable_page_cache()
    {	
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
    }
	
	function index()
	{			
		//get array of db rows		
		$result['rows']=$this->_search();
		
		$user_id_arr=array();
		foreach($result['rows'] as $row)
		{
			$user_id_arr[]=$row['id'];
		}
				
		//get user groups 
		$result['user_groups']=$this->User_model->get_user_groups($user_id_arr);
		
		//load the contents of the page into a variable
		$content=$this->load->view('users/index', $result,true);

		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//set page title
		$this->template->write('title', t('title_user_management'),true);

		//render final output
	  	$this->template->render();	
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
		$per_page = 15;
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'asc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'title';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") )
		{
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		if ($this->input->get('user_group')) {
			$rows=$this->User_model->get_users_by_group((int)$this->input->get('user_group'), $per_page, $offset,$filter, $sort_by, $sort_order);

			$total = $this->User_model->search_count();
		} else {
			//records
			$rows=$this->User_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

			//total records in the db
			$total = $this->User_model->search_count();

			if ($offset>$total)
			{
				$offset=$total-$per_page;
			
				//search again
				$rows=$this->User_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
			}
		}
		
		//set pagination options
		$base_url = site_url('admin/users');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','sort_by','sort_order'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';

		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}
	
	function add() 
	{  
        $this->data['page_title'] = t("create_user_account");
              		
        //validate form input
		$this->form_validation->set_rules('username', t('username'), 'xss_clean|max_length[20]|required|callback_username_exists');
    	$this->form_validation->set_rules('email', t('email'), 'max_length[100]|required|valid_email|callback_email_exists');
    	$this->form_validation->set_rules('first_name', t('first_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('phone1', t('phone'), 'max_length[20]|xss_clean|trim');
    	$this->form_validation->set_rules('company', t('company'), 'max_length[255]|xss_clean');
    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]');
    	$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');

		//phone is required for administrators
		/*
		if ($this->input->post("group_id")==1)
		{
	    	$this->form_validation->set_rules('phone1', t('phone'), 'xss_clean|trim|required|max_length[20]');
		}
		*/

        if ($this->form_validation->run() == true) 
		{ 
			//check to see if we are creating the user
			$username  = strtolower($this->input->post('username'));
        	$email     = $this->input->post('email');
        	$password  = $this->input->post('password');
        	
        	$additional_data = array('first_name' => $this->input->post('first_name'),
        							 'last_name'  => $this->input->post('last_name'),
        							 'company'    => $this->input->post('company'),
        							 'phone'      => $this->input->post('phone1'),// .'-'. $this->input->post('phone2') .'-'. $this->input->post('phone3'),
									 'active'     => $this->input->post('active'),
									 'country'     => $this->input->post('country'),
        							'active'     => $this->input->post('active'),
									//'group_id'     => $this->input->post('group_id'),
        							);
        	
        	//register the user
			$user_created=$this->ion_auth_model->register($username, $password, $email, $additional_data);
			
        	if ($user_created)
        	{
				$data['username']=$username;
        		$data['active']=$additional_data['active'];
				//$data['group_id']=$additional_data['group_id'];	
				
        		//get the user data by email
        		$user=$this->ion_auth->get_user_by_email($email);

				//update user group to ADMIN and ACTIVATE account
        		$this->ion_auth->update_user($user->id, $data);	        	
        	}  
        	
        	//redirect them back to the admin page
        	$this->session->set_flashdata('message', t("form_update_success") );
       		redirect("admin/users", 'refresh');
		} 
		else 
		{ 
			//display the create user form
	        
			//set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$this->data['first_name']          = array('name'   => 'first_name',
		                                              'id'      => 'first_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('first_name'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['last_name']           = array('name'   => 'last_name',
		                                              'id'      => 'last_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('last_name'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['email']              = array('name'    => 'email',
		                                              'id'      => 'email',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('email'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['username']           = array('name'    => 'username',
		                                              'id'      => 'username',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('username'),
													  'class'	=> 'form-control'
		                                             );

            $this->data['company']            = array('name'    => 'company',
		                                              'id'      => 'company',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('company'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['phone1']             = array('name'    => 'phone1',
		                                              'id'      => 'phone1',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('phone1'),
													  'class'	=> 'form-control'
		                                             );
		    $this->data['password']           = array('name'    => 'password',
		                                              'id'      => 'password',
		                                              'type'    => 'password',
		                                              'value'   => $this->form_validation->set_value('password'),
													  'class'	=> 'form-control'
		                                             );
            $this->data['password_confirm']   = array('name'    => 'password_confirm',
                                                      'id'      => 'password_confirm',
                                                      'type'    => 'password',
                                                      'value'   => $this->form_validation->set_value('password_confirm'),
													  'class'	=> 'form-control'
                                                     );
            $this->data['active']=$this->form_validation->set_value('active',1);
			$this->data['groups']=array();
			
			//load user group selection from POST
			if ($this->input->post('group_id'))
			{
				$this->data['groups']=$this->input->post('group_id');
			}
			
            $content=$this->load->view('users/create', $this->data,TRUE);
			
			//pass data to the site's template
			$this->template->write('content', $content,true);
			
			//set page title
			$this->template->write('title', $this->data['page_title'],true);
	
			//render final output
			$this->template->render();	

		}
    }	
	
	function edit($id) 
	{  		
        $this->data['page_title'] = t("edit_user_account");	
		$use_complex_password=$this->config->item("require_complex_password");
	              		
        //validate form input
		$this->form_validation->set_rules('username', t('username'), 'trim|required|callback_username_exists');
    	$this->form_validation->set_rules('email', t('email'), 'max_length[100]|required|valid_email|callback_email_exists');		
    	$this->form_validation->set_rules('first_name', t('first_name'), 'trim|required|xss_clean');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'trim|required|xss_clean');
    	$this->form_validation->set_rules('phone1', t('phone'), 'trim|xss_clean');
    	$this->form_validation->set_rules('company', t('company_name'), 'trim|xss_clean');

		if ($this->input->post("password") || $this->input->post("password_confirm") )
		{
	    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]|is_complex_password['.$use_complex_password.']');
    		$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');
		}
				
        if ($this->form_validation->run() == true) 
		{ 
        	$data = array(
						'username' => $this->input->post('username'),
						'email' 	=> $this->input->post('email'),
						'first_name' => $this->input->post('first_name'),
						'last_name'  => $this->input->post('last_name'),
						'company'    => $this->input->post('company'),
						'phone'      => $this->input->post('phone1'),
						'active'     => $this->input->post('active'),
						//'group_id'     => $this->input->post('group_id'),
						'country'     => $this->input->post('country'),
        				);
						
			//change password, if not empty
			if ($this->input->post("password") )
			{
					$data['password']=$this->input->post('password');
			}
			
        	//update user 
        	$this->ion_auth->update_user($id,$data);
        	
        	//redirect them back to the admin page
        	$this->session->set_flashdata('message', "User updated");
       		redirect("admin/users", 'refresh');
		} 
		else 
		{ 
			//displaying the form for the first time
	        
			//get user id
			$db_data=$this->ion_auth->get_user($id);
			
			if (!$db_data)
			{
				show_404();
			}
			
			//load data from post-back. need this for loading user group selection, 
			//other values are populated on postback
			if($this->input->post('id'))
			{
				$db_data->groups=$this->input->post('group_id');
			}
			
			//set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$this->data['id']          			= array('name'   => 'id',
		                                              'id'      => 'id',
		                                              'type'    => 'hidden',
		                                              'value'   => $this->form_validation->set_value('id',$id),
		                                             );

			$this->data['first_name']          = array('name'   => 'first_name',
		                                              'id'      => 'first_name',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('first_name',$db_data->first_name),
													  'class'	=> 'form-control'
		                                             );
            $this->data['last_name']           = array('name'   => 'last_name',
		                                              'id'      => 'last_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('last_name',$db_data->last_name),
													  'class'	=> 'form-control'
													);
            $this->data['email']              = array('name'    => 'email',
		                                              'id'      => 'email',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('email',$db_data->email),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['username']           = array('name'    => 'username',
		                                              'id'      => 'username',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('username',$db_data->username),
													  'class'	=> 'form-control'													  
		                                             );

            $this->data['company']            = array('name'    => 'company',
		                                              'id'      => 'company',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('company',$db_data->company),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['phone1']             = array('name'    => 'phone1',
		                                              'id'      => 'phone1',
		                                              'type'    => 'text',
													  'value'   => $this->form_validation->set_value('phone1',$db_data->phone),
													  'class'	=> 'form-control'													  
		                                             );
		    $this->data['password']           = array('name'    => 'password',
		                                              'id'      => 'password',
		                                              'type'    => 'password',
													  'value'   => $this->form_validation->set_value('password'),
													  'class'	=> 'form-control'													  
		                                             );
            $this->data['password_confirm']   = array('name'    => 'password_confirm',
                                                      'id'      => 'password_confirm',
                                                      'type'    => 'password',
													  'value'   => $this->form_validation->set_value('password_confirm'),
													  'class'	=> 'form-control'													  
                                                     );
			$this->data['country']=$db_data->country;										 
            //$this->data['group_id']	=$this->form_validation->set_value('group_id',$db_data->group_id);
            $this->data['active']	=$this->form_validation->set_value('active',$db_data->active);
			$this->data['groups']=$db_data->groups;
													 
            $content=$this->load->view('users/edit', $this->data,TRUE);
			
			//pass data to the site's template
			$this->template->write('content', $content,true);
			
			//set page title
			$this->template->write('title', $this->data['page_title'],true);
	
			//render final output
			$this->template->render();	

		}
    }	
	
	//check if the email address exists in db
	function email_exists($email)
	{
		$user_data=$this->ion_auth->get_user_by_email($email);

		if (!$user_data)
		{
			RETURN TRUE;
		}

		//check if editing user, exclude the current user
		$userid=$this->input->post("id");
		
		if ($userid==$user_data->id)
		{
			return TRUE;
		}

		if ($user_data)
		{
			$this->form_validation->set_message('email_exists', t('callback_email_exists') );
			return FALSE;
		}
		return TRUE;
	}
	
	//check if the username exists in db
	function username_exists($username)
	{
		$user_data=$this->ion_auth->get_user_by_username($username);
		
		if (!$user_data)
		{
			RETURN TRUE;
		}

		//check if editing user, exclude the current user
		$userid=$this->input->post("id");
		
		if ($userid==$user_data->id)
		{
			return TRUE;
		}
		
		if ($user_data)
		{
			$this->form_validation->set_message('username_exists', t('callback_username_exists') );
			return FALSE;
		}
		return TRUE;
	}
		
	

	function _save_user($id=-1)
	{

		$this->session->set_flashdata('message', '<div class="success"><i>'.'user '.'</i> updated</div>' );
		redirect('admin/users');
		exit;

		$u= new User;
		
		if ($id>-1)
		{
			//edit user
			$u->id=$id;
		}

		//populate with post data
		$u->username =$this->input->post('username');
		$u->email = $this->input->post('email');
		
		//skip validation if editing and passwords are blank
		if ($id!=-1 && $this->input->post('password') =="" && $this->input->post('passconf')=="")
		
		//editing an existing user
		if ($id>-1)
		{
			if ($this->input->post('password')=="" && $this->input->post('passconf')=="")
			{
				//skip validation
			}
			else
			{
				$u->password = $this->input->post('password');
				$u->confirm_password = $this->input->post('passconf');
			}
		}
		//add a new record
		else if ($id==-1)
		{
			$u->password = $this->input->post('password');
			$u->confirm_password = $this->input->post('passconf');			
		}

		$u->title=$this->input->post('title');
		$u->fname=$this->input->post('fname');
		$u->lname=$this->input->post('lname');
		$u->organization=$this->input->post('organization');
		$u->address=$this->input->post('address');
		$u->country=$this->input->post('country');
		$u->telephone=$this->input->post('telephone');
		$u->fax=$this->input->post('fax');
		$u->status=$this->input->post('status');
		$u->role=$this->input->post('role');
		
		$u->validate();
		
		if ($u->valid)
		{
			// Validation Passed
			echo 'validation passed';
		}
		else
		{
			// Validation Failed
			$this->errors='<div class="error-box">'.$u->error->string.'</div>';
			return false;
		}	

		// Begin transaction
		$u->trans_begin();
			
		// Attempt to save user
		$u->save();

		// Check status of transaction
		if ($u->trans_status() === FALSE)
		{
			// Transaction failed, rollback
			$u->trans_rollback();
			
			$this->errors='<div class="error-box">'.$u->error->string.'</div>';
			return false;
		}
		else
		{
			// Transaction successful, commit
			$u->trans_commit();
			$this->session->set_flashdata('message', '<div class="success-box"><i>'.$u->username.'</i> updated</div>' );
			redirect('admin/users');
		}
			
		// Show all errors
		//echo $u->error->string;

		//success
		/*$success_msg['message']='Form updated successfully.';
		$this->session->set_flashdata('message', 'Form updated successfully-session flash.');
		$content=$this->load->view('success',$success_msg,true);*/

	}//end-function
	
	

	//validation for add/edit user	
	function _edit_validation($is_editing=FALSE)
	{	
		$this->form_validation->set_error_delimiters('<li>', '</li>');
		$this->form_validation->set_rules('username', t('username'), 'trim|required|min_length[5]|max_length[20]|alpha_numeric');
		
		//skip validation
		if ($is_editing==TRUE && !isset($_POST['password']) )
		{
			$this->form_validation->set_rules('password', 'Password', 'required|matches[passconf]|md5');
			$this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');
		}
		
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
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
				redirect('admin/users',"refresh");
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
				redirect('admin/users');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//confirm delete	
				$this->User_model->delete($item);
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
				redirect('admin/users');
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
	
	/**
	*
	* Batch import users using CSV
	*
	**/
	function batch_import()
	{	
		if ($this->input->post("csv"))
		{
			$this->_do_batch_import($this->input->post("csv"));
		}
		
		$content=$this->load->view("users/batch_import",NULL,TRUE);		
		$this->template->write('content', $content,true);
		$this->template->render();		
	}
	
	function _do_batch_import($csv_data,$seperator=',')
	{
		$this->load->library('csvreader');		
		$this->csvreader->separator = $seperator;
		$users_arr=$this->csvreader->parse_string($csv_data, $p_NamedFields = true);
		
		if (count($users_arr)>0)
		{
			foreach($users_arr as $user)
			{
				//log
				$this->db_logger->write_log('user-batch-import',$user['email']);
	
				//check to see if we are creating the user
				$username  = strtolower($user['firstname']).' '.strtolower($user['lastname']);
				$email     = $user['email'];
				$password  = $user['password'];
				
				$additional_data = array('first_name' => $user['firstname'],
										 'last_name'  => $user['lastname'],
										 'company'    => 'N/A',
										 'phone'      => '0000',
										 'country'      => $user['country'],
										 'email'		=>	$email,
										 'identity'		=>$username
										);
				
				//register the user
				$result=$this->ion_auth->register($username,$password,$email,$additional_data);

				if ($result)
				{
					echo '<BR>user account created successfully for: '.$email;
				}
				else
				{
					echo '<BR>failed: '.$email;
				}
			}
			exit;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	*
	*Impersonate as other users
	*/
	function impersonate()
	{
		//get admin accounts with limited access
		$data['users']=$this->ion_auth_model->get_limited_admins();		
		
		if($this->input->post("user"))
		{
			$this->ion_auth_model->impersonate((int)$this->input->post("user"),$this->acl->current_user());
			redirect('admin');return;
		}
		
		$content=$this->load->view('users/impersonate',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();	
	}
	
	function exit_impersonate()
	{
		$this->ion_auth_model->exit_impersonate();
		redirect("admin");	
	}
	
	
	function permissions($user_id)
	{
		if(!is_numeric($user_id))
		{
			show_404();
		}
		
		$data=array();
		
		if ($this->input->post("submit"))
		{
			//update user global roles
			$this->ion_auth_model->update_user_global_roles($user_id,$this->input->post("global_role"));
			
			//update user per collection roles	
			$collection_roles=$this->input->post("collection_role");
			
			//remove all existing user roles for all collections
			$this->ion_auth_model->delete_user_collection_roles_all($user_id);

			//add roles per collection
			if (!empty($collection_roles)){				
				foreach($collection_roles as $collection_id=>$collection_roles)
				{	
					$this->ion_auth_model->insert_user_collection_roles($user_id,$collection_id,$collection_roles);
				}
			}
			
			$data['message']=t('form_update_success');
			if($this->input->get('destination'))
			{
				redirect($this->input->get('destination'));return;
			}
			redirect('admin/users');
		}
		
		$this->load->model('repository_model');
	
		$user_group_access_types=(array)$this->ion_auth_model->get_user_account_type($user_id);
		
		//we can have only one type of user group access
		if (in_array('unlimited',$user_group_access_types))
		{
			$user_group_access_type='unlimited';
		}
		else if (in_array('limited',$user_group_access_types))
		{
			$user_group_access_type='limited';
		}
		else
		{
			$user_group_access_type='none';
		}
	
		
		//$data['global_roles']=$this->ion_auth_model->get_limited_global_roles();
		
		$data['global_roles']['user']=$this->ion_auth_model->get_user_groups(NULL,'user');
		$data['global_roles']['reviewer']=$this->ion_auth_model->get_user_groups(NULL,'reviewer');
		$data['global_roles']['limited']=$this->ion_auth_model->get_user_groups('limited');
		$data['global_roles']['unlimited']=$this->ion_auth_model->get_user_groups('unlimited');
		//$data['global_roles']=array_merge($data['global_roles'],$this->ion_auth_model->get_user_groups('unlimited'));
		//$data['global_roles']=array_merge($data['global_roles'],$this->ion_auth_model->get_user_groups('limited'));
		
		$data['collections']=$this->repository_model->select_all();
		$data['collection_roles']=$this->repository_model->get_repo_permission_groups();
		$data['assigned_roles']['collections']=$this->ion_auth_model->get_user_repo_groups($user_id);
		$data['assigned_roles']['global']=$this->ion_auth_model->get_groups_by_user($user_id);;
		$data['user_id']=$user_id;
		$data['user_group_access_type']=$user_group_access_type;
		$data['user']=$this->ion_auth_model->get_user($user_id);
		$data['destination']=$this->input->get('destination');
				
		if(!$data['destination'])
		{
			$data['destination']='admin/users';
		}
		
		//assign empty arrays for collections with no user groups assigned
		foreach($data['collections'] as $collection)
		{
			if (!array_key_exists($collection['id'],$data['assigned_roles']['collections']))
			{
				$data['assigned_roles']['collections'][$collection['id']]=array();
			}
		}
		
		$content=$this->load->view('users/permissions',$data,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();	
	}
}

/* End of file users.php */
/* Location: ./system/application/controllers/users.php */