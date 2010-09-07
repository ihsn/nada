<?php

class Install extends Controller {

	function Install()
	{		
		parent::Controller();
		$this->load->database();
		
		//initialize
		$this->load->dbforge();
		
		//database utilities	
		$this->load->dbutil();
		
		$this->template->set_template('installer');

		$this->lang->load("users");
		$this->lang->load("install");
		//$this->output->enable_profiler(TRUE);
	}

	function index()
	{		
		//test database connectivity
		$data['db_connect']=$this->_test_connection();		

		//database connection successful
		if ($data['db_connect']!==FALSE)
		{
			//database version
			$data['db_version']=$this->db->version();
		
			//test if application is already installed
			//function will stop the execution of the installer if already installed
			$this->is_already_installed();
		}

		//php version
		$data['php_version']=phpversion();
		
		//test the required extensions
		$data['extensions']=$this->load->view("install/extensions",NULL,TRUE);
		
		//check required extensions
		$data['other_settings']=$this->load->view("install/other_settings",NULL,TRUE);
	
		//test folder rights
		$data['permissions']=$this->load->view("install/file_rw",NULL,TRUE);
						
		$content=$this->load->view('install/index',$data,TRUE);
				
		//render final output		
		$this->template->write('title', t('title_data_catalog'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	
	function installing($step=NULL)
	{				
		if ($step==NULL)
		{
			//test database connection
			if($this->_test_connection()===FALSE)
			{
				$this->session->set_flashdata('message', t('database_connection_failed'));
				redirect('install');
			}

			//exit if already installed	
			$this->is_already_installed();	
			
			//check if the database already exists or create a new one
			$created=$this->_create_database();
			
			if ($created===FALSE)
			{
				$this->session->set_flashdata('message', t('database_creation_failed'));
				redirect('install');		
			}
			
			redirect ('install/installing/create_tables');	
		}
		
		else if ($step=='create_tables')
		{
		
			//test database connection
			if($this->_test_connection()===FALSE)
			{
				$this->session->set_flashdata('error', t('database_connection_failed'));
				redirect('install');
			}

			//exit if already installed	
			$this->is_already_installed();				
			
			//create tables and add insert data
			$this->_create_tables();			
			$this->session->set_flashdata('message', t('database_tables_created'));
			
			//redirect to admin account registration
			redirect('install/create_user');						
		}			
	}
	
	/**
	 * 
	 * Creates Admin user account
	 * 
	 * @return void
	 */
	function create_user() 
	{  				
		//exit if already installed	
		$this->is_already_installed();			
		
        $this->load->library('form_validation');
		
		
        $this->data['page_title'] = t("create_admin_account");		
              		
        //validate form input
		$this->form_validation->set_rules('username', t('username'), 'xss_clean|max_length[20]|callback_username_exists');
    	$this->form_validation->set_rules('email', t('email'), 'max_length[100]|required|valid_email|callback_email_exists');
    	$this->form_validation->set_rules('first_name', t('first_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'max_length[20]|required|xss_clean');
    	$this->form_validation->set_rules('phone1', t('phone'), 'max_length[20]|xss_clean|trim');
    	$this->form_validation->set_rules('company', t('company'), 'max_length[255]|xss_clean');
    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]');
    	$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');
		
        if ($this->form_validation->run() == true) 
		{ 
			//check to see if we are creating the user
			$username  = strtolower($this->input->post('first_name'). ' '. $this->input->post('last_name'));
        	$email     = $this->input->post('email');
        	$password  = $this->input->post('password');
        	
        	$additional_data = array('first_name' => $this->input->post('first_name'),
        							 'last_name'  => $this->input->post('last_name'),
        							 'company'    => $this->input->post('company'),
        							 'phone'      => $this->input->post('phone1'),// .'-'. $this->input->post('phone2') .'-'. $this->input->post('phone3'),
									 'active'     => $this->input->post('active'),
									 'country'     => $this->input->post('country'),
        							);
        	
        	//register the user
        	$user_created=$this->ion_auth->register($username,$password,$email,$additional_data);

			//get the user data by email
			$user=$this->ion_auth->get_user_by_email($email);
				
			if ($user)
			{
				//$data=$additional_data;
				$data['username']=$username;
				$data['active']=1;
				$data['group_id']=1;	
												
				//update user group to ADMIN and ACTIVATE account
				$this->ion_auth->update_user($user->id, $data);	
			
				//auto login the user
				$this->ion_auth->login($email, $password, $remember=true);
				
				//redirect them back to the admin page
				$this->session->set_flashdata('message', t("form_update_success") );
				redirect("install/complete", 'refresh');
				return;
			}
			else
			{
				$this->error=$this->ion_auth->errors();
			}        	
		} 
		//display the create user form
		
		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		
		$this->data['first_name']          = array('name'   => 'first_name',
												  'id'      => 'first_name',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('first_name'),
												  'class'=>'input-fixed300'
												 );
		$this->data['last_name']           = array('name'   => 'last_name',
												  'id'      => 'last_name',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('last_name'),
												  'class'=>'input-fixed300'
												 );
		$this->data['email']              = array('name'    => 'email',
												  'id'      => 'email',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('email'),
												  'class'=>'input-fixed300'
												 );
		$this->data['username']           = array('name'    => 'username',
												  'id'      => 'username',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('username'),
												  'class'=>'input-fixed300'
												 );

		$this->data['company']            = array('name'    => 'company',
												  'id'      => 'company',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('company'),
												  'class'=>'input-fixed300'
												 );
		$this->data['phone1']             = array('name'    => 'phone1',
												  'id'      => 'phone1',
												  'type'    => 'text',
												  'value'   => $this->form_validation->set_value('phone1'),
												  'class'=>'input-fixed300'
												 );
		$this->data['password']           = array('name'    => 'password',
												  'id'      => 'password',
												  'type'    => 'password',
												  'value'   => $this->form_validation->set_value('password'),
												  'class'=>'input-fixed200'
												 );
		$this->data['password_confirm']   = array('name'    => 'password_confirm',
												  'id'      => 'password_confirm',
												  'type'    => 'password',
												  'value'   => $this->form_validation->set_value('password_confirm'),
												  'class'=>'input-fixed200'
												 );
		$this->data['active']=$this->form_validation->set_value('active',1);
		
		$content=$this->load->view('install/create_user', $this->data,TRUE);
		
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//set page title
		$this->template->write('title', $this->data['page_title'],true);

		//render final output
		$this->template->render();	

    }
	
	function complete()
	{
			//exit if already installed	
			$this->is_already_installed();
					
			//update db configurations
			$data=array('name'=>'app_installed', 'value'=>date("U"));
			$result=$this->db->insert("configurations",$data);			

			if ($result)
			{
				//unset session	
				$this->session->unset_userdata("installing");		
			}
			
			$content=$this->load->view('install/completed',NULL,TRUE);	
			
			//pass data to the site's template
			$this->template->write('content', $content,true);
			
			//render final output
			$this->template->render();	
	}
	
	
	/**
	 * 
	 * Check if the Application is already installed
	 * If application is installed, the installer will not continue
	 * 
	 * @return bool
	 */
	function is_already_installed()
	{
		$this->db->select("name");	
		$this->db->where("name", "app_installed");
		$query=$this->db->get("configurations");
		
		if ($query)
		{
			$result=$query->row_array();
			
			if (isset($result['name']))
			{
				//Application is already installed, exit the installer
				show_error(t('page_not_found').' - <a href="'.site_url().'">'.t('return_to_site').'</a>');
				exit;
			}
		}		
		return FALSE;
	}
	
	
	
	/**
	*
	* Test database connectivity
	*
	* return	bool
	*/
	function _test_connection()
	{
		//return false;
		$conn_id = @mysql_connect($this->db->hostname, $this->db->username, $this->db->password, TRUE);
        
        if (! $conn_id ) 
        {
            return FALSE;
        } 
        else 
        {
			mysql_close($conn_id);
            return TRUE;
        }
	}
	
	
	//create database if not already exists
	function _create_database()
	{
		//list of databases on the server
		$dbs = $this->dbutil->list_databases();

		//check if database already exists
		foreach($dbs as $db)
		{
			if (strtolower($db)==strtolower($this->db->database))
			{
				return TRUE;
			}
		}
		
		//try creating the database
		if ($this->dbforge->create_database($this->db->database))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	*
	* Check folder permissions
	*
	**/
	function _check_folder_permissions()
	{
		//folder that need WRITE Permissions
		$folders=array(
					'datasets'=>$this->config->item("catalog_root"),
					'cache'=>$this->config->item("cache_path"),
					'log'=>$this->config->item("log_path"),
					);			
	}

	/**
	*
	* Create database tables
	*/
	function _create_tables()
	{
		//sql file to restore database
		$filename='install/schema.sql';
		
		if (!file_exists($filename))
		{
			show_error(t('file_not_found'). ' - schema.sql');
		}
		
		// Temporary variable, used to store current query
		$templine = '';
		
		// Read in entire file
		$lines = file($filename);
		
		// Loop through each line
		foreach ($lines as $line)
		{
			// Skip it if it's a comment
			if (substr($line, 0, 1) == '#' || $line == '')
				continue;
		 
			// Add this line to the current segment
			$templine .= $line;
			
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';')
			{
				$this->db->query($templine);

				// Reset temp variable to empty
				$templine = '';
			}
		}
	}
	

}

/* End of file install.php */
/* Location: ./system/application/controllers/install.php */