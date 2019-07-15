<?php
class Auth extends MY_Controller {

    function __construct()
    {
        parent::__construct($skip_auth=TRUE);

		$this->load->library('Nada_csrf');
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
    private function disable_page_cache()
    {
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
	}
	
	function generate_api_key()
	{
		$this->_is_logged_in();
		$this->ion_auth->set_api_key($this->session->userdata('user_id'));
		redirect("auth/profile", 'refresh');
	}


	function delete_api_key()
	{
		$this->_is_logged_in();
		$this->ion_auth->delete_api_key($this->session->userdata('user_id'),$this->input->get("api_key"));
		redirect("auth/profile", 'refresh');
	}


	function profile()
	{
		//don't let browsers cache this page
		$this->disable_page_cache();

		//check if user is logged in
		$this->_is_logged_in();
		$this->load->model('Licensed_model');
		$this->lang->load("licensed_request");

		//get user info
		$data['user']= $this->ion_auth->get_user($this->session->userdata('user_id'));

		//get user api keys
		$data['api_keys']=$this->ion_auth->get_api_keys($this->session->userdata('user_id'));

		//get user licensed requests
		$data['lic_requests']=$this->Licensed_model->get_user_requests($data['user']->id);

		$content=$this->load->view('auth/profile_view',$data,TRUE);

		$this->template->write('title', t('profile'),true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	function edit_profile()
	{
		$this->disable_page_cache();
		$this->_is_logged_in();
		$csrf=$this->nada_csrf->generate_token();

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
        	$this->session->set_flashdata('message', t("profile_updated"));
       		redirect("auth/profile", 'refresh');
		}
		$data['csrf']=$csrf;
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
        $this->disable_page_cache();
		$this->data['title'] = t("logout");
        $logout = $this->ion_auth->logout();
        redirect('', 'refresh');
	}
	

    //change password
	function change_password()
	{
		$this->disable_page_cache();
		$csrf=$this->nada_csrf->generate_token();
	    $use_complex_password=$this->config->item("require_complex_password");

	    $this->form_validation->set_rules('old', t('old_password'), 'required|max_length[20]|xss_clean');
	    $this->form_validation->set_rules('new', t('new_password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[new_confirm]|is_complex_password['.$use_complex_password.']');
	    $this->form_validation->set_rules('new_confirm', t('confirm_new_password'), 'required|max_length[20]');
   	    $this->form_validation->set_rules('form_token', 'FORM TOKEN', 'trim|callback_validate_token');

	    if (!$this->ion_auth->logged_in()) {
	    	redirect('auth/login', 'refresh');
		}
		
	    $user = $this->ion_auth->get_user($this->session->userdata('user_id'));

	    if ($this->form_validation->run() == false){
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
			$this->data['csrf']=$csrf;
        	$output=$this->load->view('auth/change_password', $this->data,TRUE);

			$this->template->write('content', $output,true);
			$this->template->write('title', t('change_password'),true);
			$this->template->render();
	    }
	    else{
	        $identity = $this->session->userdata($this->config->item('identity'));
	        $change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

    		if ($change) {
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
		$this->disable_page_cache();
		$this->form_validation->set_rules('email', t('email'), 'trim|required|xss_clean|max_length[100]');

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
		$this->disable_page_cache();
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
		$this->disable_page_cache();
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
		$this->disable_page_cache();
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
		$this->disable_page_cache();
		$this->_create_user();
	}

    //create a new user
	function _create_user()
	{		
		$this->data['title'] = t("register");		
		$content=NULL;

		$use_complex_password=$this->config->item("require_complex_password");
		$csrf=$this->nada_csrf->generate_token();

        //validate form input
    	$this->form_validation->set_rules('first_name', t('first_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('last_name', t('last_name'), 'trim|required|xss_clean|max_length[50]');
    	$this->form_validation->set_rules('email', t('email'), 'trim|required|valid_email|max_length[100]|callback__email_exists');
    	//$this->form_validation->set_rules('phone1', t('phone'), 'trim|xss_clean|max_length[20]');
    	//$this->form_validation->set_rules('company', t('company'), 'trim|xss_clean|max_length[100]');
		$this->form_validation->set_rules('country', t('country'), 'trim|xss_clean|max_length[150]|callback_country_valid');
    	$this->form_validation->set_rules('password', t('password'), 'required|min_length['.$this->config->item('min_password_length').']|max_length['.$this->config->item('max_password_length').']|matches[password_confirm]|is_complex_password['.$use_complex_password.']');
    	$this->form_validation->set_rules('password_confirm', t('password_confirmation'), 'required');
		//$this->form_validation->set_rules('form_token', 'FORM TOKEN', 'trim|callback_validate_token');
		$this->form_validation->set_rules('csrf_token', 'CSRF TOKEN', 'trim|callback_validate_token');
    	$this->form_validation->set_rules($this->captcha_lib->get_question_field(), t('captcha'), 'trim|required|callback_validate_captcha');

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
        	$this->ion_auth->register($username,$password,$email,$additional_data);
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
			$this->data['csrf']=$csrf;	
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

		if ($output===FALSE){
			$this->form_validation->set_message('validate_captcha', t('invalid_captcha'));
		}

		return $output;
	}

	/**
	*
	* validate CSRF token
	*
	*/
	function validate_token()
	{		
		if (!$this->nada_csrf->validate_token())
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
	function _email_exists($email)
	{
		$user_data=$this->ion_auth->get_user_by_email($email);

		if ($user_data)
		{
			$this->form_validation->set_message('_email_exists', t('callback_email_exists'));
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


	/**
	 * 
	 * Verify OTP code
	 * 
	 */
	function verify_code()
	{
		if ($this->config->item("otp_verification")!==1 || !$this->ion_auth->is_admin()){
			show_404();
		}

		$this->form_validation->set_rules('code', t('verification_code'), 'trim|required|xss_clean|max_length[10]');

		if ($this->form_validation->run() == false)
		{
	    	//set any errors and display the form
        	$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
    		$content=$this->load->view('auth/verify_otp', null,true);

			$this->template->write('content', $content,true);
			$this->template->write('title', t('verify_otp'),true);
		  	$this->template->render();
	    }
	    else
		{
			$user=$this->ion_auth->current_user();
			$code=$this->input->post("code");

			try{							
				//otp expired or not set?
				if (date("U")>$user->otp_expiry || !$user->otp_code){
					throw new Exception("Code has expired");
				}
				
				if($code==$user->otp_code){
					$this->session->set_userdata("verify_otp",1);
					$this->session->set_userdata("verified_otp",$code);
					redirect("admin", 'refresh');
				}
				
				throw new exception("Code verification failed");
			}
			catch(Exception $e){
				$this->db_logger->write_log('otp-error',$e->getMessage(). ' user: '.$user->email);
				$this->session->set_flashdata('error', $e->getMessage());
				redirect("auth/verify_code", 'refresh');
			}			
	    }
	}

	/**
	 * 
	 * 
	 * Email new OTP code
	 * 
	 */
	function send_otp_code()
	{
		if ($this->config->item("otp_verification")!==1 || !$this->ion_auth->is_admin()){
			show_404();
		}
		
		$user_id=$this->session->userdata('user_id');
		$this->ion_auth->send_otp_code($user_id);
		//write_log($type, $message=NULL, $section=NULL,$surveyid=0)
		$this->db_logger->write_log('otp','code sent for user:'.$user_id);
		$this->session->set_flashdata('message', t('Check your email for verification code'));
		redirect("auth/verify_code", 'refresh');
	}

}//end-class
