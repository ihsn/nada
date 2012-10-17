<?php
class MY_Controller extends CI_Controller
{	
    public $_ci_plugins = array();
    var $_ci_ob_level;
    public $_ci_view_path      = '';
    var $_ci_library_paths  = array();
    var $_ci_model_paths    = array();
    var $_ci_helper_paths   = array();
    var $_base_classes      = array(); // Set by the controller class
    var $_ci_cached_vars    = array();
    var $_ci_classes        = array();
    var $_ci_loaded_files   = array();
    var $_ci_models         = array();
    var $_ci_helpers        = array();
    var $_ci_varmap         = array('unit_test' => 'unit', 'user_agent' => 'agent');
	
	var $is_admin=TRUE;
	
	/**
	* Manages both admin/non-admin users
	*
	* @skip			skip authentication  (true=skip authentication)
	* @is_admin		requires the user to have admin rights
	*/
	public function MY_Controller($skip=FALSE,$is_admin=TRUE)
	{		
		parent::__construct();
		
		//test if application is installed
		$this->is_app_installed();
	
		//switch language
		$this->_switch_language();
		$this->lang->load("general");
		$this->load->model('Permissions_model');
			
		$this->load->library(array('site_configurations','session','ion_auth','form_validation','acl'));	
		$this->is_admin=$is_admin;
			
		if ($skip===FALSE)
		{
		   //apply IP restrictions for site administration
		   $this->apply_ip_restrictions();
		}
	  
	  
		//skip authentication
		if ($skip!==TRUE)
		{
			//perform authentication
			$this->_auth();
			
			$user=$this->ion_auth->current_user();
		
			if (!$user)
			{
				return FALSE;
			}
			// group_id 1 == super admin
			if ((int)$user->group_id !== 1 && !$this->Permissions_model->group_has_url_access($user->group_id, $this->uri->uri_string())) {
				show_error(t('access_denied') . $this->uri->uri_string());
				
			}
			//$this->_has_access();
		}
	}


	/**
	*
	* Check user has access to the current page
	**/	

	
	/**
	*
	* Check user has access to the current page
	**/
	/*
	//TODO: Remove later
	
	function _has_access()
	{	
		$excluded_urls=array('auth','catalog');

		if (in_array($this->uri->segment(1),$excluded_urls) || $this->uri->uri_string()=='')
		{
			return FALSE;
		}

		//get currently logged in user
		$user=$this->ion_auth->current_user();
		
		if (!$user)
		{
			return FALSE;
		}
		
		//test user has access to the current url
		$access=$this->ion_auth->has_access($user->id,$this->uri->uri_string());
		
		if ($access===FALSE)
		{
			show_error("You don't have permissions to access content");
		}

		//check study level permissions
		$this->acl->check_study_permissions();				
	}
	*/
	


	/**
	 * 
	 * Apply IP restrictions for Site Admin
	 * 
	 */
	 public function apply_ip_restrictions()
	 {
		$user_ip=$this->input->ip_address();  		
		$ip_list=$this->config->item("admin_allowed_ip");
		
		if ($ip_list!==FALSE)
		{
		  if (is_array($ip_list) && count($ip_list)>0)
		  {
			  //check ip is in the allowed list  
			  if (!in_array($user_ip, $ip_list))
			  {
				 //log
				 $this->db_logger->write_log('blocked','site access blocked from ip:'.$user_ip,'access-blocked');
				 
				 //show page not found  
				 show_404(); 
			  }  
		  }     
		} 
	 }
    
	/**
	* Switch site language using cookies
	*
	**/
	function _switch_language()
	{
		if($this->session->userdata('language'))
		{	
	        //switch language
			$this->config->set_item('language',$this->session->userdata('language'));
		}
	}
	
	/**
	*
	*
	* check if user is logged in or not
	**/
	function _auth()
	{
		$destination=$this->uri->uri_string();
		
		//check if ajax is set
		if ($this->input->get_post("ajax"))
		{
			$destination.='/?ajax='.$this->input->get_post("ajax");
		}
		//check if print is set
		if ($this->input->get_post("print"))
		{
			$destination.='/?print='.$this->input->get_post("print");
		}
				
		$this->session->set_userdata("destination",$destination);

		//not logged in
    	if (!$this->ion_auth->logged_in()) 
		{
			//redirect them to the login page
			redirect("auth/login/?destination=$destination", 'refresh');
    	}
    	elseif (!$this->ion_auth->is_admin() && $this->is_admin==TRUE ) 
		{
			//redirect them to the home page because they must be an administrator to view this
			//redirect($this->config->item('base_url'), 'refresh');
			//redirect("auth/login/?destination=$destination", 'refresh');
			show_error("access_denied");
    	}
	}

	//get public site menu
	function _menu()
	{
		$data['menus']= $this->Menu_model->select_all();		
		$content=$this->load->view('default_menu', $data,true);
		return $content;
	}
	
 	/**
	* Test if app is properly installed and can connect to db
	*/
 	function is_app_installed()
	{
		$this->load->database();
		
		//check if database connection settings are filled in
		if ($this->db->dbdriver=='' || $this->db->username=='' || $this->db->database=='')
		{
			show_error('You have not setup a database');
		}
		
		//test reading from database tables
		$this->db->limit(1);
		$query=$this->db->get('configurations');
		
		if ($query)
		{
			return TRUE;
		}
				
		//test database connection only if everything else above has failed
		switch($this->db->dbdriver)
		{
			case 'mysql':
				$conn_id = @mysql_connect($this->db->hostname, $this->db->username, $this->db->password, TRUE);
				break;
			case 'postgre':
				$conn_id=@pg_connect("host={$this->db->hostname} user={$this->db->username} password={$this->db->password} connect_timeout=5 dbname=postgres");
				break;
			case 'sqlsrv':
				$auth_info = array( "UID"=>$this->db->username,"PWD"=>$this->db->password);
				$conn_id = @sqlsrv_connect($this->db->hostname, $auth_info);
				break;
			default:
				show_error('MY_CONTROLLER::database not supported '.$this->db->dbdriver);
		}

		if (!$conn_id ) 
        {
            //cannot connect to database server
			show_error('Failed to connect to database, check database settings');
        } 
        else //can connect to db server but not to the database
        {
            redirect("install");
        }
	}

	
}	