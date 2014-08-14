<?php
class Auth extends MY_Controller {
 
    function __construct() 
    {
        parent::__construct($skip_auth=TRUE);
		
        $this->load->library('ion_auth');
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->database();
        $this->load->helper('url');
    	$this->load->helper('admin_notifications');
            
    	$this->template->set_template('default');	
    	$this->template->write('sidebar', $this->_menu(),true);	
    
		$this->lang->load('general');
		$this->lang->load('users');
		
		$this->load->model('token_model');	
		$this->load->driver('captcha_lib');
	 
      	//$this->output->enable_profiler(TRUE);
    }
 
    //redirect to login page
    function index() 
    {
    	redirect("auth/login");
		show_404();		
    }
    
    //expire page immediately
    private function clear_page_cache()
    {	
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
    }
    
	function profile()
	{	
		//don't let browsers cache this page
		$this->clear_page_cache();
		
		//check if user is logged in
		$this->_is_logged_in();
		$this->load->model('Licensed_model');
		$this->lang->load("licensed_request");

		//log
		$this->db_logger->write_log('profile');
		
		//get user info
		$data['user']= $this->ion_auth->get_user($this->session->userdata('user_id'));
		
		//get user licensed requests
		$data['lic_requests']=$this->Licensed_model->get_user_requests($data['user']->id);
		
		$content=$this->load->view('auth/profile_view',$data,TRUE);
		$content.=date("H:i:s");
		
		$this->template->write('title', t('profile'),true);
		$this->template->write('content', $content,true);		
	  	$this->template->render();
	}

	function edit_profile()
	{	
		//check if user is logged in
		$this->_is_logged_in();

		//log
		$this->db_logger->write_log('profile-edit');
		
		//create a form token
		if ($this->input->post("form_token"))
		{
			//use the one in the postback
			$this->form_token=$this->input->post("form_token");
		}
		else
		{
			//create a new token
			$this->form_token=$this->token_model->create_token();
		}

		
		//currently logged in user
		$data['user']= $this->ion_auth->get_user($this->session->userdata('user_id'));													 		
		
    	$this->form_validation->set_rules('first_name', t('first_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('phone', t('phone'), 'trim|xss_clean|max_length[20]');
    	$this->form_validation->set_rules('company', t('company'), 'trim|xss_clean|max_length[100]');
		$this->form_validation->set_rules('country', t('country'), 'trim|xss_clean|max_length[100]');
		$this->form_validation->set_rules('form_token', 'FORM TOKEN', 'trim|callback_validate_token');
		
		if ($this->form_validation->run() == true) { 
			$update_data = array(
				'first_name' => $this->input->post('first_name'),
				'last_name'  => $this->input->post('last_name'),
				'company'    => $this->input->post('company'),
				'phone'      => $this->input->post('phone'),
				'country'      => $this->input->post('country'),
				);
			$this->ion_auth->update_user($data['user']->id,$update_data);
			
			//delete the token so form can't be re-submitted
			$this->token_model->remove_token($this->input->post('form_token'));
			
        	$this->session->set_flashdata('message', t("profile_updated"));
       		redirect("auth/profile", 'refresh');
		}		
		
		$content=$this->load->view('auth/profile_edit',$data,TRUE);

		$this->template->write('title', t('edit_profile'),true);
		$this->template->write('content', $content,true);		
	  	$this->template->render();
	}

	/**
	* checks if a user is logged in, otherwise redirects to the login page
	*
	*/
	function _is_logged_in()
	{
		$destination=$this->uri->uri_string();
		$this->session->set_userdata("destination",$destination);

    	if (!$this->ion_auth->logged_in()) 
		{
	    	//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
    	}
	}
	
    //log the user in
    function login() 
    {
		$this->template->set_template('blank');
        $this->data['title'] = t("login");

		if($this->input->get('destination'))
		{
			$destination=$this->input->get('destination');
			$this->session->unset_userdata('destination');
		}
		else {
        	$destination=$this->session->userdata("destination");
		}	
		
        //validate form input
    	$this->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]');
	    $this->form_validation->set_rules('password', t('password'), 'required|max_length[100]');

        if ($this->form_validation->run() == true) { //check to see if the user is logging in
        	//check for "remember me"
        	if ($this->input->post('remember') == 1) 
			{
        		$remember = true;
        	}
        	else 
			{
        		$remember = false;
        	}
        	
			
			//track login attempts?
			if ($this->config->item("track_login_attempts")===TRUE)
			{
				//check if max login attempts limit reached
				$max_login_limit=$this->ion_auth->is_max_login_attempts_exceeded($this->input->post('email'));
				
				if ($max_login_limit)
				{
					$this->session->set_flashdata('error', t("max_login_attempted"));
					sleep(3);
					redirect("auth/login");
				}
			}
			
			
        	if ($this->ion_auth->login($this->input->post('email'), $this->input->post('password'), $remember)) //if the login is successful
			{ 
				//log
				$this->db_logger->write_log('login',$this->input->post('email'));
				        		
				if ($destination!="")
				{
					redirect($destination, 'refresh');	
				}
				else
				{
				 	redirect($this->config->item('base_url'), 'refresh');
				}
	        }
	        else 
			{ 	//if the login was un-successful
	        	//redirect them back to the login page
	        	$this->session->set_flashdata('error', t("login_failed"));
				
				//log
				$this->db_logger->write_log('login-failed',$this->input->post('email'));

	        	redirect("auth/login", 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
	        }
        }
		else 
		{  	//the user is not logging in so display the login page
	        //set the flash data error message if there is one
	        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
		    
			$this->data['email']      = array('name'    => 'email',
                                              'id'      => 'email',
                                              'type'    => 'text',
                                              'value'   => $this->form_validation->set_value('email'),
                                             );
            $this->data['password']   = array('name'    => 'password',
                                              'id'      => 'password',
                                              'type'    => 'password',
                                             );
	    	
			$content=$this->load->view('auth/login', $this->data,TRUE);
			
			//pass data to the site's template
			$this->template->write('content', $content,true);
			
			//set page title
			$this->template->write('title', t('login'),true);
	
			//render final output
			$this->template->render();	
		}
    }
    
    //log the user out
	function logout() 
	{
        $this->data['title'] = t("logout");
        
		//log
		$this->db_logger->write_log('logout');
		
        //log the user out
        $logout = $this->ion_auth->logout();
			    
        //redirect them back to the page they came from
        redirect('', 'refresh');
    }
    
    //change password
	function change_password() 
	{	 
		//log
		$this->db_logger->write_log('change-pass');
   
   		//create a form token
		if ($this->input->post("form_token"))
		{
			//use the one in the postback
			$this->form_token=$this->input->post("form_token");
		}
		else
		{
			//create a new token
			$this->form_token=$this->token_model->create_token();
		}
		
	    $use_complex_password=$this->config->item("require_complex_password");
		
	    $this->form_validation->set_rules('old', t('old_password'), 'required|max_length[20]|xss_clean');
	    $this->form_validation->set_rules('new', t('new_password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[new_confirm]|is_complex_password['.$use_complex_password.']');
	    $this->form_validation->set_rules('new_confirm', t('confirm_new_password'), 'required|max_length[20]');
   	    $this->form_validation->set_rules('form_token', 'FORM TOKEN', 'trim|callback_validate_token');

	    if (!$this->ion_auth->logged_in()) {
	    	redirect('auth/login', 'refresh');
	    }
	    $user = $this->ion_auth->get_user($this->session->userdata('user_id'));
	    
	    if ($this->form_validation->run() == false) //display the form
		{ 
	        //set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
	        $this->data['old_password']           = array('name'    => 'old',
		                                               	  'id'      => 'old',
		                                              	  'type'    => 'password',
		                                                 );
	        $this->data['new_password']           = array('name'    => 'new',
		                                               	  'id'      => 'new',
		                                              	  'type'    => 'password',
		                                                 );
        	$this->data['new_password_confirm']   = array('name'    => 'new_confirm',
                                                      	  'id'      => 'new_confirm',
                                                      	  'type'    => 'password',
        												 );
        	$this->data['user_id']                = array('name'    => 'user_id',
                                                      	  'id'      => 'user_id',
                                                      	  'type'    => 'hidden',
        												  'value'   => $user->id,
        												 );
	        
        	//render
        	$output=$this->load->view('auth/change_password', $this->data,TRUE);

			$this->template->write('content', $output,true);		
			$this->template->write('title', t('change_password'),true);
			$this->template->render();	

	    }
	    else 
		{
	        $identity = $this->session->userdata($this->config->item('identity'));
	        
	        $change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));
			
			//delete the token so form can't be re-submitted
			$this->token_model->remove_token($this->input->post('form_token'));
		
    		if ($change) { //if the password was successfully changed
    			$this->session->set_flashdata('message', t('password_changed_success'));
    			$this->logout();
    		}
    		else {
    			$this->session->set_flashdata('error', t('password_change_failed'));
    			redirect('auth/change_password', 'refresh');
    		}
	    }
	}
	
	//forgot password
	function forgot_password() 
	{
		$this->form_validation->set_rules('email', t('email'), 'trim|required|xss_clean|max_length[100]|');
	    
		if ($this->form_validation->run() == false) 
		{
	    	//setup the input
	    	$this->data['email'] = array('name'    => 'email',
                                         'id'      => 'email',
        						   	    );
	    	//set any errors and display the form
        	$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
    		$content=$this->load->view('auth/forgot_password', $this->data,true);			

			$this->template->write('content', $content,true);
			$this->template->write('title', t('forgot_password'),true);
		  	$this->template->render();			
	    }
	    else 
		{
			//log
			$this->db_logger->write_log('logout',$this->input->post('email'));

	        //run the forgotten password method to email an activation code to the user
			$forgotten = $this->ion_auth->forgotten_password($this->input->post('email'));
			
			if ($forgotten) //if there were no errors
			{ 
				//$this->session->set_flashdata('message', $this->message);
	            //redirect("auth/login", 'refresh'); //we should display a confirmation page here instead of the login page
				$contents=$this->load->view('auth/forgot_pass_confirm',NULL,TRUE);
				$this->template->write('content', $contents,true);
				$this->template->write('title', t('forgot_password'),true);
				$this->template->render();							
			}
			else {
				$this->session->set_flashdata('message', t('email_failed'));
	            redirect("auth/forgot_password", 'refresh');
			}
	    }
	}
	
	//reset password - final step for forgotten password
	public function reset_password($code) 
	{
		$reset = $this->ion_auth->forgotten_password_complete($code);
		
		if ($reset) //if the reset worked then send them to the login page
		{  
			//show instructions to login after resetting the password
			$content=$this->load->view('auth/forgot_pass_success',NULL,TRUE);			
			$this->template->write('content', $content,true);
			$this->template->write('title', t('forgot_password'),true);
			$this->template->render();	
		}
		else //if the reset didnt work then send them back to the forgot password page
		{ 
			$this->session->set_flashdata('message', t('forgot_password_failed'));
            redirect("auth/forgot_password", 'refresh');
		}
	}

	//activate the user
	function activate($id=NULL, $code=false) 
	{        
		$activation = $this->ion_auth->activate($id, $code);
		
		$data=array();
		
        if ($activation) 
		{
			$data['success']=true;
        }
        else 
		{
			$data['failed']=true;
        }
		
		$content=$this->load->view('auth/msg_account_activation',$data,TRUE);
		$this->template->write('title', t('user_account_activation'),true);
		$this->template->write('content', $content,true);
		$this->template->write('title', t('user_account_activation'),true);
		$this->template->render();
    }
    
    //deactivate the user
	function deactivate($id) 
	{        
		if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) 
		{
	        //de-activate the user
	        $this->ion_auth->deactivate($id);
		} 
        //redirect them back to the auth page
        redirect("auth", 'refresh');
    }
    
	
	function create_user()
	{
		/*if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) 
		{
			redirect('auth', 'refresh');
		}*/

		$this->_create_user();
	}
	
    //create a new user
	function _create_user() 
	{  
        $this->data['title'] = t("register");
        $content=NULL;

		//create a form token
		if ($this->input->post("form_token"))
		{
			//use the one in the postback
			$this->form_token=$this->input->post("form_token");
		}
		else
		{
			//create a new token
			$this->form_token=$this->token_model->create_token();
		}
	
	$use_complex_password=$this->config->item("require_complex_password");
		
		
        //validate form input
    	$this->form_validation->set_rules('first_name', t('first_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]|callback_email_exists');
    	//$this->form_validation->set_rules('phone1', t('phone'), 'trim|xss_clean|max_length[20]');
    	//$this->form_validation->set_rules('company', t('company'), 'trim|xss_clean|max_length[100]');
	$this->form_validation->set_rules('country', t('country'), 'trim|xss_clean|max_length[150]|callback_country_valid');
    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]|is_complex_password['.$use_complex_password.']');
    	$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');		
	$this->form_validation->set_rules('form_token', 'FORM TOKEN', 'trim|callback_validate_token');		
    	$this->form_validation->set_rules($this->captcha_lib->get_question_field(), t('captcha'), 'trim|required|max_length[15]|callback_validate_captcha');
		
        if ($this->form_validation->run() === TRUE) 
		{ 
			//log
			$this->db_logger->write_log('register',$this->input->post('email'));

			//check to see if we are creating the user
			$username  = $this->input->post('first_name').' '.$this->input->post('last_name');
        	$email     = $this->input->post('email');
        	$password  = $this->input->post('password');
        	
        	$additional_data = array('first_name' => $this->input->post('first_name'),
        							 'last_name'  => $this->input->post('last_name'),
        							 //'company'    => $this->input->post('company'),
        							 //'phone'      => $this->input->post('phone1'),// .'-'. $this->input->post('phone2') .'-'. $this->input->post('phone3'),
									 'country'      => $this->input->post('country'),
									 'email'=>$email,
									 'identity'=>$username
        							);
        	
        	//register the user
        	$this->ion_auth->register($username,$password,$email,$additional_data);
			
			//delete the token so form can't be re-submitted
			$this->token_model->remove_token($this->input->post('form_token'));
			
			//show the success message
			$content=$this->load->view('auth/create_user_confirm',NULL,TRUE);
			
			//notify admins
			$subject=sprintf('[%s] - %s',t('notification'), t('new_user_registration')).' - '.$username;
			$message=$this->load->view('auth/email/admin_notice_new_registration', $additional_data,true);
			notify_admin($subject,$message);
		} 
		else 
		{ 
			//set the flash data error message if there is one
	        $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$this->data['captcha_question']=$this->captcha_lib->get_html();
			
			$this->data['first_name']          = array('name'   => 'first_name',
		                                              'id'      => 'first_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('first_name'),
		                                             );
            $this->data['last_name']           = array('name'   => 'last_name',
		                                              'id'      => 'last_name',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('last_name'),
		                                             );
            $this->data['email']              = array('name'    => 'email',
		                                              'id'      => 'email',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('email'),
		                                             );
            /*$this->data['company']            = array('name'    => 'company',
		                                              'id'      => 'company',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('company'),
		                                             );
            $this->data['phone1']             = array('name'    => 'phone1',
		                                              'id'      => 'phone1',
		                                              'type'    => 'text',
		                                              'value'   => $this->form_validation->set_value('phone1'),
		                                             );*/
		    $this->data['password']           = array('name'    => 'password',
		                                              'id'      => 'password',
		                                              'type'    => 'password',
		                                              'value'   => $this->form_validation->set_value('password'),
		                                             );
            $this->data['password_confirm']   = array('name'    => 'password_confirm',
                                                      'id'      => 'password_confirm',
                                                      'type'    => 'password',
                                                      'value'   => $this->form_validation->set_value('password_confirm'),
                                                     );
            $content=$this->load->view('auth/create_user', $this->data,TRUE);
		}
			//render final output
			$this->template->write('content', $content,true);
			$this->template->write('title', $this->data['title'],true);
			$this->template->render();	
    }
	
	
	/**
	*
	* validate captcha
	*
	*/
	function validate_captcha()
	{
		$output=$this->captcha_lib->check_answer();
		
		if ($output===FALSE)
		{
			$this->form_validation->set_message('validate_captcha', t('invalid_captcha'));		
		}
		
		return $output;
	}

	/**
	*
	* validate form token. avoids duplicate entries
	*
	*/
	function validate_token($str)
	{
		$exists=$this->token_model->token_exists($str);
		
		if ($exists===FALSE)
		{
			$this->form_validation->set_message('validate_token', t('form_already_saved'));
			return FALSE;
		}
		return TRUE;
	}
	
	
	//check country name is selected
	function country_valid($country)
	{
		if (strlen($country)<4)
		{
			$this->form_validation->set_message('country_valid', t('callback_country_invalid'));
			return FALSE;
		}
		return TRUE;
	}
	
	//check if the email address exists in db
	function email_exists($email)
	{
		$user_data=$this->ion_auth->get_user_by_email($email);

		if ($user_data)
		{
			$this->form_validation->set_message('email_exists', t('callback_email_exists'));
			return FALSE;
		}
		return TRUE;
	}
	
	function register()
	{
		//show 404 if User Registration is disabled or Site is running under protected mode
		if ($this->config->item("site_user_register")==='no' || $this->config->item("site_password_protect")==='yes')
		{
			show_404();
		}
		
		$this->_create_user();
	}
	
	//get public site menu
	function _menu()
	{
		$this->load->model('menu_model');
		$data['menus']= $this->menu_model->select_all();		
		$content=$this->load->view('default_menu', $data,true);
		return $content;
	}
	

	function _remap($method) 
	{
        if (in_array(strtolower($method), array_map('strtolower', get_class_methods($this)))) 
		{
            $uri = $this->uri->segment_array();
            unset($uri[1]);
            unset($uri[2]);
     
            call_user_func_array(array($this, $method), $uri);
        }
        else {
			show_404();
		}    
	}	

}//end-class
